<?php

namespace App\Models\Training;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_attempt_id',
        'test_question_id',
        'selected_option_id',
        'is_correct',
        'marks_obtained',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'marks_obtained' => 'float',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(TestOption::class, 'selected_option_id');
    }
}
