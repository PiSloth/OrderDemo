<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTaskSubmission extends Model
{
    protected $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
        'is_late' => 'boolean',
        'first_approved_at' => 'datetime',
        'final_approved_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(KpiTaskInstance::class, 'task_instance_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(KpiTaskApprovalStep::class, 'task_submission_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(KpiTaskSubmissionImage::class, 'task_submission_id');
    }
}
