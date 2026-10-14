<?php

namespace App\IssueTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueRootCauseLog extends Model
{
    protected $table = 'issue_root_cause_logs';

    protected $fillable = [
        'issue_id',
        'root_cause_id',
        'remark',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    public function rootCause(): BelongsTo
    {
        return $this->belongsTo(IssueRootCause::class, 'root_cause_id');
    }
}
