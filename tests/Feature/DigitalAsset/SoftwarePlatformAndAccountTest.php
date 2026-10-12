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
}
