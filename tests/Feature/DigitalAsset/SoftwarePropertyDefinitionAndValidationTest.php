<?php

namespace Tests\Feature\DigitalAsset;

use App\Models\Department;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountPropertyHistory;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use App\Models\EmailList;
use App\Models\User;
use App\Services\DigitalAsset\SoftwareAccountSecurityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class SoftwarePropertyDefinitionAndValidationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected SoftwarePlatform $platform;
    protected SoftwareAccount $account;
    protected SoftwareAccountSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'suspended' => false]);
        $this->platform = SoftwarePlatform::create([
            'code' => 'plat_sec_' . uniqid(),
            'name' => 'Security Platform',
            'status' => 'active',
        ]);
        $this->account = SoftwareAccount::create([
            'software_platform_id' => $this->platform->id,
            'account_name' => 'Sec Account',
            'username' => 'sec@company.test',
            'properties' => [],
            'created_by' => $this->user->id,
        ]);
        $this->securityService = app(SoftwareAccountSecurityService::class);
    }

    public function test_validating_global_and_platform_specific_definitions(): void
    {
        // Global definition
        $globalDef = SoftwarePropertyDefinition::create([
            'software_platform_id' => null,
            'code' => 'two_factor_enabled_' . uniqid(),
            'label' => '2FA Enabled',
            'data_type' => 'boolean',
            'is_active' => true,
        ]);

        // Platform-specific definition
        $platformDef = SoftwarePropertyDefinition::create([
            'software_platform_id' => $this->platform->id,
            'code' => 'org_role_' . uniqid(),
            'label' => 'Org Role',
            'data_type' => 'text',
            'is_active' => true,
        ]);

        $validated = $this->securityService->validateProperties($this->account, [
            $globalDef->code => true,
            $platformDef->code => 'Owner',
        ]);

        $this->assertTrue($validated[$globalDef->code]);
        $this->assertEquals('Owner', $validated[$platformDef->code]);
    }

    public function test_rejecting_unknown_property_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown or unpermitted property code");

        $this->securityService->validateProperties($this->account, [
            'completely_unknown_key' => 'some_value',
        ]);
    }

    public function test_validating_email_reference_values(): void
    {
        $department = Department::first() ?? Department::create(['name' => 'IT Dept']);
        $emailRecord = EmailList::create([
            'user_name' => 'Valid Ref User',
            'email' => 'valid_ref_' . uniqid() . '@company.test',
            'department_id' => $department->id,
        ]);

        $emailRefDef = SoftwarePropertyDefinition::create([
            'software_platform_id' => $this->platform->id,
            'code' => 'recovery_email_ref_' . uniqid(),
            'label' => 'Recovery Email Ref',
            'data_type' => 'email_reference',
            'is_active' => true,
        ]);

        $validated = $this->securityService->validateSingleProperty($emailRefDef, $emailRecord->id);
        $this->assertEquals($emailRecord->id, $validated);

        // Invalid non-existent email id
        $this->expectException(InvalidArgumentException::class);
        $this->securityService->validateSingleProperty($emailRefDef, 99999999);
    }

    public function test_updating_property_and_inserting_history_atomically(): void
    {
        $def = SoftwarePropertyDefinition::create([
            'software_platform_id' => $this->platform->id,
            'code' => 'auth_feature_' . uniqid(),
            'label' => 'Auth Feature Enabled',
            'data_type' => 'boolean',
            'requires_approval' => false,
            'is_active' => true,
        ]);

        $history = $this->securityService->updateProperty(
            account: $this->account,
            propertyCode: $def->code,
            newValue: true,
            changedByUserId: $this->user->id,
            changeReason: 'Enforcing security baseline'
        );

        $this->account->refresh();
        $this->assertTrue($this->account->properties[$def->code]);
        $this->assertEquals('not_required', $history->review_status);

        $this->assertDatabaseHas('software_account_property_history', [
            'id' => $history->id,
            'software_account_id' => $this->account->id,
            'property_definition_id' => $def->id,
            'changed_by' => $this->user->id,
            'change_reason' => 'Enforcing security baseline',
        ]);
    }

    public function test_enforcing_approval_requirements(): void
    {
        $def = SoftwarePropertyDefinition::create([
            'software_platform_id' => $this->platform->id,
            'code' => 'critical_setting_' . uniqid(),
            'label' => 'Critical Security Setting',
            'data_type' => 'text',
            'requires_approval' => true,
            'is_active' => true,
        ]);

        // Submit change
        $history = $this->securityService->updateProperty(
            account: $this->account,
            propertyCode: $def->code,
            newValue: 'SensitiveConfigV2',
            changedByUserId: $this->user->id,
            changeReason: 'Upgrade security profile'
        );

        $this->account->refresh();
        // Since approval is required, properties must NOT contain the new value yet!
        $this->assertArrayNotHasKey($def->code, $this->account->properties ?? []);
        $this->assertEquals('pending', $history->review_status);

        // Approve change
        $approver = User::factory()->create(['role' => 'admin', 'suspended' => false]);
        $this->securityService->approvePropertyChange($history, $approver->id, 'Approved by SecOps');

        $this->account->refresh();
        $this->assertEquals('SensitiveConfigV2', $this->account->properties[$def->code]);
        $this->assertEquals('approved', $history->fresh()->review_status);
    }
}
