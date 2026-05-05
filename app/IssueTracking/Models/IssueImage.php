<?php

namespace App\IssueTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueImage extends Model
{
    protected $fillable = ['issue_id', 'image_path'];
    public function issue(): BelongsTo { return $this->belongsTo(Issue::class); }
}
