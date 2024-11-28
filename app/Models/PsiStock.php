<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsiStock extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function reorderPoint()
    {
        return $this->hasOne(ReorderPoint::class);
    }

    public function branchPsiProduct()
    {
        return $this->belongsTo(BranchPsiProduct::class);
    }
}
