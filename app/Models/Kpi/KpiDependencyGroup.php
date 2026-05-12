<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiDependencyGroup extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTaskTemplate::class, 'task_template_id');
    }

    public function firstApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_approver_user_id');
    }

    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approver_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(KpiDependencyGroupMember::class, 'dependency_group_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(KpiDependencyGroupRun::class, 'dependency_group_id');
    }
}
