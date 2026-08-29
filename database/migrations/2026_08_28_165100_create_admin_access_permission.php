<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'admin.access', 'guard_name' => 'web']);

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminCap = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $superAdmin->givePermissionTo($permission);
        $superAdminCap->givePermissionTo($permission);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'admin.access')->where('guard_name', 'web')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
