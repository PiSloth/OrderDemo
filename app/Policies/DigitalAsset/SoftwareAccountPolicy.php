<?php

namespace App\Policies\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SoftwareAccountPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->suspended) {
            return false;
        }

        if ($user->isSuperUser() || $user->isFromItDepartment()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('account.view') || $user->can('digital_asset.account.view') || $user->isAdmin();
    }

    public function view(User $user, SoftwareAccount $account): bool
    {
        if ($user->can('account.view') || $user->can('digital_asset.account.view') || $user->isAdmin()) {
            return true;
        }

        // Active PIC assigned to this account can view it
        return $account->activePics()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('account.create') || $user->can('digital_asset.account.create') || $user->isAdmin();
    }

    public function update(User $user, SoftwareAccount $account): bool
    {
        if ($user->can('account.edit') || $user->can('account.update') || $user->can('digital_asset.account.update') || $user->isAdmin()) {
            return true;
        }

        return $account->activePics()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, SoftwareAccount $account): bool
    {
        return $user->can('account.delete') || $user->can('digital_asset.account.delete') || $user->can('digital_asset.account.retire') || $user->isAdmin();
    }

    public function managePics(User $user, SoftwareAccount $account): bool
    {
        return $user->can('digital_asset.pic.manage') || $user->isAdmin();
    }

    public function updateProperties(User $user, SoftwareAccount $account): bool
    {
        if ($user->can('digital_asset.property.update') || $user->isAdmin()) {
            return true;
        }

        return $account->activePics()->where('user_id', $user->id)->exists();
    }

    public function approveProperty(User $user, SoftwareAccount $account): bool
    {
        return $user->can('digital_asset.property.approve') || $user->isAdmin();
    }

    public function uploadEvidence(User $user, SoftwareAccount $account): bool
    {
        if ($user->can('digital_asset.evidence.upload') || $user->isAdmin()) {
            return true;
        }

        return $account->activePics()->where('user_id', $user->id)->exists();
    }

    public function verifyEvidence(User $user, SoftwareAccount $account): bool
    {
        return $user->can('digital_asset.evidence.verify') || $user->isAdmin();
    }

    public function scheduleReviews(User $user, SoftwareAccount $account): bool
    {
        return $user->can('digital_asset.review.schedule') || $user->isAdmin();
    }

    public function completeReviews(User $user, SoftwareAccount $account): bool
    {
        return $user->can('digital_asset.review.complete') || $user->isAdmin();
    }

    public function viewAuditLogs(User $user, SoftwareAccount $account): bool
    {
        return $user->can('digital_asset.audit.view') || $user->isAdmin();
    }
}
