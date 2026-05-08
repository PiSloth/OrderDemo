<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiDependencyGroupRunMember extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
        'acted_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(KpiDependencyGroupRun::class, 'dependency_group_run_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(KpiTaskAssignment::class, 'task_assignment_id');
    }

    public function taskInstance(): BelongsTo
    {
        return $this->belongsTo(KpiTaskInstance::class, 'task_instance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
