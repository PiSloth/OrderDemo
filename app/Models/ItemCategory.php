<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    protected $table = 'item_categories';

    protected $fillable = [
        'name',
    ];

    public function jewelryItems(): HasMany
    {
        return $this->hasMany(JewelryItem::class, 'item_category_id');
    }
}
