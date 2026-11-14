<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dutySales(): HasMany
    {
        return $this->hasMany(Sale::class, 'duty_staff_id');
    }

    public function inputSales(): HasMany
    {
        return $this->hasMany(Sale::class, 'input_staff_id');
    }
}
