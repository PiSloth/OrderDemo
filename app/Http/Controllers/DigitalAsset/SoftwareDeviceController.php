<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountDevice;
use App\Models\DigitalAsset\SoftwareDevice;
use App\Models\OfficeAsset;
use App\Services\DigitalAsset\SoftwareAccountDeviceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SoftwareDeviceController extends Controller
{
    public function __construct(protected SoftwareAccountDeviceService $deviceService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SoftwareDevice::class);

        $query = SoftwareDevice::with(['fixedAsset', 'accountDevices.account.platform']);

        if ($request->filled('ownership_type')) {
            $query->where('ownership_type', $request->input('ownership_type'));
        }

        if ($request->filled('device_status')) {
            $query->where('device_status', $request->input('device_status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('hostname', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('model_name', 'like', "%{$search}%")
                  ->orWhere('model_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $devices = $query->orderBy('hostname')->paginate(15)->withQueryString();

        $officeAssets = OfficeAsset::orderBy('name')->get(['id', 'name', 'cost']);
        $accounts = SoftwareAccount::where('lifecycle_status', 'active')->orderBy('account_name')->get(['id', 'account_name', 'username']);

        $stats = [
            'total' => SoftwareDevice::count(),
            'company' => SoftwareDevice::where('ownership_type', 'company')->count(),
            'personal' => SoftwareDevice::where('ownership_type', 'personal')->count(),
            'active' => SoftwareDevice::where('device_status', 'active')->count(),
        ];

        return Inertia::render('DigitalAsset/Devices/Index', [
            'devices' => $devices,
            'officeAssets' => $officeAssets,
            'accounts' => $accounts,
            'filters' => $request->only(['ownership_type', 'device_status', 'search']),
            'stats' => $stats,
        ]);

    }

    public function store(Request $request)
    {
        $this->authorize('create', SoftwareDevice::class);

        $validated = $request->validate([
            'fixed_asset_id' => 'nullable|exists:office_assets,id',
            'ownership_type' => 'required|in:company,personal,other',
            'manufacturer' => 'nullable|string|max:100',
            'model_name' => 'nullable|string|max:150',
            'model_number' => 'nullable|string|max:150',
            'serial_number' => 'required|string|max:150|unique:software_devices,serial_number',
            'imei' => 'nullable|string|max:30',
            'hostname' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:100',
            'operating_system_version' => 'nullable|string|max:100',
            'device_status' => 'required|in:active,lost,retired,blocked,unknown',
            'notes' => 'nullable|string|max:1000',
            'link_account_id' => 'nullable|exists:software_accounts,id',
            'relationship_type' => 'nullable|in:authorized,observed,registered,blocked',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            $device = $this->deviceService->registerDevice($validated, auth()->id());

            if (!empty($validated['link_account_id'])) {
                $account = SoftwareAccount::findOrFail($validated['link_account_id']);
                $this->deviceService->linkDeviceToAccount(
                    account: $account,
                    device: $device,
                    relationshipType: $validated['relationship_type'] ?? 'authorized',
                    actorUserId: auth()->id(),
                    userId: $validated['user_id'] ?? null
                );
            }

            return back()->with('success', 'Device registered in inventory.');
        } catch (\Throwable $e) {
            return back()->withErrors(['device' => $e->getMessage()]);
        }
    }

    public function link(Request $request, SoftwareAccount $account)
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'software_device_id' => 'required|exists:software_devices,id',
            'relationship_type' => 'required|in:authorized,observed,registered,blocked',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $device = SoftwareDevice::findOrFail($validated['software_device_id']);

        try {
            $this->deviceService->linkDeviceToAccount(
                account: $account,
                device: $device,
                relationshipType: $validated['relationship_type'],
                actorUserId: auth()->id(),
                userId: $validated['user_id'] ?? null
            );

            return back()->with('success', 'Device linked to software account.');
        } catch (\Throwable $e) {
            return back()->withErrors(['link' => $e->getMessage()]);
        }
    }

    public function verifyLink(SoftwareAccountDevice $link)
    {
        $this->authorize('update', $link->account);

        try {
            $this->deviceService->verifyDeviceLink($link, auth()->id());
            return back()->with('success', 'Device relationship authorized.');
        } catch (\Throwable $e) {
            return back()->withErrors(['verify' => $e->getMessage()]);
        }
    }

    public function update(Request $request, SoftwareDevice $device)
    {
        $this->authorize('update', $device);

        $validated = $request->validate([
            'fixed_asset_id' => 'nullable|exists:office_assets,id',
            'ownership_type' => 'required|in:company,personal,other',
            'manufacturer' => 'nullable|string|max:100',
            'model_name' => 'nullable|string|max:150',
            'model_number' => 'nullable|string|max:150',
            'serial_number' => [
                'required',
                'string',
                'max:150',
                Rule::unique('software_devices', 'serial_number')->ignore($device->id),
            ],
            'imei' => 'nullable|string|max:30',
            'hostname' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:100',
            'operating_system_version' => 'nullable|string|max:100',
            'device_status' => 'required|in:active,lost,retired,blocked,unknown',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->deviceService->updateDevice($device, $validated, auth()->id());

            return back()->with('success', 'Device updated successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['device' => $e->getMessage()]);
        }
    }

    public function destroy(SoftwareDevice $device)
    {
        $this->authorize('delete', $device);

        try {
            $this->deviceService->deleteDevice($device, auth()->id());

            return back()->with('success', 'Device removed from inventory.');
        } catch (\Throwable $e) {
            return back()->withErrors(['device' => $e->getMessage()]);
        }
    }

    public function unlink(SoftwareAccountDevice $link)
    {
        $this->authorize('update', $link->account);

        try {
            $this->deviceService->unlinkDeviceFromAccount($link, auth()->id());

            return back()->with('success', 'Device unlinked from account.');
        } catch (\Throwable $e) {
            return back()->withErrors(['link' => $e->getMessage()]);
        }
    }
}

