<?php

namespace App\Livewire\Todo;

use App\Models\TodoDueTime;
use App\Models\TodoStatus;
use App\Models\User;
use App\Models\Location;
use App\Models\Branch;
use App\Models\Department;
use App\Models\TodoList as TodoListModel;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.todo')]
class TodoList extends Component
{
    public $dueTimes;
    public $statuses;
    public $locations;
    public $branches;
    public $departments;
    public $todoLists;
    public $archivedTasks;
    public $activeTab = 'active';
    public $viewStyle = 'card'; // 'card' or 'table'
    public $viewMode = 'calendar'; // 'list' or 'calendar'
    public $selectedDate = null;
    public $selectedCategory = null;
    public $calendarTasks = [];
    public $selectedTaskId = null;
    public $showTaskCommentsModal = false;
    public $selectedMonth = null; // Format: 'Y-m' (e.g., '2025-12')
    public $monthsWithTasks = []; // List of months with unsuccessful tasks

    // Form properties
    public $selectedDueTimeId = '';
    public $selectedStatusId = '';
    public $task = '';
    public $dueDate = '';
    public $assignedUserId = '';
    public $requestedByBranchId = '';

    // Filter and UI properties
    public $isFormCollapsed = true;
    public $filterBranchId = '';
    public $filterDepartmentId = '';
    public $selectedStatusIds = [];
    public $sortBy = 'due_date'; // due_date, created_at, priority

    protected $listeners = ['dueTimeSelected' => 'calculateDueDate'];

    public function mount()
    {
        $this->loadData();
        $this->selectedMonth = now()->format('Y-m'); // Default to current month
        $this->loadMonthsWithTasks();
        if ($this->viewMode === 'calendar') {
            $this->loadCalendarData();
        }
        // Auto-set requested by branch to current user's branch
        if (Auth::check() && Auth::user()->branch_id) {
            $this->requestedByBranchId = Auth::user()->branch_id;
        }
    }

    public function loadData()
    {
        $this->dueTimes = TodoDueTime::with('category', 'priority')->get();
        $this->statuses = TodoStatus::all();
        $this->locations = Location::all();
        $this->branches = Branch::all();
        $this->departments = Department::all();

        // Build query for active tasks with filters
        $activeQuery = TodoListModel::with('dueTime.category', 'dueTime.priority', 'status', 'assignedUser', 'createdByUser', 'department', 'requestedByBranch', 'comments');

        // Apply filters
        if ($this->filterBranchId) {
            $activeQuery->where('requested_by_branch_id', $this->filterBranchId);
        }
        if ($this->filterDepartmentId) {
            $activeQuery->where('requested_by_department_id', $this->filterDepartmentId);
        }
        if (!empty($this->selectedStatusIds)) {
            $activeQuery->whereIn('todo_status_id', $this->selectedStatusIds);
        }

        // Apply sorting
        switch ($this->sortBy) {
            case 'due_date':
                $activeQuery->orderBy('due_date', 'asc');
                break;
            case 'created_at':
                $activeQuery->orderBy('created_at', 'desc');
                break;
            case 'priority':
                $activeQuery->join('todo_due_times', 'todo_lists.todo_due_time_id', '=', 'todo_due_times.id')
                    ->join('todo_priorities', 'todo_due_times.todo_priority_id', '=', 'todo_priorities.id')
                    ->orderBy('todo_priorities.rank', 'asc')
                    ->select('todo_lists.*');
                break;
            default:
                $activeQuery->orderBy('due_date', 'asc');
        }

        $this->todoLists = $activeQuery->get();

        // Archived tasks (not filtered)
        $this->archivedTasks = TodoListModel::onlyTrashed()->with('dueTime.category', 'dueTime.priority', 'status', 'assignedUser', 'createdByUser', 'department', 'requestedByBranch', 'comments')->get();
    }

    public function getFormattedDueTimesProperty()
    {
        return $this->dueTimes->map(function ($dueTime) {
            return [
                'id' => $dueTime->id,
                'name' => ($dueTime->category->name ?? 'N/A') . ' - ' . ($dueTime->priority->level ?? 'N/A') . ' (' . $dueTime->duration . 'h)',
            ];
        })->toArray();
    }

    public function getStatusOptionsProperty()
    {
        return $this->statuses->map(function ($status) {
            return [
                'id' => $status->id,
                'name' => $status->status,
            ];
        })->toArray();
    }

    public function updatedFilterBranchId()
    {
        $this->loadData();
        $this->loadMonthsWithTasks();
        if ($this->viewMode === 'calendar') {
            $this->loadCalendarData();
        }
    }

    public function updatedFilterDepartmentId()
    {
        $this->loadData();
        $this->loadMonthsWithTasks();
        if ($this->viewMode === 'calendar') {
            $this->loadCalendarData();
        }
    }


    public function updatedSelectedStatusIds()
    {
        $this->loadData();
        $this->loadMonthsWithTasks();
        if ($this->viewMode === 'calendar') {
            $this->loadCalendarData();
        }
    }

    public function updatedSortBy()
    {
        $this->loadData();
    }

    public function updatedSelectedMonth($newMonth)
    {
        // When month is updated via property binding, reload the calendar data
        if ($this->viewMode === 'calendar' && $newMonth) {
            $this->loadCalendarData();
        }
    }

    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'list' ? 'calendar' : 'list';
        if ($this->viewMode === 'calendar') {
            // Ensure we have a selected month for the calendar
            if (!$this->selectedMonth) {
                $this->selectedMonth = now()->format('Y-m');
            }
            $this->loadCalendarData();
        }
    }

    public function loadCalendarData()
    {
        // Get the start and end dates for the selected month
        $selectedDate = \Carbon\Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
        $startDate = $selectedDate->copy()->startOfMonth()->startOfDay();
        $endDate = $selectedDate->copy()->endOfMonth()->endOfDay();

        Log::info('loadCalendarData', [
            'selectedMonth' => $this->selectedMonth,
            'startDate' => $startDate->toDateTimeString(),
            'endDate' => $endDate->toDateTimeString()
        ]);

        $tasks = TodoListModel::with('dueTime.category', 'dueTime.priority', 'status', 'assignedUser', 'department')
            ->whereDate('due_date', '>=', $startDate->toDateString())
            ->whereDate('due_date', '<=', $endDate->toDateString())
            ->whereNull('deleted_at');

        // Apply filters
        if ($this->filterBranchId) {
            $tasks->where('requested_by_branch_id', $this->filterBranchId);
        }
        if ($this->filterDepartmentId) {
            $tasks->where('requested_by_department_id', $this->filterDepartmentId);
        }
        if (!empty($this->selectedStatusIds)) {
            $tasks->whereIn('todo_status_id', $this->selectedStatusIds);
        }

        $tasks = $tasks->get();

        Log::info('loadCalendarData results', [
            'taskCount' => $tasks->count(),
            'dueDates' => $tasks->pluck('due_date')->unique()->map(fn($d) => $d->format('Y-m-d'))->values(),
            'sampleDueDates' => $tasks->take(5)->pluck('due_date')->map(fn($d) => $d ? $d->toDateTimeString() : 'null')
        ]);

        // Group tasks by date and category
        $this->calendarTasks = [];
        foreach ($tasks as $task) {
            if (!$task->due_date) {
                continue;
            }
            $dateKey = $task->due_date->format('Y-m-d');
            $categoryId = (int)($task->dueTime?->category?->id ?? 0);
            $categoryName = $task->dueTime?->category?->name ?? 'Uncategorized';

            if (!isset($this->calendarTasks[$dateKey])) {
                $this->calendarTasks[$dateKey] = [];
            }

            if (!isset($this->calendarTasks[$dateKey][$categoryId])) {
                $this->calendarTasks[$dateKey][$categoryId] = [
                    'name' => $categoryName,
                    'count' => 0,
                    'tasks' => []
                ];
            }

            $this->calendarTasks[$dateKey][$categoryId]['count']++;
            $this->calendarTasks[$dateKey][$categoryId]['tasks'][] = $task;
        }

        Log::info('calendarTasks populated', [
            'dates' => array_keys($this->calendarTasks),
            'totalTasksInCalendar' => array_sum(array_map(fn($date) => array_sum(array_map(fn($cat) => $cat['count'], $date)), $this->calendarTasks))
        ]);
    }

    public function selectCategory($date, $categoryId)
    {
        // Ensure date is in string format 'Y-m-d' and categoryId is integer
        $date = (string)$date;
        $categoryId = (int)$categoryId;

        // Debug logging
        Log::info('selectCategory called', [
            'date' => $date,
            'categoryId' => $categoryId,
            'selectedMonth' => $this->selectedMonth,
            'calendarTasksKeys' => array_keys($this->calendarTasks),
            'dateExists' => isset($this->calendarTasks[$date]),
            'categoryExists' => isset($this->calendarTasks[$date][$categoryId]) ?? false
        ]);

        // If data doesn't exist, try refreshing calendar data for the selected month
        if (!isset($this->calendarTasks[$date])) {
            Log::warning('Category modal data not found, refreshing calendar data', [
                'date' => $date,
                'categoryId' => $categoryId,
                'selectedMonth' => $this->selectedMonth
            ]);
            // $this->loadCalendarData(); // Commented out to use existing data
        }

        $this->selectedDate = $date;
        $this->selectedCategory = $categoryId;
    }

    public function refreshCalendarData()
    {
        // Public method to manually refresh calendar data if needed
        Log::info('refreshCalendarData called manually');
        $this->loadCalendarData();
    }

    public function closeCategoryModal()
    {
        $this->selectedDate = null;
        $this->selectedCategory = null;
    }

    public function openTaskCommentsModal($taskId)
    {
        Log::info("TodoList: openTaskCommentsModal called with taskId: " . ($taskId ?? 'null'));
        $this->selectedTaskId = $taskId;
        Log::info("TodoList: selectedTaskId set to: " . ($this->selectedTaskId ?? 'null'));
        $this->showTaskCommentsModal = true;
        Log::info("TodoList: showTaskCommentsModal set to: " . ($this->showTaskCommentsModal ? 'true' : 'false'));

        // Dispatch event to load the task in TaskComments component
        $this->dispatch('loadTask', $taskId);
    }

    public function closeTaskCommentsModal()
    {
        $this->showTaskCommentsModal = false;
        $this->selectedTaskId = null;
    }

    public function toggleForm()
    {
        $this->isFormCollapsed = !$this->isFormCollapsed;
    }

    public function clearFilters()
    {
        $this->filterBranchId = '';
        $this->filterDepartmentId = '';
        $this->selectedStatusIds = [];
        $this->sortBy = 'due_date';
        $this->loadData();
        $this->loadMonthsWithTasks();
        if ($this->viewMode === 'calendar') {
            $this->loadCalendarData();
        }
    }

    public function calculateDueDate()
    {
        if ($this->selectedDueTimeId) {
            $dueTime = TodoDueTime::find($this->selectedDueTimeId);
            if ($dueTime) {
                $this->dueDate = now()->addHours($dueTime->duration)->format('Y-m-d\TH:i');
            }
        }
    }

    public function createTask()
    {
        $this->validate([
            'selectedDueTimeId' => 'required|exists:todo_due_times,id',
            'task' => 'required|string|max:255',
            'assignedUserId' => 'nullable|exists:users,id',
            'requestedByBranchId' => 'required|exists:branches,id',
        ]);

        // Auto-calculate due date if not set
        if (!$this->dueDate && $this->selectedDueTimeId) {
            $dueTime = TodoDueTime::find($this->selectedDueTimeId);
            if ($dueTime) {
                $this->dueDate = now()->addHours($dueTime->duration)->format('Y-m-d H:i:s');
            }
        }

        // Find or create "new" status
        $newStatus = TodoStatus::where('status', 'new')->first();
        if (!$newStatus) {
            $newStatus = TodoStatus::create([
                'status' => 'new',
                'description' => 'New task status',
                'color_code' => 'blue'
            ]);
        }

        TodoListModel::create([
            'todo_due_time_id' => $this->selectedDueTimeId,
            'todo_status_id' => $newStatus->id, // Use "new" status instead of null
            'task' => $this->task,
            'due_date' => $this->dueDate,
            'assigned_user_id' => $this->assignedUserId ?: null,
            'created_by_user_id' => Auth::id(),
            'requested_by_department_id' => Auth::user()->department_id,
            'requested_by_branch_id' => $this->requestedByBranchId,
        ]);

        $this->resetForm();
        $this->loadData();
        $this->loadMonthsWithTasks();
        $this->loadCalendarData();
        session()->flash('message', 'Todo Task Created Successfully');
    }

    public function closeTask($taskId)
    {
        $task = TodoListModel::find($taskId);
        if ($task) {
            $now = now();
            $dueDate = $task->due_date;

            // Find appropriate status based on completion time
            $status = null;
            if ($now->greaterThan($dueDate)) {
                // Late completion - find "fail" status
                $status = TodoStatus::where('status', 'like', '%fail%')->first();
                if (!$status) {
                    // If no "fail" status exists, create a default one or use the first available
                    $status = TodoStatus::first();
                }
            } else {
                // On time completion - find "success" or "completed" status
                $status = TodoStatus::where('status', 'like', '%success%')
                    ->orWhere('status', 'like', '%complete%')
                    ->orWhere('status', 'like', '%done%')
                    ->first();
                if (!$status) {
                    $status = TodoStatus::first();
                }
            }

            $task->update([
                'todo_status_id' => $status ? $status->id : null,
            ]);

            $this->loadData();
            $this->loadMonthsWithTasks();
            $this->loadCalendarData();
            session()->flash('message', 'Task closed successfully. Status: ' . ($status ? $status->status : 'Updated'));
        }
    }

    public function archiveTask($taskId)
    {
        $task = TodoListModel::find($taskId);
        if ($task && $task->todo_status_id) {
            // Only archive if task has a status (is closed)
            $task->delete();
            $this->loadData();
            $this->loadMonthsWithTasks();
            $this->loadCalendarData();
            session()->flash('message', 'Task archived successfully');
        }
    }

    public function restoreTask($taskId)
    {
        $task = TodoListModel::withTrashed()->find($taskId);
        if ($task) {
            $task->restore();
            $this->loadData();
            $this->loadMonthsWithTasks();
            $this->loadCalendarData();
            session()->flash('message', 'Task restored from archive successfully');
        }
    }

    private function resetForm()
    {
        $this->selectedDueTimeId = '';
        $this->task = '';
        $this->assignedUserId = '';
        $this->dueDate = ''; // Reset due date as well

        // Re-set requested by branch to current user's branch
        if (Auth::check() && Auth::user()->branch_id) {
            $this->requestedByBranchId = Auth::user()->branch_id;
        } else {
            $this->requestedByBranchId = '';
        }
    }

    public function loadMonthsWithTasks()
    {
        // Get all unsuccessful tasks (not deleted/archived) with current filters applied
        $tasks = TodoListModel::whereNull('deleted_at');

        // Apply the same filters as calendar data
        if ($this->filterBranchId) {
            $tasks->where('requested_by_branch_id', $this->filterBranchId);
        }
        if ($this->filterDepartmentId) {
            $tasks->where('requested_by_department_id', $this->filterDepartmentId);
        }
        if (!empty($this->selectedStatusIds)) {
            $tasks->whereIn('todo_status_id', $this->selectedStatusIds);
        }

        $unsuccessfulTasks = $tasks->get();

        // Group by month and count
        $monthCounts = [];
        foreach ($unsuccessfulTasks as $task) {
            if (!$task->due_date) {
                continue;
            }
            $monthKey = $task->due_date->format('Y-m');
            if (!isset($monthCounts[$monthKey])) {
                $monthCounts[$monthKey] = 0;
            }
            $monthCounts[$monthKey]++;
        }

        // Sort by month descending (most recent first)
        krsort($monthCounts);

        // Format for display
        $this->monthsWithTasks = [];
        foreach ($monthCounts as $monthKey => $count) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth();
            $this->monthsWithTasks[$monthKey] = [
                'label' => $date->format('F Y'),
                'count' => $count,
                'value' => $monthKey
            ];
        }
    }

    public function changeMonth($month)
    {
        $this->selectedMonth = $month;
        $this->loadCalendarData();
    }

    public function toggleViewStyle()
    {
        $this->viewStyle = $this->viewStyle === 'card' ? 'table' : 'card';
    }

    public function getStatusBadgeClasses($status)
    {
        if ($status && $status->color_code) {
            return 'bg-' . $status->color_code . '-100 text-' . $status->color_code . '-800';
        }

        // Default colors based on status name
        $statusColors = [
            'Successed' => 'bg-green-100 text-green-800',
            'Completed' => 'bg-green-100 text-green-800',
            'Done' => 'bg-green-100 text-green-800',
            'Finished' => 'bg-green-100 text-green-800',
            'Processing' => 'bg-blue-100 text-blue-800',
            'Pending' => 'bg-yellow-100 text-yellow-800',
            'Failed' => 'bg-red-100 text-red-800',
            'Cancelled' => 'bg-gray-100 text-gray-800',
            'Rejected' => 'bg-red-100 text-red-800',
        ];

        return $statusColors[$status->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function render()
    {
        return view('livewire.todo.todo-list');
    }
}
