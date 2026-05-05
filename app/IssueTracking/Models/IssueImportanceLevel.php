<?php

namespace App\IssueTracking\Models;

use Illuminate\Database\Eloquent\Model;

class IssueImportanceLevel extends Model
{
    protected $table = 'issue_importance_levels';
    protected $fillable = ['name', 'level'];
}
