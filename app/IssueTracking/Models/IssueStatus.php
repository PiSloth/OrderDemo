<?php

namespace App\IssueTracking\Models;

use Illuminate\Database\Eloquent\Model;

class IssueStatus extends Model
{
    protected $fillable = ['name', 'code'];
}
