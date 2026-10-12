<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminNexgenUserPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminNexgenPermissionSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_nexgen_user_has_full_permissions(): void
    {
        $this->seed(AdminNexgenUserPermissionSeeder::class);

        $user = User::where('email', 'admin@nexgen.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('admin', $user->role);
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->isSuperUser());

        // Check essential Digital Asset CRUD permissions
        $this->assertTrue($user->can('digital_asset.account.view'));
        $this->assertTrue($user->can('digital_asset.account.create'));
        $this->assertTrue($user->can('digital_asset.account.update'));
        $this->assertTrue($user->can('digital_asset.account.delete'));

        $this->assertTrue($user->can('digital_asset.platform.view'));
        $this->assertTrue($user->can('digital_asset.platform.create'));
        $this->assertTrue($user->can('digital_asset.platform.update'));
        $this->assertTrue($user->can('digital_asset.platform.delete'));

        $this->assertTrue($user->can('digital_asset.property_definition.view'));
        $this->assertTrue($user->can('digital_asset.property_definition.create'));
        $this->assertTrue($user->can('digital_asset.property_definition.update'));
        $this->assertTrue($user->can('digital_asset.property_definition.delete'));

        $this->assertTrue($user->can('digital_asset.property.update'));
        $this->assertTrue($user->can('digital_asset.property.approve'));

        $this->assertTrue($user->can('digital_asset.pic.manage'));
        $this->assertTrue($user->can('digital_asset.evidence.upload'));
        $this->assertTrue($user->can('digital_asset.evidence.verify'));
        $this->assertTrue($user->can('digital_asset.review.schedule'));
        $this->assertTrue($user->can('digital_asset.review.complete'));
        $this->assertTrue($user->can('digital_asset.device.view'));
        $this->assertTrue($user->can('digital_asset.device.create'));
        $this->assertTrue($user->can('digital_asset.audit.view'));

        // Check alias admin@nexgen as well
        $aliasUser = User::where('email', 'admin@nexgen')->first();
        $this->assertNotNull($aliasUser);
        $this->assertTrue($aliasUser->can('digital_asset.account.view'));
    }
}
