<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    protected array $permissions = [
        'global-scope.view',
        'department-scope.view',
        'branch-scope.view',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Grant all scope permissions to Super Admin and Admin roles
        $adminRoles = Role::whereIn('name', ['Super Admin', 'super_admin', 'Admin', 'admin'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($this->permissions);
        }

        // Grant to users with admin / super_admin role string
        $adminUsers = \App\Models\User::whereIn('role', ['Super Admin', 'super_admin', 'Admin', 'admin'])->get();
        foreach ($adminUsers as $user) {
            try {
                $user->givePermissionTo($this->permissions);
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->where('guard_name', 'web')->delete();
    }
};
