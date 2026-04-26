<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoteMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'user_id',
        'message',
        'image_path',
    ];

    public function dailyNote(): BelongsTo
    {
        return $this->belongsTo(DailyNote::class, 'note_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(NoteMessageRead::class, 'note_message_id');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'note_message_reads', 'note_message_id', 'user_id')
            ->withPivot(['read_at'])
            ->withTimestamps();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . ltrim($this->image_path, '/')) : null;
    }
}
