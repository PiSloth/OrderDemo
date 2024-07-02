<?php

namespace App\Providers;

use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('isSuperAdmin', function(User $user) {
            return $user->position->name == "Super Admin";
        });

        Gate::define('isAGM', function(User $user) {
            return $user->position->name == "AGM";
        });

        Gate::define('isSupplierDataApprover', function(User $user) {
            $authorizedUsers = ["Super Admin", "AGM"];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isInventory', function(User $user) {
            return $user->position->name == "Inventory";
        });

        Gate::define('isPurchaser', function(User $user) {
            $authorizedUsers = ["Purchaser", "Super Admin"];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isBranchSupervisor', function(User $user) {
            return $user->position->name == "Branch Supervisor";
        });

        Gate::define('isGuest', function(User $user) {
            return $user->position->name == "Guest";
        });

        Gate::define('isAuthorizedToEndMeeting', function(User $user) {
            $authorizedUsers = ["Super Admin", "AGM", "Inventory", "Purchaser"];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isAuthorized', function(User $user) {
            $authorizedUsers = ["Super Admin", "AGM", "Inventory", "Purchaser", "Branch Supervisor"];

            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });
    }
}
