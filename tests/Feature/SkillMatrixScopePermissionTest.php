<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SkillMatrixScopePermissionTest extends TestCase
{
    use DatabaseTransactions;

    protected Department $deptA;
    protected Department $deptB;
    protected Branch $branch1;
    protected Branch $branch2;

    protected User $userGlobal;
    protected User $userDept;
    protected User $userBranch;
    protected User $userNone;
    protected User $targetDeptA_Branch1;
    protected User $targetDeptA_Branch2;
    protected User $targetDeptB_Branch1;
    protected User $targetDeptB_Branch2;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permissions exist
        Permission::firstOrCreate(['name' => 'global-scope.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'department-scope.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'branch-scope.view', 'guard_name' => 'web']);

        $this->deptA = Department::firstOrCreate(['name' => 'Test Dept A']);
        $this->deptB = Department::firstOrCreate(['name' => 'Test Dept B']);
        $this->branch1 = Branch::firstOrCreate(['name' => 'Test Branch 1']);
        $this->branch2 = Branch::firstOrCreate(['name' => 'Test Branch 2']);

        // Test Targets
        $this->targetDeptA_Branch1 = User::factory()->create([
            'name' => 'Emp DeptA Branch1',
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branch1->id,
            'role' => 'staff',
            'suspended' => false,
        ]);

        $this->targetDeptA_Branch2 = User::factory()->create([
            'name' => 'Emp DeptA Branch2',
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branch2->id,
            'role' => 'staff',
            'suspended' => false,
        ]);

        $this->targetDeptB_Branch1 = User::factory()->create([
            'name' => 'Emp DeptB Branch1',
            'department_id' => $this->deptB->id,
            'branch_id' => $this->branch1->id,
            'role' => 'staff',
            'suspended' => false,
        ]);

        $this->targetDeptB_Branch2 = User::factory()->create([
            'name' => 'Emp DeptB Branch2',
            'department_id' => $this->deptB->id,
            'branch_id' => $this->branch2->id,
            'role' => 'staff',
            'suspended' => false,
        ]);

        // Global scope user
        $this->userGlobal = User::factory()->create([
            'name' => 'Global Scope User',
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branch1->id,
            'role' => 'staff',
            'suspended' => false,
        ]);
        $this->userGlobal->givePermissionTo('global-scope.view');

        // Department scope user (Dept A)
        $this->userDept = User::factory()->create([
            'name' => 'Dept Scope User',
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branch1->id,
            'role' => 'staff',
            'suspended' => false,
        ]);
        $this->userDept->givePermissionTo('department-scope.view');

        // Branch scope user (Branch 1)
        $this->userBranch = User::factory()->create([
            'name' => 'Branch Scope User',
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branch1->id,
            'role' => 'staff',
            'suspended' => false,
        ]);
        $this->userBranch->givePermissionTo('branch-scope.view');

        // User with no skill matrix permissions
        $this->userNone = User::factory()->create([
            'name' => 'No Perm User',
            'department_id' => $this->deptA->id,
            'branch_id' => $this->branch1->id,
            'role' => 'staff',
            'suspended' => false,
        ]);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $response = $this->actingAs($this->userNone)->get('/training/skill-matrix');
        $response->assertStatus(403);

        $responseUsers = $this->actingAs($this->userNone)->getJson('/training/skill-matrix/users');
        $responseUsers->assertStatus(403);

        $responseEmp = $this->actingAs($this->userNone)->getJson("/training/skill-matrix/employees/{$this->targetDeptA_Branch1->id}");
        $responseEmp->assertStatus(403);
    }

    public function test_global_scope_can_view_all_matrix(): void
    {
        $response = $this->actingAs($this->userGlobal)->get('/training/skill-matrix');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Training/SkillMatrix/Index')
                ->where('can_view_all', true)
                ->where('user_scope', 'global')
                ->has('employees')
        );

        $responseUsers = $this->actingAs($this->userGlobal)->getJson('/training/skill-matrix/users');
        $responseUsers->assertStatus(200);
        $userIds = collect($responseUsers->json('users'))->pluck('id')->all();

        $this->assertContains($this->targetDeptA_Branch1->id, $userIds);
        $this->assertContains($this->targetDeptA_Branch2->id, $userIds);
        $this->assertContains($this->targetDeptB_Branch1->id, $userIds);
        $this->assertContains($this->targetDeptB_Branch2->id, $userIds);

        // Can view any employee matrix
        $this->actingAs($this->userGlobal)
            ->getJson("/training/skill-matrix/employees/{$this->targetDeptB_Branch2->id}")
            ->assertStatus(200);
    }

    public function test_department_scope_can_view_only_same_department_persons(): void
    {
        $response = $this->actingAs($this->userDept)->get('/training/skill-matrix');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Training/SkillMatrix/Index')
                ->where('can_view_all', false)
                ->where('user_scope', 'department')
        );

        $responseUsers = $this->actingAs($this->userDept)->getJson('/training/skill-matrix/users');
        $responseUsers->assertStatus(200);
        $userIds = collect($responseUsers->json('users'))->pluck('id')->all();

        // Same department (Dept A)
        $this->assertContains($this->targetDeptA_Branch1->id, $userIds);
        $this->assertContains($this->targetDeptA_Branch2->id, $userIds);

        // Different department (Dept B) should NOT be present
        $this->assertNotContains($this->targetDeptB_Branch1->id, $userIds);
        $this->assertNotContains($this->targetDeptB_Branch2->id, $userIds);

        // Can view employee in same department
        $this->actingAs($this->userDept)
            ->getJson("/training/skill-matrix/employees/{$this->targetDeptA_Branch2->id}")
            ->assertStatus(200);

        // Cannot view employee in different department
        $this->actingAs($this->userDept)
            ->getJson("/training/skill-matrix/employees/{$this->targetDeptB_Branch1->id}")
            ->assertStatus(403);
    }

    public function test_branch_scope_can_view_only_same_branch_persons(): void
    {
        $response = $this->actingAs($this->userBranch)->get('/training/skill-matrix');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Training/SkillMatrix/Index')
                ->where('can_view_all', false)
                ->where('user_scope', 'branch')
        );

        $responseUsers = $this->actingAs($this->userBranch)->getJson('/training/skill-matrix/users');
        $responseUsers->assertStatus(200);
        $userIds = collect($responseUsers->json('users'))->pluck('id')->all();

        // Same branch (Branch 1)
        $this->assertContains($this->targetDeptA_Branch1->id, $userIds);
        $this->assertContains($this->targetDeptB_Branch1->id, $userIds);

        // Different branch (Branch 2) should NOT be present
        $this->assertNotContains($this->targetDeptA_Branch2->id, $userIds);
        $this->assertNotContains($this->targetDeptB_Branch2->id, $userIds);

        // Can view employee in same branch
        $this->actingAs($this->userBranch)
            ->getJson("/training/skill-matrix/employees/{$this->targetDeptB_Branch1->id}")
            ->assertStatus(200);

        // Cannot view employee in different branch
        $this->actingAs($this->userBranch)
            ->getJson("/training/skill-matrix/employees/{$this->targetDeptA_Branch2->id}")
            ->assertStatus(403);
    }
}
