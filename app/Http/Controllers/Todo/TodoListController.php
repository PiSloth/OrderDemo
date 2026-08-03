<?php

namespace App\Http\Controllers\Todo;

use App\Helpers\WorkingHoursHelper;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\TaskComment;
use App\Models\TodoCategory;
use App\Models\TodoDueTime;
use App\Models\TodoList;
use App\Models\TodoStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TodoListController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = Auth::user();

        $withRelations = [
            'assignedUser.department',
            'createdByUser.department',
            'requestedByBranch',
            'status',
            'dueTime.category',
            'dueTime.priority',
            'comments.user',
            'closedByUser.department',
            'kpiTaskInstance.user',
            'kpiTaskInstances.user.department',
            'kpiTaskInstances.template',
            'kpiTaskInstances.group',
        ];

        // Active tasks
        $todoLists = TodoList::with($withRelations)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Archived tasks
        $archivedTasks = TodoList::onlyTrashed()
            ->with($withRelations)
            ->orderBy('deleted_at', 'desc')
            ->get();

        // Master datasets
        $dueTimes = TodoDueTime::with(['category', 'priority', 'kpiGroup', 'kpiTemplate'])->get();
        $statuses = TodoStatus::all();
        $branches = Branch::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        $itAdminDepartments = $departments->filter(function ($dept) {
            $name = strtolower($dept->name);
            return str_contains($name, 'it') || str_contains($name, 'admin');
        })->values();

        if ($itAdminDepartments->isEmpty()) {
            $itAdminDepartments = $departments;
        }

        $users = User::query()
            ->where('suspended', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department_id', 'branch_id']);

        $formattedDueTimes = $dueTimes->map(function ($dueTime) {
            return [
                'id' => $dueTime->id,
                'name' => ($dueTime->category ? $dueTime->category->name : 'N/A') . ' (' . ($dueTime->priority ? $dueTime->priority->level : 'N/A') . ') - ' . $dueTime->duration . ' Hours',
                'duration' => $dueTime->duration,
                'department_id' => $dueTime->category?->department_id,
            ];
        });

        // Top Performers Leaderboard
        $closedTasks = TodoList::with(['closedByUser.department'])
            ->whereNotNull('closed_by_user_id')
            ->get();

        $performersMap = [];
        foreach ($closedTasks as $cTask) {
            $cUser = $cTask->closedByUser;
            if (!$cUser) continue;
            $uId = $cUser->id;
            if (!isset($performersMap[$uId])) {
                $performersMap[$uId] = [
                    'id' => $uId,
                    'name' => $cUser->name,
                    'email' => $cUser->email,
                    'department' => $cUser->department?->name ?? 'General',
                    'completed_count' => 0,
                ];
            }
            $performersMap[$uId]['completed_count']++;
        }

        $topPerformers = array_values($performersMap);
        usort($topPerformers, fn($a, $b) => $b['completed_count'] - $a['completed_count']);

        return Inertia::render('Todo/Dashboard', [
            'todoLists' => $todoLists,
            'archivedTasks' => $archivedTasks,
            'dueTimes' => $dueTimes,
            'formattedDueTimes' => $formattedDueTimes,
            'statuses' => $statuses,
            'branches' => $branches,
            'departments' => $departments,
            'categories' => TodoCategory::orderBy('name')->get(),
            'itAdminDepartments' => $itAdminDepartments,
            'users' => $users,
            'userBranchId' => $user->branch_id ?? null,
            'topPerformers' => $topPerformers,
        ]);
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();

        // Filters & options
        $filterBranchId = $request->query('filterBranchId', '');
        $filterDepartmentId = $request->query('filterDepartmentId', '');
        $selectedStatusIds = $request->query('selectedStatusIds');
        if (is_string($selectedStatusIds)) {
            $selectedStatusIds = array_filter(explode(',', $selectedStatusIds));
        } elseif (!is_array($selectedStatusIds)) {
            $selectedStatusIds = [];
        }
        $selectedStatusIds = array_map('intval', $selectedStatusIds);

        $sortBy = $request->query('sortBy', 'due_date');
        $selectedMonth = $request->query('selectedMonth', now()->format('Y-m'));
        $viewMode = $request->query('viewMode', 'calendar'); // 'list' or 'calendar'
        $viewStyle = $request->query('viewStyle', 'card'); // 'card' or 'table'

        $dueTimes = TodoDueTime::with(['category', 'priority'])->get();
        $statuses = TodoStatus::all();
        $branches = Branch::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        // Filter departments containing 'IT' or 'Admin' in their name for task assignment selection
        $itAdminDepartments = $departments->filter(function ($dept) {
            $name = strtolower($dept->name);
            return str_contains($name, 'it') || str_contains($name, 'admin');
        })->values();

        if ($itAdminDepartments->isEmpty()) {
            $itAdminDepartments = $departments;
        }

        $users = User::query()
            ->where('suspended', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department_id', 'branch_id']);

        // Build active tasks query
        $activeQuery = TodoList::query()
            ->with([
                'dueTime.category',
                'dueTime.priority',
                'status',
                'assignedUser.department',
                'createdByUser.department',
                'department',
                'requestedByBranch',
                'comments.user',
                'kpiTaskInstance.user',
                'kpiTaskInstances.user.department',
                'kpiTaskInstances.template',
                'kpiTaskInstances.group',
            ]);

        if ($filterBranchId) {
            $activeQuery->where('requested_by_branch_id', $filterBranchId);
        }
        if ($filterDepartmentId) {
            $activeQuery->where('requested_by_department_id', $filterDepartmentId);
        }
        if (!empty($selectedStatusIds)) {
            $activeQuery->whereIn('todo_status_id', $selectedStatusIds);
        }

        switch ($sortBy) {
            case 'created_at':
                $activeQuery->orderBy('created_at', 'desc');
                break;
            case 'priority':
                $activeQuery->join('todo_due_times', 'todo_lists.todo_due_time_id', '=', 'todo_due_times.id')
                    ->join('todo_priorities', 'todo_due_times.todo_priority_id', '=', 'todo_priorities.id')
                    ->orderBy('todo_priorities.rank', 'asc')
                    ->select('todo_lists.*');
                break;
            case 'due_date':
            default:
                $activeQuery->orderBy('due_date', 'asc');
                break;
        }

        $todoLists = $activeQuery->get();

        // Archived tasks
        $archivedTasks = TodoList::onlyTrashed()
            ->with([
                'dueTime.category',
                'dueTime.priority',
                'status',
                'assignedUser',
                'createdByUser',
                'department',
                'requestedByBranch',
                'comments.user',
            ])
            ->get();

        // Calendar tasks for selectedMonth
        try {
            $monthDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $selectedMonth = now()->format('Y-m');
            $monthDate = now()->startOfMonth();
        }

        $startDate = $monthDate->copy()->startOfMonth()->startOfDay();
        $endDate = $monthDate->copy()->endOfMonth()->endOfDay();

        $calendarQuery = TodoList::query()
            ->with(['dueTime.category', 'dueTime.priority', 'status', 'assignedUser', 'department', 'comments.user'])
            ->whereDate('due_date', '>=', $startDate->toDateString())
            ->whereDate('due_date', '<=', $endDate->toDateString())
            ->whereNull('deleted_at');

        if ($filterBranchId) {
            $calendarQuery->where('requested_by_branch_id', $filterBranchId);
        }
        if ($filterDepartmentId) {
            $calendarQuery->where('requested_by_department_id', $filterDepartmentId);
        }
        if (!empty($selectedStatusIds)) {
            $calendarQuery->whereIn('todo_status_id', $selectedStatusIds);
        }

        $rawCalendarTasks = $calendarQuery->get();

        $calendarTasks = [];
        foreach ($rawCalendarTasks as $task) {
            if (!$task->due_date) {
                continue;
            }
            $dateKey = Carbon::parse($task->due_date)->format('Y-m-d');
            $categoryId = (int) ($task->dueTime?->category?->id ?? 0);
            $categoryName = $task->dueTime?->category?->name ?? 'Uncategorized';

            if (!isset($calendarTasks[$dateKey])) {
                $calendarTasks[$dateKey] = [];
            }

            if (!isset($calendarTasks[$dateKey][$categoryId])) {
                $calendarTasks[$dateKey][$categoryId] = [
                    'categoryId' => $categoryId,
                    'name' => $categoryName,
                    'count' => 0,
                    'tasks' => [],
                ];
            }

            $calendarTasks[$dateKey][$categoryId]['count']++;
            $calendarTasks[$dateKey][$categoryId]['tasks'][] = $task;
        }

        // Months with tasks for month switcher
        $unsuccessfulQuery = TodoList::whereNull('deleted_at');
        if ($filterBranchId) {
            $unsuccessfulQuery->where('requested_by_branch_id', $filterBranchId);
        }
        if ($filterDepartmentId) {
            $unsuccessfulQuery->where('requested_by_department_id', $filterDepartmentId);
        }
        if (!empty($selectedStatusIds)) {
            $unsuccessfulQuery->whereIn('todo_status_id', $selectedStatusIds);
        }

        $monthCounts = [];
        foreach ($unsuccessfulQuery->get() as $task) {
            if (!$task->due_date) {
                continue;
            }
            $mKey = Carbon::parse($task->due_date)->format('Y-m');
            if (!isset($monthCounts[$mKey])) {
                $monthCounts[$mKey] = 0;
            }
            $monthCounts[$mKey]++;
        }
        krsort($monthCounts);

        $monthsWithTasks = [];
        foreach ($monthCounts as $mKey => $count) {
            try {
                $mDate = Carbon::createFromFormat('Y-m', $mKey)->startOfMonth();
                $monthsWithTasks[] = [
                    'value' => $mKey,
                    'label' => $mDate->format('F Y'),
                    'count' => $count,
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        // Formatted due times options for select dropdown
        $formattedDueTimes = $dueTimes->map(fn($dt) => [
            'id' => $dt->id,
            'name' => ($dt->category->name ?? 'N/A') . ' - ' . ($dt->priority->level ?? 'N/A') . ' (' . $dt->duration . 'h)',
            'duration' => $dt->duration,
            'department_id' => $dt->category?->department_id,
        ])->values()->all();

        return Inertia::render('Todo/TaskList', [
            'todoLists' => $todoLists,
            'archivedTasks' => $archivedTasks,
            'dueTimes' => $dueTimes,
            'formattedDueTimes' => $formattedDueTimes,
            'statuses' => $statuses,
            'branches' => $branches,
            'departments' => $departments,
            'categories' => TodoCategory::orderBy('name')->get(),
            'itAdminDepartments' => $itAdminDepartments,
            'users' => $users,
            'calendarTasks' => $calendarTasks,
            'monthsWithTasks' => $monthsWithTasks,
            'userBranchId' => $user?->branch_id,
            'userDepartmentId' => $user?->department_id,
            'filters' => [
                'filterBranchId' => $filterBranchId,
                'filterDepartmentId' => $filterDepartmentId,
                'selectedStatusIds' => $selectedStatusIds,
                'sortBy' => $sortBy,
                'selectedMonth' => $selectedMonth,
                'viewMode' => $viewMode,
                'viewStyle' => $viewStyle,
            ],
        ]);
    }

    public function store(Request $request, \App\Services\Todo\TodoTaskService $taskService)
    {
        $validated = $request->validate([
            'selectedDueTimeId' => ['nullable', 'exists:todo_due_times,id'],
            'todo_due_time_id' => ['nullable', 'exists:todo_due_times,id'],
            'task' => ['required', 'string', 'max:1000'],
            'assignedUserId' => ['nullable', 'exists:users,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'requestedByBranchId' => ['nullable', 'exists:branches,id'],
            'requested_by_branch_id' => ['nullable', 'exists:branches,id'],
            'dueDate' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        $dueTimeId = $validated['selectedDueTimeId'] ?? $validated['todo_due_time_id'] ?? null;
        $branchId = $validated['requestedByBranchId'] ?? $validated['requested_by_branch_id'] ?? Auth::user()?->branch_id;
        $assignedUserId = $validated['assignedUserId'] ?? $validated['assigned_user_id'] ?? null;
        $dueDate = $validated['dueDate'] ?? $validated['due_date'] ?? null;

        if (!$dueTimeId) {
            $defaultDueTime = TodoDueTime::first();
            $dueTimeId = $defaultDueTime?->id;
        }

        if (!$branchId) {
            $defaultBranch = Branch::first();
            $branchId = $defaultBranch?->id;
        }

        $task = $taskService->createTask([
            'selected_due_time_id' => $dueTimeId,
            'task' => $validated['task'],
            'assigned_user_id' => $assignedUserId,
            'requested_by_branch_id' => $branchId,
            'due_date' => $dueDate,
        ]);

        // Generate On-Demand KPI Instance if configured on the due time
        $dueTime = TodoDueTime::find($task->todo_due_time_id);
        if ($dueTime && $dueTime->generate_kpi_instance && $dueTime->kpi_group_id) {
            $targetUserIds = [];
            if (!empty($dueTime->kpi_assigned_user_ids) && is_array($dueTime->kpi_assigned_user_ids)) {
                $targetUserIds = array_filter(array_map('intval', $dueTime->kpi_assigned_user_ids));
            } elseif ($dueTime->kpi_assigned_user_id) {
                $targetUserIds = [(int) $dueTime->kpi_assigned_user_id];
            } elseif ($task->assigned_user_id) {
                $targetUserIds = [(int) $task->assigned_user_id];
            }

            $uniqueTargetUserIds = array_unique($targetUserIds);

            // Re-calculate / adjust due date considering target users' holidays
            $dueDateObj = $task->due_date
                ? Carbon::parse($task->due_date)
                : WorkingHoursHelper::calculateDueDate($dueTime->duration ?? 24, null, $uniqueTargetUserIds);

            $firstInstanceId = null;

            foreach ($uniqueTargetUserIds as $targetUserId) {
                // If a responsible person has a holiday (active holiday or pending/approved request), skip creating instance for him
                if (WorkingHoursHelper::isUserOnHoliday((int) $targetUserId, $dueDateObj)) {
                    Log::info("Skipping KPI Instance creation for user {$targetUserId} on date {$dueDateObj->toDateString()} due to holiday/request.");
                    continue;
                }

                $kpiInstance = \App\Models\Kpi\KpiTaskInstance::create([
                    'task_template_id' => $dueTime->kpi_task_template_id,
                    'kpi_group_id' => $dueTime->kpi_group_id,
                    'user_id' => $targetUserId,
                    'task_date' => $dueDateObj->toDateString(),
                    'due_at' => $dueDateObj,
                    'status' => 'pending',
                    'is_on_time' => true,
                    'todo_list_id' => $task->id,
                ]);

                if (!$firstInstanceId) {
                    $firstInstanceId = $kpiInstance->id;
                }
            }

            if ($firstInstanceId) {
                $task->update([
                    'kpi_task_instance_id' => $firstInstanceId,
                    'due_date' => $dueDateObj->format('Y-m-d H:i:s'),
                ]);
            }
        }

        $createdInstances = \App\Models\Kpi\KpiTaskInstance::with('user')->where('todo_list_id', $task->id)->get();
        $createdCount = $createdInstances->count();

        if ($createdCount > 0) {
            $userNames = $createdInstances->map(fn($i) => $i->user?->name ?? "User #{$i->user_id}")->join(', ');
            $flashMsg = "Todo Task created successfully with {$createdCount} connected KPI Task Instance(s) for {$userNames}.";
        } elseif (isset($dueTime) && $dueTime && $dueTime->generate_kpi_instance) {
            $flashMsg = "Todo Task created. KPI Instance generation skipped (user on holiday or missing KPI settings).";
        } else {
            $flashMsg = "Todo Task created successfully.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $flashMsg,
                'task' => $task->load(['dueTime.category', 'status', 'assignedUser', 'kpiTaskInstance', 'kpiTaskInstances.user']),
            ]);
        }

        return redirect()->back()->with('message', $flashMsg);
    }

    public function closeTask($id)
    {
        $task = TodoList::findOrFail($id);

        // KPI Task Closure Constraint: If linked to a KPI Instance, block manual closure from Todo module
        if ($task->kpi_task_instance_id) {
            return redirect()->back()->with('error', 'This task is managed by KPI approval and cannot be closed manually from the Todo module.');
        }

        $now = now();
        $dueDate = $task->due_date ? Carbon::parse($task->due_date) : null;

        $status = null;
        if ($dueDate && $now->greaterThan($dueDate)) {
            $status = TodoStatus::where('status', 'like', '%fail%')->first() ?: TodoStatus::first();
        } else {
            $status = TodoStatus::where('status', 'like', '%success%')
                ->orWhere('status', 'like', '%complete%')
                ->orWhere('status', 'like', '%done%')
                ->first() ?: TodoStatus::first();
        }

        $task->update([
            'todo_status_id' => $status ? $status->id : null,
            'closed_by_user_id' => Auth::id(),
            'closed_at' => $now,
        ]);

        return redirect()->back()->with('message', 'Task closed successfully. Status: ' . ($status ? $status->status : 'Updated'));
    }

    public function archiveTask($id)
    {
        $task = TodoList::findOrFail($id);
        if ($task->todo_status_id) {
            $task->delete();
            return redirect()->back()->with('message', 'Task archived successfully');
        }

        return redirect()->back()->with('error', 'Cannot archive open tasks without status');
    }

    public function restoreTask($id)
    {
        $task = TodoList::withTrashed()->findOrFail($id);
        $task->restore();

        return redirect()->back()->with('message', 'Task restored from archive successfully');
    }

    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:task_comments,id'],
            'comment_type' => ['nullable', 'string'],
            'action_status' => ['nullable', 'string'],
            'action_data' => ['nullable', 'array'],
        ]);

        $task = TodoList::withTrashed()->findOrFail($id);

        $commentType = $request->input('comment_type', 'normal');
        $actionStatus = $commentType === 'action_step' ? 'pending' : null;
        $actionData = $request->input('action_data');

        // Prevent creating another pending request of the same type
        if ($commentType === 'action_step' && isset($actionData['type'])) {
            $pendingSameType = TaskComment::where('todo_list_id', $task->id)
                ->where('comment_type', 'action_step')
                ->where('action_status', 'pending')
                ->where('action_data->type', $actionData['type'])
                ->exists();

            if ($pendingSameType) {
                $readableType = str_replace('_', ' ', $actionData['type']);
                return redirect()->back()->with('error', "A {$readableType} request is already pending for this task. Please wait for it to be resolved before submitting another.");
            }
        }

        $comment = TaskComment::create([
            'todo_list_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => $request->input('comment'),
            'comment_type' => $commentType,
            'action_status' => $actionStatus,
            'action_data' => $actionData,
            'parent_id' => $request->input('parent_id') ?: null,
        ]);

        $this->createNotificationsForComment($comment, $task);

        return redirect()->back()->with('message', 'Comment added successfully');
    }

    public function respondActionStep(Request $request, $commentId)
    {
        $request->validate([
            'action' => ['required', 'string', 'in:accept,reject,counter_offer'],
            'proposed_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $comment = TaskComment::find($commentId);
        if (!$comment) {
            return redirect(url()->previous(route('todo.dashboard')))->with('error', 'Action step comment not found.');
        }

        $task = TodoList::find($comment->todo_list_id);
        if (!$task) {
            return redirect(url()->previous(route('todo.dashboard')))->with('error', 'Associated task not found.');
        }

        $action = $request->input('action');
        $fallback = url()->previous(route('todo.dashboard'));

        if (!$comment->isActionStep() || !$comment->isPendingAction()) {
            return redirect($fallback)->with('error', 'This action step is no longer pending.');
        }

        if ($action === 'accept') {
            $comment->update(['action_status' => 'accepted']);

            if (isset($comment->action_data['type'])) {
                $type = $comment->action_data['type'];
                if ($type === 'due_date_change' && !empty($comment->action_data['new_due_date'])) {
                    $task->update(['due_date' => $comment->action_data['new_due_date']]);
                } elseif ($type === 'status_change' && !empty($comment->action_data['new_status_id'])) {
                    $task->update(['todo_status_id' => $comment->action_data['new_status_id']]);
                } elseif ($type === 'resolver_change' && !empty($comment->action_data['new_assigned_user_id'])) {
                    $task->update(['assigned_user_id' => $comment->action_data['new_assigned_user_id']]);
                }
            }

            // If action request was created after due date -> mark failed if overdue
            if ($comment->created_at > $task->due_date) {
                $failedStatus = TodoStatus::where('status', 'Failed')->first();
                if ($failedStatus) {
                    $task->update(['todo_status_id' => $failedStatus->id]);
                }
            }

            return redirect($fallback)->with('message', 'Action step request accepted successfully.');
        } elseif ($action === 'reject') {
            $comment->update(['action_status' => 'rejected']);
            return redirect($fallback)->with('message', 'Action step request rejected.');
        } elseif ($action === 'counter_offer') {
            $proposedDate = $request->input('proposed_date');
            $reason = $request->input('reason');

            $actionData = $comment->action_data ?? [];
            $actionData['negotiation_status'] = 'negotiating';
            $actionData['proposed_dates'][] = [
                'date' => $proposedDate,
                'reason' => $reason,
                'proposed_by' => Auth::id(),
                'at' => now()->toDateTimeString(),
            ];
            $actionData['new_due_date'] = $proposedDate;

            $comment->update([
                'action_data' => $actionData,
            ]);

            return redirect($fallback)->with('message', 'Counter-offer proposed successfully.');
        }

        return redirect($fallback);
    }

    public function destroyComment($commentId)
    {
        $fallback = url()->previous(route('todo.dashboard'));

        $comment = TaskComment::find($commentId);
        if (!$comment) {
            return redirect($fallback)->with('message', 'Comment deleted or not found.');
        }

        $user = Auth::user();

        if ($user && ($user->isSuperUser() || $user->id === $comment->user_id)) {
            // Clean up any child replies first
            TaskComment::where('parent_id', $comment->id)->delete();
            $comment->delete();
            return redirect($fallback)->with('message', 'Comment deleted successfully.');
        }

        return redirect($fallback)->with('error', 'You do not have permission to delete this comment.');
    }

    protected function createNotificationsForComment(TaskComment $comment, TodoList $task): void
    {
        $currentUserId = Auth::id();
        $relevantUserIds = [];

        if ($task->assigned_user_id && $task->assigned_user_id !== $currentUserId) {
            $relevantUserIds[] = $task->assigned_user_id;
        }

        if ($task->created_by_user_id && $task->created_by_user_id !== $currentUserId) {
            $relevantUserIds[] = $task->created_by_user_id;
        }

        if ($task->requested_by_department_id) {
            $deptUsers = User::where('department_id', $task->requested_by_department_id)
                ->where('id', '!=', $currentUserId)
                ->pluck('id')
                ->all();
            $relevantUserIds = array_merge($relevantUserIds, $deptUsers);
        }

        $relevantUserIds = array_unique(array_diff($relevantUserIds, [$currentUserId]));

        $sender = Auth::user();
        $senderName = $sender ? $sender->name : 'Someone';
        $title = "New comment on task #{$task->id}";
        $message = "{$senderName} commented: " . \Illuminate\Support\Str::limit($comment->comment, 60);

        foreach ($relevantUserIds as $userId) {
            \App\Models\TaskNotification::create([
                'type' => 'comment',
                'title' => $title,
                'message' => $message,
                'user_id' => $userId,
                'task_comment_id' => $comment->id,
                'todo_list_id' => $task->id,
                'is_read' => false,
            ]);
        }
    }
}
