<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department_id',
        'minimum_cost',
        'maximum_cost',
        'total_cost',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assets()
    {
        return $this->hasMany(OfficeAsset::class);
    }
}
