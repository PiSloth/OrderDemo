<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_list_id',
        'user_id',
        'comment',
        'comment_type',
        'parent_id',
        'action_data',
        'action_status',
    ];

    protected $casts = [
        'action_data' => 'array',
    ];

    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'parent_id');
    }

    public function isActionStep(): bool
    {
        return $this->comment_type === 'action_step';
    }

    public function isPendingAction(): bool
    {
        return $this->isActionStep() && $this->action_status === 'pending';
    }

    public function isAcceptedAction(): bool
    {
        return $this->isActionStep() && $this->action_status === 'accepted';
    }

    public function isRejectedAction(): bool
    {
        return $this->isActionStep() && $this->action_status === 'rejected';
    }

    public function isDueDateChangeRequest(): bool
    {
        return $this->isActionStep() &&
            isset($this->action_data['type']) &&
            $this->action_data['type'] === 'due_date_change';
    }

    public function isInNegotiation(): bool
    {
        return $this->isDueDateChangeRequest() &&
            isset($this->action_data['negotiation_status']) &&
            $this->action_data['negotiation_status'] === 'negotiating';
    }

    public function getCurrentProposedDate(): ?string
    {
        if (!$this->isDueDateChangeRequest()) {
            return null;
        }

        // Return the latest proposed date in the negotiation
        if (isset($this->action_data['proposed_dates'])) {
            $dates = $this->action_data['proposed_dates'];
            return end($dates)['date'] ?? null;
        }

        return $this->action_data['new_due_date'] ?? null;
    }

    public function getNegotiatorUserId(): ?int
    {
        if (!$this->isInNegotiation()) {
            return null;
        }

        if (isset($this->action_data['proposed_dates'])) {
            $dates = $this->action_data['proposed_dates'];
            $lastProposal = end($dates);
            return $lastProposal['proposed_by'] ?? null;
        }

        return null;
    }

    public function canUserRespond(int $userId): bool
    {
        if (!$this->isDueDateChangeRequest()) {
            return false;
        }

        $task = $this->todoList;

        // Task assignee or creator can respond to initial requests (if not the requester)
        $canApprove = $task->assigned_user_id === $userId || $task->created_by_user_id === $userId;

        // If assignee and creator are the same person (solo task), allow self-management
        if ($task->assigned_user_id === $task->created_by_user_id) {
            return $task->assigned_user_id === $userId;
        }

        if ($this->isInNegotiation()) {
            // In negotiation, users with approval rights can always respond
            // Original requester can respond if they're not the last proposer
            $lastProposer = $this->getNegotiatorUserId();
            return $canApprove || ($lastProposer !== $userId && $this->user_id === $userId);
        }

        // Prevent the requester from approving their own initial request
        if ($this->user_id === $userId) {
            return false;
        }

        // Task assignee or creator can respond to initial requests (if not the requester)
        return $canApprove;
    }

    public function addNegotiationProposal(int $userId, string $proposedDate, string $reason = null): void
    {
        $actionData = $this->action_data ?? [];

        if (!isset($actionData['proposed_dates'])) {
            $actionData['proposed_dates'] = [];
        }

        $actionData['proposed_dates'][] = [
            'date' => $proposedDate,
            'proposed_by' => $userId,
            'proposed_at' => now()->toISOString(),
            'reason' => $reason,
        ];

        $actionData['negotiation_status'] = 'negotiating';
        $actionData['current_proposed_date'] = $proposedDate;

        $this->update(['action_data' => $actionData]);
    }

    public function finalizeNegotiation(string $finalDate): void
    {
        $actionData = $this->action_data ?? [];
        $actionData['negotiation_status'] = 'finalized';
        $actionData['final_date'] = $finalDate;
        $actionData['finalized_at'] = now()->toISOString();

        $this->update(['action_data' => $actionData]);
    }
}
