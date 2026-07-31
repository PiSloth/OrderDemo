<?php

namespace Tests\Unit\Services;

use App\Models\Department;
use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskRule;
use App\Models\Kpi\KpiTaskTemplate;
use App\Models\User;
use App\Services\Kpi\KpiTaskTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KpiTaskTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KpiTaskTemplateService $service;
    protected User $user;
    protected KpiGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new KpiTaskTemplateService();

        $position = DB::table('positions')->first();
        $positionId = $position ? $position->id : DB::table('positions')->insertGetId(['name' => 'Manager', 'created_at' => now(), 'updated_at' => now()]);

        $branch = DB::table('branches')->first();
        $branchId = $branch ? $branch->id : DB::table('branches')->insertGetId(['name' => 'HQ', 'created_at' => now(), 'updated_at' => now()]);

        $location = DB::table('locations')->first();
        $locationId = $location ? $location->id : DB::table('locations')->insertGetId(['name' => 'Main Office', 'created_at' => now(), 'updated_at' => now()]);

        $department = Department::first() ?: Department::create(['name' => 'IT Operations']);

        $this->user = User::forceCreate([
            'name' => 'Test Admin',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => 'password',
            'position_id' => $positionId,
            'branch_id' => $branchId,
            'department_id' => $department->id,
            'location_id' => $locationId,
        ]);

        $this->group = KpiGroup::create([
            'department_id' => $department->id,
            'name' => 'IT Group',
            'rule_type' => 'pass_percentage',
            'target_percentage' => 80.00,
            'is_active' => true,
        ]);
    }

    public function test_can_create_template_with_performance_rule(): void
    {
        $payload = [
            'kpi_group_id' => $this->group->id,
            'title' => 'Daily Server Backup Checklist',
            'description' => 'Daily check of offsite backup servers.',
            'guideline' => 'Run automated script and check log.',
            'frequency' => 'daily',
            'monthly_required_count' => 30,
            'cutoff_time' => '18:00',
            'requires_images' => true,
            'min_images' => 1,
            'max_images' => 3,
            'is_active' => true,
            'rule_type' => 'pass_percentage',
            'target_percentage' => 95.00,
        ];

        $template = $this->service->createTemplate($payload, $this->user->id);

        $this->assertDatabaseHas('kpi_task_templates', [
            'id' => $template->id,
            'title' => 'Daily Server Backup Checklist',
            'kpi_group_id' => $this->group->id,
        ]);

        $this->assertDatabaseHas('kpi_task_rules', [
            'task_template_id' => $template->id,
            'rule_type' => 'pass_percentage',
            'target_percentage' => 95.00,
        ]);
    }

    public function test_cannot_modify_rule_or_group_if_previous_month_instances_exist(): void
    {
        $template = $this->service->createTemplate([
            'kpi_group_id' => $this->group->id,
            'title' => 'Weekly System Maintenance',
            'frequency' => 'weekly',
            'monthly_required_count' => 4,
            'requires_images' => false,
            'min_images' => 0,
            'is_active' => true,
            'rule_type' => 'pass_percentage',
            'target_percentage' => 80.00,
        ], $this->user->id);

        $assignment = KpiTaskAssignment::create([
            'task_template_id' => $template->id,
            'user_id' => $this->user->id,
            'starts_on' => '2026-06-01',
            'is_active' => true,
        ]);

        // Create historical task instance from last month
        $instance = KpiTaskInstance::create([
            'task_assignment_id' => $assignment->id,
            'task_template_id' => $template->id,
            'kpi_group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'period_type' => 'weekly',
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'task_date' => '2026-06-01',
            'status' => 'approved',
            'submitted_at' => '2026-06-01 10:00:00',
            'finalized_at' => '2026-06-01 10:00:00',
            'created_at' => '2026-06-01 10:00:00',
        ]);

        $updatePayload = [
            'kpi_group_id' => $this->group->id,
            'title' => 'Weekly System Maintenance',
            'frequency' => 'weekly',
            'monthly_required_count' => 4,
            'min_images' => 0,
            'is_active' => true,
            'rule_type' => 'pass_percentage',
            'target_percentage' => 99.00, // Changing target percentage!
        ];

        $this->assertTrue($this->service->isRuleOrGroupChanging($template, $updatePayload), 'isRuleOrGroupChanging must return true');
        $this->assertTrue($this->service->hasPreviousMonthInstances($template), 'hasPreviousMonthInstances must return true');

        $this->expectException(ValidationException::class);
        $this->service->updateTemplate($template, $updatePayload);
    }

    public function test_updating_cutoff_time_syncs_pending_current_month_instances_without_touching_closed_instances(): void
    {
        $template = $this->service->createTemplate([
            'kpi_group_id' => $this->group->id,
            'title' => 'Morning Inventory Audit',
            'frequency' => 'daily',
            'monthly_required_count' => 30,
            'cutoff_time' => '10:00',
            'requires_images' => false,
            'min_images' => 0,
            'is_active' => true,
            'rule_type' => 'pass_percentage',
            'target_percentage' => 80.00,
        ], $this->user->id);

        $assignment1 = KpiTaskAssignment::create([
            'task_template_id' => $template->id,
            'user_id' => $this->user->id,
            'starts_on' => now()->startOfMonth(),
            'is_active' => true,
        ]);

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Pending current-month instance
        $pendingInstance = KpiTaskInstance::create([
            'task_assignment_id' => $assignment1->id,
            'task_template_id' => $template->id,
            'kpi_group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'period_type' => 'daily',
            'period_start' => $today,
            'period_end' => $today,
            'task_date' => $today,
            'status' => 'pending',
            'due_at' => now()->setTime(10, 0, 0),
        ]);

        // Closed current-month instance from yesterday
        $closedInstance = KpiTaskInstance::create([
            'task_assignment_id' => $assignment1->id,
            'task_template_id' => $template->id,
            'kpi_group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'period_type' => 'daily',
            'period_start' => $yesterday,
            'period_end' => $yesterday,
            'task_date' => $yesterday,
            'status' => 'approved',
            'submitted_at' => now(),
            'finalized_at' => now(),
            'due_at' => now()->subDay()->setTime(10, 0, 0),
        ]);

        $updatePayload = [
            'kpi_group_id' => $this->group->id,
            'title' => 'Morning Inventory Audit',
            'frequency' => 'daily',
            'monthly_required_count' => 30,
            'cutoff_time' => '12:30', // Updated cutoff time
            'min_images' => 0,
            'is_active' => true,
            'rule_type' => 'pass_percentage',
            'target_percentage' => 80.00,
        ];

        $this->service->updateTemplate($template, $updatePayload);

        // Pending instance due_at is updated
        $pendingInstance->refresh();
        $this->assertEquals('12:30:00', $pendingInstance->due_at->toTimeString());

        // Closed instance due_at remains untouched
        $closedInstance->refresh();
        $this->assertEquals('10:00:00', $closedInstance->due_at->toTimeString());
    }

    public function test_cannot_delete_template_if_task_instances_exist(): void
    {
        $template = $this->service->createTemplate([
            'kpi_group_id' => $this->group->id,
            'title' => 'Temporary Task',
            'frequency' => 'daily',
            'monthly_required_count' => 1,
            'min_images' => 0,
            'is_active' => true,
            'rule_type' => 'pass_percentage',
            'target_percentage' => 80.00,
        ], $this->user->id);

        $assignment = KpiTaskAssignment::create([
            'task_template_id' => $template->id,
            'user_id' => $this->user->id,
            'starts_on' => now()->startOfMonth(),
            'is_active' => true,
        ]);

        KpiTaskInstance::create([
            'task_assignment_id' => $assignment->id,
            'task_template_id' => $template->id,
            'kpi_group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'period_type' => 'daily',
            'period_start' => now()->toDateString(),
            'period_end' => now()->toDateString(),
            'task_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->deleteTemplate($template);
    }
}
