<?php

namespace App\Providers;

use App\Models\Position;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(ShareDataComposer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('isSuperAdmin', function (User $user) {
            return $user->position->name == "Super Admin";
        });

        Gate::define('isAGM', function (User $user) {


            $authorizedUsers = ["Super Admin", "AGM"];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isSupplierDataApprover', function (User $user) {
            $authorizedUsers = ["Super Admin", "AGM", "Inventory"];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isInventory', function (User $user) {
            // return $user->position->name == "Inventory";
            $authorizedUsers = ["Inventory", "Super Admin"];

            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isPurchaser', function (User $user) {
            $authorizedUsers = ["Purchaser", "Super Admin", 'Inventory'];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isBranchSupervisor', function (User $user) {
            // return $user->position->name == "";

            $authorizedUsers = ["Branch Supervisor", "Super Admin"];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isGuest', function (User $user) {
            return $user->position->name == "Guest";
        });

        Gate::define('isAuthorizedToEndMeeting', function (User $user) {
            $authorizedUsers = ["Super Admin", "AGM", "Inventory", "Purchaser"];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isAuthorized', function (User $user) {
            $authorizedUsers = ["Super Admin", "AGM", "Inventory", "Purchaser", "Branch Supervisor"];

            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isCreator', function (User $user) {
            $authorizedUsers = ["Super Admin", "AGM", "Inventory", "Purchaser", "Branch Supervisor"];

            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isCanceller', function (User $user) {
            $authorizedUsers = ["Super Admin", "AGM", "Inventory", "Purchaser"];

            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isSupplierDataCreator', function (User $user) {
            $authorizedUsers = ["Super Admin", "Inventory", "Purchaser"];

            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isQuotationViewer', function (User $user) {
            $authorizedUsers = ["Super Admin", "Inventory", "AGM", "Purchaser"];

            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isOrderApprover', function (User $user) {
            $authorizedUsers = ["Super Admin", "Inventory", "AGM", 'Inventory'];

            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isOrderMaker', function (User $user) {
            $authorizedUsers = ["Super Admin", "Purchaser", 'Inventory'];

            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isAllCommentReader', function (User $user) {
            $authorizedUsers = ["Super Admin", "Purchaser", "AGM", "Inventory"];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });

        Gate::define('isMarketing', function (User $user) {
            $authorizedUsers = ["Super Admin", "Marketing"];
            $usr = $user->position->name;

            return in_array($usr, $authorizedUsers);
        });
    }
}
