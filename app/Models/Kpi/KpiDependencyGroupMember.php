<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiDependencyGroupMember extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(KpiDependencyGroup::class, 'dependency_group_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(KpiTaskAssignment::class, 'task_assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
