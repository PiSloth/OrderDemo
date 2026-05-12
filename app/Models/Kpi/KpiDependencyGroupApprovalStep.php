<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiDependencyGroupApprovalStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(KpiDependencyGroupRun::class, 'dependency_group_run_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
