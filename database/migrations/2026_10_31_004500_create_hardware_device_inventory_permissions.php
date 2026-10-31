<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    protected array $permissions = [
        'hardware_device_inventory.view',
        'hardware_device_inventory.create',
        'hardware_device_inventory.update',
        'hardware_device_inventory.edit',
        'hardware_device_inventory.delete',
        'digital_asset.device.view',
        'digital_asset.device.create',
        'digital_asset.device.update',
        'digital_asset.device.delete',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Grant all permissions to Super Admin / Admin roles
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
