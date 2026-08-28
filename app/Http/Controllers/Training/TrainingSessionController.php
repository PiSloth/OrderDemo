<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Training\Training;
use App\Models\Training\TrainingAssignment;
use App\Models\Training\TrainingSession;
use App\Models\Training\TrainingSessionParticipant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status');
        $trainingId = $request->input('training_id');
        $trainerId = $request->input('trainer_id');
        $search = $request->input('search');

        $query = TrainingSession::query()
            ->with([
                'training',
                'trainer',
                'creator',
                'participants.user.department',
                'participants.user.officePosition',
                'participants.assignment.latestAttempt',
            ])
            ->withCount('participants');

        if ($status) {
            $query->where('status', $status);
        }

        if ($trainingId) {
            $query->where('training_id', $trainingId);
        }

        if ($trainerId) {
            $query->where('trainer_id', $trainerId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('session_code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('training', fn($sub) => $sub->where('title', 'like', "%{$search}%"));
            });
        }

        $sessions = $query->orderByDesc('scheduled_at')->paginate(12)->withQueryString();

        $trainings = Training::query()->where('status', 'active')->orderBy('title')->get(['id', 'code', 'title']);
        $trainers = User::query()->where('suspended', false)->orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('Training/Sessions/Index', [
            'sessions' => $sessions,
            'trainings' => $trainings,
            'trainers' => $trainers,
            'statuses' => ['PENDING', 'OPEN', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'],
            'filters' => [
                'status' => $status,
                'training_id' => $trainingId,
                'trainer_id' => $trainerId,
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'training_id' => ['required', 'integer', 'exists:trainings,id'],
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'session_code' => ['nullable', 'string', 'max:100'],
            'scheduled_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:PENDING,OPEN,IN_PROGRESS,COMPLETED,CANCELLED'],
        ]);

        $training = Training::findOrFail($validated['training_id']);

        if (empty($validated['session_code'])) {
            $count = TrainingSession::where('training_id', $training->id)->count() + 1;
            $validated['session_code'] = $training->code . '-S' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
        }

        $validated['created_by'] = $request->user()->id;

        TrainingSession::create($validated);

        return back()->with('message', 'Training session created successfully.');
    }

    public function update(Request $request, TrainingSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:PENDING,OPEN,IN_PROGRESS,COMPLETED,CANCELLED'],
        ]);

        $session->update($validated);

        return back()->with('message', 'Training session updated.');
    }

    public function updateStatus(Request $request, TrainingSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:PENDING,OPEN,IN_PROGRESS,COMPLETED,CANCELLED'],
        ]);

        $session->update(['status' => $validated['status']]);

        return back()->with('message', "Session status updated to {$validated['status']}.");
    }

    public function updateAttendance(Request $request, TrainingSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'participants' => ['required', 'array'],
            'participants.*.id' => ['required', 'integer', 'exists:training_session_participants,id'],
            'participants.*.attendance_status' => ['required', 'string', 'in:REGISTERED,ATTENDED,ABSENT,EXCUSED'],
            'participants.*.notes' => ['nullable', 'string'],
        ]);

        foreach ($validated['participants'] as $pData) {
            $participant = TrainingSessionParticipant::with(['assignment.testAttempts'])->find($pData['id']);
            if (!$participant) {
                continue;
            }

            $oldStatus = $participant->attendance_status;
            $newStatus = $pData['attendance_status'];

            $participant->update([
                'attendance_status' => $newStatus,
                'attended_at' => ($newStatus === 'ATTENDED') ? ($participant->attended_at ?? now()) : null,
                'notes' => $pData['notes'] ?? null,
            ]);

            // 3-Tier Completion Check:
            // If marked ATTENDED, check if they already passed a test attempt for this assignment
            if ($newStatus === 'ATTENDED') {
                $assignment = $participant->assignment;
                if ($assignment && $assignment->status !== 'COMPLETED') {
                    $hasPassed = $assignment->testAttempts()->where('result', 'PASSED')->exists();
                    if ($hasPassed) {
                        $assignment->update([
                            'status' => 'COMPLETED',
                            'completed_at' => now(),
                        ]);
                    } else {
                        $assignment->update(['status' => 'IN_PROGRESS']);
                    }
                }
            }
        }

        return back()->with('message', 'Participant attendance recorded successfully.');
    }
}
