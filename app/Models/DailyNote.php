<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note',
        'is_number',
        'title_id',
        'department_id',
        'branch_id',
        'location_id',
        'date',
        'created_by',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'is_number' => 'boolean',
        'date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function title(): BelongsTo
    {
        return $this->belongsTo(NoteTitle::class, 'title_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(NoteMessage::class, 'note_id');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(DailyNoteAcknowledgement::class, 'note_id');
    }

    public function acknowledgedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'daily_note_acknowledgements', 'note_id', 'user_id')
            ->withPivot(['acknowledged_at'])
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('location_id', $user->location_id)
            ->where('department_id', $user->department_id);
    }

    public function scopeForDate(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }
}
