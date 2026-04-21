<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KpiTaskAssignment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
        'calendar_push_enabled' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTaskTemplate::class, 'task_template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roleAssignment(): BelongsTo
    {
        return $this->belongsTo(KpiRoleAssignment::class, 'role_assignment_id');
    }

    public function firstApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_approver_user_id');
    }

    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approver_user_id');
    }

    public function calendarControl(): HasOne
    {
        return $this->hasOne(KpiTaskCalendarControl::class, 'task_assignment_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(KpiTaskInstance::class, 'task_assignment_id');
    }

    public function exclusionRequests(): HasMany
    {
        return $this->hasMany(KpiExclusionRequest::class, 'task_assignment_id');
    }

    public function frequency(): string
    {
        return $this->template->frequency;
    }
}
