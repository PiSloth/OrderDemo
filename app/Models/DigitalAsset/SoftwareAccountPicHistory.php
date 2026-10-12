<?php

namespace App\Models\DigitalAsset;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareAccountPicHistory extends Model
{
    use HasFactory;

    protected $table = 'software_account_pic_history';

    protected $fillable = [
        'software_account_pic_id',
        'software_account_id',
        'user_id',
        'action',
        'performed_by',
        'reason',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function accountPic(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccountPic::class, 'software_account_pic_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccount::class, 'software_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
