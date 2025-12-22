<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'year',
        'month',
        'day',
        'target_gram',
        'target_pcs',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
