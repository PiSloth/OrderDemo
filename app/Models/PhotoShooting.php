<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoShooting extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function psiOrder()
    {
        return $this->belongsTo(PsiOrder::class);
    }

    public function photoShootingStatus()
    {
        return $this->belongsTo(PhotoShootingStatus::class);
    }
}
