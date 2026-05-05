<?php

namespace App\IssueTracking\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueMessage extends Model
{
    protected $fillable = ['issue_id', 'message', 'is_discussion', 'is_log_note', 'created_by'];
    protected $casts = ['is_discussion' => 'boolean', 'is_log_note' => 'boolean'];
    public function issue(): BelongsTo { return $this->belongsTo(Issue::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
