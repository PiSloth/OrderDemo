<?php

namespace App\Models\Calendar;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEventGoogleCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'calendar_event_id',
        'user_id',
        'google_calendar_id',
        'google_event_id',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
