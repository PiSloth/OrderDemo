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
                ->with('session:id,duration_days,schedule_slots')
                ->get();

            if ($participants->isEmpty()) {
                return null;
            }

            $totalRequiredDays = 0;
            $totalAttendedDays = 0;

            foreach ($participants as $participant) {
                $session = $participant->session;
                // If schedule_slots are configured, count the slots; otherwise fallback to duration_days
                $sessionDays = (is_array($session->schedule_slots) && !empty($session->schedule_slots))
                    ? count($session->schedule_slots)
                    : max(1, (int) ($session->duration_days ?: 1));

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
                    // Cap attended count to total session slots/days
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

    /**
     * Get accessible employee list for skill matrix based on department scope, office position, and containment permissions.
     */
    public function skillMatrixUsers(Request $request)
    {
        $currentUser = $request->user();
        if (!$currentUser) {
            return response()->json(['users' => []], 401);
        }

        $canViewAll = $currentUser->isSuperUser()
            || $currentUser->can('training.catalog.view')
            || $currentUser->can('training.attempt.view');

        $usersQuery = User::query()
            ->where('suspended', false)
            ->with(['department:id,name', 'officePosition:id,name']);

        if (!$canViewAll) {
            $userDeptId = $currentUser->department_id;
            $userOfficePosId = $currentUser->office_position_id;

            $usersQuery->where(function ($q) use ($currentUser, $userDeptId, $userOfficePosId) {
                // 1. Current user himself
                $q->where('id', $currentUser->id);

                // 2. Department scope: same department (if user has a department)
                if ($userDeptId) {
                    $q->orWhere(function ($sub) use ($userDeptId, $userOfficePosId) {
                        $sub->where('department_id', $userDeptId);
                        // If office_position is set, can view members with office position or general members
                        if ($userOfficePosId) {
                            $sub->where(function ($opQuery) use ($userOfficePosId) {
                                $opQuery->where('office_position_id', $userOfficePosId)
                                    ->orWhereNull('office_position_id');
                            });
                        }
                    });
                }
            });
        }

        $users = $usersQuery->orderBy('name')->get(['id', 'name', 'email', 'department_id', 'office_position_id', 'profile_photo_path'])->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'department_id' => $u->department_id,
                'office_position_id' => $u->office_position_id,
                'department' => $u->department,
                'office_position' => $u->officePosition,
                'profile_photo_url' => $u->profile_photo_url,
            ];
        });

        return response()->json([
            'users' => $users,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'current_user_id' => $currentUser->id,
            'can_view_all' => $canViewAll,
        ]);
    }

    /**
     * Get employee training skill matrix details and reliability statistics.
     */
    public function employeeSkillMatrix(Request $request, User $user)
    {
        $currentUser = $request->user();
        if (!$currentUser) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $canViewAll = $currentUser->isSuperUser()
            || $currentUser->can('training.catalog.view')
            || $currentUser->can('training.attempt.view');

        // Check if user is allowed to view target employee
        if (!$canViewAll && $currentUser->id !== $user->id) {
            $isSameDept = ($currentUser->department_id && $currentUser->department_id === $user->department_id);
            if (!$isSameDept) {
                return response()->json(['error' => 'Unauthorized to view this employee matrix.'], 403);
            }
        }

        $user->load(['department:id,name', 'officePosition:id,name']);

        // Fetch all assignments for this employee with training and test attempts
        $assignments = TrainingAssignment::query()
            ->where('user_id', $user->id)
            ->with([
                'training.category:id,name',
                'training.test:id,training_id,title',
                'testAttempts' => fn($q) => $q->orderByDesc('id'),
            ])
            ->get();

        // Calculate skill details
        $skills = $assignments->map(function ($assignment) {
            $training = $assignment->training;
            if (!$training) return null;

            // Find latest test attempt
            $latestAttempt = $assignment->testAttempts->first();
            // Also find best/passed attempt if any
            $bestAttempt = $assignment->testAttempts->where('result', 'PASSED')->sortByDesc('percentage')->first()
                ?: $assignment->testAttempts->sortByDesc('percentage')->first();

            $passingScore = (float) ($training->passing_score ?? 80.0);
            $lastScore = $latestAttempt ? (float) $latestAttempt->percentage : null;
            $bestScore = $bestAttempt ? (float) $bestAttempt->percentage : null;
            $isPassed = $latestAttempt ? ($latestAttempt->result === 'PASSED' || ($latestAttempt->percentage >= $passingScore)) : false;
            $isStar = $lastScore !== null && $lastScore >= 90.0;

            // Operational proficiency rating based on score
            if ($lastScore === null) {
                $statusLabel = 'Not Attempted';
                $statusColor = 'slate';
                $relyRating = 'Unassessed';
                $relyDescription = 'Assessment has not been taken yet.';
            } elseif ($lastScore >= 90.0) {
                $statusLabel = 'Mastery / Star Skill';
                $statusColor = 'amber';
                $relyRating = 'Highly Reliable (Star Performer)';
                $relyDescription = 'Can be fully entrusted with critical operations, training peers, and independent decisions.';
            } elseif ($lastScore >= 80.0) {
                $statusLabel = 'Proficient';
                $statusColor = 'emerald';
                $relyRating = 'Reliable Practitioner';
                $relyDescription = 'Solid execution competency with standard operational workflows and minor oversight.';
            } elseif ($lastScore >= 70.0) {
                $statusLabel = 'Competent';
                $statusColor = 'blue';
                $relyRating = 'Capable';
                $relyDescription = 'Meets standard job requirements; periodic coaching recommended for complex edge cases.';
            } elseif ($lastScore >= 60.0) {
                $statusLabel = 'Developing';
                $statusColor = 'orange';
                $relyRating = 'Needs Supervised Practice';
                $relyDescription = 'Marginal qualification; should work alongside a mentor on high-stakes tasks.';
            } else {
                $statusLabel = 'Uncertified / Failed';
                $statusColor = 'rose';
                $relyRating = 'Requires Retraining';
                $relyDescription = 'Did not meet operational criteria; retraining and re-examination required.';
            }

            return [
                'assignment_id' => $assignment->id,
                'training_id' => $training->id,
                'training_code' => $training->code,
                'training_title' => $training->title,
                'category_name' => $training->category?->name ?? 'General',
                'assignment_status' => $assignment->status,
                'passing_score' => $passingScore,
                'last_score' => $lastScore,
                'best_score' => $bestScore,
                'is_passed' => $isPassed,
                'is_star' => $isStar,
                'total_attempts' => $assignment->testAttempts->count(),
                'last_attempted_at' => $latestAttempt?->submitted_at?->toIso8601String() ?? $latestAttempt?->created_at?->toIso8601String(),
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
                'rely_rating' => $relyRating,
                'rely_description' => $relyDescription,
            ];
        })->filter()->values();

        // Overall Employee Synthesis & Reliability Calculation
        $totalSkills = $skills->count();
        $attemptedSkills = $skills->filter(fn($s) => $s['last_score'] !== null);
        $passedSkills = $skills->filter(fn($s) => $s['is_passed']);
        $starSkills = $skills->filter(fn($s) => $s['is_star']);

        $averageScore = $attemptedSkills->isNotEmpty()
            ? round($attemptedSkills->avg('last_score'), 1)
            : 0;

        // Is Employee a Star Person? (has >= 90% score across his skills, or all passed skills are >= 90%)
        $isStarPerson = false;
        if ($totalSkills > 0 && $attemptedSkills->count() === $totalSkills && $averageScore >= 90.0) {
            // Checked if all individual skills are >= 90% or strictly high proficiency
            $isStarPerson = $skills->every(fn($s) => ($s['last_score'] ?? 0) >= 90.0);
        } elseif ($starSkills->count() > 0 && $starSkills->count() === $totalSkills) {
            $isStarPerson = true;
        }

        // Qualitative synthesis: In which jobs/domains is he skillful?
        $skillfulJobs = $skills->filter(fn($s) => ($s['last_score'] ?? 0) >= 80.0)
            ->map(fn($s) => [
                'title' => $s['training_title'],
                'category' => $s['category_name'],
                'score' => $s['last_score'],
                'is_star' => $s['is_star'],
            ])->values();

        // How we should rely on this person (Overall synthesis)
        if ($totalSkills === 0) {
            $overallReliance = 'No Skills Assigned';
            $overallDescription = 'This employee currently has no assigned training modules in the system.';
            $overallBadgeColor = 'slate';
        } elseif ($isStarPerson) {
            $overallReliance = '🌟 Elite Star Performer (Highest Reliance)';
            $overallDescription = 'Exemplary mastery across all competency requirements (all skills ≥90%). Ideal for team leadership, pivotal mission-critical operations, mentoring, and autonomous execution.';
            $overallBadgeColor = 'amber';
        } elseif ($averageScore >= 85.0 && $passedSkills->count() === $totalSkills) {
            $overallReliance = 'Dependable Subject Matter Specialist';
            $overallDescription = 'Thoroughly proficient and highly dependable. Can independently execute duties with confidence and standard operational compliance.';
            $overallBadgeColor = 'emerald';
        } elseif ($passedSkills->count() > 0 && $passedSkills->count() >= ($totalSkills / 2)) {
            $overallReliance = 'Competent Operational Contributor';
            $overallDescription = 'Reliable in certified core skill areas, while continuing advancement or retraining in pending topics.';
            $overallBadgeColor = 'blue';
        } elseif ($attemptedSkills->isEmpty()) {
            $overallReliance = 'Pending Assessment & Onboarding';
            $overallDescription = 'Assignments are active, awaiting scheduled training attendance and test evaluations.';
            $overallBadgeColor = 'slate';
        } else {
            $overallReliance = 'Requires Targeted Guidance & Retraining';
            $overallDescription = 'Currently has skills below passing criteria. Supervision recommended before delegating high-impact tasks.';
            $overallBadgeColor = 'rose';
        }

        return response()->json([
            'employee' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department?->name ?? 'Unassigned Department',
                'office_position' => $user->officePosition?->name ?? 'General Staff',
                'profile_photo_url' => $user->profile_photo_url,
            ],
            'summary' => [
                'total_skills' => $totalSkills,
                'passed_skills' => $passedSkills->count(),
                'star_skills' => $starSkills->count(),
                'average_score' => $averageScore,
                'is_star_person' => $isStarPerson,
                'overall_reliance' => $overallReliance,
                'overall_description' => $overallDescription,
                'overall_badge_color' => $overallBadgeColor,
            ],
            'skillful_jobs' => $skillfulJobs,
            'skills' => $skills,
        ]);
    }
}
