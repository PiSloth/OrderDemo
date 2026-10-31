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

        if (empty($data['serial_number'])) {
            throw new InvalidArgumentException("Serial number is required.");
        }

        if (SoftwareDevice::where('serial_number', $data['serial_number'])->exists()) {
            throw new InvalidArgumentException("A device with serial number '{$data['serial_number']}' already exists.");
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
        int $actorUserId = 0,
        ?int $userId = null
    ): SoftwareAccountDevice {
        if (!in_array($relationshipType, ['authorized', 'observed', 'registered', 'blocked'], true)) {
            throw new InvalidArgumentException("Invalid relationship type: '{$relationshipType}'.");
        }

        return DB::transaction(function () use ($account, $device, $relationshipType, $actorUserId, $userId) {
            $link = SoftwareAccountDevice::updateOrCreate(
                [
                    'software_account_id' => $account->id,
                    'software_device_id' => $device->id,
                ],
                [
                    'user_id' => $userId,
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

    /**
     * Update an existing hardware device.
     */
    public function updateDevice(SoftwareDevice $device, array $data, int $actorUserId): SoftwareDevice
    {
        $ownership = $data['ownership_type'] ?? $device->ownership_type;
        if (!in_array($ownership, ['company', 'personal', 'other'], true)) {
            throw new InvalidArgumentException("Invalid ownership type: '{$ownership}'.");
        }

        $fixedAssetId = array_key_exists('fixed_asset_id', $data) ? $data['fixed_asset_id'] : $device->fixed_asset_id;
        if ($fixedAssetId && !OfficeAsset::where('id', $fixedAssetId)->exists()) {
            throw new InvalidArgumentException("Referenced office fixed asset does not exist.");
        }

        if (array_key_exists('serial_number', $data)) {
            if (empty($data['serial_number'])) {
                throw new InvalidArgumentException("Serial number is required.");
            }
            if (SoftwareDevice::where('serial_number', $data['serial_number'])->where('id', '!=', $device->id)->exists()) {
                throw new InvalidArgumentException("A device with serial number '{$data['serial_number']}' already exists.");
            }
        }

        return DB::transaction(function () use ($device, $data, $actorUserId) {
            $device->update([
                'fixed_asset_id' => array_key_exists('fixed_asset_id', $data) ? $data['fixed_asset_id'] : $device->fixed_asset_id,
                'asset_code_snapshot' => $data['asset_code_snapshot'] ?? $device->asset_code_snapshot,
                'ownership_type' => $data['ownership_type'] ?? $device->ownership_type,
                'manufacturer' => $data['manufacturer'] ?? $device->manufacturer,
                'model_name' => $data['model_name'] ?? $device->model_name,
                'model_number' => $data['model_number'] ?? $device->model_number,
                'serial_number' => $data['serial_number'] ?? $device->serial_number,
                'imei' => $data['imei'] ?? $device->imei,
                'hostname' => $data['hostname'] ?? $device->hostname,
                'operating_system' => $data['operating_system'] ?? $device->operating_system,
                'operating_system_version' => $data['operating_system_version'] ?? $device->operating_system_version,
                'device_status' => $data['device_status'] ?? $device->device_status,
                'notes' => $data['notes'] ?? $device->notes,
            ]);

            SoftwareAccountAuditService::log(
                action: 'device.updated',
                entityType: 'SoftwareDevice',
                entityId: $device->id,
                outcome: 'success',
                metadata: [
                    'ownership_type' => $device->ownership_type,
                    'hostname' => $device->hostname,
                    'device_status' => $device->device_status,
                ],
                actorUserId: $actorUserId
            );

            return $device;
        });
    }

    /**
     * Delete a hardware device and clean up associations.
     */
    public function deleteDevice(SoftwareDevice $device, int $actorUserId): void
    {
        DB::transaction(function () use ($device, $actorUserId) {
            $deviceId = $device->id;
            $hostname = $device->hostname;

            // Remove linked account associations
            $device->accountDevices()->delete();

            $device->delete();

            SoftwareAccountAuditService::log(
                action: 'device.deleted',
                entityType: 'SoftwareDevice',
                entityId: $deviceId,
                outcome: 'success',
                metadata: [
                    'hostname' => $hostname,
                ],
                actorUserId: $actorUserId
            );
        });
    }

    /**
     * Unlink a device from a software account.
     */
    public function unlinkDeviceFromAccount(SoftwareAccountDevice $link, int $actorUserId): void
    {
        DB::transaction(function () use ($link, $actorUserId) {
            $accountId = $link->software_account_id;
            $deviceId = $link->software_device_id;
            $linkId = $link->id;

            $link->delete();

            SoftwareAccountAuditService::log(
                action: 'device.unlinked',
                entityType: 'SoftwareAccountDevice',
                entityId: $linkId,
                softwareAccountId: $accountId,
                outcome: 'success',
                metadata: [
                    'device_id' => $deviceId,
                ],
                actorUserId: $actorUserId
            );
        });
    }
}

