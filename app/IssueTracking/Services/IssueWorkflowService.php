<?php

namespace App\IssueTracking\Services;

use App\IssueTracking\Models\Issue;
use App\IssueTracking\Models\IssueActivityLog;
use App\IssueTracking\Models\IssueStatus;
use DomainException;

class IssueWorkflowService
{
    private const TRANSITIONS = [
        'OPEN' => ['ASSIGNED'],
        'ASSIGNED' => ['IN_PROGRESS'],
        'IN_PROGRESS' => ['PENDING', 'DONE'],
        'PENDING' => ['IN_PROGRESS'],
        'DONE' => ['CLOSED'],
        'CLOSED' => [],
    ];

    public function transition(Issue $issue, string $toCode, int $userId): Issue
    {
        $fromCode = $issue->status->code;
        if (!in_array($toCode, self::TRANSITIONS[$fromCode] ?? [], true)) {
            throw new DomainException("Invalid transition: {$fromCode} -> {$toCode}");
        }

        $status = IssueStatus::query()->where('code', $toCode)->firstOrFail();
        $issue->update([
            'issue_status_id' => $status->id,
            'closed_date' => $toCode === 'CLOSED' ? now() : $issue->closed_date,
        ]);
        $issue->statusHistories()->create(['issue_status_id' => $status->id, 'changed_by' => $userId]);

        IssueActivityLog::query()->create([
            'issue_id' => $issue->id,
            'action' => $toCode === 'CLOSED' ? 'closed' : 'status_changed',
            'description' => "Status changed from {$fromCode} to {$toCode}",
            'performed_by' => $userId,
        ]);

        return $issue->fresh(['status']);
    }
}
