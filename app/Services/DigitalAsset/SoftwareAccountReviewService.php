<?php

namespace App\Services\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountEvidence;
use App\Models\DigitalAsset\SoftwareAccountReview;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SoftwareAccountReviewService
{
    /**
     * Schedule a new review for an account.
     */
    public function scheduleReview(
        SoftwareAccount $account,
        string $reviewType,
        string $scheduledAt,
        ?int $assignedToUserId = null,
        ?string $notes = null,
        int $actorUserId = 0
    ): SoftwareAccountReview {
        if (!in_array($reviewType, ['scheduled', 'ad_hoc', 'incident'], true)) {
            throw new InvalidArgumentException("Invalid review type: '{$reviewType}'.");
        }

        return DB::transaction(function () use ($account, $reviewType, $scheduledAt, $assignedToUserId, $notes, $actorUserId) {
            $review = SoftwareAccountReview::create([
                'software_account_id' => $account->id,
                'review_type' => $reviewType,
                'status' => 'scheduled',
                'scheduled_at' => $scheduledAt,
                'assigned_to' => $assignedToUserId,
                'notes' => $notes,
            ]);

            SoftwareAccountAuditService::log(
                action: 'review.scheduled',
                entityType: 'SoftwareAccountReview',
                entityId: $review->id,
                softwareAccountId: $account->id,
                outcome: 'success',
                reason: $notes,
                metadata: ['review_type' => $reviewType],
                actorUserId: $actorUserId ?: auth()->id()
            );

            return $review;
        });
    }

    /**
     * Start a scheduled review.
     */
    public function startReview(SoftwareAccountReview $review, int $actorUserId): SoftwareAccountReview
    {
        if ($review->status !== 'scheduled') {
            throw new InvalidArgumentException("Only scheduled reviews can be started.");
        }

        $review->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        SoftwareAccountAuditService::log(
            action: 'review.started',
            entityType: 'SoftwareAccountReview',
            entityId: $review->id,
            softwareAccountId: $review->software_account_id,
            outcome: 'success',
            actorUserId: $actorUserId
        );

        return $review;
    }

    /**
     * Complete a review and update account security status atomically.
     */
    public function completeReview(
        SoftwareAccountReview $review,
        string $result,
        int $completedByUserId,
        ?array $findings = null,
        ?string $notes = null,
        int $nextReviewDays = 90
    ): SoftwareAccountReview {
        if (!in_array($review->status, ['in_progress', 'scheduled'], true)) {
            throw new InvalidArgumentException("Cannot complete a review that is cancelled or already completed.");
        }

        if (!in_array($result, ['compliant', 'needs_action', 'at_risk'], true)) {
            throw new InvalidArgumentException("Invalid review result: '{$result}'.");
        }

        $account = $review->account;
        $platformId = $account->software_platform_id;

        // Check required property definitions when marking as compliant
        if ($result === 'compliant') {
            $requiredDefs = SoftwarePropertyDefinition::where('is_active', true)
                ->where('is_required', true)
                ->where(function ($query) use ($platformId) {
                    $query->whereNull('software_platform_id')
                        ->orWhere('software_platform_id', $platformId);
                })
                ->get();

            $accountProps = $account->properties ?? [];
            foreach ($requiredDefs as $def) {
                if (!isset($accountProps[$def->code]) || $accountProps[$def->code] === null || $accountProps[$def->code] === '') {
                    throw new InvalidArgumentException("Cannot complete review as 'compliant': Required property '{$def->label}' is missing.");
                }
            }
        }

        // Check required evidence definitions
        $evidenceDefs = SoftwarePropertyDefinition::where('is_active', true)
            ->where('evidence_required', true)
            ->where(function ($query) use ($platformId) {
                $query->whereNull('software_platform_id')
                    ->orWhere('software_platform_id', $platformId);
            })
            ->get();

        foreach ($evidenceDefs as $def) {
            $hasAcceptedEvidence = SoftwareAccountEvidence::where('software_account_id', $account->id)
                ->where('property_definition_id', $def->id)
                ->where('status', 'accepted')
                ->exists();

            if (!$hasAcceptedEvidence && $result === 'compliant') {
                throw new InvalidArgumentException("Cannot complete review as 'compliant': Required verified evidence for '{$def->label}' is missing.");
            }
        }

        return DB::transaction(function () use ($review, $account, $result, $completedByUserId, $findings, $notes, $nextReviewDays) {
            $now = now();
            $review->update([
                'status' => 'completed',
                'completed_at' => $now,
                'completed_by' => $completedByUserId,
                'result' => $result,
                'findings' => $findings,
                'notes' => $notes,
            ]);

            // Synchronize account's security summary fields
            $account->last_reviewed_at = $now;
            $account->security_status = $result;
            $account->next_review_at = $now->copy()->addDays($nextReviewDays);
            $account->updated_by = $completedByUserId;
            $account->save();

            SoftwareAccountAuditService::log(
                action: 'review.completed',
                entityType: 'SoftwareAccountReview',
                entityId: $review->id,
                softwareAccountId: $account->id,
                outcome: 'success',
                reason: $notes,
                metadata: [
                    'result' => $result,
                    'next_review_at' => $account->next_review_at->toIso8601String(),
                ],
                actorUserId: $completedByUserId
            );

            return $review;
        });
    }

    /**
     * Get all software accounts that violate compliance alignment criteria:
     * - Missing active PIC
     * - Missing mandatory security property
     * - Missing required verified evidence
     *
     * (Excludes review cadence / scheduling gaps as requested by user).
     */
    public function getComplianceGapAccounts(array $filters = []): \Illuminate\Support\Collection
    {
        $allDefs = SoftwarePropertyDefinition::where('is_active', true)->get();
        $globalRequired = $allDefs->whereNull('software_platform_id')->where('is_required', true);
        $globalEvidence = $allDefs->whereNull('software_platform_id')->where('evidence_required', true);
        $platformRequired = $allDefs->whereNotNull('software_platform_id')->where('is_required', true)->groupBy('software_platform_id');
        $platformEvidence = $allDefs->whereNotNull('software_platform_id')->where('evidence_required', true)->groupBy('software_platform_id');

        $query = SoftwareAccount::with([
            'platform',
            'activePics.user',
            'evidence' => function ($q) {
                $q->where('status', 'accepted');
            },
            'todoReferences.todo.status',
            'todoReferences.todo.assignedUser',
            'todoReferences.todo.closedByUser',
        ])->where('lifecycle_status', '!=', 'retired');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhereHas('platform', function ($pQ) use ($search) {
                      $pQ->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        $accounts = $query->orderBy('account_name')->get();

        $gapAccounts = collect();

        foreach ($accounts as $account) {
            $gaps = [];

            // 1. Check Missing Active PIC
            if ($account->activePics->isEmpty()) {
                $gaps[] = [
                    'type' => 'missing_pic',
                    'category' => 'Governance',
                    'title' => 'No Active PIC',
                    'description' => 'Account has no active Person In Charge assigned for accountability.',
                    'severity' => 'critical',
                ];
            }

            // 2. Check Missing Mandatory Properties
            $required = $globalRequired->merge($platformRequired->get($account->software_platform_id, collect()));
            $accountProps = $account->properties ?? [];
            foreach ($required as $def) {
                if (!isset($accountProps[$def->code]) || $accountProps[$def->code] === null || $accountProps[$def->code] === '') {
                    $gaps[] = [
                        'type' => 'missing_property',
                        'category' => 'Security Property',
                        'code' => $def->code,
                        'title' => "Missing: {$def->label}",
                        'label' => $def->label,
                        'description' => "Mandatory security property '{$def->label}' ({$def->code}) is not configured.",
                        'severity' => 'critical',
                    ];
                }
            }

            // 3. Check Missing Required Evidence
            $evidenceRequired = $globalEvidence->merge($platformEvidence->get($account->software_platform_id, collect()));
            $acceptedEvidenceDefIds = $account->evidence->pluck('property_definition_id')->flip();
            foreach ($evidenceRequired as $def) {
                if (!isset($acceptedEvidenceDefIds[$def->id])) {
                    $gaps[] = [
                        'type' => 'missing_evidence',
                        'category' => 'Audit Evidence',
                        'code' => $def->code,
                        'title' => "Evidence Needed: {$def->label}",
                        'label' => $def->label,
                        'description' => "Verified acceptance evidence is required for '{$def->label}'.",
                        'severity' => 'warning',
                    ];
                }
            }

            if (empty($gaps)) {
                continue;
            }

            // Filter by gap_type if requested
            if (!empty($filters['gap_type'])) {
                $gapType = $filters['gap_type'];
                $hasMatchingGap = collect($gaps)->contains('type', $gapType);
                if (!$hasMatchingGap) {
                    continue;
                }
            }

            // Determine ToDo references
            $activeTodoRef = $account->todoReferences->first(function ($ref) {
                return empty($ref->todo?->closed_at);
            });
            $hasPendingTodo = !empty($activeTodoRef);
            $hasCompletedTodo = $account->todoReferences->contains(function ($ref) {
                return !empty($ref->todo?->closed_at);
            });
            $hasAnyTodo = $account->todoReferences->isNotEmpty();

            // Filter by gap_todo_status if requested
            if (!empty($filters['todo_status'])) {
                $todoFilter = $filters['todo_status'];
                if ($todoFilter === 'pending' && !$hasPendingTodo) {
                    continue;
                }
                if ($todoFilter === 'completed' && (!$hasCompletedTodo || $hasPendingTodo)) {
                    continue;
                }
                if ($todoFilter === 'has_todo' && !$hasAnyTodo) {
                    continue;
                }
                if ($todoFilter === 'no_todo' && $hasPendingTodo) {
                    continue;
                }
            }

            $account->compliance_gaps = $gaps;
            $account->gaps_count = count($gaps);
            $account->has_pending_todo = $hasPendingTodo;
            $account->has_completed_todo = $hasCompletedTodo;
            $account->active_todo_reference = $activeTodoRef;

            $gapAccounts->push($account);
        }

        return $gapAccounts;
    }
}
