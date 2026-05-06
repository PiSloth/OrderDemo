<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchChecklistHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'check_list_id',
        'user_id',
        'branch_id',
        'department_id',
        'location_id',
        'remark',
        'is_done',
        'checked_at',
        'created_at',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'checked_at' => 'date',
        'created_at' => 'datetime',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(BranchChecklist::class, 'check_list_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
