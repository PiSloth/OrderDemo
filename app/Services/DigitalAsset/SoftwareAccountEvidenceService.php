<?php

namespace App\Services\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountEvidence;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SoftwareAccountEvidenceService
{
    protected const DEFAULT_ALLOWED_MIMES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'application/pdf',
    ];

    protected const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10MB

    /**
     * Store and register an evidence file.
     */
    public function uploadEvidence(
        SoftwareAccount $account,
        SoftwarePropertyDefinition $definition,
        UploadedFile $file,
        int $uploadedByUserId,
        ?int $propertyHistoryId = null
    ): SoftwareAccountEvidence {
        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw new InvalidArgumentException("Evidence file exceeds maximum size limit (10MB).");
        }

        $detectedMime = $file->getMimeType();
        $allowedMimes = $definition->evidence_types ?? self::DEFAULT_ALLOWED_MIMES;

        if (!in_array($detectedMime, $allowedMimes, true)) {
            throw new InvalidArgumentException("File type '{$detectedMime}' is not permitted for this evidence.");
        }

        // Calculate SHA-256 integrity hash
        $sha256 = hash_file('sha256', $file->getRealPath());

        // Store privately on 'local' disk
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $path = "evidence/software_accounts/{$account->id}/{$sha256}.{$extension}";
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return DB::transaction(function () use ($account, $definition, $propertyHistoryId, $path, $file, $detectedMime, $sha256, $uploadedByUserId) {
            $evidence = SoftwareAccountEvidence::create([
                'software_account_id' => $account->id,
                'property_definition_id' => $definition->id,
                'property_history_id' => $propertyHistoryId,
                'storage_disk' => 'local',
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $detectedMime,
                'file_size_bytes' => $file->getSize(),
                'sha256_hash' => $sha256,
                'uploaded_by' => $uploadedByUserId,
                'status' => 'pending',
            ]);

            SoftwareAccountAuditService::log(
                action: 'evidence.uploaded',
                entityType: 'SoftwareAccountEvidence',
                entityId: $evidence->id,
                softwareAccountId: $account->id,
                outcome: 'success',
                metadata: [
                    'property_code' => $definition->code,
                    'sha256' => $sha256,
                    'file_size' => $file->getSize(),
                ],
                actorUserId: $uploadedByUserId
            );

            return $evidence;
        });
    }

    /**
     * Verify evidence status (accept or reject).
     */
    public function verifyEvidence(
        SoftwareAccountEvidence $evidence,
        string $status,
        int $verifierUserId,
        ?string $reviewNotes = null
    ): SoftwareAccountEvidence {
        if (!in_array($status, ['accepted', 'rejected'], true)) {
            throw new InvalidArgumentException("Status must be either 'accepted' or 'rejected'.");
        }

        return DB::transaction(function () use ($evidence, $status, $verifierUserId, $reviewNotes) {
            $evidence->update([
                'status' => $status,
                'verified_by' => $verifierUserId,
                'verified_at' => now(),
                'review_notes' => $reviewNotes,
            ]);

            SoftwareAccountAuditService::log(
                action: "evidence.{$status}",
                entityType: 'SoftwareAccountEvidence',
                entityId: $evidence->id,
                softwareAccountId: $evidence->software_account_id,
                outcome: 'success',
                reason: $reviewNotes,
                actorUserId: $verifierUserId
            );

            return $evidence;
        });
    }

    /**
     * Download authorized private evidence.
     */
    public function downloadEvidence(SoftwareAccountEvidence $evidence, int $actorUserId): StreamedResponse
    {
        if (!Storage::disk($evidence->storage_disk)->exists($evidence->file_path)) {
            abort(404, "Evidence file not found in private storage.");
        }

        SoftwareAccountAuditService::log(
            action: 'evidence.downloaded',
            entityType: 'SoftwareAccountEvidence',
            entityId: $evidence->id,
            softwareAccountId: $evidence->software_account_id,
            outcome: 'success',
            metadata: ['filename' => $evidence->original_filename],
            actorUserId: $actorUserId
        );

        return Storage::disk($evidence->storage_disk)->download(
            $evidence->file_path,
            $evidence->original_filename
        );
    }
}
