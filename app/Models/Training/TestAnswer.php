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
        'selected_option_ids',
        'is_correct',
        'marks_obtained',
    ];

    protected $casts = [
        'selected_option_ids' => 'array',
        'is_correct' => 'boolean',
        'marks_obtained' => 'float',
    ];

    protected $appends = [
        'selected_options',
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

    public function getSelectedOptionsAttribute(): array
    {
        if (!empty($this->selected_option_ids) && is_array($this->selected_option_ids)) {
            return TestOption::whereIn('id', $this->selected_option_ids)
                ->orderBy('sort_order')
                ->get()
                ->toArray();
        }

        if ($this->selected_option_id) {
            $opt = $this->selectedOption;
            return $opt ? [$opt->toArray()] : [];
        }

        return [];
    }
}
