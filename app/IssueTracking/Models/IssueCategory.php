<?php

namespace App\IssueTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssueCategory extends Model
{
    protected $fillable = ['name', 'is_erp'];
    protected $casts = ['is_erp' => 'boolean'];
    public function issues(): HasMany { return $this->hasMany(Issue::class, 'issue_category_id'); }
}
