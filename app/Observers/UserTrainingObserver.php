<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Training\TrainingAssignmentService;

class UserTrainingObserver
{
    public function created(User $user): void
    {
        if ($user->department_id) {
            app(TrainingAssignmentService::class)->assignNewUser($user);
        }
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged(['department_id', 'office_position_id']) && $user->department_id) {
            app(TrainingAssignmentService::class)->assignNewUser($user);
        }
    }
}
