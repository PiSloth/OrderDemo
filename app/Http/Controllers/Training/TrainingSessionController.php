<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Training\TestAttempt;
use App\Models\Training\Training;
use App\Models\Training\TrainingAssignment;
use App\Models\Training\TrainingSession;
use App\Models\Training\TrainingSessionParticipant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainingSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status');
        $timeFilter = $request->input('time_filter', 'all');
        $trainingId = $request->input('training_id');
        $trainerId = $request->input('trainer_id');
        $search = $request->input('search');

        $query = TrainingSession::query()
            ->with([
                'training',
                'trainer',
                'creator',
                'approver',
                'parentSession',
                'participants.user.department',
                'participants.user.officePosition',
                'participants.assignment.latestAttempt',
                'participants.assignment.testAttempts',
            ])
            ->withCount([
                'participants',
                'testAttempts as attempt_results_count' => function ($q) {
                    $q->whereNotNull('submitted_at')->orWhereIn('result', ['PASSED', 'FAILED']);
                }
            ]);

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

        // Time schedule filter (Upcoming, Ongoing, Expired)
        if ($timeFilter === 'upcoming') {
            $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('start_date')->where('start_date', '>', now()->toDateString());
                })->orWhere(function ($sub) {
                    $sub->whereNull('start_date')->where('scheduled_at', '>', now());
                });
            })->whereNotIn('status', ['COMPLETED', 'CANCELLED']);
        } elseif ($timeFilter === 'ongoing') {
            $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('start_date')->where('start_date', '<=', now()->toDateString())
                        ->where(function ($sub2) {
                            $sub2->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
                        });
                })->orWhere(function ($sub) {
                    $sub->whereNull('start_date')->whereDate('scheduled_at', now()->toDateString());
                })->orWhere('status', 'IN_PROGRESS');
            })->whereNotIn('status', ['COMPLETED', 'CANCELLED']);
        } elseif ($timeFilter === 'expired') {
            $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('end_date')->where('end_date', '<', now()->toDateString());
                })->orWhere(function ($sub) {
                    $sub->whereNull('end_date')->whereNotNull('start_date')->where('start_date', '<', now()->toDateString());
                })->orWhere(function ($sub) {
                    $sub->whereNull('start_date')->where('scheduled_at', '<', now());
                });
            })->whereNotIn('status', ['COMPLETED', 'CANCELLED']);
        }

        $sessions = $query->orderByDesc('scheduled_at')->paginate(12)->withQueryString();

        $trainings = Training::query()->where('status', 'active')->orderBy('title')->get(['id', 'code', 'title', 'duration_days']);
        $trainers = User::query()->where('suspended', false)->orderBy('name')->get(['id', 'name', 'email']);
        $allActiveUsers = User::query()
            ->where('suspended', false)
            ->with(['department:id,name', 'officePosition:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department_id', 'office_position_id']);

        return Inertia::render('Training/Sessions/Index', [
            'sessions' => $sessions,
            'trainings' => $trainings,
            'trainers' => $trainers,
            'allActiveUsers' => $allActiveUsers,
            'statuses' => ['PENDING', 'OPEN', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'],
            'permissions' => [
                'can_read' => $request->user()?->can('training-session.read') ?? false,
                'can_create' => $request->user()?->can('training-session.create') ?? false,
                'can_update' => $request->user()?->can('training-session.update') ?? false,
                'can_delete' => $request->user()?->can('training-session.delete') ?? false,
            ],
            'filters' => [
                'status' => $status,
                'time_filter' => $timeFilter,
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'venue' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:PENDING,OPEN,IN_PROGRESS,COMPLETED,CANCELLED'],
        ]);

        $training = Training::findOrFail($validated['training_id']);

        if (empty($validated['duration_days'])) {
            $validated['duration_days'] = max(1, (int) ($training->duration_days ?: 1));
        }

        if (!empty($validated['start_date']) && empty($validated['end_date'])) {
            $validated['end_date'] = \Carbon\Carbon::parse($validated['start_date'])
                ->addDays($validated['duration_days'] - 1)
                ->toDateString();
        }

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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'venue' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:PENDING,OPEN,IN_PROGRESS,COMPLETED,CANCELLED'],
        ]);

        if (!empty($validated['start_date']) && !empty($validated['duration_days'])) {
            $validated['end_date'] = \Carbon\Carbon::parse($validated['start_date'])
                ->addDays($validated['duration_days'] - 1)
                ->toDateString();
        }

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

    public function approveSession(Request $request, TrainingSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $session->update([
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'approval_notes' => $validated['notes'] ?? null,
            'status' => in_array($session->status, ['PENDING', 'OPEN']) ? 'OPEN' : $session->status,
        ]);

        return back()->with('message', 'Training session approved! Test template is now unlocked for participating employees.');
    }

    public function addParticipant(Request $request, TrainingSession $session): RedirectResponse
    {
        if ($session->approved_at !== null && !in_array($session->status, ['PENDING', 'OPEN'])) {
            return back()->withErrors(['message' => 'Cannot modify participants of an approved or finished session.']);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        $alreadyInSession = TrainingSessionParticipant::query()
            ->where('training_session_id', $session->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyInSession) {
            return back()->withErrors(['message' => "{$user->name} is already registered in this session."]);
        }

        // Find or create assignment
        $assignment = TrainingAssignment::firstOrCreate(
            [
                'training_id' => $session->training_id,
                'user_id' => $user->id,
            ],
            [
                'assignment_type' => 'FULL_TRAINING',
                'due_date' => now()->addDays(30),
                'status' => 'PENDING',
            ]
        );

        TrainingSessionParticipant::create([
            'training_session_id' => $session->id,
            'training_assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'attendance_status' => 'REGISTERED',
        ]);

        return back()->with('message', "Added {$user->name} to session participants.");
    }

    public function removeParticipant(TrainingSession $session, TrainingSessionParticipant $participant): RedirectResponse
    {
        if ($session->approved_at !== null && !in_array($session->status, ['PENDING', 'OPEN'])) {
            return back()->withErrors(['message' => 'Cannot modify participants of an approved or finished session.']);
        }

        if ($participant->training_session_id !== $session->id) {
            abort(404);
        }

        $participant->delete();

        return back()->with('message', 'Participant removed from session.');
    }

    public function updateAttendance(Request $request, TrainingSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'participants' => ['required', 'array'],
            'participants.*.id' => ['required', 'integer', 'exists:training_session_participants,id'],
            'participants.*.attendance_status' => ['required', 'string', 'in:REGISTERED,ATTENDED,ABSENT,EXCUSED'],
            'participants.*.daily_attendance' => ['nullable', 'array'],
            'participants.*.notes' => ['nullable', 'string'],
        ]);

        foreach ($validated['participants'] as $pData) {
            $participant = TrainingSessionParticipant::with(['assignment.testAttempts'])->find($pData['id']);
            if (!$participant) {
                continue;
            }

            $newStatus = $pData['attendance_status'];

            $participant->update([
                'attendance_status' => $newStatus,
                'daily_attendance' => $pData['daily_attendance'] ?? $participant->daily_attendance,
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

    public function destroy(Request $request, TrainingSession $session): RedirectResponse
    {
        $canDelete = $request->user()->can('training-session.delete') || $request->user()->can('training.session.delete') || $request->user()->can('training.catalog.delete');
        abort_if(!$canDelete, 403, 'Unauthorized to delete training session.');

        // Find all session IDs to check/clean up (including any remedial sessions created for this session)
        $sessionIds = TrainingSession::where('parent_session_id', $session->id)
            ->pluck('id')
            ->push($session->id)
            ->all();

        // If the training session has attempt results (submitted, passed, or failed), do not allow deletion!
        $hasAttemptResults = TestAttempt::query()
            ->whereIn('training_session_id', $sessionIds)
            ->where(function ($q) {
                $q->whereNotNull('submitted_at')
                  ->orWhereIn('result', ['PASSED', 'FAILED']);
            })
            ->exists();

        if ($hasAttemptResults) {
            return back()->withErrors([
                'message' => 'Cannot delete training session because employees have already submitted assessment results for this session.',
            ]);
        }

        DB::transaction(function () use ($session, $sessionIds) {
            // 1. Delete all generated test attempts and their answers related to this session
            $attempts = TestAttempt::whereIn('training_session_id', $sessionIds)->get();
            foreach ($attempts as $attempt) {
                $assignment = $attempt->assignment;
                $attempt->answers()->delete();
                $attempt->delete();

                if ($assignment) {
                    $remainingPassed = $assignment->testAttempts()->where('result', 'PASSED')->exists();
                    if (!$remainingPassed) {
                        $assignment->update([
                            'status' => 'PENDING',
                            'completed_at' => null,
                        ]);
                    }
                }
            }

            // 2. Collect assignment IDs associated with this session's participants
            $assignmentIds = TrainingSessionParticipant::whereIn('training_session_id', $sessionIds)
                ->pluck('training_assignment_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            // 3. Delete all participants
            TrainingSessionParticipant::whereIn('training_session_id', $sessionIds)->delete();

            // 4. Delete any orphaned assignments that now have no sessions and no test attempts
            if (!empty($assignmentIds)) {
                TrainingAssignment::whereIn('id', $assignmentIds)
                    ->whereDoesntHave('sessionParticipants')
                    ->whereDoesntHave('testAttempts')
                    ->delete();
            }

            // 5. Delete any child remedial sessions
            TrainingSession::where('parent_session_id', $session->id)->delete();

            // 6. Delete the session itself
            $session->delete();
        });

        return back()->with('message', 'Training session and related assessment data cleaned up successfully.');
    }
}
