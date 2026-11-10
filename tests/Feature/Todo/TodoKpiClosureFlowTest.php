<?php

namespace Tests\Feature\Todo;

use App\Livewire\Kpi\MyTasks;
use App\Models\Department;
use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskTemplate;
use App\Models\TaskComment;
use App\Models\TodoList;
use App\Models\TodoStatus;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class TodoKpiClosureFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        Config::set('session.driver', 'array');
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createTestTables();
    }

    protected function createTestTables(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('office_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('office_position_id')->nullable();
            $table->boolean('suspended')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('todo_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('description')->nullable();
            $table->string('color_code')->nullable();
            $table->timestamps();
        });

        Schema::create('todo_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('todo_due_time_id')->nullable();
            $table->unsignedBigInteger('todo_status_id')->nullable();
            $table->string('task');
            $table->dateTime('due_date')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('requested_by_department_id')->nullable();
            $table->unsignedBigInteger('requested_by_branch_id')->nullable();
            $table->unsignedBigInteger('kpi_task_instance_id')->nullable();
            $table->unsignedBigInteger('closed_by_user_id')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('todo_list_id');
            $table->unsignedBigInteger('user_id');
            $table->text('comment');
            $table->string('comment_type')->default('normal');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->json('action_data')->nullable();
            $table->string('action_status')->nullable();
            $table->timestamps();
        });

        Schema::create('task_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('task_comment_id')->nullable();
            $table->unsignedBigInteger('todo_list_id')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('kpi_task_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kpi_group_id')->nullable();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('frequency')->default('on_demand');
            $table->boolean('requires_images')->default(false);
            $table->boolean('requires_table')->default(false);
            $table->boolean('image_remark_required')->default(false);
            $table->integer('min_images')->nullable();
            $table->integer('max_images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('kpi_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_template_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('first_approver_user_id')->nullable();
            $table->unsignedBigInteger('final_approver_user_id')->nullable();
            $table->string('assignment_source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kpi_task_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_assignment_id')->nullable();
            $table->unsignedBigInteger('task_template_id')->nullable();
            $table->unsignedBigInteger('kpi_group_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('todo_list_id')->nullable();
            $table->date('task_date')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_on_time')->default(true);
            $table->string('period_type')->default('todo_on_demand');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->integer('period_index')->default(1);
            $table->integer('required_image_count')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_task_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_instance_id');
            $table->unsignedBigInteger('submitted_by_user_id');
            $table->dateTime('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->integer('sequence')->default(1);
            $table->string('status')->default('submitted');
            $table->text('employee_remark')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_task_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_submission_id');
            $table->integer('step_order')->default(1);
            $table->unsignedBigInteger('approver_user_id')->nullable();
            $table->string('role_label')->nullable();
            $table->string('status')->default('pending');
            $table->dateTime('acted_at')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_task_submission_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_submission_id');
            $table->string('image_path')->nullable();
            $table->string('title')->nullable();
            $table->text('remark')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function test_resolver_requests_closure_and_requester_department_approves_before_kpi_submission(): void
    {
        $requesterDept = Department::create(['name' => 'Operations Dept']);
        $resolverDept = Department::create(['name' => 'IT Support Dept']);
        $staffPositionId = DB::table('positions')->insertGetId(['name' => 'Staff']);

        [$requesterUser, $requesterColleague, $resolverUser, $resolverColleague] = User::withoutEvents(function () use ($requesterDept, $resolverDept, $staffPositionId) {
            return [
                User::create([
                    'name' => 'Requester Creator',
                    'email' => 'requester1@example.com',
                    'role' => 'user',
                    'position_id' => $staffPositionId,
                    'department_id' => $requesterDept->id,
                    'suspended' => false,
                ]),
                User::create([
                    'name' => 'Requester Colleague',
                    'email' => 'requester2@example.com',
                    'role' => 'user',
                    'position_id' => $staffPositionId,
                    'department_id' => $requesterDept->id,
                    'suspended' => false,
                ]),
                User::create([
                    'name' => 'Resolver Assignee',
                    'email' => 'resolver1@example.com',
                    'role' => 'user',
                    'position_id' => $staffPositionId,
                    'department_id' => $resolverDept->id,
                    'suspended' => false,
                ]),
                User::create([
                    'name' => 'Resolver Colleague',
                    'email' => 'resolver2@example.com',
                    'role' => 'user',
                    'position_id' => $staffPositionId,
                    'department_id' => $resolverDept->id,
                    'suspended' => false,
                ]),
            ];
        });

        $openStatus = TodoStatus::create([
            'status' => 'new',
            'description' => 'New task',
            'color_code' => '#3b82f6',
        ]);
        $completedStatus = TodoStatus::create([
            'status' => 'Completed',
            'description' => 'Completed task',
            'color_code' => '#10b981',
        ]);

        $kpiGroup = KpiGroup::create([
            'name' => 'IT SLA Group',
            'department_id' => $resolverDept->id,
            'is_active' => true,
        ]);

        $kpiTemplate = KpiTaskTemplate::create([
            'kpi_group_id' => $kpiGroup->id,
            'title' => 'Resolve Branch Network Ticket',
            'slug' => 'resolve-branch-network-ticket',
            'frequency' => 'on_demand',
            'requires_images' => false,
            'requires_table' => false,
            'is_active' => true,
            'created_by_user_id' => $requesterUser->id,
        ]);

        $kpiAssignment = KpiTaskAssignment::create([
            'task_template_id' => $kpiTemplate->id,
            'user_id' => $resolverUser->id,
            'first_approver_user_id' => $requesterUser->id,
            'final_approver_user_id' => $requesterUser->id,
            'assignment_source' => 'todo_on_demand',
            'is_active' => true,
        ]);

        // Simulate CreateTaskModal behavior where requested_by_department_id was set to resolverDept
        $todoTask = TodoList::create([
            'todo_status_id' => $openStatus->id,
            'task' => 'Fix router configuration at Main Branch',
            'due_date' => now()->addHours(6),
            'assigned_user_id' => $resolverUser->id,
            'created_by_user_id' => $requesterUser->id,
            'requested_by_department_id' => $resolverDept->id,
        ]);

        $kpiInstance = KpiTaskInstance::create([
            'task_assignment_id' => $kpiAssignment->id,
            'task_template_id' => $kpiTemplate->id,
            'kpi_group_id' => $kpiGroup->id,
            'user_id' => $resolverUser->id,
            'task_date' => now()->toDateString(),
            'due_at' => now()->addHours(6),
            'status' => 'pending',
            'is_on_time' => true,
            'period_type' => 'todo_on_demand',
            'period_start' => now()->toDateString(),
            'period_end' => now()->toDateString(),
            'period_index' => 1,
            'todo_list_id' => $todoTask->id,
        ]);

        $todoTask->update(['kpi_task_instance_id' => $kpiInstance->id]);

        // 1. While Todo task is open, resolver CANNOT submit the linked KPI instance
        $this->actingAs($resolverUser);
        Livewire::test(MyTasks::class)
            ->call('submitNoEvidence', $kpiInstance->id)
            ->assertHasErrors(['selectedTaskInstanceId']);

        $this->assertEquals('pending', $kpiInstance->fresh()->status);

        // 2. Requester cannot close the task directly before resolver requests closure
        $this->actingAs($requesterUser)
            ->patch(route('todo.tasks.close', $todoTask->id))
            ->assertSessionHas('error');
        $this->assertNull($todoTask->fresh()->closed_at);

        // 3. Resolver clicks Close / Request Close -> creates a pending status_change action step request
        $this->actingAs($resolverUser)
            ->patch(route('todo.tasks.close', $todoTask->id))
            ->assertSessionHas('message');

        $this->assertNull($todoTask->fresh()->closed_at);

        $pendingComment = TaskComment::where('todo_list_id', $todoTask->id)
            ->where('comment_type', 'action_step')
            ->where('action_status', 'pending')
            ->first();

        $this->assertNotNull($pendingComment);
        $this->assertEquals('status_change', $pendingComment->action_data['type'] ?? null);

        // 4. Resolver (and colleagues in resolver's department) CANNOT approve the closure request
        $this->assertFalse($pendingComment->canUserRespond($resolverUser->id));
        $this->assertFalse($pendingComment->canUserRespond($resolverColleague->id));

        $this->actingAs($resolverColleague)
            ->post(route('todo.comments.respond', $pendingComment->id), ['action' => 'accept'])
            ->assertSessionHas('error');

        $this->assertEquals('pending', $pendingComment->fresh()->action_status);
        $this->assertNull($todoTask->fresh()->closed_at);

        // 5. Users in the same department as the requester user CAN approve the closure request
        $this->assertTrue($pendingComment->canUserRespond($requesterUser->id));
        $this->assertTrue($pendingComment->canUserRespond($requesterColleague->id));

        $this->actingAs($requesterColleague)
            ->post(route('todo.comments.respond', $pendingComment->id), ['action' => 'accept'])
            ->assertSessionHas('message');

        $this->assertEquals('accepted', $pendingComment->fresh()->action_status);
        $todoTask->refresh();
        $this->assertNotNull($todoTask->closed_at);
        $this->assertEquals($requesterColleague->id, $todoTask->closed_by_user_id);
        $this->assertEquals($completedStatus->id, $todoTask->todo_status_id);
        $this->assertTrue($todoTask->isClosed());

        // 6. Now that the Todo task is closed, the resolver CAN submit the KPI instance!
        $this->actingAs($resolverUser);
        Livewire::test(MyTasks::class)
            ->call('submitNoEvidence', $kpiInstance->id)
            ->assertHasNoErrors();

        $this->assertEquals('waiting_first_approval', $kpiInstance->fresh()->status);
    }

    public function test_non_resolver_cannot_request_status_change_and_requester_can_approve_via_close_endpoint(): void
    {
        $requesterDept = Department::create(['name' => 'Finance Dept']);
        $resolverDept = Department::create(['name' => 'Admin Dept']);
        $otherDept = Department::create(['name' => 'Marketing Dept']);
        $staffPositionId = DB::table('positions')->insertGetId(['name' => 'Staff']);

        [$requesterUser, $requesterColleague, $resolverUser, $outsiderUser] = User::withoutEvents(function () use ($requesterDept, $resolverDept, $otherDept, $staffPositionId) {
            return [
                User::create([
                    'name' => 'Requester Finance',
                    'email' => 'req_fin@example.com',
                    'role' => 'user',
                    'position_id' => $staffPositionId,
                    'department_id' => $requesterDept->id,
                    'suspended' => false,
                ]),
                User::create([
                    'name' => 'Requester Finance 2',
                    'email' => 'req_fin2@example.com',
                    'role' => 'user',
                    'position_id' => $staffPositionId,
                    'department_id' => $requesterDept->id,
                    'suspended' => false,
                ]),
                User::create([
                    'name' => 'Resolver Admin',
                    'email' => 'res_adm@example.com',
                    'role' => 'user',
                    'position_id' => $staffPositionId,
                    'department_id' => $resolverDept->id,
                    'suspended' => false,
                ]),
                User::create([
                    'name' => 'Outsider Marketing',
                    'email' => 'outsider@example.com',
                    'role' => 'user',
                    'position_id' => $staffPositionId,
                    'department_id' => $otherDept->id,
                    'suspended' => false,
                ]),
            ];
        });

        $openStatus = TodoStatus::create(['status' => 'new', 'color_code' => '#3b82f6']);
        $completedStatus = TodoStatus::create(['status' => 'Completed', 'color_code' => '#10b981']);

        $todoTask = TodoList::create([
            'todo_status_id' => $openStatus->id,
            'task' => 'Repair air conditioner in Finance office',
            'due_date' => now()->addHours(12),
            'assigned_user_id' => $resolverUser->id,
            'created_by_user_id' => $requesterUser->id,
            'requested_by_department_id' => $resolverDept->id,
        ]);

        // Outsider / non-resolver cannot initiate a status_change action step request
        $this->actingAs($outsiderUser)
            ->post(route('todo.tasks.comments', $todoTask->id), [
                'comment' => 'Requested Status Change to "Completed"',
                'comment_type' => 'action_step',
                'action_data' => [
                    'type' => 'status_change',
                    'original_status_id' => $openStatus->id,
                    'new_status_id' => $completedStatus->id,
                    'new_status_name' => 'Completed',
                ],
            ])
            ->assertSessionHas('error');

        $this->assertEquals(0, TaskComment::where('todo_list_id', $todoTask->id)->count());

        // Resolver submits status_change request via storeComment
        $this->actingAs($resolverUser)
            ->post(route('todo.tasks.comments', $todoTask->id), [
                'comment' => 'Requested Status Change to "Completed"',
                'comment_type' => 'action_step',
                'action_data' => [
                    'type' => 'status_change',
                    'original_status_id' => $openStatus->id,
                    'new_status_id' => $completedStatus->id,
                    'new_status_name' => 'Completed',
                ],
            ])
            ->assertSessionHas('message');

        $pendingComment = TaskComment::where('todo_list_id', $todoTask->id)->first();
        $this->assertNotNull($pendingComment);
        $this->assertEquals('pending', $pendingComment->action_status);

        // Colleague in requester's department approves via close endpoint
        $this->actingAs($requesterColleague)
            ->patch(route('todo.tasks.close', $todoTask->id))
            ->assertSessionHas('message');

        $this->assertEquals('accepted', $pendingComment->fresh()->action_status);
        $this->assertNotNull($todoTask->fresh()->closed_at);
        $this->assertEquals($requesterColleague->id, $todoTask->fresh()->closed_by_user_id);
    }
}
