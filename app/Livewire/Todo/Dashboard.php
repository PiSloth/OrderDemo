<?php

namespace App\Livewire\Todo;

use App\Models\TodoList;
use App\Models\TodoStatus;
use App\Models\TaskComment;
use App\Models\Branch;
use App\Models\Department;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('components.layouts.todo')]
class Dashboard extends Component
{
    public $finishedTasks = [];
    public $passedTasks = [];
    public $failedTasks = [];
    public $needToDoTasks = [];
    public $newTasks = [];
    public $pendingActionSteps = [];

    // Grouped task buckets for clearer presentation
    // - my_department: tasks handled by users in my department (assigned user's department)
    // - my_requests: tasks created by users in my department but NOT handled by my department
    // - others: tasks not related to my department
    public $taskGroups = [
        'my_department' => [
            'tasks' => [],
            'new' => [],
            'need_to_do' => [],
            'overdue' => [],
            'finished' => [],
            'failed' => [],
            'pending_action_steps' => [],
        ],
        'my_requests' => [
            'tasks' => [],
            'new' => [],
            'need_to_do' => [],
            'overdue' => [],
            'finished' => [],
            'failed' => [],
            'pending_action_steps' => [],
        ],
        'others' => [
            'tasks' => [],
            'new' => [],
            'need_to_do' => [],
            'overdue' => [],
            'finished' => [],
            'failed' => [],
            'pending_action_steps' => [],
        ],
    ];

    public $stats = [
        'unfinished' => 0,
        'finished' => 0,
        'passed' => 0,
        'failed' => 0,
        'need_to_do' => 0,
        'new' => 0,
        'pending_action_steps' => 0,
        'success_rate' => 0,
        'total_tasks_in_range' => 0,
        'priority_chart' => [],
        'priority_radar' => ['categories' => [], 'series' => []],
        'category_chart' => [],
        'requesting_departments_chart' => [],
        'assigned_departments_chart' => [],
        'status_chart' => [],
        'requesting_branches_chart' => [],
        'assigned_branches_chart' => [],
        'department_handling_stats' => [],
        'department_resolution_stats' => [],
        'department_resolution_stats' => [],
    ];

    public $showTaskModal = false;
    public $selectedTask = null;
    public $showTaskCommentsModal = false;
    public $selectedTaskId = null;

    // Filter properties
    public $requestedBranchIds = [];
    public $assignedBranchIds = [];
    public $requestedDepartmentIds = [];
    public $assignedDepartmentIds = [];
    public $dateFrom = '';
    public $dateTo = '';
    public $isDepartmentFilterActive = false;

    public $branches = [];
    public $departments = [];

    public function mount()
    {
        // Load filter options
        $this->branches = Branch::all();
        $this->departments = Department::all();

        // Default date range: current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');

        // Initialize empty arrays to prevent undefined variable errors
        $this->finishedTasks = [];
        $this->passedTasks = [];
        $this->failedTasks = [];
        $this->needToDoTasks = [];
        $this->newTasks = [];
        $this->pendingActionSteps = [];

        // Try to load dashboard data, but don't fail if it doesn't work
        try {
            $this->loadDashboardData();
        } catch (\Exception $e) {
            Log::error('Dashboard load failed', ['error' => $e->getMessage()]);
            // Keep empty arrays initialized above
        }
    }

    public function loadDashboardData()
    {
        try {
            $user = Auth::user();

            // Build base query with all necessary relationships
            $query = TodoList::with([
                'status',
                'assignedUser:id,name,email,branch_id,department_id,position_id',
                'assignedUser.branch:id,name',
                'assignedUser.department:id,name',
                'assignedUser.position:id,name',
                'createdByUser:id,name,email,branch_id,department_id,position_id',
                'createdByUser.branch:id,name',
                'createdByUser.department:id,name',
                'createdByUser.position:id,name',
                'dueTime.category',
                'dueTime.priority',
                'requestedByBranch:id,name',
                'department:id,name',
                'comments'
            ])
                // Apply requested branch filter
                ->when(!empty($this->requestedBranchIds), function ($q) {
                    $q->whereIn('requested_by_branch_id', $this->requestedBranchIds);
                })
                // Apply assigned branch filter (branch of the assigned user)
                ->when(!empty($this->assignedBranchIds), function ($q) {
                    $q->whereHas('assignedUser', function ($uq) {
                        $uq->whereIn('branch_id', $this->assignedBranchIds);
                    });
                })
                // Apply requested department filter
                ->when(!empty($this->requestedDepartmentIds), function ($q) {
                    $q->whereIn('requested_by_department_id', $this->requestedDepartmentIds);
                })
                // Apply assigned department filter (department of the assigned user)
                ->when(!empty($this->assignedDepartmentIds), function ($q) {
                    $q->whereHas('assignedUser', function ($uq) {
                        $uq->whereIn('department_id', $this->assignedDepartmentIds);
                    });
                })
                // Apply date range filter (on due date)
                ->when($this->dateFrom && $this->dateTo, function ($q) {
                    $q->whereBetween('due_date', [
                        Carbon::parse($this->dateFrom)->startOfDay(),
                        Carbon::parse($this->dateTo)->endOfDay(),
                    ]);
                });

            // Execute query
            $tasks = $query->get();

            // Debug: Log task count
            Log::info('Dashboard tasks loaded', ['count' => $tasks->count(), 'date_from' => $this->dateFrom, 'date_to' => $this->dateTo]);

            // Get status IDs for categorization using database queries for better performance
            $finishedStatusIds = TodoStatus::whereIn('status', ['Completed', 'Done', 'Finished', 'Successed'])->pluck('id');
            $failedStatusIds = TodoStatus::whereIn('status', ['Cancelled', 'Failed', 'Rejected'])->pluck('id');
            $newStatusIds = TodoStatus::whereIn('status', ['New', 'new'])->pluck('id');

            // Categorize tasks using collections for better performance
            $this->finishedTasks = $tasks->filter(function ($task) use ($finishedStatusIds) {
                return $finishedStatusIds->contains($task->todo_status_id);
            })->values();

            $this->failedTasks = $tasks->filter(function ($task) use ($failedStatusIds) {
                return $failedStatusIds->contains($task->todo_status_id);
            })->values();

            $this->passedTasks = $tasks->filter(function ($task) use ($finishedStatusIds, $failedStatusIds) {
                return !$finishedStatusIds->contains($task->todo_status_id) &&
                    !$failedStatusIds->contains($task->todo_status_id) &&
                    $task->due_date < now();
            })->values();

            $this->needToDoTasks = $tasks->filter(function ($task) use ($finishedStatusIds, $failedStatusIds) {
                return !$finishedStatusIds->contains($task->todo_status_id) &&
                    !$failedStatusIds->contains($task->todo_status_id) &&
                    $task->due_date >= now() &&
                    $task->due_date <= now()->addDays(7); // Next 7 days
            })->values();

            $this->newTasks = $tasks->filter(function ($task) use ($newStatusIds) {
                return $newStatusIds->contains($task->todo_status_id);
            })->values();

            // -------- Group tasks into 3 types for clearer presentation --------
            $myDepartmentId = $user?->department_id;

            $myDepartmentTasks = collect();
            $myRequestedTasks = collect();
            $otherTasks = collect();

            if ($myDepartmentId) {
                // 1) Tasks that our department must do (assigned user's department == my department)
                $myDepartmentTasks = $tasks->filter(function ($task) use ($myDepartmentId) {
                    return $task->assignedUser && (int)$task->assignedUser->department_id === (int)$myDepartmentId;
                })->values();

                $myDepartmentTaskIds = $myDepartmentTasks->pluck('id')->all();

                // 2) Tasks that our department requested to others (created by user's department == my department), excluding #1
                $myRequestedTasks = $tasks->filter(function ($task) use ($myDepartmentId, $myDepartmentTaskIds) {
                    if (in_array($task->id, $myDepartmentTaskIds, true)) {
                        return false;
                    }

                    return $task->createdByUser && (int)$task->createdByUser->department_id === (int)$myDepartmentId;
                })->values();

                $myRequestedTaskIds = $myRequestedTasks->pluck('id')->all();

                // 3) Other tasks not related to my department
                $otherTasks = $tasks->filter(function ($task) use ($myDepartmentTaskIds, $myRequestedTaskIds) {
                    return !in_array($task->id, $myDepartmentTaskIds, true)
                        && !in_array($task->id, $myRequestedTaskIds, true);
                })->values();
            } else {
                // If user has no department, treat everything as "others"
                $otherTasks = $tasks;
            }

            $categorizeTasks = function ($taskCollection) use ($finishedStatusIds, $failedStatusIds, $newStatusIds) {
                $now = now();
                $sevenDays = now()->addDays(7);

                $finished = $taskCollection->filter(fn($t) => $finishedStatusIds->contains($t->todo_status_id))->values();
                $failed = $taskCollection->filter(fn($t) => $failedStatusIds->contains($t->todo_status_id))->values();

                $new = $taskCollection->filter(fn($t) => $newStatusIds->contains($t->todo_status_id))->values();

                $active = $taskCollection->filter(function ($t) use ($finishedStatusIds, $failedStatusIds) {
                    return !$finishedStatusIds->contains($t->todo_status_id) && !$failedStatusIds->contains($t->todo_status_id);
                })->values();

                $overdue = $active->filter(fn($t) => $t->due_date && $t->due_date < $now)->values();
                $needToDo = $active->filter(fn($t) => $t->due_date && $t->due_date >= $now && $t->due_date <= $sevenDays)->values();

                return [
                    'tasks' => $taskCollection->values()->all(),
                    'new' => $new->all(),
                    'need_to_do' => $needToDo->all(),
                    'overdue' => $overdue->all(),
                    'finished' => $finished->all(),
                    'failed' => $failed->all(),
                ];
            };

            $this->taskGroups['my_department'] = array_merge($this->taskGroups['my_department'], $categorizeTasks($myDepartmentTasks));
            $this->taskGroups['my_requests'] = array_merge($this->taskGroups['my_requests'], $categorizeTasks($myRequestedTasks));
            $this->taskGroups['others'] = array_merge($this->taskGroups['others'], $categorizeTasks($otherTasks));

            // Load pending action steps with their related data
            $pendingActionSteps = \App\Models\TaskComment::with([
                'user:id,name,email,branch_id,department_id,position_id',
                'user.branch:id,name',
                'user.department:id,name',
                'todoList:id,task,todo_status_id,assigned_user_id,created_by_user_id,requested_by_department_id',
                'todoList.status:id,status',
                'todoList.assignedUser:id,name,email,branch_id,department_id',
                'todoList.assignedUser.branch:id,name',
                'todoList.assignedUser.department:id,name',
                'todoList.createdByUser:id,name,email,branch_id,department_id',
                'todoList.createdByUser.branch:id,name',
                'todoList.createdByUser.department:id,name',
                'todoList.department:id,name'
            ])
                ->where('comment_type', 'action_step')
                ->where('action_status', 'pending')
                ->whereHas('todoList', function ($query) {
                    // Apply the same filters as the main tasks query
                    $query
                        ->when(!empty($this->requestedBranchIds), function ($q) {
                            $q->whereIn('requested_by_branch_id', $this->requestedBranchIds);
                        })
                        ->when(!empty($this->assignedBranchIds), function ($q) {
                            $q->whereHas('assignedUser', function ($uq) {
                                $uq->whereIn('branch_id', $this->assignedBranchIds);
                            });
                        })
                        ->when(!empty($this->requestedDepartmentIds), function ($q) {
                            $q->whereIn('requested_by_department_id', $this->requestedDepartmentIds);
                        })
                        ->when(!empty($this->assignedDepartmentIds), function ($q) {
                            $q->whereHas('assignedUser', function ($uq) {
                                $uq->whereIn('department_id', $this->assignedDepartmentIds);
                            });
                        })
                        ->when($this->dateFrom && $this->dateTo, function ($q) {
                            $q->whereBetween('due_date', [
                                \Carbon\Carbon::parse($this->dateFrom)->startOfDay(),
                                \Carbon\Carbon::parse($this->dateTo)->endOfDay(),
                            ]);
                        });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Store as arrays for Livewire serialization; use $pendingActionSteps (collection) for grouping.
            $this->pendingActionSteps = $pendingActionSteps->values()->all();

            // Group pending action steps based on their related task's group
            $myDepartmentTaskIdSet = collect($this->taskGroups['my_department']['tasks'])->pluck('id')->flip();
            $myRequestedTaskIdSet = collect($this->taskGroups['my_requests']['tasks'])->pluck('id')->flip();

            $this->taskGroups['my_department']['pending_action_steps'] = $this->pendingActionSteps
                ? $pendingActionSteps->filter(fn($s) => $s->todoList && isset($myDepartmentTaskIdSet[$s->todo_list_id]))->values()->all()
                : [];

            $this->taskGroups['my_requests']['pending_action_steps'] = $this->pendingActionSteps
                ? $pendingActionSteps->filter(fn($s) => $s->todoList && isset($myRequestedTaskIdSet[$s->todo_list_id]))->values()->all()
                : [];

            $this->taskGroups['others']['pending_action_steps'] = $this->pendingActionSteps
                ? $pendingActionSteps->filter(function ($s) use ($myDepartmentTaskIdSet, $myRequestedTaskIdSet) {
                    return $s->todoList
                        && !isset($myDepartmentTaskIdSet[$s->todo_list_id])
                        && !isset($myRequestedTaskIdSet[$s->todo_list_id]);
                })
                ->values()
                ->all()
                : [];

            // Calculate success rate based on due date range
            $totalTasksInRange = $tasks->count();
            $successfulTasksInRange = count($this->finishedTasks);
            $successRate = $totalTasksInRange > 0 ? round(($successfulTasksInRange / $totalTasksInRange) * 100, 1) : 0;

            // Calculate charts using collections for better performance
            $statusChart = $tasks->groupBy(function ($task) {
                return $task->status ? $task->status->status : 'Unknown';
            })->map->count()->sortDesc();

            $priorityChart = $tasks->groupBy(function ($task) {
                return $task->dueTime && $task->dueTime->priority ? $task->dueTime->priority->level : 'Unknown';
            })->map->count()->sortDesc();

            $priorityRadar = [
                'categories' => [],
                'series' => [],
            ];

            if ($priorityChart->count() > 0) {
                $categories = [];
                $data = [];

                foreach ($priorityChart as $priority => $count) {
                    $categories[] = ucfirst((string) $priority);
                    $data[] = (int) $count;
                }

                $priorityRadar = [
                    'categories' => $categories,
                    'series' => [
                        ['name' => 'Tasks', 'data' => $data],
                    ],
                ];
            }

            $categoryChart = $tasks->filter(function ($task) {
                return $task->dueTime && $task->dueTime->category;
            })->groupBy(function ($task) {
                return $task->dueTime->category->name;
            })->map->count()->sortDesc();

            // Calculate requesting branches chart (branches that make requests)
            $requestingBranchesChart = $tasks->filter->requestedByBranch
                ->groupBy(function ($task) {
                    return $task->requestedByBranch->name;
                })->map->count()->sortDesc();

            // Line chart: requesting branches over time (by due date)
            $requestingBranchesLine = [
                'categories' => [],
                'series' => [],
                'top_branch' => null,
                'top_count' => 0,
            ];

            try {
                $from = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
                $to = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;

                if (!$from || !$to) {
                    $minDue = $tasks->min('due_date');
                    $maxDue = $tasks->max('due_date');
                    $from = $minDue ? Carbon::parse($minDue)->startOfDay() : now()->startOfMonth();
                    $to = $maxDue ? Carbon::parse($maxDue)->endOfDay() : now()->endOfMonth();
                }

                $categories = [];
                $cursor = $from->copy()->startOfDay();
                $end = $to->copy()->startOfDay();
                while ($cursor->lte($end)) {
                    $categories[] = $cursor->format('Y-m-d');
                    $cursor->addDay();
                }

                // Take top N requesting branches by total count
                $topBranches = collect($requestingBranchesChart)->take(5);
                $topBranchName = $topBranches->keys()->first();
                $topBranchCount = $topBranchName ? (int)($topBranches[$topBranchName] ?? 0) : 0;

                // Build day->branch->count lookup
                $dailyCounts = [];
                foreach ($tasks as $task) {
                    if (!$task->requestedByBranch || !$task->due_date) {
                        continue;
                    }

                    $branchName = $task->requestedByBranch->name;
                    if (!$topBranches->has($branchName)) {
                        continue;
                    }

                    $day = Carbon::parse($task->due_date)->format('Y-m-d');
                    if (!isset($dailyCounts[$day])) {
                        $dailyCounts[$day] = [];
                    }

                    $dailyCounts[$day][$branchName] = ($dailyCounts[$day][$branchName] ?? 0) + 1;
                }

                $series = [];
                foreach ($topBranches as $branchName => $totalCount) {
                    $data = [];
                    foreach ($categories as $day) {
                        $data[] = (int)($dailyCounts[$day][$branchName] ?? 0);
                    }

                    $series[] = [
                        'name' => ucfirst((string)$branchName),
                        'data' => $data,
                    ];
                }

                $requestingBranchesLine = [
                    'categories' => $categories,
                    'series' => $series,
                    'top_branch' => $topBranchName ? ucfirst((string)$topBranchName) : null,
                    'top_count' => $topBranchCount,
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to build requesting branches line chart', ['error' => $e->getMessage()]);
            }

            // Calculate assigned branches chart (branches that handle requests via assigned users)
            $assignedBranchesChart = $tasks->filter(function ($task) {
                return $task->assignedUser && $task->assignedUser->branch;
            })->groupBy(function ($task) {
                return $task->assignedUser->branch->name;
            })->map->count()->sortDesc();

            // Line chart: handling departments over time (assigned user's department, by due date)
            $handlingDepartmentsLine = [
                'categories' => [],
                'series' => [],
                'top_department' => null,
                'top_count' => 0,
            ];

            try {
                $from = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
                $to = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;

                if (!$from || !$to) {
                    $minDue = $tasks->min('due_date');
                    $maxDue = $tasks->max('due_date');
                    $from = $minDue ? Carbon::parse($minDue)->startOfDay() : now()->startOfMonth();
                    $to = $maxDue ? Carbon::parse($maxDue)->endOfDay() : now()->endOfMonth();
                }

                $categories = [];
                $cursor = $from->copy()->startOfDay();
                $end = $to->copy()->startOfDay();
                while ($cursor->lte($end)) {
                    $categories[] = $cursor->format('Y-m-d');
                    $cursor->addDay();
                }

                // Top N handling departments by total assigned count
                $assignedDepartmentsChartForLine = $tasks->filter(function ($task) {
                    return $task->assignedUser && $task->assignedUser->department;
                })->groupBy(function ($task) {
                    return $task->assignedUser->department->name;
                })->map->count()->sortDesc();

                $topDepartments = collect($assignedDepartmentsChartForLine)->take(5);
                $topDepartmentName = $topDepartments->keys()->first();
                $topDepartmentCount = $topDepartmentName ? (int)($topDepartments[$topDepartmentName] ?? 0) : 0;

                $dailyCounts = [];
                foreach ($tasks as $task) {
                    if (!$task->assignedUser || !$task->assignedUser->department || !$task->due_date) {
                        continue;
                    }

                    $deptName = $task->assignedUser->department->name;
                    if (!$topDepartments->has($deptName)) {
                        continue;
                    }

                    $day = Carbon::parse($task->due_date)->format('Y-m-d');
                    if (!isset($dailyCounts[$day])) {
                        $dailyCounts[$day] = [];
                    }

                    $dailyCounts[$day][$deptName] = ($dailyCounts[$day][$deptName] ?? 0) + 1;
                }

                $series = [];
                foreach ($topDepartments as $deptName => $totalCount) {
                    $data = [];
                    foreach ($categories as $day) {
                        $data[] = (int)($dailyCounts[$day][$deptName] ?? 0);
                    }

                    $series[] = [
                        'name' => ucfirst((string)$deptName),
                        'data' => $data,
                    ];
                }

                $handlingDepartmentsLine = [
                    'categories' => $categories,
                    'series' => $series,
                    'top_department' => $topDepartmentName ? ucfirst((string)$topDepartmentName) : null,
                    'top_count' => $topDepartmentCount,
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to build handling departments line chart', ['error' => $e->getMessage()]);
            }

            // Calculate department handling statistics (departments that handle requests)
            $departmentHandlingStats = $tasks->filter->department
                ->groupBy(function ($task) {
                    return $task->department->name;
                })->map(function ($deptTasks) use ($finishedStatusIds) {
                    $total = $deptTasks->count();
                    $completed = $deptTasks->filter(function ($task) use ($finishedStatusIds) {
                        return $finishedStatusIds->contains($task->todo_status_id);
                    })->count();

                    return [
                        'total_assigned' => $total,
                        'completed' => $completed,
                        'success_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0
                    ];
                })->sortByDesc('total_assigned');

            // Calculate department resolution statistics (departments that resolve the most)
            $departmentResolutionStats = $tasks->filter(function ($task) use ($finishedStatusIds) {
                return $task->department && $finishedStatusIds->contains($task->todo_status_id);
            })->groupBy(function ($task) {
                return $task->department->name;
            })->map->count()->sortDesc();

            // Calculate requesting departments chart
            $requestingDepartmentsChart = $tasks->filter->department
                ->groupBy(function ($task) {
                    return $task->department->name;
                })->map->count()->sortDesc();

            // Calculate assigned departments chart (departments of assigned users)
            $assignedDepartmentsChart = $tasks->filter(function ($task) {
                return $task->assignedUser && $task->assignedUser->department;
            })->groupBy(function ($task) {
                return $task->assignedUser->department->name;
            })->map->count()->sortDesc();

            // Update stats
            $this->stats = array_merge($this->stats, [
                'finished' => count($this->finishedTasks),
                'passed' => count($this->passedTasks),
                'failed' => count($this->failedTasks),
                'need_to_do' => count($this->needToDoTasks),
                'new' => count($this->newTasks),
                'pending_action_steps' => count($this->pendingActionSteps),
                'success_rate' => $successRate,
                'total_tasks_in_range' => $totalTasksInRange,
                'status_chart' => $statusChart->toArray(),
                'priority_chart' => $priorityChart->toArray(),
                'priority_radar' => $priorityRadar,
                'category_chart' => $categoryChart->toArray(),
                'requesting_departments_chart' => $requestingDepartmentsChart->toArray(),
                'assigned_departments_chart' => $assignedDepartmentsChart->toArray(),
                'requesting_branches_chart' => $requestingBranchesChart->toArray(),
                'assigned_branches_chart' => $assignedBranchesChart->toArray(),
                'requesting_branches_line' => $requestingBranchesLine,
                'department_handling_line' => $handlingDepartmentsLine,
                'department_handling_stats' => $departmentHandlingStats->toArray(),
                'department_resolution_stats' => $departmentResolutionStats->toArray(),
            ]);

            // Notify frontend charts to update on filter changes
            $this->dispatch('priority-radar-chart-updated', chart: ($this->stats['priority_radar'] ?? []));
            $this->dispatch('branch-requests-chart-updated', chart: ($this->stats['requesting_branches_line'] ?? []));
            $this->dispatch('department-handling-chart-updated', chart: ($this->stats['department_handling_line'] ?? []));
        } catch (\Exception $e) {
            Log::error('Dashboard load failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // If anything fails, ensure stats array has all required keys with default values
            $this->stats = array_merge($this->stats, [
                'finished' => 0,
                'passed' => 0,
                'failed' => 0,
                'need_to_do' => 0,
                'new' => 0,
                'pending_action_steps' => 0,
                'success_rate' => 0,
                'total_tasks_in_range' => 0,
                'status_chart' => [],
                'priority_chart' => [],
                'priority_radar' => ['categories' => [], 'series' => []],
                'category_chart' => [],
                'requesting_departments_chart' => [],
                'assigned_departments_chart' => [],
                'requesting_branches_chart' => [],
                'assigned_branches_chart' => [],
                'requesting_branches_line' => ['categories' => [], 'series' => [], 'top_branch' => null, 'top_count' => 0],
                'department_handling_line' => ['categories' => [], 'series' => [], 'top_department' => null, 'top_count' => 0],
                'department_handling_stats' => [],
                'department_resolution_stats' => [],
            ]);

            // Ensure taskGroups always has expected shape
            $this->taskGroups = [
                'my_department' => ['tasks' => [], 'new' => [], 'need_to_do' => [], 'overdue' => [], 'finished' => [], 'failed' => [], 'pending_action_steps' => []],
                'my_requests' => ['tasks' => [], 'new' => [], 'need_to_do' => [], 'overdue' => [], 'finished' => [], 'failed' => [], 'pending_action_steps' => []],
                'others' => ['tasks' => [], 'new' => [], 'need_to_do' => [], 'overdue' => [], 'finished' => [], 'failed' => [], 'pending_action_steps' => []],
            ];
        }
    }

    public function showTaskDetail($taskId)
    {
        $this->selectedTaskId = $taskId;
        $this->showTaskCommentsModal = true;
    }

    public function closeTaskCommentsModal()
    {
        $this->showTaskCommentsModal = false;
        $this->selectedTaskId = null;
    }

    public function canAcknowledgeTask($task)
    {
        $user = Auth::user();

        // Check if task exists and has a status
        if (!$task || !$task->status) {
            Log::info('Task or status missing', ['task_id' => $task ? $task->id : 'null']);
            return false;
        }

        // Check if task is in a status that can be acknowledged (new, pending, etc.)
        $acknowledgeableStatuses = ['new', 'New', 'pending', 'Pending', 'unassigned'];
        if (!in_array($task->status->status, $acknowledgeableStatuses)) {
            Log::info('Task status not acknowledgeable', [
                'task_id' => $task->id,
                'status' => $task->status->status,
                'acknowledgeable' => $acknowledgeableStatuses
            ]);
            return false;
        }

        // Check if user belongs to the task's requested department
        if (!$task->department || !$user) {
            Log::info('Task department or user missing', [
                'task_id' => $task->id,
                'department_id' => $task->requested_by_department_id,
                'user_id' => $user ? $user->id : 'null'
            ]);
            return false;
        }

        // Check if user has the task's requested department
        $hasDepartment = $user->department_id == $task->requested_by_department_id;
        Log::info('Department check result', [
            'user_id' => $user->id,
            'task_requested_department_id' => $task->requested_by_department_id,
            'user_department_id' => $user->department_id,
            'has_department' => $hasDepartment
        ]);

        return $hasDepartment;
    }

    public function acknowledgeTask($taskId)
    {
        Log::info('acknowledgeTask method called', ['task_id' => $taskId]);

        $task = TodoList::find($taskId);

        if (!$task) {
            session()->flash('error', 'Task not found.');
            return;
        }

        // Debug: Check task status and department
        Log::info('Acknowledge Task Debug', [
            'task_id' => $taskId,
            'task_status' => $task->status ? $task->status->status : 'no status',
            'task_requested_department_id' => $task->requested_by_department_id,
            'user_id' => auth()->id(),
            'user_department_id' => auth()->user()->department_id,
        ]);

        if (!$this->canAcknowledgeTask($task)) {
            session()->flash('error', 'You are not authorized to acknowledge this task. Task status: ' . ($task->status ? $task->status->status : 'no status'));
            return;
        }

        // Find or create "acknowledged" status
        $acknowledgedStatus = TodoStatus::where('status', 'Acknowledged')->first();
        if (!$acknowledgedStatus) {
            $acknowledgedStatus = TodoStatus::create([
                'status' => 'Acknowledged',
                'description' => 'Task has been acknowledged by the assigned department',
                'color_code' => 'blue'
            ]);
        }

        // Update task status
        $task->update([
            'todo_status_id' => $acknowledgedStatus->id
        ]);

        // Verify the update
        $task->refresh();
        Log::info('Task status updated', [
            'task_id' => $taskId,
            'new_status' => $task->status ? $task->status->status : 'no status',
            'status_id' => $task->todo_status_id
        ]);

        // Refresh the selected task data
        $this->selectedTask = TodoList::with([
            'status',
            'assignedUser:id,name,email,branch_id,department_id,position_id',
            'assignedUser.branch:id,name',
            'assignedUser.department:id,name',
            'assignedUser.position:id,name',
            'createdByUser:id,name,email,branch_id,department_id,position_id',
            'createdByUser.branch:id,name',
            'createdByUser.department:id,name',
            'createdByUser.position:id,name',
            'dueTime.category',
            'dueTime.priority',
            'requestedByBranch:id,name',
            'department:id,name',
            'comments.user:id,name'
        ])->find($taskId);

        // Reload dashboard data to reflect changes
        $this->loadDashboardData();

        // Close the modal
        $this->closeTaskModal();

        session()->flash('message', 'Task acknowledged successfully!');
    }

    public function showActionStepDetail($actionStepId)
    {
        $actionStep = \App\Models\TaskComment::find($actionStepId);
        if ($actionStep && $actionStep->todoList) {
            $this->showTaskDetail($actionStep->todo_list_id);
        }
    }

    public function updatedRequestedBranchIds()
    {
        $this->isDepartmentFilterActive = false;
        $this->loadDashboardData();
    }

    public function updatedAssignedBranchIds()
    {
        $this->isDepartmentFilterActive = false;
        $this->loadDashboardData();
    }

    public function updatedRequestedDepartmentIds()
    {
        $this->isDepartmentFilterActive = false;
        $this->loadDashboardData();
    }

    public function updatedAssignedDepartmentIds()
    {
        // Reset department filter active state when manually changed
        $this->isDepartmentFilterActive = false;
        $this->loadDashboardData();
    }

    public function updatedDateFrom()
    {
        $this->loadDashboardData();
    }

    public function updatedDateTo()
    {
        $this->loadDashboardData();
    }

    public function render()
    {
        // Ensure all arrays are initialized before rendering
        $this->finishedTasks = $this->finishedTasks ?? [];
        $this->passedTasks = $this->passedTasks ?? [];
        $this->failedTasks = $this->failedTasks ?? [];
        $this->needToDoTasks = $this->needToDoTasks ?? [];
        $this->newTasks = $this->newTasks ?? [];
        $this->pendingActionSteps = $this->pendingActionSteps ?? [];

        // Ensure stats array has all required keys before rendering
        $this->stats = array_merge([
            'finished' => 0,
            'passed' => 0,
            'failed' => 0,
            'need_to_do' => 0,
            'new' => 0,
            'pending_action_steps' => 0,
            'success_rate' => 0,
            'total_tasks_in_range' => 0,
            'priority_chart' => [],
            'priority_radar' => ['categories' => [], 'series' => []],
            'requesting_departments_chart' => [],
            'assigned_departments_chart' => [],
            'status_chart' => [],
            'requesting_branches_chart' => [],
            'assigned_branches_chart' => [],
            'requesting_branches_line' => ['categories' => [], 'series' => [], 'top_branch' => null, 'top_count' => 0],
            'department_handling_line' => ['categories' => [], 'series' => [], 'top_department' => null, 'top_count' => 0],
            'department_handling_stats' => [],
            'department_resolution_stats' => [],
        ], $this->stats);

        return view('livewire.todo.dashboard');
    }

    public function filterMyDepartmentTasks()
    {
        $user = Auth::user();

        if (!$user || !$user->department) {
            session()->flash('error', 'You are not assigned to any department.');
            return;
        }

        if ($this->isDepartmentFilterActive) {
            // Deactivate department filter
            $this->assignedDepartmentIds = [];
            $this->isDepartmentFilterActive = false;
            session()->flash('message', 'Department filter removed.');
        } else {
            // Activate department filter
            $this->assignedDepartmentIds = [$user->department->id];
            $this->isDepartmentFilterActive = true;
            session()->flash('message', 'Filtered to show tasks for your department only.');
        }

        // Clear other filters when toggling department filter
        $this->requestedBranchIds = [];
        $this->assignedBranchIds = [];
        $this->requestedDepartmentIds = [];

        // Reload dashboard data with the new filters
        $this->loadDashboardData();
    }
}
