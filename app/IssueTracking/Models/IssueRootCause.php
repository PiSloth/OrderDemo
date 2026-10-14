<?php

namespace App\IssueTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssueRootCause extends Model
{
    protected $fillable = ['name', 'category'];

    public function logs(): HasMany
    {
        return $this->hasMany(IssueRootCauseLog::class, 'root_cause_id');
    }

    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_root_cause_logs', 'root_cause_id', 'issue_id')
            ->withPivot('id', 'remark')
            ->withTimestamps();
    }
}
