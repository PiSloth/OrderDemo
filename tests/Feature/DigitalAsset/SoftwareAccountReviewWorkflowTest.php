<?php

namespace Tests\Feature\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use App\Models\User;
use App\Services\DigitalAsset\SoftwareAccountReviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class SoftwareAccountReviewWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected SoftwarePlatform $platform;
    protected SoftwareAccount $account;
    protected SoftwareAccountReviewService $reviewService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'suspended' => false]);
        $this->platform = SoftwarePlatform::create([
            'code' => 'plat_rev_' . uniqid(),
            'name' => 'Review Platform',
            'status' => 'active',
        ]);
        $this->account = SoftwareAccount::create([
            'software_platform_id' => $this->platform->id,
            'account_name' => 'Review Account',
            'username' => 'rev@company.test',
            'properties' => [],
            'created_by' => $this->user->id,
        ]);
        $this->reviewService = app(SoftwareAccountReviewService::class);
    }

    public function test_scheduling_starting_and_completing_reviews(): void
    {
        // Satisfy seeded global required properties
        $this->account->update([
            'properties' => [
                'two_factor_enabled' => true,
                'recovery_configured' => true,
            ],
        ]);

        // Provide verified evidence for two_factor_enabled if definition exists
        $twoFactorDef = SoftwarePropertyDefinition::where('code', 'two_factor_enabled')->first();
        if ($twoFactorDef) {
            \App\Models\DigitalAsset\SoftwareAccountEvidence::create([
                'software_account_id' => $this->account->id,
                'property_definition_id' => $twoFactorDef->id,
                'storage_disk' => 'local',
                'file_path' => 'evidence/test.png',
                'original_filename' => 'test.png',
                'mime_type' => 'image/png',
                'file_size_bytes' => 1024,
                'sha256_hash' => hash('sha256', 'dummy'),
                'uploaded_by' => $this->user->id,
                'verified_by' => $this->user->id,
                'verified_at' => now(),
                'status' => 'accepted',
            ]);
        }

        $review = $this->reviewService->scheduleReview(
            account: $this->account,
            reviewType: 'scheduled',
            scheduledAt: now()->toDateTimeString(),
            assignedToUserId: $this->user->id,
            notes: 'Q4 Annual Review',
            actorUserId: $this->user->id
        );

        $this->assertEquals('scheduled', $review->status);

        $started = $this->reviewService->startReview($review, $this->user->id);
        $this->assertEquals('in_progress', $started->status);
        $this->assertNotNull($started->started_at);

        $completed = $this->reviewService->completeReview(
            review: $started,
            result: 'compliant',
            completedByUserId: $this->user->id,
            findings: ['mfa' => 'verified'],
            notes: 'All checks passed',
            nextReviewDays: 90
        );

        $this->assertEquals('completed', $completed->status);
        $this->assertEquals('compliant', $completed->result);

        $this->account->refresh();
        $this->assertEquals('compliant', $this->account->security_status);
        $this->assertNotNull($this->account->last_reviewed_at);
        $this->assertNotNull($this->account->next_review_at);
    }

    public function test_prevents_review_completion_when_mandatory_properties_missing(): void
    {
        // Account has empty properties, so any required property will trigger exception
        $review = $this->reviewService->scheduleReview(
            account: $this->account,
            reviewType: 'scheduled',
            scheduledAt: now()->toDateTimeString(),
            actorUserId: $this->user->id
        );

        $this->reviewService->startReview($review, $this->user->id);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("is missing");

        $this->reviewService->completeReview(
            review: $review,
            result: 'compliant',
            completedByUserId: $this->user->id
        );
    }
}
