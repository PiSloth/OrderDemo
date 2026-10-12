<?php

namespace Tests\Feature\DigitalAsset;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareDevice;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\OfficeAsset;
use App\Models\OfficeAssetItem;
use App\Models\User;
use App\Services\DigitalAsset\SoftwareAccountAuditService;
use App\Services\DigitalAsset\SoftwareAccountDeviceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SoftwareDeviceAndAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected SoftwarePlatform $platform;
    protected SoftwareAccount $account;
    protected SoftwareAccountDeviceService $deviceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'suspended' => false]);
        $this->platform = SoftwarePlatform::create([
            'code' => 'plat_dev_' . uniqid(),
            'name' => 'Device Platform',
            'status' => 'active',
        ]);
        $this->account = SoftwareAccount::create([
            'software_platform_id' => $this->platform->id,
            'account_name' => 'Device Account',
            'username' => 'dev@company.test',
            'properties' => [],
            'created_by' => $this->user->id,
        ]);
        $this->deviceService = app(SoftwareAccountDeviceService::class);
    }

    public function test_registering_device_and_linking_to_fixed_asset(): void
    {
        $branch = Branch::first() ?? Branch::create(['name' => 'Main HQ']);
        $department = Department::first() ?? Department::create(['name' => 'IT Dept']);
        $category = \App\Models\AssetCategory::first() ?? \App\Models\AssetCategory::create(['name' => 'Laptops']);

        $officeAsset = OfficeAsset::create([
            'name' => 'SecOps Laptop #102',
            'asset_category_id' => $category->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'balance' => 1,
        ]);

        $device = $this->deviceService->registerDevice([
            'fixed_asset_id' => $officeAsset->id,
            'ownership_type' => 'company',
            'hostname' => 'CORP-SEC-102',
            'manufacturer' => 'Apple',
            'model_name' => 'MacBook Pro M3',
            'operating_system' => 'macOS 15',
            'device_status' => 'active',
        ], $this->user->id);

        $this->assertEquals('CORP-SEC-102', $device->hostname);
        $this->assertEquals($officeAsset->id, $device->fixed_asset_id);

        // Link device to software account
        $link = $this->deviceService->linkDeviceToAccount(
            account: $this->account,
            device: $device,
            relationshipType: 'authorized',
            actorUserId: $this->user->id
        );

        $this->assertEquals('authorized', $link->relationship_type);
        $this->assertDatabaseHas('software_account_devices', [
            'software_account_id' => $this->account->id,
            'software_device_id' => $device->id,
            'relationship_type' => 'authorized',
        ]);
    }

    public function test_audit_log_redacts_sensitive_keys_automatically(): void
    {
        $log = SoftwareAccountAuditService::log(
            action: 'test.sensitive_action',
            entityType: 'TestEntity',
            entityId: 1,
            softwareAccountId: $this->account->id,
            outcome: 'success',
            metadata: [
                'user_agent' => 'TestBrowser',
                'password' => 'secret_123',
                'token' => 'ghp_secret_access_token',
                'recovery_code' => 'abc-def-ghi',
                'safe_info' => 'visible_detail',
            ],
            actorUserId: $this->user->id
        );

        $this->assertEquals('[REDACTED]', $log->metadata['password']);
        $this->assertEquals('[REDACTED]', $log->metadata['token']);
        $this->assertEquals('[REDACTED]', $log->metadata['recovery_code']);
        $this->assertEquals('visible_detail', $log->metadata['safe_info']);
    }
}
