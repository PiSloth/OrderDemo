<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiDependencyGroupSubmission extends Model
{
    protected $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
        'locked_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(KpiDependencyGroupRun::class, 'dependency_group_run_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(KpiDependencyGroupSubmissionImage::class, 'dependency_group_submission_id');
    }
}
