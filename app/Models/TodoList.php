<?php

namespace App\Models;

use App\Models\Kpi\KpiTaskInstance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class TodoList extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'todo_due_time_id',
        'todo_status_id',
        'task',
        'due_date',
        'assigned_user_id',
        'created_by_user_id',
        'requested_by_department_id',
        'requested_by_branch_id',
        'kpi_task_instance_id',
        'closed_by_user_id',
        'closed_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function dueTime()
    {
        return $this->belongsTo(TodoDueTime::class, 'todo_due_time_id');
    }

    public function status()
    {
        return $this->belongsTo(TodoStatus::class, 'todo_status_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function requestedByBranch()
    {
        return $this->belongsTo(Branch::class, 'requested_by_branch_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'requested_by_department_id');
    }

    public function kpiTaskInstance()
    {
        return $this->belongsTo(KpiTaskInstance::class, 'kpi_task_instance_id');
    }

    public function kpiTaskInstances()
    {
        return $this->hasMany(KpiTaskInstance::class, 'todo_list_id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at');
    }

    public function topLevelComments()
    {
        return $this->comments()->whereNull('parent_id');
    }

    /**
     * Helper to sync KPI instance approval status back to the Todo Task.
     */
    public static function syncKpiApproval(KpiTaskInstance $instance): void
    {
        try {
            $task = static::where('kpi_task_instance_id', $instance->id)
                ->orWhere('id', $instance->todo_list_id)
                ->first();

            if (!$task) {
                return;
            }

            $completedStatus = TodoStatus::where('status', 'like', '%complete%')
                ->orWhere('status', 'like', '%success%')
                ->orWhere('status', 'like', '%done%')
                ->first() ?: TodoStatus::first();

            $task->update([
                'todo_status_id' => $completedStatus ? $completedStatus->id : $task->todo_status_id,
                'closed_by_user_id' => $task->created_by_user_id ?: $instance->user_id, // KPI Requester
                'closed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to sync KPI approval to Todo task: ' . $e->getMessage());
        }
    }

    /**
     * Get the location through the department relationship.
     */
    public function getLocationAttribute()
    {
        return $this->department?->location;
    }
}
