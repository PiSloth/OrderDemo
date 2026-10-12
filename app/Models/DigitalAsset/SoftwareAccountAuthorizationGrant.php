<?php

namespace App\Models\DigitalAsset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareAccountAuthorizationGrant extends Model
{
    use HasFactory;

    protected $table = 'software_account_authorization_grants';

    protected $fillable = [
        'software_account_id',
        'application_name',
        'client_id_snapshot',
        'granted_scopes',
        'grant_status',
        'granted_at',
        'revoked_at',
        'last_verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'granted_scopes' => 'array',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccount::class, 'software_account_id');
    }
}
