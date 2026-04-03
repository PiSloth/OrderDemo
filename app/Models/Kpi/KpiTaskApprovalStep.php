<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTaskApprovalStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(KpiTaskSubmission::class, 'task_submission_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
