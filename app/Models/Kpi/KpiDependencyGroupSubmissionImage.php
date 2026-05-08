<?php

namespace App\Models\Kpi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiDependencyGroupSubmissionImage extends Model
{
    protected $guarded = [];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(KpiDependencyGroupSubmission::class, 'dependency_group_submission_id');
    }
}
