<?php

namespace App\IssueTracking\Models;

use Illuminate\Database\Eloquent\Model;

class IssuePriority extends Model
{
    protected $fillable = ['name', 'level'];
}
