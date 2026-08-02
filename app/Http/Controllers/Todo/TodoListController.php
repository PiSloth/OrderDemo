<?php

namespace App\Http\Controllers\Todo;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\TaskComment;
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

        // Active tasks
        $todoLists = TodoList::with(['assignedUser.department', 'requestedByBranch', 'status', 'dueTime.category', 'dueTime.priority', 'comments.user', 'closedByUser.department'])
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Archived tasks
        $archivedTasks = TodoList::onlyTrashed()
            ->with(['assignedUser.department', 'requestedByBranch', 'status', 'dueTime.category', 'dueTime.priority', 'comments.user', 'closedByUser.department'])
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
                'assignedUser',
                'createdByUser',
                'department',
                'requestedByBranch',
                'comments.user',
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
        ])->values()->all();

        return Inertia::render('Todo/TaskList', [
            'todoLists' => $todoLists,
            'archivedTasks' => $archivedTasks,
            'dueTimes' => $dueTimes,
            'formattedDueTimes' => $formattedDueTimes,
            'statuses' => $statuses,
            'branches' => $branches,
            'departments' => $departments,
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
        if ($dueTime && $dueTime->generate_kpi_instance && $dueTime->kpi_group_id && $task->assigned_user_id) {
            $dueDateObj = $task->due_date ? Carbon::parse($task->due_date) : now()->addHours($dueTime->duration ?? 24);

            $kpiInstance = \App\Models\Kpi\KpiTaskInstance::create([
                'task_template_id' => $dueTime->kpi_task_template_id,
                'kpi_group_id' => $dueTime->kpi_group_id,
                'user_id' => $task->assigned_user_id, // Assigned Employee
                'task_date' => $dueDateObj->toDateString(),
                'due_at' => $dueDateObj,
                'status' => 'pending',
                'is_on_time' => true,
                'todo_list_id' => $task->id,
            ]);

            $task->update([
                'kpi_task_instance_id' => $kpiInstance->id,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Todo Task Created Successfully' . ($task->kpi_task_instance_id ? ' (Linked to KPI Instance)' : ''),
                'task' => $task->load(['dueTime.category', 'status', 'assignedUser', 'kpiTaskInstance']),
            ]);
        }

        return redirect()->back()->with('message', 'Todo Task Created Successfully' . ($task->kpi_task_instance_id ? ' (Linked to KPI Instance)' : ''));
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
        ]);

        $task = TodoList::withTrashed()->findOrFail($id);

        $comment = TaskComment::create([
            'todo_list_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => $request->input('comment'),
            'comment_type' => 'normal',
            'parent_id' => $request->input('parent_id') ?: null,
        ]);

        $this->createNotificationsForComment($comment, $task);

        return redirect()->back()->with('message', 'Comment added successfully');
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
