<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'barcode_registered_at' => 'date',
        'quantity' => 'integer',
        'item_weight_g' => 'decimal:4',
        'stone_weight_g' => 'decimal:4',
        'gold_weight_g' => 'decimal:4',
        'decrease_weight_g' => 'decimal:4',
        'item_total_weight_g' => 'decimal:4',
        'gold_amount' => 'decimal:2',
        'stone_amount' => 'decimal:2',
        'labor_fee' => 'decimal:2',
        'polishing_fee' => 'decimal:2',
        'other_fee' => 'decimal:2',
        'profit_loss_amount' => 'decimal:2',
        'item_amount' => 'decimal:2',
        'item_total_amount' => 'decimal:2',
    ];

    public function sourceSystem(): BelongsTo
    {
        return $this->belongsTo(SourceSystem::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
