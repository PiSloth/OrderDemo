<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeAssetTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_asset_id',
        'type',
        'quantity',
        'date',
        'remark',
        'image',
        'user_id',
    ];

    public function asset()
    {
        return $this->belongsTo(OfficeAsset::class, 'office_asset_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
