<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'document.create',
            'document.update',
            'document.delete',
        ];

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminCap = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);

            $superAdmin->givePermissionTo($permission);
            $superAdminCap->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'document.create',
            'document.update',
            'document.delete',
        ])->where('guard_name', 'web')->delete();
    }
};
