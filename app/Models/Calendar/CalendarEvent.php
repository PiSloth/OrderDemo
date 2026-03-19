<?php

namespace App\Models\Calendar;

use App\Models\CalendarNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_user_id',
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'all_day',
        'reminder_minutes',
        'google_calendar_id',
        'google_event_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'all_day' => 'boolean',
        'reminder_minutes' => 'integer',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'calendar_event_attendees')
            ->withTimestamps();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(CalendarNotification::class);
    }

    public function googleCopies(): HasMany
    {
        return $this->hasMany(CalendarEventGoogleCopy::class);
    }
}
