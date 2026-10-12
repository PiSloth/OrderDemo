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

        // Check required property definitions
        $requiredDefs = SoftwarePropertyDefinition::where('is_active', true)
            ->where('is_required', true)
            ->where(function ($query) use ($platformId) {
                $query->whereNull('software_platform_id')
                    ->orWhere('software_platform_id', $platformId);
            })
            ->get();

        $accountProps = $account->properties ?? [];
        foreach ($requiredDefs as $def) {
            if (!isset($accountProps[$def->code]) || $accountProps[$def->code] === null) {
                throw new InvalidArgumentException("Cannot complete review: Required property '{$def->label}' is missing.");
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
}
