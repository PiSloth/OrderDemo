<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TodoReference extends Model
{
    use HasFactory;

    protected $table = 'todo_references';

    protected $fillable = [
        'todo_list_id',
        'reference_type',
        'reference_id',
        'module',
        'reason',
        'action_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * The linked ToDo task.
     */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(TodoList::class, 'todo_list_id');
    }

    /**
     * The originating reference model (SoftwareAccountReview, SoftwareAccount, Order, KPI, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Status delegator: retrieves status model from the linked todo task.
     */
    public function getStatusAttribute(): ?TodoStatus
    {
        return $this->todo?->status;
    }

    /**
     * Status text delegator.
     */
    public function getStatusNameAttribute(): string
    {
        return $this->todo?->status?->status ?? ($this->is_resolved ? 'Completed' : 'Pending');
    }

    /**
     * Resolved time delegator: comes directly from todo task closed_at.
     */
    public function getResolvedAtAttribute()
    {
        return $this->todo?->closed_at;
    }

    /**
     * Closed by user delegator: comes directly from todo task closedByUser.
     */
    public function getClosedByUserAttribute(): ?User
    {
        return $this->todo?->closedByUser;
    }

    /**
     * Created by user delegator: comes directly from todo task createdByUser.
     */
    public function getCreatedByUserAttribute(): ?User
    {
        return $this->todo?->createdByUser;
    }

    /**
     * Whether the linked todo task is completed/closed.
     */
    public function getIsResolvedAttribute(): bool
    {
        return $this->todo?->closed_at !== null;
    }

    /**
     * Scope for pending action items (linked task not closed).
     */
    public function scopePending($query)
    {
        return $query->whereHas('todo', function ($q) {
            $q->whereNull('closed_at');
        });
    }

    /**
     * Scope for completed action items (linked task closed).
     */
    public function scopeCompleted($query)
    {
        return $query->whereHas('todo', function ($q) {
            $q->whereNotNull('closed_at');
        });
    }

    /**
     * Scope by module name.
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
