<?php

namespace App\Models\DigitalAsset;

use App\Models\EmailList;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareAccountEmail extends Model
{
    use HasFactory;

    protected $table = 'software_account_emails';

    protected $fillable = [
        'software_account_id',
        'email_id',
        'purpose',
        'is_primary',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccount::class, 'software_account_id');
    }

    public function emailRecord(): BelongsTo
    {
        return $this->belongsTo(EmailList::class, 'email_id');
    }
}
