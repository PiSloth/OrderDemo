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

    protected static function booted()
    {
        static::updated(function ($todoList) {
            if ($todoList->isDirty('due_date')) {
                $newDueDate = $todoList->due_date;
                if ($newDueDate) {
                    \App\Models\Kpi\KpiTaskInstance::query()
                        ->where('todo_list_id', $todoList->id)
                        ->when($todoList->kpi_task_instance_id, function ($q) use ($todoList) {
                            $q->orWhere('id', $todoList->kpi_task_instance_id);
                        })
                        ->update([
                            'due_at' => $newDueDate,
                            'period_end' => $newDueDate->toDateString(),
                            'task_date' => $newDueDate->toDateString(),
                        ]);
                }
            }
        });
    }

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

    protected $appends = [
        'daily_dept_issue_no',
    ];

    public function getDailyDeptIssueNoAttribute(): int
    {
        if (isset($this->attributes['daily_dept_issue_no'])) {
            return (int) $this->attributes['daily_dept_issue_no'];
        }

        $deptId = $this->requested_by_department_id;
        if (!$deptId) {
            return 1;
        }

        $date = $this->created_at ? $this->created_at->toDateString() : now()->toDateString();

        $query = static::withTrashed()
            ->where('requested_by_department_id', $deptId)
            ->whereDate('created_at', $date);

        if ($this->id) {
            $query->where('id', '<=', $this->id);
        }

        $count = $query->count();
        return max(1, $count);
    }

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

    public function requestedByDepartment()
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

    public function references()
    {
        return $this->hasMany(TodoReference::class, 'todo_list_id');
    }

    /**
     * Check whether this Todo task is closed / completed.
     */
    public function isClosed(): bool
    {
        if ($this->closed_at !== null) {
            return true;
        }

        $statusName = strtolower((string) ($this->status?->status ?? ''));
        if ($statusName === '') {
            return false;
        }

        foreach (['complete', 'success', 'done', 'finish', 'close', 'fail'] as $keyword) {
            if (str_contains($statusName, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the given user is a resolver (assignee or in assignee's department).
     */
    public function isResolver(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperUser') && $user->isSuperUser()) {
            return true;
        }

        if ($this->assigned_user_id && (int) $this->assigned_user_id === (int) $user->id) {
            return true;
        }

        $assignedDeptId = $this->assignedUser?->department_id;
        if ($assignedDeptId && $user->department_id && (int) $user->department_id === (int) $assignedDeptId) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user is a requester (creator or in the same department as the creator).
     */
    public function isRequester(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperUser') && $user->isSuperUser()) {
            return true;
        }

        if ($this->created_by_user_id && (int) $this->created_by_user_id === (int) $user->id) {
            return true;
        }

        $requesterDeptId = $this->createdByUser?->department_id;
        if ($requesterDeptId && $user->department_id && (int) $user->department_id === (int) $requesterDeptId) {
            return true;
        }

        if (!$this->created_by_user_id && $this->requested_by_department_id && $user->department_id && (int) $user->department_id === (int) $this->requested_by_department_id) {
            return true;
        }

        return false;
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
                'closed_by_user_id' => $task->closed_by_user_id ?: ($task->created_by_user_id ?: $instance->user_id),
                'closed_at' => $task->closed_at ?: now(),
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
