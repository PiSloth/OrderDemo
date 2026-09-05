<?php

namespace App\Models\Training;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingSessionParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_session_id',
        'training_assignment_id',
        'user_id',
        'attendance_status', // REGISTERED, ATTENDED, ABSENT, EXCUSED
        'daily_attendance',
        'attended_at',
        'notes',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
        'daily_attendance' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TrainingAssignment::class, 'training_assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
