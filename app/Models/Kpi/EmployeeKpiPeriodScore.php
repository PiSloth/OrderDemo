<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeKpiPeriodScore extends Model
{
    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'completion_rate' => 'decimal:2',
        'on_time_rate' => 'decimal:2',
        'score_rate' => 'decimal:2',
        'total_spend_cost' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(KpiGroup::class, 'kpi_group_id');
    }
}
