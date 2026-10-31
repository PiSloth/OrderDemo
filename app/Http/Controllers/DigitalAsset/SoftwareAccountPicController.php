<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountPic;
use App\Models\DigitalAsset\SoftwareDevice;
use App\Services\DigitalAsset\SoftwareAccountDeviceService;
use App\Services\DigitalAsset\SoftwareAccountPicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SoftwareAccountPicController extends Controller
{
    public function __construct(
        protected SoftwareAccountPicService $picService,
        protected SoftwareAccountDeviceService $deviceService
    ) {}

    public function assign(Request $request, SoftwareAccount $account)
    {
        $this->authorize('managePics', $account);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500',
            'associate_device' => 'nullable|boolean',
            'device_mode' => 'nullable|in:select,create',
            'software_device_id' => 'nullable|exists:software_devices,id',
            'relationship_type' => 'nullable|in:authorized,observed,registered,blocked',
            'ownership_type' => 'nullable|in:company,personal,other',
            'fixed_asset_id' => 'nullable|exists:office_assets,id',
            'hostname' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:100',
            'model_name' => 'nullable|string|max:150',
            'serial_number' => [
                $request->boolean('associate_device') && $request->input('device_mode') === 'create' ? 'required' : 'nullable',
                'string',
                'max:150',
                Rule::unique('software_devices', 'serial_number'),
            ],
            'operating_system' => 'nullable|string|max:100',
            'device_status' => 'nullable|in:active,lost,retired,blocked,unknown',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($account, $validated) {
                $this->picService->assignPic(
                    account: $account,
                    userId: $validated['user_id'],
                    actorUserId: auth()->id(),
                    reason: $validated['reason'] ?? null
                );

                if (!empty($validated['associate_device'])) {
                    $relationshipType = $validated['relationship_type'] ?? 'authorized';
                    $deviceMode = $validated['device_mode'] ?? 'select';

                    if ($deviceMode === 'select' && !empty($validated['software_device_id'])) {
                        $device = SoftwareDevice::findOrFail($validated['software_device_id']);
                        $this->deviceService->linkDeviceToAccount(
                            account: $account,
                            device: $device,
                            relationshipType: $relationshipType,
                            actorUserId: auth()->id(),
                            userId: $validated['user_id']
                        );
                    } elseif ($deviceMode === 'create' && (!empty($validated['hostname']) || !empty($validated['model_name']))) {
                        $device = $this->deviceService->registerDevice([
                            'ownership_type' => $validated['ownership_type'] ?? 'company',
                            'fixed_asset_id' => $validated['fixed_asset_id'] ?? null,
                            'hostname' => $validated['hostname'] ?? null,
                            'manufacturer' => $validated['manufacturer'] ?? null,
                            'model_name' => $validated['model_name'] ?? null,
                            'serial_number' => $validated['serial_number'] ?? null,
                            'operating_system' => $validated['operating_system'] ?? null,
                            'device_status' => $validated['device_status'] ?? 'active',
                            'notes' => $validated['notes'] ?? null,
                        ], auth()->id());

                        $this->deviceService->linkDeviceToAccount(
                            account: $account,
                            device: $device,
                            relationshipType: $relationshipType,
                            actorUserId: auth()->id(),
                            userId: $validated['user_id']
                        );
                    }
                }
            });

            return back()->with('success', 'Person in Charge (PIC) assigned and device associated successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['user_id' => $e->getMessage()]);
        }
    }

    public function revoke(Request $request, SoftwareAccountPic $pic)
    {
        $this->authorize('managePics', $pic->account);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->picService->revokePic(
                pic: $pic,
                actorUserId: auth()->id(),
                reason: $validated['reason'] ?? null
            );

            return back()->with('success', 'PIC set to inactive successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['reason' => $e->getMessage()]);
        }
    }

    public function reactivate(Request $request, SoftwareAccountPic $pic)
    {
        $this->authorize('managePics', $pic->account);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->picService->reactivatePic(
                pic: $pic,
                actorUserId: auth()->id(),
                reason: $validated['reason'] ?? null
            );

            return back()->with('success', 'PIC assignment reactivated successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['reactivate' => $e->getMessage()]);
        }
    }
}
