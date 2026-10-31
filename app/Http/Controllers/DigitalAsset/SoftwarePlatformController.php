<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Services\DigitalAsset\SoftwareAccountAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SoftwarePlatformController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SoftwarePlatform::class);

        $platforms = SoftwarePlatform::withCount([
            'accounts',
            'accounts as active_accounts_count' => function ($q) {
                $q->where('lifecycle_status', 'active');
            }
        ])
        ->orderBy('name')
        ->get();

        $categories = [
            'email',
            'social_media',
            'source_control',
            'cloud',
            'business_app',
            'communication',
            'other',
        ];

        return Inertia::render('DigitalAsset/Platforms/Index', [
            'platforms' => $platforms,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SoftwarePlatform::class);

        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:software_platforms,code',
            'name' => 'required|string|max:150',
            'category' => 'nullable|string|max:50',
            'status' => 'required|string|in:active,inactive',
        ]);

        $platform = SoftwarePlatform::create($validated);

        SoftwareAccountAuditService::log(
            action: 'platform.created',
            entityType: 'SoftwarePlatform',
            entityId: $platform->id,
            outcome: 'success',
            metadata: ['name' => $platform->name, 'code' => $platform->code],
            actorUserId: auth()->id()
        );

        return back()->with('success', 'Software platform created successfully.');
    }

    public function update(Request $request, SoftwarePlatform $platform)
    {
        $this->authorize('update', $platform);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('software_platforms', 'code')->ignore($platform->id)],
            'name' => 'required|string|max:150',
            'category' => 'nullable|string|max:50',
            'status' => 'required|string|in:active,inactive',
        ]);

        // Prevent retirement if active accounts exist
        $activeAccountsCount = $platform->accounts()->where('lifecycle_status', 'active')->count();
        if ($validated['status'] === 'inactive' && $activeAccountsCount > 0) {
            return back()->withErrors([
                'status' => "Cannot deactivate platform: {$activeAccountsCount} active account(s) are still associated with this platform. Please retire or reassign them first."
            ]);
        }

        $platform->update($validated);

        SoftwareAccountAuditService::log(
            action: 'platform.updated',
            entityType: 'SoftwarePlatform',
            entityId: $platform->id,
            outcome: 'success',
            metadata: ['code' => $platform->code, 'name' => $platform->name, 'status' => $platform->status],
            actorUserId: auth()->id()
        );

        return back()->with('success', 'Software platform updated successfully.');
    }
}
