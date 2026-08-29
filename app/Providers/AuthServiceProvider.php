<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \Spatie\Permission\Models\Role::class => \App\Policies\RolePolicy::class,
        \Spatie\Permission\Models\Permission::class => \App\Policies\PermissionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Implicitly grant "super_admin" / "Super Admin" role all permissions
        Gate::before(function ($user, $ability) {
            return ($user instanceof User && ($user->hasRole('super_admin') || $user->hasRole('Super Admin'))) ? true : null;
        });

        Gate::define('isIT', function( $user){
            return optional($user->position)->name == "IT";
        });

        Gate::define('manageOperationTitles', function (User $user) {
            return $user->isAdmin() || optional($user->position)->name === 'IT';
        });

        Gate::define('training.catalog.create', function (User $user) {
            return $user->hasPermissionTo('training.catalog.create') || $user->hasPermissionTo('training.catalog.crate');
        });

        Gate::define('training.catalog.crate', function (User $user) {
            return $user->hasPermissionTo('training.catalog.create') || $user->hasPermissionTo('training.catalog.crate');
        });
    }
}
