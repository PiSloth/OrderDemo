<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountDevice;
use App\Models\DigitalAsset\SoftwareDevice;
use App\Models\OfficeAsset;
use App\Services\DigitalAsset\SoftwareAccountDeviceService;
use Illuminate\Http\Request;
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
                  ->orWhere('model_name', 'like', "%{$search}%");
            });
        }

        $devices = $query->orderBy('hostname')->paginate(15)->withQueryString();

        $officeAssets = OfficeAsset::orderBy('name')->get(['id', 'name', 'cost']);
        $accounts = SoftwareAccount::where('lifecycle_status', 'active')->orderBy('account_name')->get(['id', 'account_name', 'username']);

        return Inertia::render('DigitalAsset/Devices/Index', [
            'devices' => $devices,
            'officeAssets' => $officeAssets,
            'accounts' => $accounts,
            'filters' => $request->only(['ownership_type', 'device_status', 'search']),
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
            'serial_number' => 'nullable|string|max:150',
            'imei' => 'nullable|string|max:30',
            'hostname' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:100',
            'operating_system_version' => 'nullable|string|max:100',
            'device_status' => 'required|in:active,lost,retired,blocked,unknown',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->deviceService->registerDevice($validated, auth()->id());
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
        ]);

        $device = SoftwareDevice::findOrFail($validated['software_device_id']);

        try {
            $this->deviceService->linkDeviceToAccount(
                account: $account,
                device: $device,
                relationshipType: $validated['relationship_type'],
                actorUserId: auth()->id()
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
}
