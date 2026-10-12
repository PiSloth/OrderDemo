<?php

namespace App\Console\Commands;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Console\Command;

class CheckAdminUser extends Command
{
    protected $signature = 'check:admin-user';
    protected $description = 'Check nexgen and admin users status and panel access';

    public function handle()
    {
        $panel = Filament::getPanel('admin');
        $users = User::all();

        $this->info("Total users: " . $users->count());

        foreach ($users as $user) {
            $canAccess = $user->canAccessPanel($panel);
            $roles = $user->roles->pluck('name')->implode(', ');
            $hasAdminAccessPerm = $user->can('admin.access') ? 'YES' : 'NO';
            $this->line("ID: {$user->id} | Email: {$user->email} | RoleCol: {$user->role} | Suspended: " . ($user->suspended ? '1' : '0') . " | SpatieRoles: [{$roles}] | can(admin.access): {$hasAdminAccessPerm} | canAccessPanel: " . ($canAccess ? 'YES' : 'NO'));
        }
    }
}
