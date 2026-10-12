<?php

namespace App\Models\DigitalAsset;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareAccountAuditLog extends Model
{
    use HasFactory;

    protected $table = 'software_account_audit_logs';

    protected $fillable = [
        'software_account_id',
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'outcome',
        'reason',
        'request_id',
        'ip_address',
        'user_agent',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccount::class, 'software_account_id');
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
