<?php

namespace App\Models\Training;

use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'company_document_id',
        'training_id',
        'scope', // 'document', 'catalog', 'global'
        'document_section_reference',
        'question',
        'question_type', // MULTIPLE_CHOICE, TRUE_FALSE, MULTI_SELECT
        'marks',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'marks' => 'float',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::created(function (TestQuestion $question): void {
            if ($question->test_id) {
                \Illuminate\Support\Facades\DB::table('test_has_questions')->insertOrIgnore([
                    'test_id' => $question->test_id,
                    'test_question_id' => $question->id,
                    'marks' => $question->attributes['marks'] ?? 1.0,
                    'sort_order' => $question->sort_order ?? 0,
                    'is_mandatory' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function getMarksAttribute($value)
    {
        if (isset($this->pivot) && isset($this->pivot->marks) && $this->pivot->marks !== null) {
            return (float) $this->pivot->marks;
        }
        return (float) $value;
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function companyDocument(): BelongsTo
    {
        return $this->belongsTo(CompanyDocument::class, 'company_document_id');
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'training_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(Test::class, 'test_has_questions')
            ->withPivot(['marks', 'sort_order', 'is_mandatory'])
            ->withTimestamps();
    }

    public function options(): HasMany
    {
        return $this->hasMany(TestOption::class)->orderBy('sort_order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }
}
