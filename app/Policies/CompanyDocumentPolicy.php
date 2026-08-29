<?php

namespace App\Policies;

use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyDocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any company documents.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the company document.
     */
    public function view(?User $user, CompanyDocument $document): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create company documents.
     */
    public function create(User $user): bool
    {
        return $user->can('document.create');
    }

    /**
     * Determine whether the user can update the company document.
     */
    public function update(User $user, ?CompanyDocument $document = null): bool
    {
        return $user->can('document.update');
    }

    /**
     * Determine whether the user can delete the company document.
     */
    public function delete(User $user, ?CompanyDocument $document = null): bool
    {
        return $user->can('document.delete');
    }
}
