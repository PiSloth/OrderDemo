<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovedOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function supplierProduct()
    {
        return $this->belongsTo(SupplierProduct::class);
    }
}
