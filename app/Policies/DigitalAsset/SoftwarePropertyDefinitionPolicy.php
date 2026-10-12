<?php

namespace App\Policies\DigitalAsset;

use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SoftwarePropertyDefinitionPolicy
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
        return $user->can('digital_asset.property_definition.view') || $user->isAdmin();
    }

    public function view(User $user, SoftwarePropertyDefinition $definition): bool
    {
        return $user->can('digital_asset.property_definition.view') || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->can('digital_asset.property_definition.create') || $user->isAdmin();
    }

    public function update(User $user, SoftwarePropertyDefinition $definition): bool
    {
        return $user->can('digital_asset.property_definition.update') || $user->isAdmin();
    }

    public function delete(User $user, SoftwarePropertyDefinition $definition): bool
    {
        return $user->can('digital_asset.property_definition.delete') || $user->isAdmin();
    }
}
