<?php

namespace App\Models\Kpi;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiGroup extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'target_percentage' => 'decimal:2',
        'max_cost_amount' => 'decimal:2',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function taskTemplates(): HasMany
    {
        return $this->hasMany(KpiTaskTemplate::class);
    }

    public function periodScores(): HasMany
    {
        return $this->hasMany(EmployeeKpiPeriodScore::class);
    }
}
