<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    protected array $permissions = [
        // Software Accounts
        'digital_asset.account.view',
        'digital_asset.account.create',
        'digital_asset.account.update',
        'digital_asset.account.delete',

        // Software Platforms
        'digital_asset.platform.view',
        'digital_asset.platform.create',
        'digital_asset.platform.update',
        'digital_asset.platform.delete',

        // Property Definitions
        'digital_asset.property_definition.view',
        'digital_asset.property_definition.create',
        'digital_asset.property_definition.update',
        'digital_asset.property_definition.delete',

        // Security Properties Values & Approvals
        'digital_asset.property.update',
        'digital_asset.property.approve',

        // Persons In Charge (PICs)
        'digital_asset.pic.manage',

        // Evidence & Files
        'digital_asset.evidence.upload',
        'digital_asset.evidence.verify',
        'digital_asset.evidence.download',

        // Reviews & Audits
        'digital_asset.review.view',
        'digital_asset.review.schedule',
        'digital_asset.review.complete',

        // Devices
        'digital_asset.device.view',
        'digital_asset.device.create',
        'digital_asset.device.update',
        'digital_asset.device.link',

        // Audit Logs
        'digital_asset.audit.view',
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
