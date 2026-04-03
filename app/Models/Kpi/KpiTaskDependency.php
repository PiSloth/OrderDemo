<?php

namespace App\Models\Kpi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTaskDependency extends Model
{
    protected $guarded = [];

    protected $casts = [
        'share_final_result' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function sourceAssignment(): BelongsTo
    {
        return $this->belongsTo(KpiTaskAssignment::class, 'source_task_assignment_id');
    }

    public function targetAssignment(): BelongsTo
    {
        return $this->belongsTo(KpiTaskAssignment::class, 'target_task_assignment_id');
    }
}
