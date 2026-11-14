<?php

namespace Tests\Feature\JewelrySales;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\JewelrySales\JewelrySalesImportService;
use Database\Seeders\MyanmarRegionTownshipSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class JewelrySalesModuleTest extends TestCase
{
    protected User $user;
    protected string $branchMappingFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchMappingFile = sys_get_temp_dir() . '/test_branch_mappings_' . uniqid() . '.json';
        config(['jewelry_sales.branch_mapping_file' => $this->branchMappingFile]);

        if (DB::getDriverName() === 'sqlite' && !Schema::hasTable('sales')) {
            $migrations = [
                'database/migrations/2014_10_12_000000_create_users_table.php',
                'database/migrations/2023_11_07_041651_create_positions_table.php',
                'database/migrations/2023_11_07_122713_create_branches_table.php',
                'database/migrations/2025_12_22_201331_add_is_jewelry_shop_to_branches_table.php',
                'database/migrations/2026_08_28_162258_create_permission_tables.php',
                'database/migrations/2026_09_01_150000_create_it_satisfaction_surveys_and_ratings_table.php',
                'database/migrations/2026_11_13_100001_create_jewelry_sales_reference_tables.php',
                'database/migrations/2026_11_13_100002_create_customers_table.php',
                'database/migrations/2026_11_13_100003_create_products_table.php',
                'database/migrations/2026_11_13_100004_create_sales_and_sale_items_tables.php',
                'database/migrations/2026_11_13_100005_create_product_categories_and_scale_indexes.php',
                'database/migrations/2026_11_13_100006_add_updated_items_to_import_batches_table.php',
            ];
            foreach ($migrations as $path) {
                Artisan::call('migrate', ['--path' => $path, '--force' => true]);
            }
        }

        $this->seed(MyanmarRegionTownshipSeeder::class);
        Branch::firstOrCreate(['name' => 'Branch 1']);
        Branch::firstOrCreate(['name' => 'Branch 3']);
        Branch::firstOrCreate(['name' => 'Online Sale Gold']);
        $this->user = User::forceCreate([
            'name' => 'Test Admin',
            'email' => 'test-jewelry@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->branchMappingFile);
        parent::tearDown();
    }

    private function sampleCsvContent(): string
    {
        $headers = implode(',', [
            'source_sale_id',
            'Sale date',
            'Sale number (အရောင်းအမှတ်)',
            'Customer name (ကုန်သည်အမည်)',
            'Customer address',
            'Customer phone',
            'တာဝန်ခံ',
            'စာရင်းသွင်းသူ',
            'ပေါက်ဈေး',
            'Product name',
            'ရွှေအမျိုးအစား',
            'ပစ္စည်းအမျိုးအမည်',
            'ပန်းထိမ်ဆရာ',
            'အတိုင်းအရှည်',
            'ပစ္စည်းအမျိုးအစား',
            'Barcode',
            'Barcode creation date (register_at)',
            'Qty',
            'အထည်ချိန် (gram)',
            'ကျောက်ချိန် (gram)',
            'ရွှေချိန် (gram)',
            'အလျော့တွက် (gram)',
            'ပေါင်းရွှေချိန် (gram)',
            'ပေါင်းရွှေဖိုးသင့်ငွေ',
            'ကျောက်ဖိုး',
            'လက်ခ',
            'အရောင်တင်ခ',
            'အခြားငွေ',
            'အမြတ်အလျော့',
            'Item amount',
            'Total amount',
            'Branch',
            'မှတ်ချက်',
        ]);

        $rows = [
            // Shared counter phone 09442992231, customer 1
            'SS-33549,2026-03-31 10:41:00.000,VJ040133549,မပြုံးချိုချို,မန္တလေး၊72/29A,09442992231,Theingi Win,Su Myat Htwe,10819000.0000,YG Special Design ဆွဲသီး,18K GP,18K ဆွဲသီး,CF,Star,Gold,WPPP23021219,2023-02-07,1,0.23,0.0,0.23,0.0,0.23,135677.0000,.0000,.0000,.0000,.0000,123.0000,135800.0000,135677.0000,18K 3,Remark A',
            // Shared counter phone 09442992231, customer 2, barcode GBKL2201200
            'SS-33545,2026-03-31 10:34:46.000,VJ040133545,မကော့ဂျာ,မန္တလေး၊72/29A,09442992231,Theingi Win,Su Myat Noe,10819000.0000,နှင်းပန်းဘေးတွယ်ကလစ်,ဒင်္ဂါး,ဆံဖိ,GC,Flower,Gold,GBKL2201200,2022-01-17,1,0.21,0.0,0.21,0.0,0.21,123646.0000,.0000,11500.0000,.0000,.0000,446.0000,134700.0000,135146.0000,Gold 3,Remark B',
            // Same barcode GBKL2201200 on another invoice!
            'SS-33542,2026-03-31 09:51:20.000,VJ040133542,မကော့ဂျာ,မန္တလေး၊72/29A,09442992231,Theingi Win,Su Myat Noe,10819000.0000,နှင်းပန်းဘေးတွယ်ကလစ်,ဒင်္ဂါး,ဆံဖိ,GC,Flower,Gold,GBKL2201200,2022-01-17,1,0.21,0.0,0.21,0.0,0.21,123646.0000,.0000,11500.0000,.0000,.0000,5454.0000,140600.0000,135146.0000,Gold 3,Remark B',
            // Multi-item invoice DJ04011628 with scientific notation
            'SS-11628,2026-03-31 09:51:00.000,DJ04011628,မဖြူသွယ်ဟိန်း,-,,Hnin Wai Wai Lwin,Myo Watti Oo,10819000.0000,ဘောလုံး,999 24K,Pandora,KKA,ball,Gold,OSG9PD2602189,2026-02-19,1,2.9999999329447746E-2,0.0,2.9999999329447746E-2,0.0,2.9999999329447746E-2,16905.0000,.0000,3850.0000,.0000,.0000,2245.0000,23000.0000,20755.0000,OSG,Remark C',
            'SS-11628,2026-03-31 09:51:00.000,DJ04011628,မဖြူသွယ်ဟိန်း,-,,Hnin Wai Wai Lwin,Myo Watti Oo,10819000.0000,ဘောလုံး,999 24K,Pandora,KKA,ball,Gold,OSG9PD2602188,2026-02-19,1,2.9999999329447746E-2,0.0,2.9999999329447746E-2,0.0,2.9999999329447746E-2,16905.0000,.0000,3850.0000,.0000,.0000,2245.0000,23000.0000,20755.0000,OSG,Remark C',
            // Phone missing leading 0 + Township Kyaukse
            'SS-33538,2026-03-29 15:52:26.000,VJ032933538,မစိုးမွန်ဦး,မန္တလေး၊ကျောက်ဆည်,9782082405,Theingi Win,Su Myat Htwe,10819000.0000,စက်ကြိုး,18K GB,18K ဆွဲကြိုး,CF,"L-17""wg",Gold,3W2PN24096,2024-09-14,1,1.35,0.0,1.35,0.0,1.35,785565.0000,.0000,.0000,.0000,.0000,235.0000,785800.0000,785565.0000,18K 3,Remark D',
        ];

        return $headers . "\n" . implode("\n", $rows);
    }

    public function test_csv_import_and_idempotent_reimport(): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'jewelry_sales_') . '.csv';
        file_put_contents($tmpPath, $this->sampleCsvContent());

        try {
            $service = app(JewelrySalesImportService::class);
            $batch = $service->import($tmpPath, 'sample.csv', $this->user->id);

            $this->assertEquals('completed', $batch->status);
            $this->assertEquals(6, $batch->total_rows);
            $this->assertEquals(5, $batch->inserted_sales);
            $this->assertEquals(6, $batch->inserted_items);
            $this->assertEquals(0, $batch->error_count);

            // 4 distinct customers (even though 2 share 09442992231)
            $this->assertEquals(4, Customer::count());

            // Phone normalization & Township mapping
            $kyaukseCustomer = Customer::where('name', 'မစိုးမွန်ဦး')->first();
            $this->assertNotNull($kyaukseCustomer);
            $this->assertEquals('09782082405', $kyaukseCustomer->phone_normalized);
            $this->assertEquals('Kyaukse', $kyaukseCustomer->township?->name);
            $this->assertEquals('Mandalay', $kyaukseCustomer->region?->name);

            // Multi-item invoice aggregation
            $multiSale = Sale::where('sale_number', 'DJ04011628')->first();
            $this->assertNotNull($multiSale);
            $this->assertEquals(2, $multiSale->items()->count());
            $this->assertEqualsWithDelta(41510.00, (float) $multiSale->invoice_total, 0.01);
            $this->assertEqualsWithDelta(0.06, (float) $multiSale->invoice_total_weight_g, 0.001);

            // Branch matching via config aliases
            $gold3Sale = Sale::where('sale_number', 'VJ040133545')->first();
            $branch3 = Branch::where('name', 'Branch 3')->first();
            $osgBranch = Branch::where('name', 'Online Sale Gold')->first();
            $this->assertEquals($branch3->id, $gold3Sale->branch_id);
            $this->assertEquals($osgBranch->id, $multiSale->branch_id);

            // Product category table populated
            $this->assertTrue(ProductCategory::where('name', 'Gold')->exists());

            // Re-import same file -> idempotent overwrite
            $batch2 = $service->import($tmpPath, 'sample.csv', $this->user->id);
            $this->assertEquals(0, $batch2->inserted_sales);
            $this->assertEquals(5, $batch2->updated_sales);
            $this->assertEquals(0, $batch2->inserted_items);
            $this->assertEquals(6, $batch2->updated_items);
            $this->assertEquals(5, Sale::count());
            $this->assertEquals(6, SaleItem::count());
        } finally {
            @unlink($tmpPath);
        }
    }

    public function test_jewelry_sales_pages_and_upload_endpoint_work(): void
    {
        $file = UploadedFile::fake()->createWithContent('sales.csv', $this->sampleCsvContent());

        $this->actingAs($this->user)
            ->post(route('jewelry.sales.import.store'), ['file' => $file])
            ->assertRedirect(route('jewelry.sales.import.index'));

        $this->assertEquals(1, ImportBatch::count());
        $this->assertEquals(5, Sale::count());

        $customer = Customer::first();
        $sale = Sale::first();

        $this->actingAs($this->user)->get(route('jewelry.sales.dashboard'))->assertStatus(200);
        $this->actingAs($this->user)->get(route('jewelry.sales.import.index'))->assertStatus(200);
        $this->actingAs($this->user)->get(route('jewelry.sales.customers.index'))->assertStatus(200);
        $this->actingAs($this->user)->get(route('jewelry.sales.customers.show', $customer))->assertStatus(200);
        $this->actingAs($this->user)->get(route('jewelry.sales.products.index'))->assertStatus(200);
        $this->actingAs($this->user)->get(route('jewelry.sales.invoices.index'))->assertStatus(200);
        $this->actingAs($this->user)
            ->get(route('jewelry.sales.invoices.show', $sale))
            ->assertStatus(200)
            ->assertJsonPath('sale.id', $sale->id);
    }

    public function test_unmatched_branch_aborts_import_before_starting(): void
    {
        $csv = str_replace(',Gold 3,', ',Unknown Branch XYZ,', $this->sampleCsvContent());
        $tmpPath = tempnam(sys_get_temp_dir(), 'jewelry_bad_branch_') . '.csv';
        file_put_contents($tmpPath, $csv);

        try {
            $service = app(JewelrySalesImportService::class);
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Unknown Branch XYZ');
            $service->import($tmpPath, 'bad_branch.csv', $this->user->id);
        } finally {
            @unlink($tmpPath);
            $this->assertEquals(0, Sale::count());
            $this->assertEquals(0, SaleItem::count());
            $this->assertEquals(0, Customer::count());
            $this->assertEquals(1, ImportBatch::where('status', 'failed')->count());
        }
    }

    public function test_upload_endpoint_returns_friendly_validation_error_on_unmatched_branch(): void
    {
        $csv = str_replace(',Gold 3,', ',Unknown Branch XYZ,', $this->sampleCsvContent());
        $file = UploadedFile::fake()->createWithContent('bad_branch.csv', $csv);

        $this->actingAs($this->user)
            ->post(route('jewelry.sales.import.store'), ['file' => $file])
            ->assertRedirect(route('jewelry.sales.import.index'))
            ->assertSessionHas('unmatched_branches', ['Unknown Branch XYZ'])
            ->assertSessionHasErrors('file');

        $this->assertEquals(0, Sale::count());

        $branch3 = Branch::where('name', 'Branch 3')->first();

        $this->actingAs($this->user)
            ->post(route('jewelry.sales.import.branch_mappings'), [
                'mappings' => [
                    ['alias' => 'Unknown Branch XYZ', 'branch_id' => $branch3->id],
                ],
            ])
            ->assertRedirect(route('jewelry.sales.import.index'))
            ->assertSessionHas('success');

        $this->assertFileExists($this->branchMappingFile);
        $saved = json_decode((string) file_get_contents($this->branchMappingFile), true);
        $this->assertEquals($branch3->id, $saved['unknown branch xyz'] ?? null);

        $file2 = UploadedFile::fake()->createWithContent('bad_branch.csv', $csv);
        $this->actingAs($this->user)
            ->post(route('jewelry.sales.import.store'), ['file' => $file2])
            ->assertRedirect(route('jewelry.sales.import.index'))
            ->assertSessionHas('success');

        $this->assertEquals(5, Sale::count());
    }

    public function test_blank_source_sale_id_aborts_import_before_starting(): void
    {
        $csv = str_replace('SS-33545,', ',', $this->sampleCsvContent());
        $file = UploadedFile::fake()->createWithContent('missing_source_sale_id.csv', $csv);

        $this->actingAs($this->user)
            ->post(route('jewelry.sales.import.store'), ['file' => $file])
            ->assertRedirect(route('jewelry.sales.import.index'))
            ->assertSessionHasErrors('file');

        $this->assertEquals(0, Sale::count());
        $this->assertEquals(0, SaleItem::count());
        $this->assertEquals(1, ImportBatch::where('status', 'failed')->count());
    }

    public function test_v2_columns_address_rules_categories_and_invoice_filters(): void
    {
        $headers = implode(',', [
            'source_sale_id',
            'source_sale_item_id',
            'Sale date',
            'Sale number (အရောင်းအမှတ်)',
            'Customer name (ကုန်သည်အမည်)',
            'Customer address',
            'Customer phone',
            'တာဝန်ခံ',
            "\"စာရင်းသွ\nင်းသူ\"",
            'ပေါက်ဈေး',
            'Product name',
            'ရွှေအမျိုးအစား',
            'ပစ္စည်းအမျိုးအမည်',
            'ပန်းထိမ်ဆရာ',
            'အတိုင်းအရှည်',
            'ပစ္စည်းအမျိုးအစား',
            'Barcode',
            'Barcode creation date (register_at)',
            'Qty',
            'အထည်ချိန် (gram)',
            'ကျောက်ချိန် (gram)',
            'ရွှေချိန် (gram)',
            'အလျော့တွက် (gram)',
            'ပေါင်းရွှေချိန် (gram)',
            'ပေါင်းရွှေဖိုးသင့်ငွေ',
            'ကျောက်ဖိုး',
            'လက်ခ',
            'အရောင်တင်ခ',
            'အခြားငွေ',
            'အမြတ်အလျော့',
            'Item amount',
            'Total amount',
            'Sale invoice total',
            'Branch',
            'မှတ်ချက်',
        ]);

        $rows = [
            'SRC-1001,ITEM-1001-A,2026-04-01 10:00:00,INV-1001,Daw Aye,Amarapura,0912345678,DutyA,InputA,10800000,Diamond Ring,18K GP,လက်စွပ်,GC,Size 12,18K Ring,BC-1001,2026-01-01,1,2.50,0.0,2.50,0.0,2.50,500000,0,10000,0,0,0,510000,510000,510000,Gold 1,Test A',
            'SRC-1002,ITEM-1002-A,2026-04-01 11:00:00,INV-1002,U Ba,105x79,0987654321,DutyB,InputB,10800000,Gold Chain,999 24K,ဆွဲကြိုး,KKA,18in,24K Chain,BC-1002,2026-01-01,1,5.25,0.0,5.25,0.0,5.25,1200000,0,20000,0,0,0,1220000,1220000,1220000,OSW,Test B',
        ];

        $tmpPath = tempnam(sys_get_temp_dir(), 'jewelry_v2_') . '.csv';
        file_put_contents($tmpPath, $headers . "\n" . implode("\n", $rows));

        try {
            $batch = app(JewelrySalesImportService::class)->import($tmpPath, 'v2.csv', $this->user->id);
            $this->assertEquals('completed', $batch->status);

            // Explicit source_sale_id & source_sale_item_id & wrapped input staff header
            $sale1 = Sale::where('source_sale_id', 'SRC-1001')->first();
            $this->assertNotNull($sale1);
            $this->assertEquals('InputA', $sale1->inputStaff?->name);
            $this->assertEquals(510000.0, (float) $sale1->invoice_total);
            $this->assertTrue(SaleItem::where('source_sale_item_id', 'ITEM-1001-A')->exists());

            // Address rules: Amarapura -> Mandalay/Amarapura; 105x79 -> Mandalay/Chanmyathazi
            $custAye = Customer::where('name', 'Daw Aye')->first();
            $this->assertEquals('Mandalay', $custAye->region?->name);
            $this->assertEquals('Amarapura', $custAye->township?->name);

            $custBa = Customer::where('name', 'U Ba')->first();
            $this->assertEquals('Mandalay', $custBa->region?->name);
            $this->assertEquals('Chanmyathazi', $custBa->township?->name);

            // Auto-creation of missing region/township from address analysis
            $csvAuto = str_replace(
                ['Amarapura', '105x79'],
                ['မန္တလေး၊ဘုရားကြီး', '"CustomRegion, CustomTownship"'],
                implode("\n", [$headers, $rows[0], $rows[1]])
            );
            $csvAuto = str_replace(
                ['SRC-1001', 'SRC-1002', 'ITEM-1001-A', 'ITEM-1002-A', 'INV-1001', 'INV-1002', 'Daw Aye', 'U Ba'],
                ['SRC-2001', 'SRC-2002', 'ITEM-2001-A', 'ITEM-2002-A', 'INV-2001', 'INV-2002', 'Ma Hla', 'Ko Min'],
                $csvAuto
            );
            $tmpAuto = tempnam(sys_get_temp_dir(), 'jewelry_auto_geo_') . '.csv';
            file_put_contents($tmpAuto, $csvAuto);
            try {
                app(JewelrySalesImportService::class)->import($tmpAuto, 'auto_geo.csv', $this->user->id);
                $maHla = Customer::where('name', 'Ma Hla')->first();
                $this->assertEquals('Mandalay', $maHla->region?->name);
                $this->assertEquals('ဘုရားကြီး', $maHla->township?->name);

                $koMin = Customer::where('name', 'Ko Min')->first();
                $this->assertEquals('CustomRegion', $koMin->region?->name);
                $this->assertEquals('CustomTownship', $koMin->township?->name);
            } finally {
                @unlink($tmpAuto);
            }

            // Myanmar numeral phone conversion (၀-၉ -> 0-9)
            $csvMmPhone = str_replace(
                ['SRC-1001', 'ITEM-1001-A', 'INV-1001', 'Daw Aye', '0912345678'],
                ['SRC-3001', 'ITEM-3001-A', 'INV-3001', 'Ma Aye', '၀၉၄၄၃၂၂၅၁၁၇'],
                implode("\n", [$headers, $rows[0]])
            );
            $tmpMm = tempnam(sys_get_temp_dir(), 'jewelry_mm_phone_') . '.csv';
            file_put_contents($tmpMm, $csvMmPhone);
            try {
                app(JewelrySalesImportService::class)->import($tmpMm, 'mm_phone.csv', $this->user->id);
                $maAye = Customer::where('name', 'Ma Aye')->first();
                $this->assertEquals('09443225117', $maAye->phone_normalized);
                $mmSale = Sale::where('source_sale_id', 'SRC-3001')->first();
                $this->assertEquals('09443225117', $mmSale->customer_phone_snapshot);
            } finally {
                @unlink($tmpMm);
            }

            // ProductCategory created
            $this->assertTrue(ProductCategory::where('name', '18K Ring')->exists());
            $this->assertTrue(ProductCategory::where('name', '24K Chain')->exists());

            // Invoice research filters: customer_name, phone, product_name, weight_min/weight_max
            $this->actingAs($this->user)
                ->get(route('jewelry.sales.invoices.index', [
                    'customer_name' => 'Daw Aye',
                    'phone' => '091234',
                    'product_name' => 'Diamond Ring',
                    'weight_min' => '2.0',
                    'weight_max' => '3.0',
                ]))
                ->assertStatus(200)
                ->assertSee('INV-1001')
                ->assertDontSee('INV-1002');

            // Combined customer (name or phone) filter
            $this->actingAs($this->user)
                ->get(route('jewelry.sales.invoices.index', ['customer' => '0987654321']))
                ->assertStatus(200)
                ->assertSee('INV-1002')
                ->assertDontSee('INV-1001');
        } finally {
            @unlink($tmpPath);
        }
    }
}
