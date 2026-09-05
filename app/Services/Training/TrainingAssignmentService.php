<?php

namespace App\Services\Training;

use App\Models\Training\Training;
use App\Models\Training\TrainingAssignment;
use App\Models\Training\TrainingScope;
use App\Models\Training\TrainingSession;
use App\Models\Training\TrainingSessionParticipant;
use App\Models\Training\TrainingTrigger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrainingAssignmentService
{
    /**
     * Assign training to users based on custom target criteria (scopes, departments, positions, or specific employees).
     */
    public function assignCustom(
        Training $training,
        TrainingTrigger $trigger,
        string $targetType = 'scopes',
        array $targetIds = [],
        ?Carbon $dueDate = null,
        string $assignmentType = 'FULL_TRAINING'
    ): int {
        return DB::transaction(function () use ($training, $trigger, $targetType, $targetIds, $dueDate, $assignmentType) {
            $query = User::query()->where('suspended', false);

            if ($targetType === 'departments') {
                if (empty($targetIds)) {
                    return 0;
                }
                $query->whereIn('department_id', $targetIds);
            } elseif ($targetType === 'positions') {
                if (empty($targetIds)) {
                    return 0;
                }
                $query->whereIn('office_position_id', $targetIds);
            } elseif ($targetType === 'employees') {
                if (empty($targetIds)) {
                    return 0;
                }
                $query->whereIn('id', $targetIds);
            } else {
                // 'scopes' default
                $scopes = $training->scopes;
                if ($scopes->isEmpty()) {
                    return 0;
                }

                $query->where(function ($q) use ($scopes) {
                    foreach ($scopes as $scope) {
                        $q->orWhere(function ($sub) use ($scope) {
                            $sub->where('department_id', $scope->department_id);
                            if (!empty($scope->office_position_id)) {
                                $sub->where('office_position_id', $scope->office_position_id);
                            }
                        });
                    }
                });
            }

            $users = $query->get();
            $assignedCount = 0;

            // In FULL_TRAINING mode, provision ONE shared session for all assigned users
            $sharedSession = null;
            if ($assignmentType === 'FULL_TRAINING' && $users->isNotEmpty()) {
                $sessionNum = TrainingSession::where('training_id', $training->id)->whereNull('parent_session_id')->count() + 1;
                $durationDays = max(1, (int) ($training->duration_days ?: 1));
                $startDate = now()->addDays(7)->toDateString();
                $endDate = now()->addDays(7 + $durationDays - 1)->toDateString();

                $sharedSession = TrainingSession::create([
                    'training_id' => $training->id,
                    'trainer_id' => null,
                    'session_code' => $training->code . '-S' . str_pad((string) $sessionNum, 3, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4)),
                    'title' => $training->title . ' - Session #' . $sessionNum,
                    'scheduled_at' => now()->addDays(7)->setHour(9)->setMinute(0),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'duration_days' => $durationDays,
                    'status' => 'PENDING',
                    'created_by' => auth()->id(),
                ]);
            }

            foreach ($users as $user) {
                $assignment = $this->createAssignmentForUser(
                    training: $training,
                    user: $user,
                    trigger: $trigger,
                    dueDate: $dueDate,
                    assignmentType: $assignmentType,
                    existingSession: $sharedSession
                );
                if ($assignment) {
                    $assignedCount++;
                }
            }

            return $assignedCount;
        });
    }

    /**
     * Assign training to all users matching training scopes for a given trigger.
     */
    public function assignByScope(Training $training, TrainingTrigger $trigger): int
    {
        return $this->assignCustom($training, $trigger, 'scopes');
    }

    /**
     * Handle onboarding when a new user is created or updated with dept or dept + position.
     */
    public function assignNewUser(User $user): int
    {
        if (!$user->department_id || $user->suspended) {
            return 0;
        }

        return DB::transaction(function () use ($user) {
            // Find all active trainings that match this department (whole department or matching position)
            $matchingTrainingIds = TrainingScope::query()
                ->where('department_id', $user->department_id)
                ->where(function ($q) use ($user) {
                    $q->whereNull('office_position_id');
                    if (!empty($user->office_position_id)) {
                        $q->orWhere('office_position_id', $user->office_position_id);
                    }
                })
                ->pluck('training_id')
                ->unique();

            if ($matchingTrainingIds->isEmpty()) {
                return 0;
            }

            $trainings = Training::query()
                ->whereIn('id', $matchingTrainingIds)
                ->where('status', 'active')
                ->get();

            $count = 0;
            foreach ($trainings as $training) {
                // Check if an open/pending session already exists for this training to join
                $existingOpenSession = TrainingSession::query()
                    ->where('training_id', $training->id)
                    ->whereNull('parent_session_id')
                    ->whereIn('status', ['PENDING', 'OPEN'])
                    ->first();

                // Create a NEW_USER trigger if not exists recently
                $positionLabel = $user->officePosition?->name ?? 'All Positions';
                $deptLabel = $user->department?->name ?? 'Department';
                $trigger = TrainingTrigger::create([
                    'training_id' => $training->id,
                    'trigger_type' => 'NEW_USER',
                    'source_type' => User::class,
                    'source_id' => $user->id,
                    'reason' => "New user onboarding ({$positionLabel} in {$deptLabel})",
                    'status' => 'ACTIVE',
                    'created_by' => auth()->id() ?? $user->id,
                ]);

                $assignment = $this->createAssignmentForUser(
                    training: $training,
                    user: $user,
                    trigger: $trigger,
                    existingSession: $existingOpenSession
                );
                if ($assignment) {
                    $count++;
                }
            }

            return $count;
        });
    }

    /**
     * Create assignment and initial pending session for user.
     */
    public function createAssignmentForUser(
        Training $training,
        User $user,
        ?TrainingTrigger $trigger = null,
        ?Carbon $dueDate = null,
        string $assignmentType = 'FULL_TRAINING',
        ?TrainingSession $existingSession = null
    ): ?TrainingAssignment {
        // Calculate default due date if not provided
        if (!$dueDate) {
            $dueDate = now()->addDays(30);
        }

        // Check if there is an existing pending or in_progress assignment for this user & training
        $existing = TrainingAssignment::query()
            ->where('training_id', $training->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
            ->first();

        if ($existing) {
            if ($existingSession && $assignmentType === 'FULL_TRAINING') {
                TrainingSessionParticipant::firstOrCreate(
                    [
                        'training_session_id' => $existingSession->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'training_assignment_id' => $existing->id,
                        'attendance_status' => 'REGISTERED',
                    ]
                );
            }
            return $existing;
        }

        $assignment = TrainingAssignment::create([
            'training_id' => $training->id,
            'user_id' => $user->id,
            'training_trigger_id' => $trigger?->id,
            'assignment_type' => $assignmentType,
            'due_date' => $dueDate,
            'status' => 'PENDING',
        ]);

        // Automatically provision or link Session #1 only if FULL_TRAINING
        if ($assignmentType === 'FULL_TRAINING') {
            if ($existingSession) {
                TrainingSessionParticipant::firstOrCreate(
                    [
                        'training_session_id' => $existingSession->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'training_assignment_id' => $assignment->id,
                        'attendance_status' => 'REGISTERED',
                    ]
                );
            } else {
                $this->provisionSessionForAssignment($assignment, 1);
            }
        }

        return $assignment;
    }

    /**
     * Provision a session (or link to existing open/pending session) for the assignment.
     */
    public function provisionSessionForAssignment(TrainingAssignment $assignment, int $sessionNumber = 1): TrainingSessionParticipant
    {
        $training = $assignment->training;

        if ($sessionNumber === 1) {
            // Check if there is already an uncancelled session created for this assignment
            $existingParticipant = TrainingSessionParticipant::query()
                ->where('training_assignment_id', $assignment->id)
                ->whereHas('session', function ($q) {
                    $q->whereIn('status', ['PENDING', 'OPEN', 'IN_PROGRESS']);
                })
                ->first();

            if ($existingParticipant) {
                return $existingParticipant;
            }
        }

        $durationDays = max(1, (int) ($training->duration_days ?: 1));
        $startDate = now()->addDays(7)->toDateString();
        $endDate = now()->addDays(7 + $durationDays - 1)->toDateString();

        // Create a new session in PENDING state (waiting trainer approval/scheduling)
        $session = TrainingSession::create([
            'training_id' => $training->id,
            'trainer_id' => null,
            'session_code' => $training->code . '-S' . str_pad((string) $sessionNumber, 3, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(4)),
            'title' => $training->title . ' - Session #' . $sessionNumber,
            'scheduled_at' => now()->addDays(7)->setHour(9)->setMinute(0),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_days' => $durationDays,
            'status' => 'PENDING',
            'created_by' => auth()->id(),
        ]);

        return TrainingSessionParticipant::create([
            'training_session_id' => $session->id,
            'training_assignment_id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'attendance_status' => 'REGISTERED',
        ]);
    }

    /**
     * Attach a failed trainee to a shared remedial session referencing the parent session.
     */
    public function attachToRemedialSession(TrainingAssignment $assignment, ?int $parentSessionId = null): TrainingSessionParticipant
    {
        $training = $assignment->training;
        $parentSession = $parentSessionId ? TrainingSession::find($parentSessionId) : null;

        // Look for an existing pending/open remedial session linked to this parent session
        $remedialSession = null;
        if ($parentSession) {
            $remedialSession = TrainingSession::query()
                ->where('training_id', $training->id)
                ->where('parent_session_id', $parentSession->id)
                ->whereIn('status', ['PENDING', 'OPEN', 'IN_PROGRESS'])
                ->first();
        }

        if (!$remedialSession) {
            $parentCode = $parentSession?->session_code ?? ($training->code . '-S001');
            $remCount = TrainingSession::where('training_id', $training->id)
                ->where('parent_session_id', $parentSession?->id)
                ->count() + 1;

            $durationDays = max(1, (int) ($training->duration_days ?: 1));
            $startDate = now()->addDays(3)->toDateString();
            $endDate = now()->addDays(3 + $durationDays - 1)->toDateString();

            $remedialSession = TrainingSession::create([
                'training_id' => $training->id,
                'parent_session_id' => $parentSession?->id,
                'trainer_id' => $parentSession?->trainer_id,
                'session_code' => $parentCode . '-REM-' . str_pad((string) $remCount, 2, '0', STR_PAD_LEFT),
                'title' => $training->title . ' - Remedial Session (Ref: ' . $parentCode . ')',
                'scheduled_at' => now()->addDays(3)->setHour(9)->setMinute(0),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration_days' => $durationDays,
                'venue' => $parentSession?->venue,
                'meeting_link' => $parentSession?->meeting_link,
                'status' => 'PENDING',
                'created_by' => auth()->id(),
            ]);
        }

        return TrainingSessionParticipant::firstOrCreate(
            [
                'training_session_id' => $remedialSession->id,
                'user_id' => $assignment->user_id,
            ],
            [
                'training_assignment_id' => $assignment->id,
                'attendance_status' => 'REGISTERED',
            ]
        );
    }

    /**
     * Check retraining due records and generate upcoming assignments.
     */
    public function checkRetrainingDues(): array
    {
        $completedAssignments = TrainingAssignment::query()
            ->where('status', 'COMPLETED')
            ->whereNotNull('completed_at')
            ->with(['training', 'user'])
            ->get();

        $generatedCount = 0;
        $overdueCount = 0;

        foreach ($completedAssignments as $assignment) {
            $training = $assignment->training;
            if (!$training || $training->retrain_interval <= 0) {
                continue;
            }

            // Calculate next due date
            $nextDueDate = match ($training->retrain_unit) {
                'day' => $assignment->completed_at->copy()->addDays((int) $training->retrain_interval),
                'year' => $assignment->completed_at->copy()->addYears((int) $training->retrain_interval),
                default => $assignment->completed_at->copy()->addMonths((int) $training->retrain_interval),
            };

            // Check if there is already a newer assignment created after completed_at
            $hasNewer = TrainingAssignment::query()
                ->where('training_id', $training->id)
                ->where('user_id', $assignment->user_id)
                ->where('created_at', '>', $assignment->completed_at)
                ->exists();

            if ($hasNewer) {
                continue;
            }

            // Generate retraining assignment if within 30 days of due or already overdue
            if (now()->diffInDays($nextDueDate, false) <= 30) {
                $trigger = TrainingTrigger::create([
                    'training_id' => $training->id,
                    'trigger_type' => 'RETRAINING',
                    'source_type' => TrainingAssignment::class,
                    'source_id' => $assignment->id,
                    'reason' => 'Scheduled retraining cycle (Every ' . $training->retrain_interval . ' ' . $training->retrain_unit . '(s))',
                    'status' => 'ACTIVE',
                    'created_by' => null,
                ]);

                $this->createAssignmentForUser($training, $assignment->user, $trigger, $nextDueDate);
                $generatedCount++;

                if (now()->greaterThan($nextDueDate)) {
                    $overdueCount++;
                }
            }
        }

        // Update overdue status on existing uncompleted assignments
        TrainingAssignment::query()
            ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'OVERDUE']);

        return [
            'generated_retraining' => $generatedCount,
            'overdue_count' => $overdueCount,
        ];
    }
}
