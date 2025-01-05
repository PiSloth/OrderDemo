<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchPsiProduct extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function psiProduct()
    {
        return $this->belongsTo(PsiProduct::class);
    }
    public function psiStock()
    {
        return $this->hasOne(PsiStock::class);
    }
}
