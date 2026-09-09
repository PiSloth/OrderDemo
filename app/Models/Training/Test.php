<?php

namespace App\Models\Training;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'title',
        'description',
        'passing_score',
        'attempt_limit',
        'status', // active, draft, inactive
    ];

    protected $casts = [
        'passing_score' => 'float',
        'attempt_limit' => 'integer',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * All questions assigned to this test (via test_has_questions pivot)
     * Supports Document-owned, Catalog-owned, and Global questions.
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(TestQuestion::class, 'test_has_questions')
            ->withPivot(['marks', 'sort_order', 'is_mandatory'])
            ->withTimestamps()
            ->orderBy('test_has_questions.sort_order');
    }

    /**
     * Questions created specifically for this test
     */
    public function catalogQuestions(): HasMany
    {
        return $this->hasMany(TestQuestion::class, 'test_id')->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }
}
