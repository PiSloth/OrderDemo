<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sale_at' => 'datetime',
        'gold_price' => 'decimal:2',
        'invoice_total' => 'decimal:2',
        'invoice_total_weight_g' => 'decimal:4',
    ];

    public function sourceSystem(): BelongsTo
    {
        return $this->belongsTo(SourceSystem::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function dutyStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'duty_staff_id');
    }

    public function inputStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'input_staff_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
