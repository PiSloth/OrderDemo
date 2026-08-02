<?php

namespace App\Services\Todo;

use App\Models\TodoList;
use App\Models\TodoDueTime;
use App\Models\TodoStatus;
use App\Models\User;
use App\Models\TaskNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TodoTaskService
{
    /**
     * Create a new Todo task programmatically from any module or controller.
     *
     * @param array $data
     * @param User|null $creator
     * @return TodoList
     */
    public function createTask(array $data, ?User $creator = null): TodoList
    {
        $creator = $creator ?: Auth::user();
        $creatorId = $creator ? $creator->id : null;
        $departmentId = $data['requested_by_department_id'] ?? ($creator ? $creator->department_id : null);

        $dueTimeId = $data['selected_due_time_id'] ?? $data['todo_due_time_id'] ?? null;
        $dueDate = $data['due_date'] ?? null;

        if (!$dueDate && $dueTimeId) {
            $dueTime = TodoDueTime::find($dueTimeId);
            if ($dueTime) {
                $dueDate = now()->addHours($dueTime->duration)->format('Y-m-d H:i:s');
            }
        }

        $newStatus = TodoStatus::where('status', 'new')->first();
        if (!$newStatus) {
            $newStatus = TodoStatus::create([
                'status' => 'new',
                'description' => 'New task status',
                'color_code' => 'blue',
            ]);
        }

        $task = TodoList::create([
            'todo_due_time_id' => $dueTimeId,
            'todo_status_id' => $newStatus->id,
            'task' => $data['task'],
            'due_date' => $dueDate,
            'assigned_user_id' => !empty($data['assigned_user_id']) ? $data['assigned_user_id'] : null,
            'created_by_user_id' => $creatorId,
            'requested_by_department_id' => $departmentId,
            'requested_by_branch_id' => $data['requested_by_branch_id'],
        ]);

        // Send notification to assignee if assigned to another user
        if ($task->assigned_user_id && $task->assigned_user_id !== $creatorId) {
            try {
                TaskNotification::create([
                    'type' => 'assigned',
                    'title' => "New Task Assigned: #{$task->id}",
                    'message' => ($creator ? $creator->name : 'System') . " assigned you a new task: " . \Illuminate\Support\Str::limit($task->task, 60),
                    'user_id' => $task->assigned_user_id,
                    'todo_list_id' => $task->id,
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send task assignment notification', ['error' => $e->getMessage()]);
            }
        }

        return $task;
    }
}
