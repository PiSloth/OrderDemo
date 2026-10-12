<?php

namespace Tests\Feature\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountAuthorizationGrant;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SoftwareSchemaIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_all_13_software_tables_exist_in_mysql(): void
    {
        $tables = [
            'software_platforms',
            'software_accounts',
            'software_account_emails',
            'software_property_definitions',
            'software_account_pics',
            'software_account_pic_history',
            'software_account_property_history',
            'software_account_evidence',
            'software_account_reviews',
            'software_devices',
            'software_account_devices',
            'software_account_audit_logs',
            'software_account_authorization_grants',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                DB::getSchemaBuilder()->hasTable($table),
                "Expected table '{$table}' to exist in database."
            );
        }
    }

    public function test_authorization_grants_tracks_oauth_metadata_without_secrets(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'suspended' => false]);
        $platform = SoftwarePlatform::create([
            'code' => 'plat_oauth_' . uniqid(),
            'name' => 'OAuth Test Platform',
            'status' => 'active',
        ]);
        $account = SoftwareAccount::create([
            'software_platform_id' => $platform->id,
            'account_name' => 'OAuth Account',
            'username' => 'oauth@company.test',
            'properties' => [],
            'created_by' => $user->id,
        ]);

        $grant = SoftwareAccountAuthorizationGrant::create([
            'software_account_id' => $account->id,
            'application_name' => 'GitHub CI/CD Runner',
            'client_id_snapshot' => 'client_abc123',
            'granted_scopes' => ['repo', 'read:org'],
            'grant_status' => 'active',
            'granted_at' => now(),
            'last_verified_at' => now(),
            'verification_notes' => 'Verified scopes match security policy',
        ]);

        $this->assertEquals('GitHub CI/CD Runner', $grant->application_name);
        $this->assertEquals(['repo', 'read:org'], $grant->granted_scopes);
        $this->assertEquals('active', $grant->grant_status);

        $this->assertDatabaseHas('software_account_authorization_grants', [
            'id' => $grant->id,
            'software_account_id' => $account->id,
            'application_name' => 'GitHub CI/CD Runner',
        ]);
    }
}
