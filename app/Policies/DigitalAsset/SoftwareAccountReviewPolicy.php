<?php

namespace App\Policies\DigitalAsset;

use App\Models\DigitalAsset\SoftwareAccountReview;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SoftwareAccountReviewPolicy
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
        return $user->can('digital_asset.review.view') || $user->isAdmin();
    }

    public function view(User $user, SoftwareAccountReview $review): bool
    {
        if ($user->can('digital_asset.review.view') || $user->isAdmin()) {
            return true;
        }

        return $review->assigned_to === $user->id || $review->account->activePics()->where('user_id', $user->id)->exists();
    }

    public function schedule(User $user): bool
    {
        return $user->can('digital_asset.review.schedule') || $user->isAdmin();
    }

    public function complete(User $user, SoftwareAccountReview $review): bool
    {
        if ($user->can('digital_asset.review.complete') || $user->isAdmin()) {
            return true;
        }

        return $review->assigned_to === $user->id;
    }
}
