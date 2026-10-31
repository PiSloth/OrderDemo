<?php

namespace Database\Seeders;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountReview;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DigitalAssetReviewSampleSeeder extends Seeder
{
    /**
     * Run the database seeds for Compliance Reviews demo data.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'pos@nexgen.com')->first() ?? User::first();
        $auditorUser = User::where('email', 'auditor@shwetatar.com')->first() ?? $adminUser;
        $itSupportUser = User::where('email', 'it.support@shwetatar.com')->first() ?? $adminUser;
        $kpiAdminUser = User::where('email', 'admin@shwetatar.com')->first() ?? $adminUser;

        // Ensure platforms exist
        $platforms = [
            'google_workspace' => SoftwarePlatform::firstOrCreate(
                ['code' => 'google_workspace'],
                ['name' => 'Google Workspace (Gmail / Cloud)', 'category' => 'cloud', 'status' => 'active']
            ),
            'aws' => SoftwarePlatform::firstOrCreate(
                ['code' => 'aws'],
                ['name' => 'Amazon Web Services (AWS)', 'category' => 'cloud', 'status' => 'active']
            ),
            'github' => SoftwarePlatform::firstOrCreate(
                ['code' => 'github'],
                ['name' => 'GitHub Enterprise / Org', 'category' => 'source_control', 'status' => 'active']
            ),
            'facebook_meta' => SoftwarePlatform::firstOrCreate(
                ['code' => 'facebook_meta'],
                ['name' => 'Facebook / Meta Business Suite', 'category' => 'social_media', 'status' => 'active']
            ),
            'cloudflare' => SoftwarePlatform::firstOrCreate(
                ['code' => 'cloudflare'],
                ['name' => 'Cloudflare DNS & Security', 'category' => 'cloud', 'status' => 'active']
            ),
            'slack' => SoftwarePlatform::firstOrCreate(
                ['code' => 'slack'],
                ['name' => 'Slack Enterprise Grid', 'category' => 'communication', 'status' => 'active']
            ),
            'microsoft_365' => SoftwarePlatform::firstOrCreate(
                ['code' => 'microsoft_365'],
                ['name' => 'Microsoft 365 (Office / Azure)', 'category' => 'business_app', 'status' => 'active']
            ),
        ];

        // Sample Accounts with rich security properties
        $accountsData = [
            [
                'platform' => 'google_workspace',
                'account_name' => 'Google Workspace Main Tenant',
                'username' => 'admin@shwetatar.org',
                'properties' => [
                    'two_factor_enabled' => true,
                    'recovery_configured' => true,
                    'sso_enforced' => true,
                    'privileged_admin_access' => true,
                ],
                'security_status' => 'compliant',
                'last_reviewed_at' => Carbon::now()->subDays(60),
                'next_review_at' => Carbon::now()->addDays(30),
            ],
            [
                'platform' => 'aws',
                'account_name' => 'AWS Production Core (Root)',
                'username' => 'aws-prod-admin@shwetatar.com',
                'properties' => [
                    'two_factor_enabled' => true,
                    'recovery_configured' => true,
                    'login_methods' => ['fido2_hardware_key', 'authenticator_app'],
                    'privileged_admin_access' => true,
                ],
                'security_status' => 'compliant',
                'last_reviewed_at' => Carbon::now()->subDays(14),
                'next_review_at' => Carbon::now()->addDays(76),
            ],
            [
                'platform' => 'github',
                'account_name' => 'GitHub Enterprise Core Org',
                'username' => 'devops-lead@shwetatar.com',
                'properties' => [
                    'two_factor_enabled' => true,
                    'recovery_configured' => false,
                    'privileged_admin_access' => true,
                ],
                'security_status' => 'needs_action',
                'last_reviewed_at' => Carbon::now()->subDays(6),
                'next_review_at' => Carbon::now()->addDays(14),
            ],
            [
                'platform' => 'facebook_meta',
                'account_name' => 'Meta Business Brand Manager',
                'username' => 'marketing-lead@shwetatar.com',
                'properties' => [
                    'two_factor_enabled' => false,
                    'recovery_configured' => false,
                    'privileged_admin_access' => true,
                ],
                'security_status' => 'at_risk',
                'last_reviewed_at' => Carbon::now()->subDays(2),
                'next_review_at' => Carbon::now()->addDays(7),
            ],
            [
                'platform' => 'cloudflare',
                'account_name' => 'Cloudflare Edge DNS & WAF',
                'username' => 'dns-admin@shwetatar.com',
                'properties' => [
                    'two_factor_enabled' => true,
                    'recovery_configured' => true,
                    'privileged_admin_access' => true,
                ],
                'security_status' => 'needs_review',
                'last_reviewed_at' => Carbon::now()->subDays(90),
                'next_review_at' => Carbon::now(),
            ],
            [
                'platform' => 'slack',
                'account_name' => 'Slack Corporate Workspace',
                'username' => 'slack-admin@shwetatar.com',
                'properties' => [
                    'two_factor_enabled' => true,
                    'recovery_configured' => true,
                    'sso_enforced' => true,
                ],
                'security_status' => 'compliant',
                'last_reviewed_at' => Carbon::now()->subDays(85),
                'next_review_at' => Carbon::now()->addDays(5),
            ],
            [
                'platform' => 'microsoft_365',
                'account_name' => 'Microsoft 365 Global Tenant',
                'username' => 'm365-admin@shwetatar.com',
                'properties' => [
                    'two_factor_enabled' => true,
                    'recovery_configured' => true,
                    'sso_enforced' => true,
                    'privileged_admin_access' => true,
                ],
                'security_status' => 'compliant',
                'last_reviewed_at' => Carbon::now()->subDays(45),
                'next_review_at' => Carbon::now()->addDays(45),
            ],
        ];

        $accountModels = [];
        foreach ($accountsData as $accData) {
            $platform = $platforms[$accData['platform']];
            $account = SoftwareAccount::firstOrCreate(
                [
                    'software_platform_id' => $platform->id,
                    'username' => $accData['username'],
                ],
                [
                    'account_name' => $accData['account_name'],
                    'properties' => $accData['properties'],
                    'lifecycle_status' => 'active',
                    'security_status' => $accData['security_status'],
                    'last_reviewed_at' => $accData['last_reviewed_at'],
                    'next_review_at' => $accData['next_review_at'],
                    'created_by' => $adminUser->id,
                    'updated_by' => $adminUser->id,
                ]
            );
            $accountModels[$accData['platform']] = $account;
        }

        // Sample Reviews across different statuses and results
        $reviews = [
            // 1. Completed & Compliant (Routine Scheduled Review)
            [
                'account' => $accountModels['google_workspace'],
                'review_type' => 'scheduled',
                'status' => 'completed',
                'scheduled_at' => Carbon::now()->subDays(60)->setTime(9, 30),
                'started_at' => Carbon::now()->subDays(60)->setTime(10, 0),
                'completed_at' => Carbon::now()->subDays(59)->setTime(15, 45),
                'assigned_to' => $auditorUser->id,
                'completed_by' => $auditorUser->id,
                'result' => 'compliant',
                'notes' => 'Q3 Routine Compliance Audit: 2FA hardware tokens verified for all administrative users. Corporate recovery mailbox verified and active. SSO enforcement operational.',
            ],
            // 2. Completed & Compliant (AWS Cloud Audit)
            [
                'account' => $accountModels['aws'],
                'review_type' => 'scheduled',
                'status' => 'completed',
                'scheduled_at' => Carbon::now()->subDays(14)->setTime(14, 0),
                'started_at' => Carbon::now()->subDays(14)->setTime(14, 15),
                'completed_at' => Carbon::now()->subDays(13)->setTime(16, 20),
                'assigned_to' => $itSupportUser->id,
                'completed_by' => $itSupportUser->id,
                'result' => 'compliant',
                'notes' => 'Root account MFA verified via hardware security key (YubiKey). AWS CloudTrail multi-region logging verified and active. Root login alert notifications active.',
            ],
            // 3. Completed & Needs Action (Ad-hoc offboarding/policy check)
            [
                'account' => $accountModels['github'],
                'review_type' => 'ad_hoc',
                'status' => 'completed',
                'scheduled_at' => Carbon::now()->subDays(7)->setTime(11, 0),
                'started_at' => Carbon::now()->subDays(7)->setTime(11, 30),
                'completed_at' => Carbon::now()->subDays(6)->setTime(17, 0),
                'assigned_to' => $auditorUser->id,
                'completed_by' => $auditorUser->id,
                'result' => 'needs_action',
                'notes' => 'Ad-Hoc Access Review: 2 team members without 2FA hardware keys found. Granted 14-day compliance grace period; follow-up audit scheduled.',
            ],
            // 4. Completed & At Risk (Incident Investigation)
            [
                'account' => $accountModels['facebook_meta'],
                'review_type' => 'incident',
                'status' => 'completed',
                'scheduled_at' => Carbon::now()->subDays(3)->setTime(8, 0),
                'started_at' => Carbon::now()->subDays(3)->setTime(8, 15),
                'completed_at' => Carbon::now()->subDays(2)->setTime(12, 0),
                'assigned_to' => $adminUser->id,
                'completed_by' => $adminUser->id,
                'result' => 'at_risk',
                'notes' => 'Security Incident #SEC-2026-08: Suspicious login challenge detected from unrecognized device. Passwords rotated, sessions terminated, pending mandatory hardware token setup.',
            ],
            // 5. In Progress (Currently undergoing review)
            [
                'account' => $accountModels['cloudflare'],
                'review_type' => 'scheduled',
                'status' => 'in_progress',
                'scheduled_at' => Carbon::now()->setTime(10, 0),
                'started_at' => Carbon::now()->subHours(2),
                'completed_at' => null,
                'assigned_to' => $itSupportUser->id,
                'completed_by' => null,
                'result' => null,
                'notes' => 'Auditing DNS zone transfer ACLs, WAF rate-limiting rules, and scoped API token expiration dates.',
            ],
            // 6. Scheduled (Upcoming Routine Review)
            [
                'account' => $accountModels['slack'],
                'review_type' => 'scheduled',
                'status' => 'scheduled',
                'scheduled_at' => Carbon::now()->addDays(2)->setTime(14, 0),
                'started_at' => null,
                'completed_at' => null,
                'assigned_to' => $auditorUser->id,
                'completed_by' => null,
                'result' => null,
                'notes' => 'Quarterly check on multi-workspace guest invitations, bot app permissions, and public channel archive policies.',
            ],
            // 7. Scheduled (Upcoming Ad-Hoc Review)
            [
                'account' => $accountModels['microsoft_365'],
                'review_type' => 'ad_hoc',
                'status' => 'scheduled',
                'scheduled_at' => Carbon::now()->addDays(5)->setTime(15, 30),
                'started_at' => null,
                'completed_at' => null,
                'assigned_to' => $kpiAdminUser->id,
                'completed_by' => null,
                'result' => null,
                'notes' => 'Conditional Access and Privileged Identity Management (PIM) audit prior to annual external certification.',
            ],
            // 8. Scheduled (Future Routine Review)
            [
                'account' => $accountModels['google_workspace'],
                'review_type' => 'scheduled',
                'status' => 'scheduled',
                'scheduled_at' => Carbon::now()->addDays(30)->setTime(10, 0),
                'started_at' => null,
                'completed_at' => null,
                'assigned_to' => $auditorUser->id,
                'completed_by' => null,
                'result' => null,
                'notes' => 'Upcoming Q4 standard routine security compliance verification.',
            ],
        ];

        $createdReviewModels = [];
        foreach ($reviews as $rev) {
            $createdReviewModels[] = SoftwareAccountReview::firstOrCreate(
                [
                    'software_account_id' => $rev['account']->id,
                    'review_type' => $rev['review_type'],
                    'scheduled_at' => $rev['scheduled_at'],
                ],
                [
                    'status' => $rev['status'],
                    'started_at' => $rev['started_at'],
                    'completed_at' => $rev['completed_at'],
                    'assigned_to' => $rev['assigned_to'],
                    'completed_by' => $rev['completed_by'],
                    'result' => $rev['result'],
                    'notes' => $rev['notes'],
                ]
            );
        }

        // Seed sample Action ToDo references for trace demonstration
        $refService = app(\App\Services\Todo\TodoReferenceService::class);

        // 1. Pending Action ToDo for GitHub review (needs_action)
        $githubReview = SoftwareAccountReview::where('result', 'needs_action')->first();
        if ($githubReview && !$githubReview->todoReferences()->exists()) {
            $refService->createReferencedTask(
                referenceModel: $githubReview,
                taskData: [
                    'task' => 'Enforce hardware 2FA key policy for contractor lead',
                    'assigned_user_id' => $itSupportUser->id,
                    'due_date' => Carbon::now()->addDays(7)->toDateTimeString(),
                ],
                reason: 'Ad-Hoc review found 2 contractors lacking mandated 2FA hardware keys.',
                module: 'digital_asset_review',
                actionType: 'remediation',
                creator: $auditorUser
            );
        }

        // 2. Completed Action ToDo for Meta Business review (at_risk)
        $metaReview = SoftwareAccountReview::where('result', 'at_risk')->first();
        if ($metaReview && !$metaReview->todoReferences()->exists()) {
            $metaRef = $refService->createReferencedTask(
                referenceModel: $metaReview,
                taskData: [
                    'task' => 'Emergency credential rotation & active session revocation',
                    'assigned_user_id' => $adminUser->id,
                    'due_date' => Carbon::now()->subDay()->toDateTimeString(),
                ],
                reason: 'Security Incident #SEC-2026-08: Suspicious login challenge detected.',
                module: 'digital_asset_review',
                actionType: 'investigation',
                creator: $adminUser
            );
            $metaRef->markResolved($adminUser->id);
        }

        $this->command?->info("✓ Digital Asset compliance review sample data successfully seeded (" . count($reviews) . " reviews with action ToDo references).");
    }
}
