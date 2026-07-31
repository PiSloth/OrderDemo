<?php

namespace Tests\Unit\Services;

use App\Models\Department;
use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskRule;
use App\Models\Kpi\KpiTaskTemplate;
use App\Services\Kpi\KpiGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KpiGroupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KpiGroupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KpiGroupService();
    }

    public function test_can_create_kpi_group(): void
    {
        $data = [
            'name' => 'Sales KPI Group',
            'code' => 'SALES_01',
            'rule_type' => KpiTaskRule::TYPE_PASS_PERCENTAGE,
            'target_percentage' => 85.00,
            'is_active' => true,
        ];

        $group = $this->service->createGroup($data);

        $this->assertInstanceOf(KpiGroup::class, $group);
        $this->assertEquals('Sales KPI Group', $group->name);
        $this->assertEquals('SALES_01', $group->code);
    }

    public function test_can_update_kpi_group(): void
    {
        $group = KpiGroup::create([
            'name' => 'Original Group',
            'rule_type' => KpiTaskRule::TYPE_PASS_PERCENTAGE,
            'target_percentage' => 80.00,
            'is_active' => true,
        ]);

        $updated = $this->service->updateGroup($group, [
            'name' => 'Updated Group',
            'target_percentage' => 90.00,
        ]);

        $this->assertEquals('Updated Group', $updated->name);
        $this->assertEquals(90.00, $updated->target_percentage);
    }

    public function test_prevents_deleting_kpi_group_with_child_task_templates(): void
    {
        $group = KpiGroup::create([
            'name' => 'Group With Templates',
            'rule_type' => KpiTaskRule::TYPE_PASS_PERCENTAGE,
            'target_percentage' => 80.00,
            'is_active' => true,
        ]);

        KpiTaskTemplate::create([
            'kpi_group_id' => $group->id,
            'title' => 'Sample Template',
            'slug' => 'sample-template',
            'frequency' => 'daily',
            'monthly_required_count' => 1,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->deleteGroup($group);
    }

    public function test_prevents_updating_rule_configuration_if_previous_month_instances_exist(): void
    {
        $group = KpiGroup::create([
            'name' => 'Historical Group',
            'rule_type' => KpiTaskRule::TYPE_PASS_PERCENTAGE,
            'target_percentage' => 80.00,
            'is_active' => true,
        ]);

        KpiTaskInstance::create([
            'kpi_group_id' => $group->id,
            'task_date' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
        ]);

        $this->expectException(ValidationException::class);

        $this->service->updateGroup($group, [
            'name' => 'Historical Group Updated Name',
            'target_percentage' => 95.00,
        ]);
    }

    public function test_allows_updating_rule_configuration_if_only_current_month_instances_exist(): void
    {
        $group = KpiGroup::create([
            'name' => 'Current Month Group',
            'rule_type' => KpiTaskRule::TYPE_PASS_PERCENTAGE,
            'target_percentage' => 80.00,
            'is_active' => true,
        ]);

        KpiTaskInstance::create([
            'kpi_group_id' => $group->id,
            'task_date' => now()->startOfMonth()->toDateString(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ]);

        $updated = $this->service->updateGroup($group, [
            'name' => 'Current Month Group',
            'target_percentage' => 90.00,
        ]);

        $this->assertEquals(90.00, $updated->target_percentage);
    }

    public function test_can_delete_empty_kpi_group(): void
    {
        $group = KpiGroup::create([
            'name' => 'Empty Group',
            'rule_type' => KpiTaskRule::TYPE_PASS_PERCENTAGE,
            'target_percentage' => 80.00,
            'is_active' => true,
        ]);

        $groupId = $group->id;
        $this->service->deleteGroup($group);

        $this->assertSoftDeleted('kpi_groups', ['id' => $groupId]);
    }
}
