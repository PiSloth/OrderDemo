<?php

namespace App\IssueTracking\Models;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    protected $fillable = [
        'title', 'description', 'issue_category_id', 'issue_priority_id', 'issue_importance_id', 'issue_by',
        'issue_at', 'created_by', 'proposed_solution', 'resolution_department_id', 'assigned_user_id', 'due_date',
        'issue_status_id', 'follow_up_date', 'follow_up_interval', 'closed_date',
    ];

    protected $casts = [
        'issue_at' => 'datetime',
        'due_date' => 'datetime',
        'follow_up_date' => 'datetime',
        'closed_date' => 'datetime',
    ];

    protected $appends = ['is_overdue', 'is_urgent'];

    public function category(): BelongsTo { return $this->belongsTo(IssueCategory::class, 'issue_category_id'); }
    public function priority(): BelongsTo { return $this->belongsTo(IssuePriority::class, 'issue_priority_id'); }
    public function importance(): BelongsTo { return $this->belongsTo(IssueImportanceLevel::class, 'issue_importance_id'); }
    public function status(): BelongsTo { return $this->belongsTo(IssueStatus::class, 'issue_status_id'); }
    public function resolutionDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'resolution_department_id'); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function messages(): HasMany { return $this->hasMany(IssueMessage::class); }
    public function images(): HasMany { return $this->hasMany(IssueImage::class); }
    public function statusHistories(): HasMany { return $this->hasMany(IssueStatusHistory::class); }
    public function activityLogs(): HasMany { return $this->hasMany(IssueActivityLog::class); }
    public function rootCauses(): BelongsToMany { return $this->belongsToMany(IssueRootCause::class, 'issue_root_cause_logs', 'issue_id', 'root_cause_id')->withTimestamps(); }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null && $this->closed_date === null && $this->due_date->isPast();
    }

    public function getIsUrgentAttribute(): bool
    {
        return ($this->priority?->level ?? 0) >= 3 && ($this->importance?->level ?? 0) >= 2;
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')->whereNull('closed_date')->where('due_date', '<', now());
    }

    public function scopeUrgent(Builder $query): Builder
    {
        return $query
            ->whereHas('priority', fn(Builder $q) => $q->where('level', '>=', 3))
            ->whereHas('importance', fn(Builder $q) => $q->where('level', '>=', 2));
    }

    public function scopeErp(Builder $query): Builder
    {
        return $query->whereHas('category', fn(Builder $q) => $q->where('is_erp', true));
    }
}

