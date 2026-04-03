<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KpiTaskInstance extends Model
{
    protected $guarded = [];

    protected $casts = [
        'task_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_at' => 'datetime',
        'submitted_at' => 'datetime',
        'finalized_at' => 'datetime',
        'is_on_time' => 'boolean',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(KpiTaskAssignment::class, 'task_assignment_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTaskTemplate::class, 'task_template_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(KpiGroup::class, 'kpi_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(KpiTaskSubmission::class, 'task_instance_id');
    }

    public function latestSubmission(): HasOne
    {
        return $this->hasOne(KpiTaskSubmission::class, 'task_instance_id')->latestOfMany('submitted_at');
    }
}
