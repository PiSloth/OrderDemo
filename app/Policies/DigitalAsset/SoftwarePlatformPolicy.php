<?php

namespace App\Policies\DigitalAsset;

use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SoftwarePlatformPolicy
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
        return $user->can('digital_asset.platform.view') || $user->isAdmin();
    }

    public function view(User $user, SoftwarePlatform $platform): bool
    {
        return $user->can('digital_asset.platform.view') || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->can('digital_asset.platform.create') || $user->isAdmin();
    }

    public function update(User $user, SoftwarePlatform $platform): bool
    {
        return $user->can('digital_asset.platform.update') || $user->isAdmin();
    }

    public function delete(User $user, SoftwarePlatform $platform): bool
    {
        return $user->can('digital_asset.platform.delete') || $user->isAdmin();
    }
}
