<?php

namespace Database\Seeders;

use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use Illuminate\Database\Seeder;

class DigitalAssetSeeder extends Seeder
{
    public function run(): void
    {
        // Default platforms
        $platforms = [
            ['code' => 'google_workspace', 'name' => 'Google Workspace (Gmail / Cloud)', 'category' => 'cloud'],
            ['code' => 'microsoft_365', 'name' => 'Microsoft 365 (Office / Azure)', 'category' => 'business_app'],
            ['code' => 'github', 'name' => 'GitHub Enterprise / Org', 'category' => 'source_control'],
            ['code' => 'aws', 'name' => 'Amazon Web Services (AWS)', 'category' => 'cloud'],
            ['code' => 'facebook_meta', 'name' => 'Facebook / Meta Business Suite', 'category' => 'social_media'],
            ['code' => 'tiktok_business', 'name' => 'TikTok for Business', 'category' => 'social_media'],
            ['code' => 'youtube_channel', 'name' => 'YouTube Brand Channel', 'category' => 'social_media'],
            ['code' => 'slack', 'name' => 'Slack Enterprise Grid', 'category' => 'communication'],
        ];

        foreach ($platforms as $p) {
            SoftwarePlatform::firstOrCreate(
                ['code' => $p['code']],
                ['name' => $p['name'], 'category' => $p['category'], 'status' => 'active']
            );
        }

        // Default common global security property definitions
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

        foreach ($definitions as $d) {
            SoftwarePropertyDefinition::firstOrCreate(
                ['code' => $d['code'], 'software_platform_id' => null],
                array_merge($d, ['is_active' => true])
            );
        }
    }
}
