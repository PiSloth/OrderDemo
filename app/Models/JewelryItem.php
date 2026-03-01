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
        'item_category_id',
        'quality',
        'gold_weight',
        'barcode',
        'total_weight',
        'kyauk_weight',
        'goldsmith_deduction',
        'goldsmith_labor_fee',
        'stone_price',
        'profit_loss',
        'profit_labor_fee',
        'batch_id',
        'is_register',
        'register_by_id',
    ];

    protected $casts = [
        'branch_id' => 'int',
        'item_category_id' => 'int',
        'gold_weight' => 'decimal:3',
        'total_weight' => 'decimal:3',
        'kyauk_weight' => 'decimal:3',
        'goldsmith_deduction' => 'decimal:3',
        'goldsmith_labor_fee' => 'int',
        'stone_price' => 'int',
        'profit_loss' => 'decimal:2',
        'profit_labor_fee' => 'int',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
