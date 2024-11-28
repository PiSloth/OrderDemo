<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReorderPoint extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function psiStockStatus()
    {
        return $this->belongsTo(PsiStockStatus::class);
    }
}
