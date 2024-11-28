<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsiOrder extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function psiPrice()
    {
        return $this->belongsTo(PsiPrice::class);
    }
    public function psiStatus()
    {
        return $this->belongsTo(PsiStatus::class);
    }

    public function branchPsiProduct()
    {
        return $this->belongsTo(BranchPsiProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
