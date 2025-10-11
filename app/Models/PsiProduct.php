<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsiProduct extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function shape()
    {
        return $this->belongsTo(Shape::class);
    }

    public function productPhoto()
    {
        return $this->hasOne(ProductPhoto::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function design()
    {
        return $this->belongsTo(Design::class);
    }
    public function quality()
    {
        return $this->belongsTo(Quality::class);
    }

    public function psiPrice()
    {
        return $this->belongsTo(PsiPrice::class);
    }

    public function manufactureTechnique()
    {
        return $this->belongsTo(ManufactureTechnique::class);
    }

    public function branchProducts()
    {
        return $this->hasMany(BranchPsiProduct::class);
    }
}
