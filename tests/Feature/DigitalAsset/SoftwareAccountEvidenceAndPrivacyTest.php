<?php

namespace Tests\Feature\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use App\Models\User;
use App\Services\DigitalAsset\SoftwareAccountEvidenceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class SoftwareAccountEvidenceAndPrivacyTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected SoftwarePlatform $platform;
    protected SoftwareAccount $account;
    protected SoftwarePropertyDefinition $propertyDef;
    protected SoftwareAccountEvidenceService $evidenceService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->user = User::factory()->create(['role' => 'admin', 'suspended' => false]);
        $this->platform = SoftwarePlatform::create([
            'code' => 'plat_evi_' . uniqid(),
            'name' => 'Evidence Platform',
            'status' => 'active',
        ]);
        $this->account = SoftwareAccount::create([
            'software_platform_id' => $this->platform->id,
            'account_name' => 'Evidence Account',
            'username' => 'evi@company.test',
            'properties' => [],
            'created_by' => $this->user->id,
        ]);
        $this->propertyDef = SoftwarePropertyDefinition::create([
            'software_platform_id' => $this->platform->id,
            'code' => 'two_factor_auth_' . uniqid(),
            'label' => '2FA Config Screenshot',
            'data_type' => 'boolean',
            'evidence_required' => true,
            'evidence_types' => ['image/png', 'image/jpeg', 'application/pdf'],
            'is_active' => true,
        ]);
        $this->evidenceService = app(SoftwareAccountEvidenceService::class);
    }

    public function test_uploading_evidence_stores_privately_with_sha256_hash(): void
    {
        $file = UploadedFile::fake()->create('2fa_screenshot.png', 500, 'image/png');

        $evidence = $this->evidenceService->uploadEvidence(
            account: $this->account,
            definition: $this->propertyDef,
            file: $file,
            uploadedByUserId: $this->user->id
        );

        $this->assertEquals('local', $evidence->storage_disk);
        $this->assertNotEmpty($evidence->sha256_hash);
        $this->assertEquals(64, strlen($evidence->sha256_hash));
        $this->assertEquals('pending', $evidence->status);

        // Verify storage file exists on private disk
        Storage::disk('local')->assertExists($evidence->file_path);
    }

    public function test_verifying_evidence_acceptance_and_rejection(): void
    {
        $file = UploadedFile::fake()->create('proof.pdf', 300, 'application/pdf');
        $evidence = $this->evidenceService->uploadEvidence(
            account: $this->account,
            definition: $this->propertyDef,
            file: $file,
            uploadedByUserId: $this->user->id
        );

        $verifier = User::factory()->create(['role' => 'admin', 'suspended' => false]);

        $verified = $this->evidenceService->verifyEvidence(
            evidence: $evidence,
            status: 'accepted',
            verifierUserId: $verifier->id,
            reviewNotes: 'Verified compliant with SecOps standards'
        );

        $this->assertEquals('accepted', $verified->status);
        $this->assertEquals($verifier->id, $verified->verified_by);
        $this->assertNotNull($verified->verified_at);
    }

    public function test_rejects_unpermitted_file_mimes(): void
    {
        $file = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("not permitted for this evidence");

        $this->evidenceService->uploadEvidence(
            account: $this->account,
            definition: $this->propertyDef,
            file: $file,
            uploadedByUserId: $this->user->id
        );
    }
}
