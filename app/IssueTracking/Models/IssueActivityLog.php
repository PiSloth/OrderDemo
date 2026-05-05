<?php

namespace App\IssueTracking\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueActivityLog extends Model
{
    protected $fillable = ['issue_id', 'action', 'description', 'performed_by'];
    public function issue(): BelongsTo { return $this->belongsTo(Issue::class); }
    public function performer(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
}
