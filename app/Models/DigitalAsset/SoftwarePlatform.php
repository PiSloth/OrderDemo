<?php

namespace App\Models\DigitalAsset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwarePlatform extends Model
{
    use HasFactory;

    protected $table = 'software_platforms';

    protected $fillable = [
        'code',
        'name',
        'category',
        'status',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(SoftwareAccount::class, 'software_platform_id');
    }

    public function propertyDefinitions(): HasMany
    {
        return $this->hasMany(SoftwarePropertyDefinition::class, 'software_platform_id');
    }
}
