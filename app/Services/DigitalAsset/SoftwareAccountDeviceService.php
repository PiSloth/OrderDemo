<?php

namespace App\Services\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountDevice;
use App\Models\DigitalAsset\SoftwareDevice;
use App\Models\OfficeAsset;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SoftwareAccountDeviceService
{
    /**
     * Create or register a hardware device.
     */
    public function registerDevice(array $data, int $actorUserId): SoftwareDevice
    {
        $ownership = $data['ownership_type'] ?? 'company';
        if (!in_array($ownership, ['company', 'personal', 'other'], true)) {
            throw new InvalidArgumentException("Invalid ownership type: '{$ownership}'.");
        }

        $fixedAssetId = $data['fixed_asset_id'] ?? null;
        if ($fixedAssetId && !OfficeAsset::where('id', $fixedAssetId)->exists()) {
            throw new InvalidArgumentException("Referenced office fixed asset does not exist.");
        }

        return DB::transaction(function () use ($data, $actorUserId) {
            $device = SoftwareDevice::create([
                'fixed_asset_id' => $data['fixed_asset_id'] ?? null,
                'asset_code_snapshot' => $data['asset_code_snapshot'] ?? null,
                'ownership_type' => $data['ownership_type'] ?? 'company',
                'manufacturer' => $data['manufacturer'] ?? null,
                'model_name' => $data['model_name'] ?? null,
                'model_number' => $data['model_number'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'imei' => $data['imei'] ?? null,
                'hostname' => $data['hostname'] ?? null,
                'operating_system' => $data['operating_system'] ?? null,
                'operating_system_version' => $data['operating_system_version'] ?? null,
                'device_status' => $data['device_status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            SoftwareAccountAuditService::log(
                action: 'device.registered',
                entityType: 'SoftwareDevice',
                entityId: $device->id,
                outcome: 'success',
                metadata: [
                    'ownership_type' => $device->ownership_type,
                    'hostname' => $device->hostname,
                ],
                actorUserId: $actorUserId
            );

            return $device;
        });
    }

    /**
     * Associate a device with a software account.
     */
    public function linkDeviceToAccount(
        SoftwareAccount $account,
        SoftwareDevice $device,
        string $relationshipType = 'registered',
        int $actorUserId = 0
    ): SoftwareAccountDevice {
        if (!in_array($relationshipType, ['authorized', 'observed', 'registered', 'blocked'], true)) {
            throw new InvalidArgumentException("Invalid relationship type: '{$relationshipType}'.");
        }

        return DB::transaction(function () use ($account, $device, $relationshipType, $actorUserId) {
            $link = SoftwareAccountDevice::updateOrCreate(
                [
                    'software_account_id' => $account->id,
                    'software_device_id' => $device->id,
                ],
                [
                    'relationship_type' => $relationshipType,
                    'last_seen_at' => now(),
                    'status' => 'active',
                ]
            );

            if (!$link->first_seen_at) {
                $link->first_seen_at = now();
                $link->save();
            }

            SoftwareAccountAuditService::log(
                action: 'device.linked',
                entityType: 'SoftwareAccountDevice',
                entityId: $link->id,
                softwareAccountId: $account->id,
                outcome: 'success',
                metadata: [
                    'device_id' => $device->id,
                    'relationship_type' => $relationshipType,
                ],
                actorUserId: $actorUserId ?: auth()->id()
            );

            return $link;
        });
    }

    /**
     * Verify and authorize a device relationship.
     */
    public function verifyDeviceLink(
        SoftwareAccountDevice $link,
        int $verifierUserId
    ): SoftwareAccountDevice {
        $link->update([
            'relationship_type' => 'authorized',
            'verified_by' => $verifierUserId,
            'verified_at' => now(),
            'status' => 'active',
        ]);

        SoftwareAccountAuditService::log(
            action: 'device.authorized',
            entityType: 'SoftwareAccountDevice',
            entityId: $link->id,
            softwareAccountId: $link->software_account_id,
            outcome: 'success',
            actorUserId: $verifierUserId
        );

        return $link;
    }
}
