<?php

namespace Database\Seeders;

use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Production-Safe Digital Asset Seeder
 *
 * This seeder can be run safely in production multiple times without data loss or duplication:
 *   php artisan db:seed --class=DigitalAssetSeeder --force
 */
class DigitalAssetSeeder extends Seeder
{
    /**
     * Complete list of Digital Asset permissions across all modules.
     */
    protected array $permissions = [
        // 1. Software Accounts CRUD
        'account.view',
        'account.create',
        'account.edit',
        'account.update',
        'account.delete',
        'digital_asset.account.view',
        'digital_asset.account.create',
        'digital_asset.account.update',
        'digital_asset.account.delete',
        'digital_asset.account.retire',

        // 2. Security Property Definitions CRUD
        'security_property_definition.view',
        'security_property_definition.create',
        'security_property_definition.update',
        'security_property_definition.edit',
        'security_property_definition.delete',
        'property_definition.view',
        'property_definition.create',
        'property_definition.update',
        'property_definition.edit',
        'property_definition.delete',
        'security_property.view',
        'security_property.create',
        'security_property.update',
        'security_property.edit',
        'security_property.delete',

        // 3. Security Audit Trail / Logs View
        'security_audit_trial.view',
        'security_audit_trail.view',
        'security-audit-trial.view',
        'security-audit-trail.view',
        'audit_trail.view',
        'audit.view',
        'digital_asset.audit.view',

        // 4. Hardware Device Inventory CRUD
        'hardware_device_inventory.view',
        'hardware_device_inventory.create',
        'hardware_device_inventory.update',
        'hardware_device_inventory.edit',
        'hardware_device_inventory.delete',
        'digital_asset.device.view',
        'digital_asset.device.create',
        'digital_asset.device.update',
        'digital_asset.device.delete',

        // 5. PIC, Evidence, and Review Workflows
        'digital_asset.pic.assign',
        'digital_asset.pic.revoke',
        'digital_asset.pic.view',
        'digital_asset.review.schedule',
        'digital_asset.review.complete',
        'digital_asset.evidence.upload',
        'digital_asset.evidence.verify',
    ];

    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Seed Permissions safely
        $createdPermCount = 0;
        foreach ($this->permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
            if ($permission->wasRecentlyCreated) {
                $createdPermCount++;
            }
        }
        $this->command?->info("✓ Digital Asset permissions verified: " . count($this->permissions) . " total ({$createdPermCount} newly created).");

        // 3. Grant permissions safely to Admin and Super Admin roles
        $adminRoles = Role::whereIn('name', ['Super Admin', 'super_admin', 'Admin', 'admin'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($this->permissions);
        }
        $this->command?->info("✓ Granted permissions to " . $adminRoles->count() . " administrative roles.");

        // 4. Grant permissions to existing administrative users
        $adminUsers = User::whereIn('role', ['Super Admin', 'super_admin', 'Admin', 'admin'])->get();
        foreach ($adminUsers as $user) {
            try {
                $user->givePermissionTo($this->permissions);
            } catch (\Throwable $e) {
                // Silently skip if user already has permissions or guard mismatch
            }
        }
        $this->command?->info("✓ Assigned permissions to " . $adminUsers->count() . " administrative users.");

        // 5. Seed Core Platforms (firstOrCreate ensures existing custom platforms are never modified)
        $platforms = [
            ['code' => 'facebook_meta', 'name' => 'Facebook / Meta Business Suite', 'category' => 'social_media'],
            ['code' => 'tiktok_business', 'name' => 'TikTok for Business', 'category' => 'social_media'],
            ['code' => 'youtube_channel', 'name' => 'YouTube Brand Channel', 'category' => 'social_media'],
            ['code' => 'telegram', 'name' => 'Telegram Official Channels / Bots', 'category' => 'communication'],
            ['code' => 'Odoo ERP', 'name' => 'Odoo ERP', 'category' => 'business_app'],
            ['code' => 'Google Workspace', 'name' => 'Google Workspace (Gmail / Cloud)', 'category' => 'business_app'],
            ['code' => 'Organization Email', 'name' => 'Organization Email', 'category' => 'business_app'],
        ];

        $createdPlatformCount = 0;
        foreach ($platforms as $p) {
            $platform = SoftwarePlatform::firstOrCreate(
                ['code' => $p['code']],
                ['name' => $p['name'], 'category' => $p['category'], 'status' => 'active']
            );
            if ($platform->wasRecentlyCreated) {
                $createdPlatformCount++;
            }
        }
        $this->command?->info("✓ Default software platforms verified: " . count($platforms) . " baseline ({$createdPlatformCount} newly created).");

        // 6. Seed Baseline Global Security Property Definitions
        $definitions = [
            [
                'code' => 'two_factor_enabled',
                'label' => 'Two-Factor Authentication (2FA/MFA)',
                'data_type' => 'boolean',
                'description' => 'Whether two-factor authentication is enforced and active on this account.',
                'is_required' => true,
                'evidence_required' => true,
                'evidence_types' => ['image/png', 'image/jpeg', 'application/pdf'],
                'sort_order' => 10,
            ],
            [
                'code' => 'last_password_changed_at',
                'label' => 'Last Password Change Date',
                'data_type' => 'date',
                'description' => 'Last verified date when the master credential or password was rotated.',
                'is_required' => false,
                'sort_order' => 20,
            ],
            [
                'code' => 'login_methods',
                'label' => 'Allowed Login Methods',
                'data_type' => 'multi_choice',
                'description' => 'Authentication methods permitted (e.g. Password, Authenticator App, Security Key / FIDO2, SSO).',
                'validation_rules' => ['options' => ['password', 'authenticator_app', 'fido2_hardware_key', 'sso_saml', 'sms_otp']],
                'sort_order' => 30,
            ],
            [
                'code' => 'recovery_configured',
                'label' => 'Account Recovery Options Configured',
                'data_type' => 'boolean',
                'description' => 'Whether corporate recovery email and emergency security contact details are properly set up.',
                'is_required' => true,
                'sort_order' => 40,
            ],
            [
                'code' => 'recovery_email_id',
                'label' => 'Corporate Recovery Email Master Reference',
                'data_type' => 'email_reference',
                'description' => 'Reference to corporate email record in email_lists table assigned for account recovery.',
                'sort_order' => 50,
            ],
            [
                'code' => 'privileged_admin_access',
                'label' => 'Privileged Administrative Access',
                'data_type' => 'boolean',
                'description' => 'Does this software account hold super-admin, owner, or tenant-wide control?',
                'is_sensitive' => true,
                'requires_approval' => true,
                'sort_order' => 60,
            ],
        ];

        $createdDefCount = 0;
        foreach ($definitions as $d) {
            $def = SoftwarePropertyDefinition::firstOrCreate(
                ['code' => $d['code'], 'software_platform_id' => null],
                array_merge($d, ['is_active' => true])
            );
            if ($def->wasRecentlyCreated) {
                $createdDefCount++;
            }
        }
        $this->command?->info("✓ Baseline security property definitions verified: " . count($definitions) . " total ({$createdDefCount} newly created).");

        // 7. Flush permission cache once more
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info("🚀 Digital Asset production seeding finished safely without data loss.");
    }
}
