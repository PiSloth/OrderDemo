<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\OfficePosition;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentRbacCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $permRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $permPos = Position::firstOrCreate(['name' => 'Super Admin']);
        $branch = Branch::firstOrCreate(['name' => 'HQ Branch']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin Tester',
            'email' => 'superadmin.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $permPos->id,
            'branch_id' => $branch->id,
        ]);

        $this->superAdmin->assignRole($permRole);
    }

    public function test_admin_can_access_user_resource_list_and_create_pages(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/users');
        $response->assertStatus(200);

        $responseCreate = $this->actingAs($this->superAdmin)->get('/admin/users/create');
        $responseCreate->assertStatus(200);
    }

    public function test_admin_can_access_permission_resource_list_and_create_pages(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/permissions');
        $response->assertStatus(200);

        $responseCreate = $this->actingAs($this->superAdmin)->get('/admin/permissions/create');
        $responseCreate->assertStatus(200);
    }

    public function test_admin_can_access_role_resource_pages(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/shield/roles');
        $response->assertStatus(200);
    }

    public function test_can_create_and_assign_role_and_permission_to_user(): void
    {
        $role = Role::create(['name' => 'editor_' . uniqid(), 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'publish_post_' . uniqid(), 'guard_name' => 'web']);

        $role->givePermissionTo($permission);
        $this->assertTrue($role->hasPermissionTo($permission->name));

        $permPos = Position::firstOrCreate(['name' => 'General Staff']);
        $branch = Branch::firstOrCreate(['name' => 'HQ Branch']);

        $user = User::create([
            'name' => 'Editor User',
            'email' => 'editor.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $permPos->id,
            'branch_id' => $branch->id,
        ]);

        $user->assignRole($role);
        $this->assertTrue($user->hasRole($role->name));
        $this->assertTrue($user->hasPermissionTo($permission->name));
    }

    public function test_user_with_admin_access_permission_can_access_admin_panel(): void
    {
        $permPos = Position::firstOrCreate(['name' => 'General Staff']);
        $branch = Branch::firstOrCreate(['name' => 'HQ Branch']);

        $user = User::create([
            'name' => 'Staff User With Admin Access',
            'email' => 'staff.admin.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $permPos->id,
            'branch_id' => $branch->id,
        ]);

        $permission = Permission::firstOrCreate(['name' => 'admin.access', 'guard_name' => 'web']);
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_user_without_admin_access_permission_is_forbidden_from_admin_panel(): void
    {
        $permPos = Position::firstOrCreate(['name' => 'General Staff']);
        $branch = Branch::firstOrCreate(['name' => 'HQ Branch']);

        $user = User::create([
            'name' => 'Regular User Without Admin Access',
            'email' => 'regular.user.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $permPos->id,
            'branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_suspended_user_is_forbidden_even_with_admin_access(): void
    {
        $permPos = Position::firstOrCreate(['name' => 'General Staff']);
        $branch = Branch::firstOrCreate(['name' => 'HQ Branch']);

        $user = User::create([
            'name' => 'Suspended Staff User',
            'email' => 'suspended.staff.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $permPos->id,
            'branch_id' => $branch->id,
            'suspended' => true,
        ]);

        $permission = Permission::firstOrCreate(['name' => 'admin.access', 'guard_name' => 'web']);
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }
}
