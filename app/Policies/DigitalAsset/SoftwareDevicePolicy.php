<?php

namespace App\Policies\DigitalAsset;

use App\Models\DigitalAsset\SoftwareDevice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SoftwareDevicePolicy
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
        return $user->can('digital_asset.device.view') || $user->isAdmin();
    }

    public function view(User $user, SoftwareDevice $device): bool
    {
        return $user->can('digital_asset.device.view') || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->can('digital_asset.device.create') || $user->isAdmin();
    }

    public function update(User $user, SoftwareDevice $device): bool
    {
        return $user->can('digital_asset.device.update') || $user->isAdmin();
    }
}
