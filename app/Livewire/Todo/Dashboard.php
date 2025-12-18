<?php

namespace App\Livewire\Todo;

use App\Models\TodoList;
use App\Models\TodoStatus;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.todo')]
class Dashboard extends Component
{
    public $unfinishedTasks = [];
    public $finishedTasks = [];
    public $passedTasks = [];
    public $failedTasks = [];
    public $needToDoTasks = [];

    public $stats = [
        'unfinished' => 0,
        'finished' => 0,
        'passed' => 0,
        'failed' => 0,
        'need_to_do' => 0,
    ];

    public $showTaskModal = false;
    public $selectedTask = null;

    public function mount()
    {
        // Show daily tasks by default when coming from dashboard
        $this->showDailyTasks = true;
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $user = Auth::user();

        // Get all tasks (considering user's branch if applicable)
        $query = TodoList::with('status', 'assignedUser', 'createdByUser', 'dueTime');

        // If user has a branch, filter tasks by branch or assigned to user
        if ($user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->where('requested_by_branch_id', $user->branch_id)
                    ->orWhere('assigned_user_id', $user->id);
            });
        }

        $tasks = $query->get();

        // Get status names for categorization (assuming these statuses exist)
        $finishedStatusIds = TodoStatus::whereIn('status', ['Completed', 'Done', 'Finished', 'Successed'])->pluck('id')->toArray();
        $failedStatusIds = TodoStatus::whereIn('status', ['Cancelled', 'Failed', 'Rejected'])->pluck('id')->toArray();

        $this->unfinishedTasks = $tasks->filter(function ($task) use ($finishedStatusIds, $failedStatusIds) {
            return !in_array($task->todo_status_id, array_merge($finishedStatusIds, $failedStatusIds));
        });

        $this->finishedTasks = $tasks->filter(function ($task) use ($finishedStatusIds) {
            return in_array($task->todo_status_id, $finishedStatusIds);
        });

        $this->failedTasks = $tasks->filter(function ($task) use ($failedStatusIds) {
            return in_array($task->todo_status_id, $failedStatusIds);
        });

        $this->passedTasks = $tasks->filter(function ($task) use ($finishedStatusIds, $failedStatusIds) {
            return !in_array($task->todo_status_id, array_merge($finishedStatusIds, $failedStatusIds))
                && $task->due_date < now();
        });

        $this->needToDoTasks = $tasks->filter(function ($task) use ($finishedStatusIds, $failedStatusIds) {
            return !in_array($task->todo_status_id, array_merge($finishedStatusIds, $failedStatusIds))
                && $task->due_date >= now()
                && $task->due_date <= now()->addDays(7); // Next 7 days
        });

        // Update stats
        $this->stats = [
            'unfinished' => $this->unfinishedTasks->count(),
            'finished' => $this->finishedTasks->count(),
            'passed' => $this->passedTasks->count(),
            'failed' => $this->failedTasks->count(),
            'need_to_do' => $this->needToDoTasks->count(),
        ];
    }

    public function showTaskDetail($taskId)
    {
        $this->selectedTask = TodoList::with('status', 'assignedUser', 'createdByUser', 'dueTime.category', 'dueTime.priority', 'requestedByBranch', 'department')
            ->find($taskId);
        $this->showTaskModal = true;
    }

    public function closeTaskModal()
    {
        $this->showTaskModal = false;
        $this->selectedTask = null;
    }

    public function render()
    {
        return view('livewire.todo.dashboard');
    }
}
