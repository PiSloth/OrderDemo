<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'training-session.read',
            'training-session.create',
            'training-session.update',
            'training-session.delete',
            'training.session.read',
            'training.session.view',
            'training.session.create',
            'training.session.update',
            'training.session.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'training-session.read',
            'training-session.create',
            'training-session.update',
            'training-session.delete',
            'training.session.read',
            'training.session.view',
            'training.session.create',
            'training.session.update',
            'training.session.delete',
        ])->delete();
    }
};
