<?php

namespace App\IssueTracking\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueStatusHistory extends Model
{
    protected $fillable = ['issue_id', 'issue_status_id', 'changed_by'];
    public function issue(): BelongsTo { return $this->belongsTo(Issue::class); }
    public function status(): BelongsTo { return $this->belongsTo(IssueStatus::class, 'issue_status_id'); }
    public function changer(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
