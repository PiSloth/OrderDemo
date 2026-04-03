<?php

namespace App\Models\Kpi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTaskTemplateEvidenceField extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
        'select_options' => 'array',
        'unit_options' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTaskTemplate::class, 'task_template_id');
    }
}
