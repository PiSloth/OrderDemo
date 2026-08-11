<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiSaleKpiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base testing records
        $this->branch = Branch::factory()->create(['name' => 'Test Branch']);
        $this->department = Department::factory()->create(['name' => 'IT']);
        $position = \App\Models\Position::create(['name' => 'Admin']);

        $this->user = User::factory()->create([
            'branch_id' => $this->branch->id,
            'position_id' => $position->id,
        ]);
    }

    /**
     * Test the Sale KPI view loads.
     */
    public function test_sale_kpi_dashboard_page_loads_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('kpi.sale-kpi'));

        $response->assertStatus(200);
    }

    /**
     * Test data API returns successfully.
     */
    public function test_sale_kpi_data_api_returns_valid_kpi_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('kpi.sale-kpi.data'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'start_date',
                'end_date',
                'gram_chart',
                'pcs_chart',
                'line_chart' => [
                    'labels',
                    'weight',
                    'quantity',
                    'customer',
                    'overlap_counts',
                    'overlap_details',
                ],
                'rewards_table',
                'promote_actions',
            ]);
    }

    /**
     * Test creating a promote action.
     */
    public function test_can_create_promote_action_via_api(): void
    {
        $payload = [
            'name' => 'Black Friday Promo',
            'target_branch_id' => $this->branch->id,
            'action_by' => $this->department->id,
            'start_at' => '2026-11-20',
            'end_at' => '2026-11-27',
            'reference' => ['todo_list_id' => 1],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('kpi.sale-kpi.promote-actions.store'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Promote action created successfully.',
            ]);

        $this->assertDatabaseHas('promote_actions', [
            'name' => 'Black Friday Promo',
            'target_branch_id' => $this->branch->id,
            'action_by' => $this->department->id,
            'start_at' => '2026-11-20',
            'end_at' => '2026-11-27',
        ]);
    }
}
