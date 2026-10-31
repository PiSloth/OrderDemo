<?php

namespace App\Models\Traits;

use App\Models\TodoReference;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTodoReferences
{
    /**
     * All ToDo task references linked to this model.
     */
    public function todoReferences(): MorphMany
    {
        return $this->morphMany(TodoReference::class, 'reference');
    }

    /**
     * Pending ToDo references for this model (task not closed).
     */
    public function activeTodoReferences(): MorphMany
    {
        return $this->morphMany(TodoReference::class, 'reference')
            ->whereHas('todo', function ($q) {
                $q->whereNull('closed_at');
            });
    }

    /**
     * Completed ToDo references for this model (task closed).
     */
    public function completedTodoReferences(): MorphMany
    {
        return $this->morphMany(TodoReference::class, 'reference')
            ->whereHas('todo', function ($q) {
                $q->whereNotNull('closed_at');
            });
    }

    /**
     * Whether this model has any unresolved ToDo tasks.
     */
    public function hasPendingTodos(): bool
    {
        return $this->activeTodoReferences()->exists();
    }

    /**
     * Whether this model has any completed ToDo tasks.
     */
    public function hasCompletedTodos(): bool
    {
        return $this->completedTodoReferences()->exists();
    }
}
