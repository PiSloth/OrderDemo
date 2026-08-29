<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'training.catalog.view',
            'training.catalog.create',
            'training.catalog.crate',
            'training.catalog.update',
            'training.catalog.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'training.catalog.view',
            'training.catalog.create',
            'training.catalog.crate',
            'training.catalog.update',
            'training.catalog.delete',
        ])->delete();
    }
};
