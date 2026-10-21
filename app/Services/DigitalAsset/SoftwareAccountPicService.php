<?php

namespace App\Services\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountPic;
use App\Models\DigitalAsset\SoftwareAccountPicHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SoftwareAccountPicService
{
    /**
     * Assign a user as PIC to a software account.
     */
    public function assignPic(
        SoftwareAccount $account,
        int $userId,
        int $actorUserId,
        ?string $reason = null
    ): SoftwareAccountPic {
        $user = User::findOrFail($userId);
        if ($user->suspended) {
            throw new InvalidArgumentException("Suspended users cannot be assigned as PIC.");
        }

        // Prevent duplicate active assignment
        $existingActive = SoftwareAccountPic::where('software_account_id', $account->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();

        if ($existingActive) {
            throw new InvalidArgumentException("User is already an active PIC for this software account.");
        }

        return DB::transaction(function () use ($account, $userId, $actorUserId, $reason) {
            $pic = SoftwareAccountPic::create([
                'software_account_id' => $account->id,
                'user_id' => $userId,
                'status' => 'active',
                'assigned_at' => now(),
            ]);

            SoftwareAccountPicHistory::create([
                'software_account_pic_id' => $pic->id,
                'software_account_id' => $account->id,
                'user_id' => $userId,
                'action' => 'assigned',
                'performed_by' => $actorUserId,
                'reason' => $reason,
                'occurred_at' => now(),
            ]);

            SoftwareAccountAuditService::log(
                action: 'pic.assigned',
                entityType: 'SoftwareAccountPic',
                entityId: $pic->id,
                softwareAccountId: $account->id,
                outcome: 'success',
                reason: $reason,
                metadata: ['assigned_user_id' => $userId],
                actorUserId: $actorUserId
            );

            return $pic;
        });
    }

    /**
     * Revoke / Inactivate an active PIC assignment.
     */
    public function revokePic(
        SoftwareAccountPic $pic,
        int $actorUserId,
        ?string $reason = null
    ): SoftwareAccountPic {
        if ($pic->status !== 'active') {
            throw new InvalidArgumentException("Only active PIC assignments can be set to inactive.");
        }

        $reasonText = $reason ?: 'Status changed to inactive';

        return DB::transaction(function () use ($pic, $actorUserId, $reasonText) {
            $pic->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revoked_by' => $actorUserId,
                'revocation_reason' => $reasonText,
            ]);

            SoftwareAccountPicHistory::create([
                'software_account_pic_id' => $pic->id,
                'software_account_id' => $pic->software_account_id,
                'user_id' => $pic->user_id,
                'action' => 'inactivated',
                'performed_by' => $actorUserId,
                'reason' => $reasonText,
                'occurred_at' => now(),
            ]);

            $picUser = User::find($pic->user_id);
            $actorUser = User::find($actorUserId);

            SoftwareAccountAuditService::log(
                action: 'pic.inactivated',
                entityType: 'SoftwareAccountPic',
                entityId: $pic->id,
                softwareAccountId: $pic->software_account_id,
                outcome: 'success',
                reason: $reasonText,
                metadata: [
                    'pic_user_id' => $pic->user_id,
                    'pic_user_name' => $picUser?->name,
                    'new_status' => 'inactive',
                    'changed_by_user_id' => $actorUserId,
                    'changed_by_user_name' => $actorUser?->name,
                ],
                actorUserId: $actorUserId
            );

            return $pic;
        });
    }

    /**
     * Reactivate a previously revoked PIC assignment.
     */
    public function reactivatePic(
        SoftwareAccountPic $pic,
        int $actorUserId,
        ?string $reason = null
    ): SoftwareAccountPic {
        if ($pic->status !== 'revoked') {
            throw new InvalidArgumentException("Only revoked PIC assignments can be reactivated.");
        }

        $user = User::findOrFail($pic->user_id);
        if ($user->suspended) {
            throw new InvalidArgumentException("Cannot reactivate a suspended user.");
        }

        // Ensure no other active assignment exists for this user and account
        $hasActive = SoftwareAccountPic::where('software_account_id', $pic->software_account_id)
            ->where('user_id', $pic->user_id)
            ->where('status', 'active')
            ->exists();

        if ($hasActive) {
            throw new InvalidArgumentException("An active assignment already exists for this user.");
        }

        $reasonText = $reason ?: 'Reactivated by user';

        return DB::transaction(function () use ($pic, $actorUserId, $reasonText) {
            $pic->update([
                'status' => 'active',
                'assigned_at' => now(),
                'revoked_at' => null,
                'revoked_by' => null,
                'revocation_reason' => null,
            ]);

            SoftwareAccountPicHistory::create([
                'software_account_pic_id' => $pic->id,
                'software_account_id' => $pic->software_account_id,
                'user_id' => $pic->user_id,
                'action' => 'reactivated',
                'performed_by' => $actorUserId,
                'reason' => $reasonText,
                'occurred_at' => now(),
            ]);

            $picUser = User::find($pic->user_id);
            $actorUser = User::find($actorUserId);

            SoftwareAccountAuditService::log(
                action: 'pic.reactivated',
                entityType: 'SoftwareAccountPic',
                entityId: $pic->id,
                softwareAccountId: $pic->software_account_id,
                outcome: 'success',
                reason: $reasonText,
                metadata: [
                    'reactivated_user_id' => $pic->user_id,
                    'reactivated_user_name' => $picUser?->name,
                    'new_status' => 'active',
                    'changed_by_user_id' => $actorUserId,
                    'changed_by_user_name' => $actorUser?->name,
                ],
                actorUserId: $actorUserId
            );

            return $pic;
        });
    }
}
