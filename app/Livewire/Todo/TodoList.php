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
use Livewire\Attributes\Layout;

#[Layout('components.layouts.todo')]
class TodoList extends Component
{
    public $dueTimes;
    public $statuses;
    public $users;
    public $locations;
    public $branches;
    public $departments;
    public $todoLists;
    public $archivedTasks;
    public $activeTab = 'active';
    public $viewStyle = 'card'; // 'card' or 'table'

    // Form properties
    public $selectedDueTimeId = '';
    public $selectedStatusId = '';
    public $task = '';
    public $dueDate = '';
    public $assignedUserId = '';
    public $locationId = '';
    public $departmentId = '';
    public $requestedByBranchId = '';

    // Filter and UI properties
    public $isFormCollapsed = true;
    public $filterBranchId = '';
    public $filterDepartmentId = '';
    public $filterStatusId = '';
    public $sortBy = 'due_date'; // due_date, created_at, priority
    public $showDailyTasks = false;

    protected $listeners = ['dueTimeSelected' => 'calculateDueDate'];

    public function mount()
    {
        $this->loadData();
        // Auto-set requested by branch to current user's branch
        if (Auth::check() && Auth::user()->branch_id) {
            $this->requestedByBranchId = Auth::user()->branch_id;
        }
    }

    public function loadData()
    {
        $this->dueTimes = TodoDueTime::with('category', 'priority')->get();
        $this->statuses = TodoStatus::all();
        $this->users = User::all();
        $this->locations = Location::all();
        $this->branches = Branch::all();
        $this->departments = Department::with('location')->get();

        // Build query for active tasks with filters
        $activeQuery = TodoListModel::with('dueTime.category', 'dueTime.priority', 'status', 'assignedUser', 'createdByUser', 'location', 'department', 'requestedByBranch', 'comments');

        // Apply filters
        if ($this->filterBranchId) {
            $activeQuery->where('requested_by_branch_id', $this->filterBranchId);
        }
        if ($this->filterDepartmentId) {
            $activeQuery->where('department_id', $this->filterDepartmentId);
        }
        if ($this->filterStatusId) {
            $activeQuery->where('todo_status_id', $this->filterStatusId);
        }

        // Apply daily tasks filter
        if ($this->showDailyTasks) {
            $activeQuery->whereDate('due_date', today());
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
        $this->archivedTasks = TodoListModel::onlyTrashed()->with('dueTime.category', 'dueTime.priority', 'status', 'assignedUser', 'createdByUser', 'location', 'department', 'requestedByBranch', 'comments')->get();
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

    public function updatedFilterBranchId()
    {
        $this->loadData();
    }

    public function updatedFilterDepartmentId()
    {
        $this->loadData();
    }

    public function updatedFilterStatusId()
    {
        $this->loadData();
    }

    public function updatedSortBy()
    {
        $this->loadData();
    }

    public function updatedShowDailyTasks()
    {
        $this->loadData();
    }

    public function toggleForm()
    {
        $this->isFormCollapsed = !$this->isFormCollapsed;
    }

    public function clearFilters()
    {
        $this->filterBranchId = '';
        $this->filterDepartmentId = '';
        $this->filterStatusId = '';
        $this->showDailyTasks = false;
        $this->sortBy = 'due_date';
        $this->loadData();
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
            'locationId' => 'required|exists:locations,id',
            'departmentId' => 'required|exists:departments,id',
            'requestedByBranchId' => 'required|exists:branches,id',
        ]);

        // Auto-calculate due date if not set
        if (!$this->dueDate && $this->selectedDueTimeId) {
            $dueTime = TodoDueTime::find($this->selectedDueTimeId);
            if ($dueTime) {
                $this->dueDate = now()->addHours($dueTime->duration)->format('Y-m-d H:i:s');
            }
        }

        TodoListModel::create([
            'todo_due_time_id' => $this->selectedDueTimeId,
            'todo_status_id' => null, // Always null initially
            'task' => $this->task,
            'due_date' => $this->dueDate,
            'assigned_user_id' => $this->assignedUserId ?: null,
            'created_by_user_id' => Auth::id(),
            'location_id' => $this->locationId,
            'department_id' => $this->departmentId,
            'requested_by_branch_id' => $this->requestedByBranchId,
        ]);

        $this->resetForm();
        $this->loadData();
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
            session()->flash('message', 'Task archived successfully');
        }
    }

    public function restoreTask($taskId)
    {
        $task = TodoListModel::withTrashed()->find($taskId);
        if ($task) {
            $task->restore();
            $this->loadData();
            session()->flash('message', 'Task restored from archive successfully');
        }
    }

    private function resetForm()
    {
        $this->selectedDueTimeId = '';
        $this->task = '';
        $this->assignedUserId = '';
        $this->locationId = '';
        $this->departmentId = '';
        $this->dueDate = ''; // Reset due date as well

        // Re-set requested by branch to current user's branch
        if (Auth::check() && Auth::user()->branch_id) {
            $this->requestedByBranchId = Auth::user()->branch_id;
        } else {
            $this->requestedByBranchId = '';
        }
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
