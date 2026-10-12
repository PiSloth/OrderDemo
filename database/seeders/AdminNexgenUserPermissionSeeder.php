<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminNexgenUserPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultBranch = Branch::first() ?? Branch::create(['name' => 'HO']);
        $defaultPosition = Position::where('name', 'Super Admin')->first()
            ?? Position::first()
            ?? Position::create(['name' => 'Super Admin']);

        // 1. Target all nexgen and admin test accounts
        $targetEmails = [
            'admin@nexgen.com',
            'admin@nexgen',
            'pos@nexgen.com',
            'admin@shwetatar.com',
        ];

        // Ensure Super Admin roles exist in Spatie
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRoleLower = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Collect all permissions currently registered in the database
        $allPermissions = Permission::all();

        // Give all permissions to the roles
        $superAdminRole->syncPermissions($allPermissions);
        $superAdminRoleLower->syncPermissions($allPermissions);
        $adminRole->syncPermissions($allPermissions);

        foreach ($targetEmails as $email) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Nexgen Admin',
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'suspended' => false,
                    'branch_id' => $defaultBranch->id,
                    'position_id' => $defaultPosition->id,
                    'email_verified_at' => now(),
                ]
            );

            // Ensure role column and active status
            $user->update([
                'role' => 'admin',
                'suspended' => false,
            ]);

            // Assign Super Admin Spatie roles
            $user->syncRoles(['Super Admin', 'super_admin', 'admin']);

            // Directly sync all permissions to guarantee full coverage
            $user->syncPermissions($allPermissions);

            $this->command->info("Granted full permissions to user: {$email} (User ID: {$user->id})");
        }

        $this->command->info("Successfully assigned all permissions and Super Admin access to all target accounts.");
    }
}
