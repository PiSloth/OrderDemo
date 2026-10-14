<?php

namespace App\IssueTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class IssueRootCause extends Model
{
    protected $fillable = ['name', 'category'];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            if (!Schema::hasColumn($model->getTable(), 'category')) {
                unset($model->attributes['category']);
            }
        });
    }

    public function getCategoryAttribute($value)
    {
        return $value ?: 'General';
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IssueRootCauseLog::class, 'root_cause_id');
    }

    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_root_cause_logs', 'root_cause_id', 'issue_id')
            ->withTimestamps();
    }
}
