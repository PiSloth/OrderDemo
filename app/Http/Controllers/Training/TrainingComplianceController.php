<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\OfficePosition;
use App\Models\Training\Test;
use App\Models\Training\TestAttempt;
use App\Models\Training\Training;
use App\Models\Training\TrainingAssignment;
use App\Models\Training\TrainingSessionParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainingComplianceController extends Controller
{
    public function index(Request $request): Response
    {
        $departmentId = $request->input('department_id');
        $officePositionId = $request->input('office_position_id');
        $trainingId = $request->input('training_id');
        $status = $request->input('status');
        $triggerType = $request->input('trigger_type');
        $dueFrom = $request->input('due_from');
        $dueTo = $request->input('due_to');
        $search = $request->input('search');

        // Overall Active Users
        $totalActiveUsers = User::query()->where('suspended', false)->count();

        // Total assignments summary
        $totalAssignments = TrainingAssignment::query()->count();
        $completedAssignments = TrainingAssignment::query()->where('status', 'COMPLETED')->count();
        $inProgressAssignments = TrainingAssignment::query()->where('status', 'IN_PROGRESS')->count();
        $pendingAssignments = TrainingAssignment::query()->where('status', 'PENDING')->count();
        $overdueAssignments = TrainingAssignment::query()->where('status', 'OVERDUE')->count();

        // Upcoming assignments (pending/in_progress with due_date within next 14 days)
        $upcomingAssignments = TrainingAssignment::query()
            ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(14)->toDateString()])
            ->count();

        $overallCompletionRate = $totalAssignments > 0
            ? round(($completedAssignments / $totalAssignments) * 100, 1)
            : 0;

        // Department breakdown: Attendance Complete Rate based on Approved Training Sessions
        // Exclude departments without any assigned approved training session participants
        $departments = Department::query()->orderBy('name')->get();
        $departmentStats = $departments->map(function ($dept) {
            // Find participants in approved training sessions for this department's active users
            $participants = TrainingSessionParticipant::query()
                ->whereHas('session', fn($q) => $q->whereNotNull('approved_at'))
                ->whereHas('user', fn($q) => $q->where('department_id', $dept->id)->where('suspended', false))
                ->with('session:id,duration_days')
                ->get();

            if ($participants->isEmpty()) {
                return null;
            }

            $totalRequiredDays = 0;
            $totalAttendedDays = 0;

            foreach ($participants as $participant) {
                $sessionDays = max(1, (int) ($participant->session->duration_days ?: 1));
                $totalRequiredDays += $sessionDays;

                $daily = $participant->daily_attendance;
                if (is_array($daily) && !empty($daily)) {
                    $attendedCount = 0;
                    foreach ($daily as $dayRecord) {
                        $st = is_array($dayRecord) ? ($dayRecord['status'] ?? '') : '';
                        if (in_array(strtoupper($st), ['ATTENDED', 'PRESENT'], true)) {
                            $attendedCount++;
                        }
                    }
                    // Cap attended count to session duration days
                    $totalAttendedDays += min($sessionDays, $attendedCount);
                } else {
                    if ($participant->attendance_status === 'ATTENDED') {
                        $totalAttendedDays += $sessionDays;
                    }
                }
            }

            $rate = $totalRequiredDays > 0 ? round(($totalAttendedDays / $totalRequiredDays) * 100, 1) : 0;

            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'total_attendance_days' => $totalRequiredDays,
                'attended_days' => $totalAttendedDays,
                'completion_rate' => $rate,
                'total_participants' => $participants->count(),
            ];
        })->filter()->values();

        // Test Performance: Pass rate per training
        $trainings = Training::query()->where('status', 'active')->orderBy('title')->get();
        $testPerformance = $trainings->map(function ($training) {
            $totalAttempts = TestAttempt::query()
                ->whereHas('test', fn($q) => $q->where('training_id', $training->id))
                ->whereIn('result', ['PASSED', 'FAILED'])
                ->count();

            $passedAttempts = TestAttempt::query()
                ->whereHas('test', fn($q) => $q->where('training_id', $training->id))
                ->where('result', 'PASSED')
                ->count();

            $passRate = $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100, 1) : 0;

            return [
                'id' => $training->id,
                'code' => $training->code,
                'title' => $training->title,
                'total_attempts' => $totalAttempts,
                'passed_attempts' => $passedAttempts,
                'pass_rate' => $passRate,
            ];
        });

        // Compliance Matrix query
        $matrixQuery = TrainingAssignment::query()
            ->with([
                'user.department',
                'user.officePosition',
                'training.test',
                'trigger',
                'sessionParticipants.session',
                'testAttempts' => fn($q) => $q->with([
                    'session.trainer',
                    'answers.selectedOption',
                    'answers.question.options',
                ])->latest(),
            ]);

        if ($departmentId) {
            $matrixQuery->whereHas('user', fn($q) => $q->where('department_id', $departmentId));
        }

        if ($officePositionId) {
            $matrixQuery->whereHas('user', fn($q) => $q->where('office_position_id', $officePositionId));
        }

        if ($trainingId) {
            $matrixQuery->where('training_id', $trainingId);
        }

        if ($status) {
            $matrixQuery->where('status', $status);
        }

        if ($triggerType) {
            $matrixQuery->whereHas('trigger', fn($q) => $q->where('trigger_type', $triggerType));
        }

        if ($dueFrom) {
            $matrixQuery->where('due_date', '>=', $dueFrom);
        }

        if ($dueTo) {
            $matrixQuery->where('due_date', '<=', $dueTo);
        }

        if ($search) {
            $matrixQuery->where(function ($q) use ($search) {
                $q->whereHas('user', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('training', function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            });
        }

        $matrix = $matrixQuery->orderByDesc('id')->paginate(15)->withQueryString();

        $officePositions = OfficePosition::query()->orderBy('name')->get(['id', 'name']);

        $canViewAttempts = $request->user()?->can('training.attempt.view') || $request->user()?->can('training.catalog.view') ?? false;

        return Inertia::render('Training/Dashboard/Compliance', [
            'metrics' => [
                'active_users' => $totalActiveUsers,
                'completion_rate' => $overallCompletionRate,
                'upcoming' => $upcomingAssignments,
                'overdue' => $overdueAssignments,
                'completed' => $completedAssignments,
                'in_progress' => $inProgressAssignments,
                'pending' => $pendingAssignments,
                'total' => $totalAssignments,
            ],
            'departmentStats' => $departmentStats,
            'testPerformance' => $testPerformance,
            'matrix' => $matrix,
            'filterOptions' => [
                'departments' => $departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name]),
                'officePositions' => $officePositions,
                'trainings' => $trainings->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'title' => $t->title]),
                'statuses' => ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'OVERDUE', 'EXPIRED'],
                'triggerTypes' => ['NEW_USER', 'WORKFLOW_CHANGE', 'RETRAINING', 'MANUAL'],
            ],
            'filters' => [
                'department_id' => $departmentId,
                'office_position_id' => $officePositionId,
                'training_id' => $trainingId,
                'status' => $status,
                'trigger_type' => $triggerType,
                'due_from' => $dueFrom,
                'due_to' => $dueTo,
                'search' => $search,
            ],
            'permissions' => [
                'can_view_attempts' => $canViewAttempts,
                'can_delete' => $request->user()?->can('training.catalog.delete') || $request->user()?->can('training-session.delete') || $request->user()?->can('training.session.delete') ?? false,
            ],
        ]);
    }

    /**
     * Fetch complete attempt history for an assignment (Admin / Authorized user).
     */
    public function attemptHistory(Request $request, TrainingAssignment $assignment)
    {
        $currentUser = $request->user();
        if ($assignment->user_id !== $currentUser->id && !$currentUser->can('training.attempt.view') && !$currentUser->can('training.catalog.view')) {
            abort(403, 'Unauthorized to view test attempt history.');
        }

        $assignment->load([
            'user.department',
            'user.officePosition',
            'training.category',
            'training.test',
            'testAttempts' => function ($q) {
                $q->with([
                    'session.trainer',
                    'answers.selectedOption',
                    'answers.question.options',
                ])->latest();
            },
        ]);

        return response()->json([
            'assignment' => $assignment,
            'attempts' => $assignment->testAttempts,
        ]);
    }

    /**
     * Delete an individual training assignment (if no submitted attempt results exist).
     */
    public function deleteAssignment(Request $request, TrainingAssignment $assignment)
    {
        $canDelete = $request->user()?->can('training.catalog.delete') || $request->user()?->can('training-session.delete') || $request->user()?->can('training.session.delete');
        abort_if(!$canDelete, 403, 'Unauthorized to delete training assignment.');

        // Protect if assessment attempts with results exist
        $hasResults = $assignment->testAttempts()
            ->where(function ($q) {
                $q->whereNotNull('submitted_at')->orWhereIn('result', ['PASSED', 'FAILED']);
            })
            ->exists();

        if ($hasResults) {
            return back()->withErrors([
                'message' => 'Cannot delete assignment because employee has already submitted assessment attempt results.',
            ]);
        }

        DB::transaction(function () use ($assignment) {
            // Delete any unattempted / draft attempts
            foreach ($assignment->testAttempts as $att) {
                $att->answers()->delete();
                $att->delete();
            }

            // Remove from session participants
            $assignment->sessionParticipants()->delete();

            // Delete the assignment
            $assignment->delete();
        });

        return back()->with('message', 'Training assignment deleted successfully.');
    }

    /**
     * One-click cleanup for all pending assignments that have no active sessions and no submitted test attempts.
     */
    public function cleanupOrphaned(Request $request)
    {
        $canDelete = $request->user()?->can('training.catalog.delete') || $request->user()?->can('training-session.delete') || $request->user()?->can('training.session.delete');
        abort_if(!$canDelete, 403, 'Unauthorized to cleanup orphaned assignments.');

        $deletedCount = 0;

        DB::transaction(function () use (&$deletedCount) {
            $orphans = TrainingAssignment::query()
                ->whereIn('status', ['PENDING', 'IN_PROGRESS', 'OVERDUE'])
                ->whereDoesntHave('sessionParticipants')
                ->whereDoesntHave('testAttempts', function ($q) {
                    $q->whereNotNull('submitted_at')->orWhereIn('result', ['PASSED', 'FAILED']);
                })
                ->get();

            foreach ($orphans as $orphan) {
                foreach ($orphan->testAttempts as $att) {
                    $att->answers()->delete();
                    $att->delete();
                }
                $orphan->delete();
                $deletedCount++;
            }
        });

        return back()->with('message', "Successfully purged {$deletedCount} unlinked/orphaned assignment record(s).");
    }
}
