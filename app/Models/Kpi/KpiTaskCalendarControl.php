<?php

namespace App\Models\Kpi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTaskCalendarControl extends Model
{
    protected $guarded = [];

    protected $casts = [
        'daily_reminder_enabled' => 'boolean',
        'weekly_monthly_refresh_enabled' => 'boolean',
        'push_until_finalized' => 'boolean',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(KpiTaskAssignment::class, 'task_assignment_id');
    }
}
