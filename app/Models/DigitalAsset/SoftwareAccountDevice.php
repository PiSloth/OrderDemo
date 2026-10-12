<?php

namespace App\Models\DigitalAsset;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareAccountDevice extends Model
{
    use HasFactory;

    protected $table = 'software_account_devices';

    protected $fillable = [
        'software_account_id',
        'software_device_id',
        'relationship_type',
        'first_seen_at',
        'last_seen_at',
        'status',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccount::class, 'software_account_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(SoftwareDevice::class, 'software_device_id');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
