<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteboardDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'created_by',
        'decision',
        'appointment_at',
        'invited_person',
    ];

    protected $casts = [
        'appointment_at' => 'datetime',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(WhiteboardContent::class, 'content_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
