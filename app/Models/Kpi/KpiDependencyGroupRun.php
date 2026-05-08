<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KpiDependencyGroupRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'run_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_at' => 'datetime',
        'submitted_at' => 'datetime',
        'first_confirmed_at' => 'datetime',
        'fully_confirmed_at' => 'datetime',
        'locked_at' => 'datetime',
        'cutoff_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(KpiDependencyGroup::class, 'dependency_group_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(KpiDependencyGroupRunMember::class, 'dependency_group_run_id');
    }

    public function submission(): HasOne
    {
        return $this->hasOne(KpiDependencyGroupSubmission::class, 'dependency_group_run_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(KpiDependencyGroupApprovalStep::class, 'dependency_group_run_id');
    }
}
