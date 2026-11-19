<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    protected array $permissions = [
        'customer.view',
        'customer.export',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $adminRoles = Role::whereIn('name', ['Super Admin', 'super_admin', 'Admin', 'admin'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($this->permissions);
        }

        $adminUsers = \App\Models\User::whereIn('role', ['Super Admin', 'super_admin', 'Admin', 'admin'])->get();
        foreach ($adminUsers as $user) {
            try {
                $user->givePermissionTo($this->permissions);
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->where('guard_name', 'web')->delete();
    }
};
