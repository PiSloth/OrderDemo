<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function quality()
    {
        return $this->belongsTo(Quality::class);
    }
    public function design()
    {
        return $this->belongsTo(Design::class);
    }
}
