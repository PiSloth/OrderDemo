<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountEmail;
use App\Models\DigitalAsset\SoftwareAccountPropertyHistory;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use App\Models\EmailList;
use App\Models\User;
use App\Services\DigitalAsset\SoftwareAccountAuditService;
use App\Services\DigitalAsset\SoftwareAccountPicService;
use App\Services\DigitalAsset\SoftwareAccountSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SoftwareAccountController extends Controller
{
    public function __construct(
        protected SoftwareAccountSecurityService $securityService,
        protected SoftwareAccountPicService $picService
    ) {}

    /**
     * Display accounts catalog with filters and summary metrics.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SoftwareAccount::class);

        $query = SoftwareAccount::with(['platform', 'activePics.user', 'emails.emailRecord']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('platform_id')) {
            $query->where('software_platform_id', $request->input('platform_id'));
        }

        if ($request->filled('lifecycle_status')) {
            $query->where('lifecycle_status', $request->input('lifecycle_status'));
        }

        if ($request->filled('security_status')) {
            $query->where('security_status', $request->input('security_status'));
        }

        $accounts = $query->orderBy('account_name')->paginate(15)->withQueryString();

        $platforms = SoftwarePlatform::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);

        $stats = [
            'total_accounts' => SoftwareAccount::count(),
            'active_accounts' => SoftwareAccount::where('lifecycle_status', 'active')->count(),
            'compliant_accounts' => SoftwareAccount::where('security_status', 'compliant')->count(),
            'at_risk_accounts' => SoftwareAccount::where('security_status', 'at_risk')->count(),
            'needs_review_accounts' => SoftwareAccount::where(function ($q) {
                $q->where('security_status', 'needs_review')
                  ->orWhere('next_review_at', '<=', now());
            })->count(),
        ];

        return Inertia::render('DigitalAsset/Accounts/Index', [
            'accounts' => $accounts,
            'platforms' => $platforms,
            'filters' => $request->only(['search', 'platform_id', 'lifecycle_status', 'security_status']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show detailed software account profile, security properties, PICs, and evidence.
     */
    public function show(SoftwareAccount $account): Response
    {
        $this->authorize('view', $account);

        $account->load([
            'platform',
            'creator',
            'updater',
            'emails.emailRecord',
            'pics.user',
            'pics.revokedByUser',
            'activePics.user',
            'picHistories' => fn($q) => $q->latest('occurred_at'),
            'picHistories.user',
            'picHistories.performedByUser',
            'propertyHistories.propertyDefinition',
            'propertyHistories.changedByUser',
            'evidence.propertyDefinition',
            'evidence.uploadedByUser',
            'evidence.verifiedByUser',
            'reviews.assignedToUser',
            'reviews.completedByUser',
            'accountDevices.device.fixedAsset',
            'authorizationGrants',
            'auditLogs' => fn($q) => $q->latest('occurred_at')->limit(100),
            'auditLogs.actorUser',
        ]);

        $platformId = $account->software_platform_id;
        $definitions = SoftwarePropertyDefinition::where('is_active', true)
            ->where(function ($query) use ($platformId) {
                $query->whereNull('software_platform_id')
                    ->orWhere('software_platform_id', $platformId);
            })
            ->orderBy('sort_order')
            ->get();

        $availableUsers = User::with(['department', 'branch', 'position', 'officePosition'])
            ->where('suspended', false)
            ->orderBy('name')
            ->get();
        $departments = \App\Models\Department::orderBy('name')->get(['id', 'name']);
        $availableEmails = EmailList::orderBy('email')->get(['id', 'email', 'user_name']);
        $availableDevices = \App\Models\DigitalAsset\SoftwareDevice::with('fixedAsset')
            ->where('device_status', 'active')
            ->orderBy('hostname')
            ->get();
        $officeAssets = \App\Models\OfficeAsset::orderBy('name')->get(['id', 'name', 'cost']);

        return Inertia::render('DigitalAsset/Accounts/Show', [
            'account' => $account,
            'definitions' => $definitions,
            'availableUsers' => $availableUsers,
            'departments' => $departments,
            'availableEmails' => $availableEmails,
            'availableDevices' => $availableDevices,
            'officeAssets' => $officeAssets,
        ]);
    }

    /**
     * Create a new software account.
     */
    public function store(Request $request)
    {
        $this->authorize('create', SoftwareAccount::class);

        $validated = $request->validate([
            'software_platform_id' => 'required|exists:software_platforms,id',
            'account_name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'properties' => 'nullable|array',
            'initial_email_id' => 'nullable|exists:email_lists,id',
            'initial_email_purpose' => 'nullable|string|in:registration,recovery,contact,notification,other',
            'initial_pic_user_id' => 'nullable|exists:users,id',
        ]);

        $platform = SoftwarePlatform::findOrFail($validated['software_platform_id']);
        if ($platform->status !== 'active') {
            return back()->withErrors(['software_platform_id' => 'Cannot create account for an inactive platform.']);
        }

        $account = DB::transaction(function () use ($validated, $platform) {
            $rawProps = $validated['properties'] ?? [];
            
            // Dummy account instance for property validation
            $tempAccount = new SoftwareAccount(['software_platform_id' => $platform->id]);
            $validatedProps = $this->securityService->validateProperties($tempAccount, $rawProps);

            $account = SoftwareAccount::create([
                'software_platform_id' => $platform->id,
                'account_name' => $validated['account_name'],
                'username' => $validated['username'],
                'properties' => $validatedProps,
                'lifecycle_status' => 'active',
                'security_status' => 'unknown',
                'created_by' => auth()->id(),
            ]);

            // Link initial email if provided
            if (!empty($validated['initial_email_id'])) {
                SoftwareAccountEmail::create([
                    'software_account_id' => $account->id,
                    'email_id' => $validated['initial_email_id'],
                    'purpose' => $validated['initial_email_purpose'] ?? 'registration',
                    'is_primary' => true,
                    'status' => 'active',
                ]);
            }

            // Assign initial PIC if specified
            if (!empty($validated['initial_pic_user_id'])) {
                $this->picService->assignPic($account, $validated['initial_pic_user_id'], auth()->id(), 'Initial PIC on account creation');
            }

            SoftwareAccountAuditService::log(
                action: 'account.created',
                entityType: 'SoftwareAccount',
                entityId: $account->id,
                softwareAccountId: $account->id,
                outcome: 'success',
                metadata: [
                    'platform' => $platform->name,
                    'account_name' => $account->account_name,
                    'username' => $account->username,
                ],
                actorUserId: auth()->id()
            );

            return $account;
        });

        return redirect()->route('digital-assets.accounts.show', $account->id)
            ->with('success', 'Software account registered successfully.');
    }

    /**
     * Update account core information.
     */
    public function update(Request $request, SoftwareAccount $account)
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'lifecycle_status' => 'required|string|in:active,suspended,retired',
        ]);

        $oldLifecycle = $account->lifecycle_status;
        $account->update([
            'account_name' => $validated['account_name'],
            'username' => $validated['username'],
            'lifecycle_status' => $validated['lifecycle_status'],
            'updated_by' => auth()->id(),
        ]);

        SoftwareAccountAuditService::log(
            action: 'account.updated',
            entityType: 'SoftwareAccount',
            entityId: $account->id,
            softwareAccountId: $account->id,
            outcome: 'success',
            metadata: [
                'old_lifecycle' => $oldLifecycle,
                'new_lifecycle' => $validated['lifecycle_status'],
            ],
            actorUserId: auth()->id()
        );

        return back()->with('success', 'Account details updated.');
    }

    /**
     * Atomically update a single property value.
     */
    public function updateProperty(Request $request, SoftwareAccount $account)
    {
        $this->authorize('updateProperties', $account);

        $validated = $request->validate([
            'property_code' => 'required|string',
            'value' => 'nullable',
            'change_reason' => 'nullable|string|max:500',
        ]);

        try {
            $history = $this->securityService->updateProperty(
                account: $account,
                propertyCode: $validated['property_code'],
                newValue: $validated['value'],
                changedByUserId: auth()->id(),
                changeReason: $validated['change_reason']
            );

            $msg = $history->review_status === 'pending'
                ? 'Property change submitted for security approval.'
                : 'Security property updated successfully.';

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withErrors(['value' => $e->getMessage()]);
        }
    }

    /**
     * Approve a pending property update.
     */
    public function approveProperty(Request $request, SoftwareAccountPropertyHistory $history)
    {
        $this->authorize('approveProperty', $history->account);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->securityService->approvePropertyChange($history, auth()->id(), $validated['notes'] ?? null);
            return back()->with('success', 'Property change approved and applied.');
        } catch (\Throwable $e) {
            return back()->withErrors(['approval' => $e->getMessage()]);
        }
    }

    /**
     * Reject a pending property update.
     */
    public function rejectProperty(Request $request, SoftwareAccountPropertyHistory $history)
    {
        $this->authorize('approveProperty', $history->account);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->securityService->rejectPropertyChange($history, auth()->id(), $validated['reason']);
            return back()->with('success', 'Property change rejected.');
        } catch (\Throwable $e) {
            return back()->withErrors(['rejection' => $e->getMessage()]);
        }
    }

    /**
     * Link an email address to this software account.
     */
    public function linkEmail(Request $request, SoftwareAccount $account)
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'email_id' => 'required|exists:email_lists,id',
            'purpose' => 'required|string|in:registration,recovery,contact,notification,other',
            'is_primary' => 'boolean',
        ]);

        // Check if primary is requested, unset previous primary
        if (!empty($validated['is_primary'])) {
            SoftwareAccountEmail::where('software_account_id', $account->id)
                ->where('status', 'active')
                ->update(['is_primary' => false]);
        }

        SoftwareAccountEmail::create([
            'software_account_id' => $account->id,
            'email_id' => $validated['email_id'],
            'purpose' => $validated['purpose'],
            'is_primary' => $validated['is_primary'] ?? false,
            'status' => 'active',
        ]);

        SoftwareAccountAuditService::log(
            action: 'email.linked',
            entityType: 'SoftwareAccountEmail',
            softwareAccountId: $account->id,
            outcome: 'success',
            metadata: ['email_id' => $validated['email_id'], 'purpose' => $validated['purpose']],
            actorUserId: auth()->id()
        );

        return back()->with('success', 'Email associated with account.');
    }
}
