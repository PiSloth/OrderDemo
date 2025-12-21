<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    protected $casts = [
        'due_date' => 'datetime',
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

    public function requestedByBranch()
    {
        return $this->belongsTo(Branch::class, 'requested_by_branch_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'requested_by_department_id');
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
     * Get the location through the department relationship.
     */
    public function getLocationAttribute()
    {
        return $this->department?->location;
    }
}
