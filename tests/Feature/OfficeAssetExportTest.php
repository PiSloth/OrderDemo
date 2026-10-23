<?php

namespace Tests\Feature;

use App\Livewire\OfficeAssetManager;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Department;
use App\Models\OfficeAsset;
use App\Models\OfficeAssetItem;
use App\Models\OfficeAssetTransaction;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class OfficeAssetExportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Branch $branch;
    protected Department $department;
    protected AssetCategory $category;
    protected OfficeAssetItem $item;
    protected OfficeAsset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $position = Position::firstOrCreate(['name' => 'IT Admin ' . uniqid()]);
        $this->branch = Branch::firstOrCreate(['name' => 'Yangon Branch ' . uniqid()]);
        $this->department = Department::firstOrCreate(['name' => 'Logistics ' . uniqid()]);
        $this->category = AssetCategory::create(['name' => 'Electronics ' . uniqid()]);

        $this->user = User::create([
            'name' => 'Asset Manager',
            'email' => 'asset.manager.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $position->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
        ]);

        $this->item = OfficeAssetItem::create([
            'asset_category_id' => $this->category->id,
            'name' => 'Dell Laptop ' . uniqid(),
        ]);

        $this->asset = OfficeAsset::create([
            'office_asset_item_id' => $this->item->id,
            'asset_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'name' => $this->item->name,
            'cost' => 1200.50,
            'balance' => 10,
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_access_office_asset_route_with_group_by_category(): void
    {
        $response = $this->get(route('office-asset.index', ['groupBy' => 'category']));
        $response->assertOk();
        $response->assertSee('Export Transactions');
    }

    public function test_open_export_modal_initializes_defaults_and_preselected_filters(): void
    {
        Livewire::test(OfficeAssetManager::class)
            ->set('filterBranch', $this->branch->id)
            ->set('filterCategory', $this->category->id)
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->assertSet('exportStartDate', Carbon::now()->startOfMonth()->format('Y-m-d'))
            ->assertSet('exportEndDate', Carbon::now()->format('Y-m-d'))
            ->assertSet('exportBranchId', (string) $this->branch->id)
            ->assertSet('exportCategoryId', (string) $this->category->id)
            ->assertSet('exportType', 'all')
            ->assertSet('exportFormat', 'xlsx');
    }

    public function test_date_range_presets_work(): void
    {
        Livewire::test(OfficeAssetManager::class)
            ->call('openExportModal')
            ->call('setDateRangePreset', 'last_30_days')
            ->assertSet('exportStartDate', Carbon::now()->subDays(30)->format('Y-m-d'))
            ->assertSet('exportEndDate', Carbon::now()->format('Y-m-d'))
            ->call('setDateRangePreset', 'all')
            ->assertSet('exportStartDate', '')
            ->assertSet('exportEndDate', '');
    }

    public function test_validation_fails_when_end_date_before_start_date(): void
    {
        Livewire::test(OfficeAssetManager::class)
            ->set('exportStartDate', '2026-10-15')
            ->set('exportEndDate', '2026-10-10')
            ->call('exportTransactions')
            ->assertHasErrors(['exportEndDate']);
    }

    public function test_export_transactions_downloads_file_with_date_range_filtering(): void
    {
        // Transaction inside range
        $txInside = OfficeAssetTransaction::create([
            'office_asset_id' => $this->asset->id,
            'type' => 'in',
            'quantity' => 5,
            'date' => '2026-10-15',
            'remark' => 'Batch arrival',
            'user_id' => $this->user->id,
        ]);

        // Transaction outside range
        $txOutside = OfficeAssetTransaction::create([
            'office_asset_id' => $this->asset->id,
            'type' => 'out',
            'quantity' => 2,
            'date' => '2026-09-01',
            'remark' => 'Old record',
            'user_id' => $this->user->id,
        ]);

        $component = Livewire::test(OfficeAssetManager::class)
            ->set('exportStartDate', '2026-10-01')
            ->set('exportEndDate', '2026-10-31')
            ->set('exportBranchId', $this->branch->id)
            ->set('exportCategoryId', $this->category->id)
            ->set('exportFormat', 'csv');

        $this->assertEquals(1, $component->get('exportCount'));

        $response = $component->call('exportTransactions');

        $response->assertFileDownloaded('office-asset-transactions_2026-10-01_to_2026-10-31.csv');
    }

    public function test_export_transactions_contains_requested_columns_and_values(): void
    {
        $uniqueRemark = 'Special test arrival ' . uniqid();
        OfficeAssetTransaction::create([
            'office_asset_id' => $this->asset->id,
            'type' => 'in',
            'quantity' => 17,
            'date' => '2026-10-20',
            'remark' => $uniqueRemark,
            'user_id' => $this->user->id,
        ]);

        $response = Livewire::test(OfficeAssetManager::class)
            ->set('exportStartDate', '2026-10-01')
            ->set('exportEndDate', '2026-10-31')
            ->set('exportFormat', 'csv')
            ->call('exportTransactions');

        $downloadedFilePath = $response->effects['download']['name'] ?? null;
        $this->assertNotNull($downloadedFilePath);
        $this->assertStringContainsString('office-asset-transactions_2026-10-01_to_2026-10-31.csv', $downloadedFilePath);

        // Test XLSX export as well
        $responseXlsx = Livewire::test(OfficeAssetManager::class)
            ->set('exportStartDate', '2026-10-01')
            ->set('exportEndDate', '2026-10-31')
            ->set('exportFormat', 'xlsx')
            ->call('exportTransactions');

        $downloadedXlsx = $responseXlsx->effects['download']['name'] ?? null;
        $this->assertNotNull($downloadedXlsx);
        $this->assertStringContainsString('.xlsx', $downloadedXlsx);
    }
}
