<?php

namespace App\Services\Training;

use App\Models\Training\Test;
use App\Models\Training\TestAnswer;
use App\Models\Training\TestAttempt;
use App\Models\Training\TestOption;
use App\Models\Training\TrainingAssignment;
use App\Models\Training\TrainingSessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TestEvaluationService
{
    public function __construct(
        protected TrainingAssignmentService $assignmentService
    ) {}

    /**
     * Start a new test attempt.
     */
    public function startAttempt(Test $test, User $user, TrainingAssignment $assignment, ?int $sessionId = null): TestAttempt
    {
        $attemptCount = TestAttempt::query()
            ->where('test_id', $test->id)
            ->where('training_assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->count();

        return TestAttempt::create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'training_assignment_id' => $assignment->id,
            'training_session_id' => $sessionId,
            'attempt_number' => $attemptCount + 1,
            'started_at' => now(),
            'result' => 'IN_PROGRESS',
        ]);
    }

    /**
     * Submit and evaluate test answers.
     */
    public function submitAttempt(TestAttempt $attempt, array $submittedAnswers): array
    {
        return DB::transaction(function () use ($attempt, $submittedAnswers) {
            $test = $attempt->test()->with(['questions.options'])->firstOrFail();
            $assignment = $attempt->assignment;

            $totalScore = 0.0;
            $maxScore = 0.0;

            foreach ($test->questions as $question) {
                $maxScore += (float) $question->marks;
                $rawAnswer = $submittedAnswers[$question->id] ?? null;

                $isCorrect = false;
                $marksObtained = 0.0;
                $selectedOptionId = null;
                $selectedOptionIds = [];

                if ($question->question_type === 'MULTI_SELECT') {
                    if (is_array($rawAnswer)) {
                        $selectedOptionIds = array_values(array_filter(array_map('intval', $rawAnswer)));
                    } elseif ($rawAnswer !== null && $rawAnswer !== '') {
                        $selectedOptionIds = [(int) $rawAnswer];
                    }

                    $selectedOptionId = !empty($selectedOptionIds) ? $selectedOptionIds[0] : null;

                    $correctOptionIds = $question->options
                        ->where('is_correct', true)
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->sort()
                        ->values()
                        ->all();

                    $userChoiceIds = collect($selectedOptionIds)->sort()->values()->all();

                    // Exact match rule: user must select ALL correct answers and NO incorrect answers
                    if (!empty($correctOptionIds) && $userChoiceIds === $correctOptionIds) {
                        $isCorrect = true;
                        $marksObtained = (float) $question->marks;
                    }
                } else {
                    if (is_array($rawAnswer)) {
                        $selectedOptionId = !empty($rawAnswer) ? (int) reset($rawAnswer) : null;
                    } else {
                        $selectedOptionId = $rawAnswer ? (int) $rawAnswer : null;
                    }

                    if ($selectedOptionId) {
                        $selectedOptionIds = [$selectedOptionId];
                        $option = $question->options->firstWhere('id', $selectedOptionId);
                        if ($option && $option->is_correct) {
                            $isCorrect = true;
                            $marksObtained = (float) $question->marks;
                        }
                    }
                }

                $totalScore += $marksObtained;

                TestAnswer::updateOrCreate(
                    [
                        'test_attempt_id' => $attempt->id,
                        'test_question_id' => $question->id,
                    ],
                    [
                        'selected_option_id' => $selectedOptionId,
                        'selected_option_ids' => $selectedOptionIds,
                        'is_correct' => $isCorrect,
                        'marks_obtained' => $marksObtained,
                    ]
                );
            }

            $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0.0;
            $passed = $percentage >= (float) $test->passing_score;
            $result = $passed ? 'PASSED' : 'FAILED';

            $attempt->update([
                'submitted_at' => now(),
                'score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'result' => $result,
            ]);

            // Handle Completion Rule:
            // 1. Session attendance verified (if assignment requires session)?
            // 2. Test passed?
            // 3. Training requirement completed?
            $isTestOnly = ($assignment->assignment_type === 'TEST_ONLY');

            $hasAttended = false;
            if (!$isTestOnly) {
                if ($attempt->training_session_id) {
                    $participant = TrainingSessionParticipant::query()
                        ->where('training_session_id', $attempt->training_session_id)
                        ->where('user_id', $attempt->user_id)
                        ->first();
                    $hasAttended = ($participant && $participant->attendance_status === 'ATTENDED');
                } else {
                    // Check any attended session for this assignment
                    $hasAttended = TrainingSessionParticipant::query()
                        ->where('training_assignment_id', $assignment->id)
                        ->where('user_id', $attempt->user_id)
                        ->where('attendance_status', 'ATTENDED')
                        ->exists();
                }
            }

            if ($passed && ($isTestOnly || $hasAttended)) {
                $assignment->update([
                    'status' => 'COMPLETED',
                    'completed_at' => now(),
                ]);
            } elseif ($passed) {
                // Passed test, but attendance is pending trainer confirmation
                $assignment->update([
                    'status' => 'IN_PROGRESS',
                ]);
            } else {
                // FAILED test
                $assignment->update([
                    'status' => 'IN_PROGRESS',
                ]);

                // Next action: Provision next session only if full training requires session
                if (!$isTestOnly) {
                    $nextSessionNum = $attempt->attempt_number + 1;
                    $this->assignmentService->provisionSessionForAssignment($assignment, $nextSessionNum);
                }
            }

            return [
                'attempt' => $attempt->fresh(['answers.selectedOption', 'answers.question']),
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'passed' => $passed,
                'requirement_completed' => ($passed && ($isTestOnly || $hasAttended)),
            ];
        });
    }
}
