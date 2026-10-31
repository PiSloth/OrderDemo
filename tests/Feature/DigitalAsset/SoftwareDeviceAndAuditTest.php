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
            'serial_number' => 'SN-CORP-102',
            'manufacturer' => 'Apple',
            'model_name' => 'MacBook Pro M3',
            'operating_system' => 'macOS 15',
            'device_status' => 'active',
        ], $this->user->id);

        $this->assertEquals('CORP-SEC-102', $device->hostname);
        $this->assertEquals($officeAsset->id, $device->fixed_asset_id);
        $this->assertEquals('SN-CORP-102', $device->serial_number);

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

    public function test_updating_device(): void
    {
        $device = $this->deviceService->registerDevice([
            'ownership_type' => 'personal',
            'hostname' => 'PERSONAL-MAC-01',
            'serial_number' => 'SN-PERS-001',
            'manufacturer' => 'Apple',
            'model_name' => 'MacBook Air M2',
            'device_status' => 'active',
        ], $this->user->id);

        $response = $this->actingAs($this->user)->put(route('digital-assets.devices.update', $device), [
            'ownership_type' => 'company',
            'hostname' => 'CORP-MAC-01',
            'serial_number' => 'SN-PERS-001-MOD',
            'manufacturer' => 'Apple',
            'model_name' => 'MacBook Air M2 (Upgraded)',
            'device_status' => 'retired',
            'notes' => 'Transferred to company asset',
        ]);

        $response->assertSessionHasNoErrors();
        $device->refresh();

        $this->assertEquals('CORP-MAC-01', $device->hostname);
        $this->assertEquals('SN-PERS-001-MOD', $device->serial_number);
        $this->assertEquals('company', $device->ownership_type);
        $this->assertEquals('retired', $device->device_status);
        $this->assertEquals('Transferred to company asset', $device->notes);
    }

    public function test_deleting_device_and_cleaning_up_links(): void
    {
        $device = $this->deviceService->registerDevice([
            'ownership_type' => 'company',
            'hostname' => 'TEMP-DEVICE-TO-DELETE',
            'serial_number' => 'SN-DEL-999',
            'device_status' => 'active',
        ], $this->user->id);

        $link = $this->deviceService->linkDeviceToAccount(
            account: $this->account,
            device: $device,
            relationshipType: 'authorized',
            actorUserId: $this->user->id
        );

        $this->assertDatabaseHas('software_devices', ['id' => $device->id]);
        $this->assertDatabaseHas('software_account_devices', ['id' => $link->id]);

        $response = $this->actingAs($this->user)->delete(route('digital-assets.devices.destroy', $device));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('software_devices', ['id' => $device->id]);
        $this->assertDatabaseMissing('software_account_devices', ['id' => $link->id]);
    }

    public function test_unlinking_device_from_account(): void
    {
        $device = $this->deviceService->registerDevice([
            'ownership_type' => 'company',
            'hostname' => 'UNLINK-TEST-DEV',
            'serial_number' => 'SN-UNLINK-777',
            'device_status' => 'active',
        ], $this->user->id);

        $link = $this->deviceService->linkDeviceToAccount(
            account: $this->account,
            device: $device,
            relationshipType: 'authorized',
            actorUserId: $this->user->id
        );

        $this->assertDatabaseHas('software_account_devices', ['id' => $link->id]);

        $response = $this->actingAs($this->user)->delete(route('digital-assets.devices.unlink', $link));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('software_account_devices', ['id' => $link->id]);
        $this->assertDatabaseHas('software_devices', ['id' => $device->id]);
    }

    public function test_serial_number_must_be_required_and_unique(): void
    {
        // 1. Missing serial number throws InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Serial number is required.');
        $this->deviceService->registerDevice([
            'ownership_type' => 'company',
            'hostname' => 'MISSING-SN',
        ], $this->user->id);
    }

    public function test_duplicate_serial_number_is_rejected(): void
    {
        $this->deviceService->registerDevice([
            'ownership_type' => 'company',
            'hostname' => 'DEV-A',
            'serial_number' => 'SN-UNIQUE-12345',
        ], $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("A device with serial number 'SN-UNIQUE-12345' already exists.");
        $this->deviceService->registerDevice([
            'ownership_type' => 'company',
            'hostname' => 'DEV-B',
            'serial_number' => 'SN-UNIQUE-12345',
        ], $this->user->id);
    }

    public function test_user_without_permission_cannot_access_audit_logs(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'staff',
            'department_id' => null,
            'suspended' => false,
        ]);

        $response = $this->actingAs($regularUser)->get(route('digital-assets.audit.index'));
        $response->assertForbidden();
    }

    public function test_user_with_security_audit_trial_view_permission_can_access_audit_logs(): void
    {
        $authorizedUser = User::factory()->create([
            'role' => 'staff',
            'department_id' => null,
            'suspended' => false,
        ]);

        $authorizedUser->givePermissionTo('security_audit_trial.view');

        $response = $this->actingAs($authorizedUser)->get(route('digital-assets.audit.index'));
        $response->assertOk();
    }

    public function test_unauthorized_user_cannot_access_or_manage_devices(): void
    {
        $unauthorized = User::factory()->create([
            'role' => 'staff',
            'department_id' => null,
            'suspended' => false,
        ]);

        $this->actingAs($unauthorized)->get(route('digital-assets.devices.index'))->assertForbidden();

        $this->actingAs($unauthorized)->post(route('digital-assets.devices.store'), [
            'ownership_type' => 'company',
            'hostname' => 'HACKED-DEVICE',
            'serial_number' => 'SN-HACKED-1',
            'device_status' => 'active',
        ])->assertForbidden();
    }

    public function test_user_with_hardware_device_inventory_permissions_can_manage_devices(): void
    {
        $deviceAdmin = User::factory()->create([
            'role' => 'staff',
            'department_id' => null,
            'suspended' => false,
        ]);

        $deviceAdmin->givePermissionTo([
            'hardware_device_inventory.view',
            'hardware_device_inventory.create',
            'hardware_device_inventory.update',
            'hardware_device_inventory.delete',
        ]);

        // 1. View Any
        $this->actingAs($deviceAdmin)->get(route('digital-assets.devices.index'))->assertOk();

        // 2. Create
        $sn = 'SN-PERM-' . uniqid();
        $createRes = $this->actingAs($deviceAdmin)->post(route('digital-assets.devices.store'), [
            'ownership_type' => 'company',
            'hostname' => 'HOST-PERM-01',
            'serial_number' => $sn,
            'device_status' => 'active',
        ]);
        $createRes->assertSessionHasNoErrors();
        $this->assertDatabaseHas('software_devices', ['serial_number' => $sn]);

        $device = SoftwareDevice::where('serial_number', $sn)->firstOrFail();

        // 3. Update
        $updateRes = $this->actingAs($deviceAdmin)->put(route('digital-assets.devices.update', $device), [
            'ownership_type' => 'company',
            'hostname' => 'HOST-PERM-UPDATED',
            'serial_number' => $sn,
            'device_status' => 'retired',
        ]);
        $updateRes->assertSessionHasNoErrors();
        $this->assertEquals('HOST-PERM-UPDATED', $device->fresh()->hostname);

        // 4. Delete
        $deleteRes = $this->actingAs($deviceAdmin)->delete(route('digital-assets.devices.destroy', $device));
        $deleteRes->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('software_devices', ['id' => $device->id]);
    }
}

