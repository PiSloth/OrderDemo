<?php

namespace App\Models\DigitalAsset;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareAccountPic extends Model
{
    use HasFactory;

    protected $table = 'software_account_pics';

    protected $fillable = [
        'software_account_id',
        'user_id',
        'status',
        'assigned_at',
        'assigned_by',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccount::class, 'software_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SoftwareAccountPicHistory::class, 'software_account_pic_id');
    }
}
