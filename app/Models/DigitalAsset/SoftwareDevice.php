<?php

namespace App\Models\DigitalAsset;

use App\Models\OfficeAsset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareDevice extends Model
{
    use HasFactory;

    protected $table = 'software_devices';

    protected $fillable = [
        'fixed_asset_id',
        'asset_code_snapshot',
        'ownership_type',
        'manufacturer',
        'model_name',
        'model_number',
        'serial_number',
        'imei',
        'hostname',
        'operating_system',
        'operating_system_version',
        'device_status',
        'first_verified_at',
        'last_verified_at',
        'notes',
    ];

    protected $casts = [
        'first_verified_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(OfficeAsset::class, 'fixed_asset_id');
    }

    public function accountDevices(): HasMany
    {
        return $this->hasMany(SoftwareAccountDevice::class, 'software_device_id');
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(SoftwareAccount::class, 'software_account_devices', 'software_device_id', 'software_account_id')
            ->withPivot(['relationship_type', 'first_seen_at', 'last_seen_at', 'status', 'verified_by', 'verified_at'])
            ->withTimestamps();
    }
}
