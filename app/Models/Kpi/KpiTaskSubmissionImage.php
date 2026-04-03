<?php

namespace App\Models\Kpi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTaskSubmissionImage extends Model
{
    protected $guarded = [];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(KpiTaskSubmission::class, 'task_submission_id');
    }
}
