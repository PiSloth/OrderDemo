<?php

namespace Tests\Feature\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\User;
use App\Services\DigitalAsset\SoftwareAccountPicService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class SoftwareAccountPicManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $picUser;
    protected SoftwarePlatform $platform;
    protected SoftwareAccount $account;
    protected SoftwareAccountPicService $picService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['role' => 'admin', 'suspended' => false]);
        $this->picUser = User::factory()->create(['role' => 'employee', 'suspended' => false]);
        $this->platform = SoftwarePlatform::create([
            'code' => 'plat_pic_' . uniqid(),
            'name' => 'Platform PIC Test',
            'status' => 'active',
        ]);
        $this->account = SoftwareAccount::create([
            'software_platform_id' => $this->platform->id,
            'account_name' => 'Account PIC Test',
            'username' => 'pic_test@company.test',
            'properties' => [],
            'created_by' => $this->adminUser->id,
        ]);
        $this->picService = app(SoftwareAccountPicService::class);
    }

    public function test_assigning_revoking_and_reactivating_pics(): void
    {
        // 1. Assign PIC
        $pic = $this->picService->assignPic($this->account, $this->picUser->id, $this->adminUser->id, 'Initial Assignment');
        $this->assertEquals('active', $pic->status);
        $this->assertDatabaseHas('software_account_pics', [
            'id' => $pic->id,
            'software_account_id' => $this->account->id,
            'user_id' => $this->picUser->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('software_account_pic_history', [
            'software_account_pic_id' => $pic->id,
            'action' => 'assigned',
            'user_id' => $this->picUser->id,
            'performed_by' => $this->adminUser->id,
        ]);

        // 2. Revoke PIC
        $revokedPic = $this->picService->revokePic($pic, $this->adminUser->id, 'Employee transfer');
        $this->assertEquals('revoked', $revokedPic->status);
        $this->assertDatabaseHas('software_account_pic_history', [
            'software_account_pic_id' => $pic->id,
            'action' => 'inactivated',
            'user_id' => $this->picUser->id,
            'reason' => 'Employee transfer',
        ]);

        // 3. Reactivate PIC
        $reactivatedPic = $this->picService->reactivatePic($revokedPic, $this->adminUser->id, 'Returned to project');
        $this->assertEquals('active', $reactivatedPic->status);
        $this->assertDatabaseHas('software_account_pic_history', [
            'software_account_pic_id' => $pic->id,
            'action' => 'reactivated',
            'user_id' => $this->picUser->id,
            'reason' => 'Returned to project',
        ]);
    }

    public function test_preventing_duplicate_active_pic_assignments(): void
    {
        $this->picService->assignPic($this->account, $this->picUser->id, $this->adminUser->id, 'First assignment');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("already an active PIC");

        // Attempt second active assignment for the same user & account
        $this->picService->assignPic($this->account, $this->picUser->id, $this->adminUser->id, 'Duplicate attempt');
    }
}
