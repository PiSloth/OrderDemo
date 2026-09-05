<?php

namespace App\Models\Training;

use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'training_category_id',
        'retrain_interval',
        'retrain_unit',
        'duration_days',
        'passing_score',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'retrain_interval' => 'float',
        'passing_score' => 'float',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class, 'training_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(TrainingScope::class);
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(TrainingTrigger::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function test(): HasOne
    {
        return $this->hasOne(Test::class)->where('status', 'active')->latestOfMany();
    }

    public function companyDocuments(): BelongsToMany
    {
        return $this->belongsToMany(CompanyDocument::class, 'training_company_documents');
    }
}
