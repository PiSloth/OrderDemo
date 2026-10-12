<?php

namespace App\Models\DigitalAsset;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareAccount extends Model
{
    use HasFactory;

    protected $table = 'software_accounts';

    protected $fillable = [
        'software_platform_id',
        'account_name',
        'username',
        'properties',
        'lifecycle_status',
        'security_status',
        'last_reviewed_at',
        'next_review_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'properties' => 'array',
        'last_reviewed_at' => 'datetime',
        'next_review_at' => 'datetime',
    ];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(SoftwarePlatform::class, 'software_platform_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(SoftwareAccountEmail::class, 'software_account_id');
    }

    public function pics(): HasMany
    {
        return $this->hasMany(SoftwareAccountPic::class, 'software_account_id');
    }

    public function activePics(): HasMany
    {
        return $this->hasMany(SoftwareAccountPic::class, 'software_account_id')->where('status', 'active');
    }

    public function picHistories(): HasMany
    {
        return $this->hasMany(SoftwareAccountPicHistory::class, 'software_account_id');
    }

    public function propertyHistories(): HasMany
    {
        return $this->hasMany(SoftwareAccountPropertyHistory::class, 'software_account_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(SoftwareAccountEvidence::class, 'software_account_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SoftwareAccountReview::class, 'software_account_id');
    }

    public function accountDevices(): HasMany
    {
        return $this->hasMany(SoftwareAccountDevice::class, 'software_account_id');
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(SoftwareDevice::class, 'software_account_devices', 'software_account_id', 'software_device_id')
            ->withPivot(['relationship_type', 'first_seen_at', 'last_seen_at', 'status', 'verified_by', 'verified_at'])
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(SoftwareAccountAuditLog::class, 'software_account_id');
    }

    public function authorizationGrants(): HasMany
    {
        return $this->hasMany(SoftwareAccountAuthorizationGrant::class, 'software_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
