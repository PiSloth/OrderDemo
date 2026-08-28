<?php

namespace App\Models\Training;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingTrigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'trigger_type', // NEW_USER, WORKFLOW_CHANGE, RETRAINING, MANUAL
        'source_type',
        'source_id',
        'source_version_id',
        'reason',
        'status',
        'created_by',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }
}
