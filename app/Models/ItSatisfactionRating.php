<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItSatisfactionRating extends Model
{
    use HasFactory;

    protected $table = 'it_satisfaction_ratings';

    protected $fillable = [
        'survey_id',
        'user_id',
        'user_name',
        'rating',
        'aspect_ratings',
        'feedback',
        'submitted_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'aspect_ratings' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(ItSatisfactionSurvey::class, 'survey_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
