<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_batch_id',
        'office_asset_item_id',
        'asset_category_id',
        'branch_id',
        'department_id',
        'name',
        'photo',
        'cost',
        'balance',
        'minimum_balance',
    ];

    public function item()
    {
        return $this->belongsTo(OfficeAssetItem::class, 'office_asset_item_id');
    }

    public function batch()
    {
        return $this->belongsTo(AssetBatch::class, 'asset_batch_id');
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function transactions()
    {
        return $this->hasMany(OfficeAssetTransaction::class);
    }
}
