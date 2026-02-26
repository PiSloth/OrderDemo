<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JewelryItem extends Model
{
    protected $table = 'jewelry_items';

    protected $fillable = [
        'group_number_id',
        'branch_id',
        'product_name',
        'quality',
        'barcode',
        'total_weight',
        'l_gram',
        'l_mmk',
        'kyauk_gram',
        'batch_id',
        'is_register',
        'register_by_id',
    ];

    protected $casts = [
        'branch_id' => 'int',
        'total_weight' => 'decimal:3',
        'l_gram' => 'decimal:3',
        'kyauk_gram' => 'decimal:3',
        'l_mmk' => 'int',
        'batch_id' => 'int',
        'is_register' => 'bool',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(GroupNumber::class, 'group_number_id');
    }

    public function registerBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'register_by_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
