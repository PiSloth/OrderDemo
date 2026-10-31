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
        return $user->can('security_property_definition.view')
            || $user->can('property_definition.view')
            || $user->can('security_property.view')
            || $user->can('digital_asset.property_definition.view')
            || $user->isAdmin();
    }

    public function view(User $user, SoftwarePropertyDefinition $definition): bool
    {
        return $user->can('security_property_definition.view')
            || $user->can('property_definition.view')
            || $user->can('security_property.view')
            || $user->can('digital_asset.property_definition.view')
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->can('security_property_definition.create')
            || $user->can('property_definition.create')
            || $user->can('security_property.create')
            || $user->can('digital_asset.property_definition.create')
            || $user->isAdmin();
    }

    public function update(User $user, SoftwarePropertyDefinition $definition): bool
    {
        return $user->can('security_property_definition.update')
            || $user->can('security_property_definition.edit')
            || $user->can('property_definition.update')
            || $user->can('property_definition.edit')
            || $user->can('security_property.update')
            || $user->can('security_property.edit')
            || $user->can('digital_asset.property_definition.update')
            || $user->isAdmin();
    }

    public function delete(User $user, SoftwarePropertyDefinition $definition): bool
    {
        return $user->can('security_property_definition.delete')
            || $user->can('property_definition.delete')
            || $user->can('security_property.delete')
            || $user->can('digital_asset.property_definition.delete')
            || $user->isAdmin();
    }
}
