<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainingSkillMatrixController extends Controller
{
    /**
     * Display the Skill Matrix page with Department, Branch, and Employee Matrix views.
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();

        $isGlobalScope = $currentUser->isSuperUser()
            || $currentUser->can('global-scope.view')
            || $currentUser->can('training.catalog.view')
            || $currentUser->can('training.attempt.view');

        $hasDeptScope = $currentUser->can('department-scope.view');
        $hasBranchScope = $currentUser->can('branch-scope.view');

        // Check if user has permission to view skill matrix
        if (!$isGlobalScope && !$hasDeptScope && !$hasBranchScope) {
            abort(403, 'Unauthorized. You do not have permission to view the skill matrix.');
        }

        // Base users query
        $usersQuery = User::query()
            ->where('suspended', false)
            ->with([
                'department:id,name',
                'branch:id,name',
                'location:id,name',
                'officePosition:id,name',
                'trainingAssignments' => function ($q) {
                    $q->with([
                        'training:id,code,title,passing_score,training_category_id',
                        'training.category:id,name',
                        'testAttempts' => function ($attemptQ) {
                            $attemptQ->orderByDesc('id');
                        }
                    ]);
                }
            ]);

        // Restrict based on scopes if not authorized for global view
        if (!$isGlobalScope) {
            $deptId = $currentUser->department_id;
            $branchId = $currentUser->branch_id;

            $usersQuery->where(function ($q) use ($currentUser, $deptId, $branchId, $hasDeptScope, $hasBranchScope) {
                // User can always see themselves
                $q->where('id', $currentUser->id);

                // Department scope: same department persons
                if ($hasDeptScope && $deptId) {
                    $q->orWhere('department_id', $deptId);
                }

                // Branch scope: same branch persons
                if ($hasBranchScope && $branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            });
        }

        $rawUsers = $usersQuery->orderBy('name')->get();

        // Fetch Departments, Branches, Locations with accurate scoped user counts
        if ($isGlobalScope) {
            $departments = Department::withCount('users')->orderBy('name')->get(['id', 'name']);
            $branches = Branch::withCount('users')->orderBy('name')->get(['id', 'name']);
            $locations = Location::withCount('users')->orderBy('name')->get(['id', 'name']);
        } else {
            $visibleDeptIds = $rawUsers->pluck('department_id')->filter()->unique();
            $visibleBranchIds = $rawUsers->pluck('branch_id')->filter()->unique();
            $visibleLocationIds = $rawUsers->pluck('location_id')->filter()->unique();

            $departments = Department::whereIn('id', $visibleDeptIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function ($dept) use ($rawUsers) {
                    $dept->users_count = $rawUsers->where('department_id', $dept->id)->count();
                    return $dept;
                });

            $branches = Branch::whereIn('id', $visibleBranchIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function ($b) use ($rawUsers) {
                    $b->users_count = $rawUsers->where('branch_id', $b->id)->count();
                    return $b;
                });

            $locations = Location::whereIn('id', $visibleLocationIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function ($loc) use ($rawUsers) {
                    $loc->users_count = $rawUsers->where('location_id', $loc->id)->count();
                    return $loc;
                });
        }

        // Compute overall matrix scores and competency metrics for every employee
        $employees = $rawUsers->map(function ($u) {
            $assignments = $u->trainingAssignments;

            $skills = $assignments->map(function ($assignment) {
                $training = $assignment->training;
                if (!$training) return null;

                $latestAttempt = $assignment->testAttempts->first();
                $bestAttempt = $assignment->testAttempts->where('result', 'PASSED')->sortByDesc('percentage')->first()
                    ?: $assignment->testAttempts->sortByDesc('percentage')->first();

                $passingScore = (float) ($training->passing_score ?? 80.0);
                $lastScore = $latestAttempt ? (float) $latestAttempt->percentage : null;
                $bestScore = $bestAttempt ? (float) $bestAttempt->percentage : null;
                $isPassed = $latestAttempt ? ($latestAttempt->result === 'PASSED' || ($latestAttempt->percentage >= $passingScore)) : false;
                $isStar = $lastScore !== null && $lastScore >= 90.0;

                return [
                    'assignment_id' => $assignment->id,
                    'training_id' => $training->id,
                    'training_code' => $training->code,
                    'training_title' => $training->title,
                    'category_name' => $training->category?->name ?? 'General',
                    'passing_score' => $passingScore,
                    'last_score' => $lastScore,
                    'best_score' => $bestScore,
                    'is_passed' => $isPassed,
                    'is_star' => $isStar,
                ];
            })->filter()->values();

            $totalSkills = $skills->count();
            $attemptedSkills = $skills->filter(fn($s) => $s['last_score'] !== null);
            $passedSkills = $skills->filter(fn($s) => $s['is_passed']);
            $starSkills = $skills->filter(fn($s) => $s['is_star']);

            $averageScore = $attemptedSkills->isNotEmpty()
                ? round($attemptedSkills->avg('last_score'), 1)
                : 0.0;

            // Star Person check (all attempted skills >= 90% or star performers)
            $isStarPerson = false;
            if ($totalSkills > 0 && $attemptedSkills->count() === $totalSkills && $averageScore >= 90.0) {
                $isStarPerson = $skills->every(fn($s) => ($s['last_score'] ?? 0) >= 90.0);
            } elseif ($starSkills->count() > 0 && $starSkills->count() === $totalSkills) {
                $isStarPerson = true;
            }

            // High competencies / skillful domains
            $skillfulJobs = $skills->filter(fn($s) => ($s['last_score'] ?? 0) >= 80.0)
                ->map(fn($s) => [
                    'title' => $s['training_title'],
                    'category' => $s['category_name'],
                    'score' => $s['last_score'],
                    'is_star' => $s['is_star'],
                ])->values();

            // Overall reliance label and color
            if ($totalSkills === 0) {
                $relianceLabel = 'No Skills Assigned';
                $relianceDescription = 'No active training modules assigned in the system.';
                $relianceColor = 'slate';
            } elseif ($isStarPerson) {
                $relianceLabel = '🌟 Elite Star Performer';
                $relianceDescription = 'Exemplary mastery across all competency requirements (all skills ≥90%).';
                $relianceColor = 'amber';
            } elseif ($averageScore >= 85.0 && $passedSkills->count() === $totalSkills) {
                $relianceLabel = 'Dependable Specialist';
                $relianceDescription = 'Thoroughly proficient and highly dependable subject matter contributor.';
                $relianceColor = 'emerald';
            } elseif ($passedSkills->count() > 0 && $passedSkills->count() >= ($totalSkills / 2)) {
                $relianceLabel = 'Competent Contributor';
                $relianceDescription = 'Reliable in certified core skill areas while continuing advancement in pending topics.';
                $relianceColor = 'blue';
            } elseif ($attemptedSkills->isEmpty()) {
                $relianceLabel = 'Pending Assessment';
                $relianceDescription = 'Assignments are active, awaiting scheduled training attendance and test evaluations.';
                $relianceColor = 'slate';
            } else {
                $relianceLabel = 'Requires Retraining';
                $relianceDescription = 'Currently has skills below passing criteria. Guided supervision recommended.';
                $relianceColor = 'rose';
            }

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'profile_photo_url' => $u->profile_photo_url,
                'department_id' => $u->department_id,
                'department' => $u->department ? ['id' => $u->department->id, 'name' => $u->department->name] : null,
                'branch_id' => $u->branch_id,
                'branch' => $u->branch ? ['id' => $u->branch->id, 'name' => $u->branch->name] : null,
                'location_id' => $u->location_id,
                'location' => $u->location ? ['id' => $u->location->id, 'name' => $u->location->name] : null,
                'office_position_id' => $u->office_position_id,
                'office_position' => $u->officePosition ? ['id' => $u->officePosition->id, 'name' => $u->officePosition->name] : null,
                'total_skills' => $totalSkills,
                'attempted_skills' => $attemptedSkills->count(),
                'passed_skills' => $passedSkills->count(),
                'star_skills' => $starSkills->count(),
                'matrix_score' => $averageScore,
                'has_attempts' => $attemptedSkills->isNotEmpty(),
                'is_star_person' => $isStarPerson,
                'reliance_label' => $relianceLabel,
                'reliance_description' => $relianceDescription,
                'reliance_color' => $relianceColor,
                'skillful_jobs' => $skillfulJobs,
                'skills' => $skills,
            ];
        });

        // Summary calculations
        $totalEmployees = $employees->count();
        $evaluatedEmployees = $employees->filter(fn($e) => $e['has_attempts'])->count();
        $avgScore = $evaluatedEmployees > 0
            ? round($employees->filter(fn($e) => $e['has_attempts'])->avg('matrix_score'), 1)
            : 0.0;
        $starPerformersCount = $employees->where('is_star_person', true)->count();
        $totalPassedSkills = $employees->sum('passed_skills');
        $totalAssignedSkills = $employees->sum('total_skills');

        $defaultTab = 'department';
        if (!$isGlobalScope && $hasBranchScope && !$hasDeptScope) {
            $defaultTab = 'branch';
        }

        $userScope = $isGlobalScope ? 'global' : ($hasDeptScope && $hasBranchScope ? 'department_and_branch' : ($hasBranchScope ? 'branch' : 'department'));

        return Inertia::render('Training/SkillMatrix/Index', [
            'departments' => $departments,
            'branches' => $branches,
            'locations' => $locations,
            'employees' => $employees,
            'summary' => [
                'total_employees' => $totalEmployees,
                'evaluated_employees' => $evaluatedEmployees,
                'average_score' => $avgScore,
                'star_performers_count' => $starPerformersCount,
                'total_passed_skills' => $totalPassedSkills,
                'total_assigned_skills' => $totalAssignedSkills,
            ],
            'current_user_id' => $currentUser->id,
            'can_view_all' => $isGlobalScope,
            'user_scope' => $userScope,
            'current_user' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'department_id' => $currentUser->department_id,
                'department_name' => $currentUser->department?->name,
                'branch_id' => $currentUser->branch_id,
                'branch_name' => $currentUser->branch?->name,
            ],
            'initialFilters' => [
                'department_id' => $request->query('department_id', ''),
                'branch_id' => $request->query('branch_id', ''),
                'location_id' => $request->query('location_id', ''),
                'tab' => $request->query('tab', $defaultTab),
            ],
        ]);
    }
}
