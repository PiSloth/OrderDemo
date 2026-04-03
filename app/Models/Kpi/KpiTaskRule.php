<?php

namespace App\Models\Kpi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTaskRule extends Model
{
    public const TYPE_PASS_PERCENTAGE = 'pass_percentage';
    public const TYPE_FAIL_COUNT = 'fail_count';
    public const TYPE_SPEND_COST_LTE = 'spend_cost_lte';

    protected $guarded = [];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTaskTemplate::class, 'task_template_id');
    }
}
