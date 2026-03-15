<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'google_user_id',
        'email',
        'token_json',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
