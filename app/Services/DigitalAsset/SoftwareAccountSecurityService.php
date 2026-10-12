<?php

namespace App\Services\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountPropertyHistory;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use App\Models\EmailList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SoftwareAccountSecurityService
{
    /**
     * Validate an array of properties against active definitions for the account.
     *
     * @param SoftwareAccount $account
     * @param array $properties Key-value pairs of property code => value
     * @return array Normalized and validated properties
     * @throws InvalidArgumentException
     */
    public function validateProperties(SoftwareAccount $account, array $properties): array
    {
        $platformId = $account->software_platform_id;

        // Fetch applicable active definitions (global or platform-specific)
        $definitions = SoftwarePropertyDefinition::where('is_active', true)
            ->where(function ($query) use ($platformId) {
                $query->whereNull('software_platform_id')
                    ->orWhere('software_platform_id', $platformId);
            })
            ->get()
            ->keyBy('code');

        $validated = [];

        foreach ($properties as $code => $value) {
            if (!$definitions->has($code)) {
                throw new InvalidArgumentException("Unknown or unpermitted property code: '{$code}'.");
            }

            /** @var SoftwarePropertyDefinition $def */
            $def = $definitions->get($code);
            $validated[$code] = $this->validateSingleProperty($def, $value);
        }

        return $validated;
    }

    /**
     * Validate a single property value against its definition.
     */
    public function validateSingleProperty(SoftwarePropertyDefinition $def, mixed $value): mixed
    {
        if ($value === null) {
            if ($def->is_required) {
                throw new InvalidArgumentException("Property '{$def->code}' is required.");
            }
            return null;
        }

        switch ($def->data_type) {
            case 'boolean':
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
                    throw new InvalidArgumentException("Property '{$def->code}' must be a boolean.");
                }
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'text':
                if (!is_scalar($value)) {
                    throw new InvalidArgumentException("Property '{$def->code}' must be a text string.");
                }
                $strValue = (string) $value;
                // Reject obvious secrets in Phase 1
                if (preg_match('/^(ghp_|gho_|xoxb-|xoxp-|eyJh|-----BEGIN)/i', $strValue)) {
                    throw new InvalidArgumentException("Actual secrets/tokens are strictly prohibited in Phase 1.");
                }
                return $strValue;

            case 'integer':
                if (!is_numeric($value) || (int) $value != $value) {
                    throw new InvalidArgumentException("Property '{$def->code}' must be an integer.");
                }
                return (int) $value;

            case 'decimal':
                if (!is_numeric($value)) {
                    throw new InvalidArgumentException("Property '{$def->code}' must be a valid number.");
                }
                return (float) $value;

            case 'date':
                if (!strtotime((string) $value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                    throw new InvalidArgumentException("Property '{$def->code}' must be a valid date format (YYYY-MM-DD).");
                }
                return (string) $value;

            case 'datetime':
                if (!strtotime((string) $value)) {
                    throw new InvalidArgumentException("Property '{$def->code}' must be a valid datetime.");
                }
                return date('Y-m-d H:i:s', strtotime((string) $value));

            case 'email_reference':
                if (!is_numeric($value) || !EmailList::where('id', $value)->exists()) {
                    throw new InvalidArgumentException("Property '{$def->code}' must reference a valid email record.");
                }
                return (int) $value;

            case 'single_choice':
                $allowed = $def->validation_rules['options'] ?? [];
                if (!in_array($value, $allowed, true)) {
                    throw new InvalidArgumentException("Property '{$def->code}' value '{$value}' is not among allowed options.");
                }
                return $value;

            case 'multi_choice':
                if (!is_array($value)) {
                    throw new InvalidArgumentException("Property '{$def->code}' must be an array of choices.");
                }
                $allowed = $def->validation_rules['options'] ?? [];
                foreach ($value as $item) {
                    if (!in_array($item, $allowed, true)) {
                        throw new InvalidArgumentException("Option '{$item}' is not allowed for '{$def->code}'.");
                    }
                }
                return array_values($value);

            case 'secret_reference':
                // Phase 1 accepts only safe identifiers / vault references, never plaintext secrets
                $ref = (string) $value;
                if (strlen($ref) > 100 || preg_match('/\s/', $ref)) {
                    throw new InvalidArgumentException("Secret references must be compact identifier strings only.");
                }
                return $ref;

            default:
                throw new InvalidArgumentException("Unsupported property data type: '{$def->data_type}'.");
        }
    }

    /**
     * Atomically update a property value and append to history.
     */
    public function updateProperty(
        SoftwareAccount $account,
        string $propertyCode,
        mixed $newValue,
        int $changedByUserId,
        ?string $changeReason = null
    ): SoftwareAccountPropertyHistory {
        $platformId = $account->software_platform_id;

        $def = SoftwarePropertyDefinition::where('code', $propertyCode)
            ->where('is_active', true)
            ->where(function ($query) use ($platformId) {
                $query->whereNull('software_platform_id')
                    ->orWhere('software_platform_id', $platformId);
            })
            ->firstOrFail();

        $validatedValue = $this->validateSingleProperty($def, $newValue);
        $currentProps = $account->properties ?? [];
        $oldValue = $currentProps[$propertyCode] ?? null;

        $requiresApproval = (bool) $def->requires_approval;
        $reviewStatus = $requiresApproval ? 'pending' : 'not_required';
        $requestId = (string) Str::uuid();

        return DB::transaction(function () use ($account, $def, $oldValue, $validatedValue, $propertyCode, $changedByUserId, $changeReason, $reviewStatus, $requiresApproval, $requestId) {
            $history = SoftwareAccountPropertyHistory::create([
                'software_account_id' => $account->id,
                'property_definition_id' => $def->id,
                'old_value' => $oldValue !== null ? ['value' => $oldValue] : null,
                'new_value' => $validatedValue !== null ? ['value' => $validatedValue] : null,
                'changed_by' => $changedByUserId,
                'changed_at' => now(),
                'change_reason' => $changeReason,
                'review_status' => $reviewStatus,
                'request_id' => $requestId,
            ]);

            // Only apply directly if approval is not required
            if (!$requiresApproval) {
                $props = $account->properties ?? [];
                $props[$propertyCode] = $validatedValue;
                $account->properties = $props;
                $account->updated_by = $changedByUserId;
                $account->save();
            }

            SoftwareAccountAuditService::log(
                action: $requiresApproval ? 'property.change_requested' : 'property.updated',
                entityType: 'SoftwareAccountProperty',
                entityId: $history->id,
                softwareAccountId: $account->id,
                outcome: 'success',
                reason: $changeReason,
                metadata: [
                    'property_code' => $propertyCode,
                    'requires_approval' => $requiresApproval,
                    'review_status' => $reviewStatus,
                ],
                actorUserId: $changedByUserId
            );

            return $history;
        });
    }

    /**
     * Approve a pending property change.
     */
    public function approvePropertyChange(
        SoftwareAccountPropertyHistory $history,
        int $reviewerUserId,
        ?string $notes = null
    ): void {
        if ($history->review_status !== 'pending') {
            throw new InvalidArgumentException("Property change is not in pending status.");
        }

        DB::transaction(function () use ($history, $reviewerUserId, $notes) {
            $history->update([
                'review_status' => 'approved',
            ]);

            $account = $history->account;
            $code = $history->propertyDefinition->code;
            $newValue = $history->new_value['value'] ?? null;

            $props = $account->properties ?? [];
            $props[$code] = $newValue;
            $account->properties = $props;
            $account->updated_by = $reviewerUserId;
            $account->save();

            SoftwareAccountAuditService::log(
                action: 'property.change_approved',
                entityType: 'SoftwareAccountPropertyHistory',
                entityId: $history->id,
                softwareAccountId: $account->id,
                outcome: 'success',
                reason: $notes,
                metadata: ['property_code' => $code],
                actorUserId: $reviewerUserId
            );
        });
    }

    /**
     * Reject a pending property change.
     */
    public function rejectPropertyChange(
        SoftwareAccountPropertyHistory $history,
        int $reviewerUserId,
        string $reason
    ): void {
        if ($history->review_status !== 'pending') {
            throw new InvalidArgumentException("Property change is not in pending status.");
        }

        DB::transaction(function () use ($history, $reviewerUserId, $reason) {
            $history->update([
                'review_status' => 'rejected',
                'change_reason' => $history->change_reason ? $history->change_reason . " (Rejected: {$reason})" : "Rejected: {$reason}",
            ]);

            SoftwareAccountAuditService::log(
                action: 'property.change_rejected',
                entityType: 'SoftwareAccountPropertyHistory',
                entityId: $history->id,
                softwareAccountId: $history->software_account_id,
                outcome: 'success',
                reason: $reason,
                metadata: ['property_code' => $history->propertyDefinition->code],
                actorUserId: $reviewerUserId
            );
        });
    }
}
