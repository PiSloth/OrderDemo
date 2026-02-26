<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeAssetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_category_id',
        'name',
        'photo',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function locations()
    {
        return $this->hasMany(OfficeAsset::class, 'office_asset_item_id');
    }
}
