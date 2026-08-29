<?php

namespace App\Models\Training;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'user_id',
        'training_trigger_id',
        'assignment_type', // FULL_TRAINING, TEST_ONLY
        'due_date',
        'status', // PENDING, IN_PROGRESS, COMPLETED, OVERDUE, EXPIRED
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(TrainingTrigger::class, 'training_trigger_id');
    }

    public function sessionParticipants(): HasMany
    {
        return $this->hasMany(TrainingSessionParticipant::class);
    }

    public function testAttempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function latestAttempt()
    {
        return $this->hasOne(TestAttempt::class)->latestOfMany();
    }
}
