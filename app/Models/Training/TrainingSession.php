<?php

namespace App\Models\Training;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'parent_session_id',
        'trainer_id',
        'session_code',
        'title',
        'scheduled_at',
        'start_date',
        'end_date',
        'duration_days',
        'venue',
        'meeting_link',
        'schedule_slots',
        'status', // PENDING, OPEN, IN_PROGRESS, COMPLETED, CANCELLED
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'duration_days' => 'integer',
        'schedule_slots' => 'array',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'session_dates',
        'schedule_state',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function parentSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'parent_session_id');
    }

    public function remedialSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'parent_session_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TrainingSessionParticipant::class);
    }

    public function testAttempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    /**
     * Compute array of session dates based on schedule_slots or start_date and duration_days.
     */
    public function getSessionDatesAttribute(): array
    {
        if (is_array($this->schedule_slots) && !empty($this->schedule_slots)) {
            $dates = [];
            foreach ($this->schedule_slots as $slot) {
                if (!empty($slot['date'])) {
                    $dates[] = $slot['date'];
                }
            }
            if (!empty($dates)) {
                return array_values(array_unique($dates));
            }
        }

        $baseDate = $this->start_date ?? ($this->scheduled_at ? $this->scheduled_at->copy()->startOfDay() : now()->startOfDay());
        $days = max(1, (int) ($this->duration_days ?: 1));

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = $baseDate->copy()->addDays($i)->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * Compute schedule state: upcoming, ongoing, expired, completed, cancelled.
     */
    public function getScheduleStateAttribute(): string
    {
        if ($this->status === 'COMPLETED') {
            return 'completed';
        }
        if ($this->status === 'CANCELLED') {
            return 'cancelled';
        }

        $today = now()->startOfDay();
        $startDate = $this->start_date ? $this->start_date->copy()->startOfDay() : ($this->scheduled_at ? $this->scheduled_at->copy()->startOfDay() : null);
        $endDate = $this->end_date ? $this->end_date->copy()->endOfDay() : null;

        if (!$endDate && $startDate) {
            $days = max(1, (int) ($this->duration_days ?: 1));
            $endDate = $startDate->copy()->addDays($days - 1)->endOfDay();
        }

        if (!$startDate) {
            return 'upcoming';
        }

        if ($today->lt($startDate)) {
            return 'upcoming';
        }

        if ($today->gt($endDate)) {
            return 'expired';
        }

        return 'ongoing';
    }
}

