<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $permissions = [
            'it.satisfaction.view',
            'it.satisfaction.create',
            'it.satisfaction.update',
            'it.satisfaction.delete',
            'it.satisfaction.export',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Assign to Super Admin / super_admin roles
        $superAdminRoles = Role::whereIn('name', ['Super Admin', 'super_admin', 'Admin', 'admin'])->get();
        foreach ($superAdminRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        // Also assign to any existing users with role 'Super Admin' or 'admin'
        $adminUsers = \App\Models\User::whereIn('role', ['Super Admin', 'super_admin', 'Admin', 'admin'])->get();
        foreach ($adminUsers as $user) {
            try {
                $user->givePermissionTo($permissions);
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        $permissions = [
            'it.satisfaction.view',
            'it.satisfaction.create',
            'it.satisfaction.update',
            'it.satisfaction.delete',
            'it.satisfaction.export',
        ];

        foreach ($permissions as $perm) {
            Permission::where('name', $perm)->delete();
        }
    }
};
