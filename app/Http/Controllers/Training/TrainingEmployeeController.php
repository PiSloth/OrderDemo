<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Training\Test;
use App\Models\Training\TestAttempt;
use App\Models\Training\TrainingAssignment;
use App\Services\Training\TestEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingEmployeeController extends Controller
{
    public function myTrainings(Request $request): Response
    {
        $user = $request->user();

        $assignments = TrainingAssignment::query()
            ->where('user_id', $user->id)
            ->with([
                'training.category',
                'training.test.questions',
                'trigger',
                'sessionParticipants.session.trainer',
                'sessionParticipants.session.approver',
                'testAttempts' => fn($q) => $q->with('answers')->latest(),
            ])
            ->orderByRaw("FIELD(status, 'PENDING', 'IN_PROGRESS', 'OVERDUE', 'COMPLETED', 'EXPIRED')")
            ->orderBy('due_date')
            ->get();

        return Inertia::render('Training/Employee/MyTrainings', [
            'assignments' => $assignments,
        ]);
    }

    public function takeTest(Request $request, TrainingAssignment $assignment, Test $test): Response
    {
        $canViewAnyAttempt = $request->user()->can('training.attempt.view') || $request->user()->can('training.catalog.view');
        abort_if($assignment->user_id !== $request->user()->id && !$canViewAnyAttempt, 403, 'Unauthorized');

        // Check if session is approved before unlocking test for FULL_TRAINING
        if ($assignment->assignment_type === 'FULL_TRAINING' && !$canViewAnyAttempt) {
            $latestParticipant = $assignment->sessionParticipants()->latest('id')->with('session')->first();
            if ($latestParticipant) {
                if ($latestParticipant->attendance_status === 'ABSENT') {
                    abort(403, 'You were marked absent for this training session. Please attend the rescheduled session before taking the assessment.');
                }
                $session = $latestParticipant->session;
                $isApproved = ($session && ($session->approved_at !== null || in_array($session->status, ['APPROVED', 'COMPLETED'])));
                if (!$isApproved) {
                    abort(403, 'This assessment is locked until the trainer finishes and approves the training session.');
                }
            }
        }

        $assignment->load(['training.category', 'sessionParticipants.session', 'user.department', 'user.officePosition']);

        // Load test questions and options (without is_correct to prevent inspect cheating while taking test)
        $test->load(['questions' => function ($q) {
            $q->orderBy('sort_order')->with(['options' => function ($opt) {
                $opt->select(['id', 'test_question_id', 'answer', 'sort_order'])->orderBy('sort_order');
            }]);
        }]);

        // Load evaluated attempts with questions and their correct answers for the review breakdown
        $previousAttempts = TestAttempt::query()
            ->where('training_assignment_id', $assignment->id)
            ->where('test_id', $test->id)
            ->with([
                'user.department',
                'user.officePosition',
                'session.trainer',
                'answers.selectedOption',
                'answers.question.options',
            ])
            ->latest()
            ->get();

        return Inertia::render('Training/Employee/TakeTest', [
            'assignment' => $assignment,
            'test' => $test,
            'previousAttempts' => $previousAttempts,
            'canViewAttempts' => $canViewAnyAttempt,
        ]);
    }

    public function submitTest(
        Request $request,
        TrainingAssignment $assignment,
        Test $test,
        TestEvaluationService $evaluationService
    ): RedirectResponse {
        abort_if($assignment->user_id !== $request->user()->id, 403, 'Unauthorized');

        // Gate test submission behind session approval
        if ($assignment->assignment_type === 'FULL_TRAINING') {
            $latestParticipant = $assignment->sessionParticipants()->latest('id')->with('session')->first();
            if ($latestParticipant) {
                if ($latestParticipant->attendance_status === 'ABSENT') {
                    abort(403, 'You were marked absent for this training session. Please attend the rescheduled session before taking the assessment.');
                }
                $session = $latestParticipant->session;
                $isApproved = ($session && ($session->approved_at !== null || in_array($session->status, ['APPROVED', 'COMPLETED'])));
                if (!$isApproved) {
                    abort(403, 'This assessment is locked until the trainer finishes and approves the training session.');
                }
            }
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'training_session_id' => ['nullable', 'integer', 'exists:training_sessions,id'],
        ]);

        $attempt = $evaluationService->startAttempt(
            test: $test,
            user: $request->user(),
            assignment: $assignment,
            sessionId: $validated['training_session_id'] ?? null
        );

        $result = $evaluationService->submitAttempt($attempt, $validated['answers']);

        $message = $result['passed']
            ? ($result['requirement_completed']
                ? 'Congratulations! You passed the test and completed the training requirement.'
                : 'You passed the test! Training requirement will be completed upon attendance verification.')
            : 'Test submitted. Unfortunately, you did not achieve the required passing score. A new training session has been requested.';

        return redirect()
            ->route('training.employee.take-test', ['assignment' => $assignment->id, 'test' => $test->id])
            ->with('message', $message);
    }
}
