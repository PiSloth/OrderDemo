<?php

namespace App\Services\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccountAuditLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class SoftwareAccountAuditService
{
    /**
     * Keys that should never be stored in metadata or audit logs.
     */
    protected const SECRET_KEYS = [
        'password',
        'passwd',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'private_key',
        'recovery_code',
        'recovery_codes',
        'client_secret',
        'auth_code',
        'pin',
    ];

    /**
     * Record an audit event.
     */
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?int $softwareAccountId = null,
        string $outcome = 'success',
        ?string $reason = null,
        ?array $metadata = null,
        ?int $actorUserId = null
    ): SoftwareAccountAuditLog {
        $actorId = $actorUserId ?? auth()->id();
        $sanitizedMetadata = $metadata ? self::sanitizeMetadata($metadata) : null;

        return SoftwareAccountAuditLog::create([
            'software_account_id' => $softwareAccountId,
            'actor_user_id' => $actorId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'outcome' => $outcome,
            'reason' => $reason,
            'request_id' => (string) Str::uuid(),
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => substr(Request::userAgent() ?? 'System', 0, 500),
            'metadata' => $sanitizedMetadata,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Recursively sanitize any metadata to ensure secrets are never stored.
     */
    public static function sanitizeMetadata(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSecret = false;
            foreach (self::SECRET_KEYS as $secretKey) {
                if (str_contains($lowerKey, $secretKey)) {
                    $isSecret = true;
                    break;
                }
            }

            if ($isSecret) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeMetadata($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
