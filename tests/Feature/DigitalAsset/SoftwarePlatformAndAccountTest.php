<?php

namespace Tests\Feature\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountEmail;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\EmailList;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SoftwarePlatformAndAccountTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'suspended' => false]);
    }

    public function test_can_create_platform_and_software_account(): void
    {
        $platform = SoftwarePlatform::create([
            'code' => 'test_platform_' . uniqid(),
            'name' => 'Test Cloud Platform',
            'category' => 'cloud',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->post(route('digital-assets.accounts.store'), [
            'software_platform_id' => $platform->id,
            'account_name' => 'Production Master Account',
            'username' => 'admin@company.test',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('software_accounts', [
            'software_platform_id' => $platform->id,
            'account_name' => 'Production Master Account',
            'username' => 'admin@company.test',
            'lifecycle_status' => 'active',
            'security_status' => 'unknown',
        ]);
    }

    public function test_rejects_inactive_or_nonexistent_platform(): void
    {
        $inactivePlatform = SoftwarePlatform::create([
            'code' => 'inactive_plat_' . uniqid(),
            'name' => 'Inactive Platform',
            'category' => 'cloud',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->user)->post(route('digital-assets.accounts.store'), [
            'software_platform_id' => $inactivePlatform->id,
            'account_name' => 'Should Fail',
            'username' => 'fail@company.test',
        ]);

        $response->assertSessionHasErrors(['software_platform_id']);

        $response2 = $this->actingAs($this->user)->post(route('digital-assets.accounts.store'), [
            'software_platform_id' => 9999999,
            'account_name' => 'Nonexistent Platform',
            'username' => 'fail@company.test',
        ]);

        $response2->assertSessionHasErrors(['software_platform_id']);
    }

    public function test_linking_multiple_email_addresses_with_different_purposes(): void
    {
        $platform = SoftwarePlatform::create([
            'code' => 'plat_email_' . uniqid(),
            'name' => 'Platform With Emails',
            'status' => 'active',
        ]);

        $account = SoftwareAccount::create([
            'software_platform_id' => $platform->id,
            'account_name' => 'Account Multi Email',
            'username' => 'multi@company.test',
            'properties' => [],
            'created_by' => $this->user->id,
        ]);

        $department = \App\Models\Department::first() ?? \App\Models\Department::create(['name' => 'IT Dept']);

        $email1 = EmailList::create([
            'user_name' => 'Reg User',
            'email' => 'reg_' . uniqid() . '@company.test',
            'department_id' => $department->id,
        ]);

        $email2 = EmailList::create([
            'user_name' => 'Recovery User',
            'email' => 'rec_' . uniqid() . '@company.test',
            'department_id' => $department->id,
        ]);

        $this->actingAs($this->user)->post(route('digital-assets.accounts.emails.store', $account->id), [
            'email_id' => $email1->id,
            'purpose' => 'registration',
            'is_primary' => true,
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->user)->post(route('digital-assets.accounts.emails.store', $account->id), [
            'email_id' => $email2->id,
            'purpose' => 'recovery',
            'is_primary' => false,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('software_account_emails', [
            'software_account_id' => $account->id,
            'email_id' => $email1->id,
            'purpose' => 'registration',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('software_account_emails', [
            'software_account_id' => $account->id,
            'email_id' => $email2->id,
            'purpose' => 'recovery',
            'is_primary' => false,
        ]);
    }

    public function test_can_update_platform_code(): void
    {
        $platform = SoftwarePlatform::create([
            'code' => 'initial_code_' . uniqid(),
            'name' => 'Initial Platform Name',
            'category' => 'cloud',
            'status' => 'active',
        ]);

        $newCode = 'updated_code_' . uniqid();

        $response = $this->actingAs($this->user)->put(route('digital-assets.platforms.update', $platform->id), [
            'code' => $newCode,
            'name' => 'Updated Platform Name',
            'category' => 'business_app',
            'status' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('software_platforms', [
            'id' => $platform->id,
            'code' => $newCode,
            'name' => 'Updated Platform Name',
            'category' => 'business_app',
        ]);
    }

    public function test_prevents_deactivating_platform_with_active_accounts(): void
    {
        $platform = SoftwarePlatform::create([
            'code' => 'active_dep_plat_' . uniqid(),
            'name' => 'Platform With Active Accounts',
            'status' => 'active',
        ]);

        SoftwareAccount::create([
            'software_platform_id' => $platform->id,
            'account_name' => 'Active Account in Platform',
            'username' => 'user_' . uniqid() . '@company.test',
            'lifecycle_status' => 'active',
            'properties' => [],
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->put(route('digital-assets.platforms.update', $platform->id), [
            'code' => $platform->code,
            'name' => $platform->name,
            'category' => 'cloud',
            'status' => 'inactive',
        ]);

        $response->assertSessionHasErrors(['status']);
        $this->assertEquals('active', $platform->fresh()->status);
    }

    public function test_allows_deactivating_platform_with_zero_active_accounts(): void
    {
        $platform = SoftwarePlatform::create([
            'code' => 'deact_plat_' . uniqid(),
            'name' => 'Platform No Active Accounts',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->put(route('digital-assets.platforms.update', $platform->id), [
            'code' => $platform->code,
            'name' => $platform->name,
            'category' => 'cloud',
            'status' => 'inactive',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('inactive', $platform->fresh()->status);
    }

    public function test_unauthorized_user_cannot_create_or_delete_software_account(): void
    {
        $regularUser = User::factory()->create(['role' => 'user', 'suspended' => false]);
        $platform = SoftwarePlatform::create([
            'code' => 'plat_unauth_' . uniqid(),
            'name' => 'Platform For Unauth',
            'status' => 'active',
        ]);

        $account = SoftwareAccount::create([
            'software_platform_id' => $platform->id,
            'account_name' => 'Account Protected',
            'username' => 'unauth_' . uniqid() . '@company.test',
            'lifecycle_status' => 'active',
            'properties' => [],
            'created_by' => $this->user->id,
        ]);

        // Cannot create without account.create
        $createRes = $this->actingAs($regularUser)->post(route('digital-assets.accounts.store'), [
            'software_platform_id' => $platform->id,
            'account_name' => 'Unauthorized Attempt',
            'username' => 'unauth_sub@company.test',
        ]);
        $createRes->assertStatus(403);

        // Cannot delete without account.delete
        $delRes = $this->actingAs($regularUser)->delete(route('digital-assets.accounts.destroy', $account->id));
        $delRes->assertStatus(403);
    }

    public function test_authorized_user_with_permission_can_delete_software_account(): void
    {
        $customUser = User::factory()->create(['role' => 'user', 'suspended' => false]);
        $customUser->givePermissionTo('account.delete');
        $customUser->givePermissionTo('account.view');

        $platform = SoftwarePlatform::create([
            'code' => 'plat_del_' . uniqid(),
            'name' => 'Platform For Deletion',
            'status' => 'active',
        ]);

        $account = SoftwareAccount::create([
            'software_platform_id' => $platform->id,
            'account_name' => 'Account To Delete',
            'username' => 'to_del_' . uniqid() . '@company.test',
            'lifecycle_status' => 'active',
            'properties' => [],
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($customUser)->delete(route('digital-assets.accounts.destroy', $account->id));
        $response->assertRedirect(route('digital-assets.accounts.index'));
        $this->assertDatabaseMissing('software_accounts', [
            'id' => $account->id,
        ]);
    }
}
