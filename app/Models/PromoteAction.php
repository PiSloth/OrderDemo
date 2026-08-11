<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoteAction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_at' => 'date',
        'end_at' => 'date',
        'reference' => 'array',
    ];

    /**
     * Get the branch target of this promote action (null for all branches).
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'target_branch_id');
    }

    /**
     * Get the department that created this promote action.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'action_by');
    }
}
