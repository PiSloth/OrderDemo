<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'training.attempt.view', 'guard_name' => 'web']);

        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'Super Admin', 'Admin'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'training.attempt.view')->delete();
    }
};
