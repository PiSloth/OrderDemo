<?php

namespace App\Services\JewelrySales;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Region;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SourceSystem;
use App\Models\Staff;
use App\Models\Township;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\SimpleExcel\SimpleExcelReader;

class JewelrySalesImportService
{
    private const CHUNK_INVOICE_LIMIT = 200;

    /** @var array<string, int> */
    private array $branchCache = [];

    /** @var array<int, bool> */
    private array $branchIdSet = [];

    /** @var array<string, string> */
    private array $branchAliases = [];

    /** @var array<string, int> */
    private array $branchIdMappings = [];

    /** @var array<string, int> */
    private array $staffCache = [];

    /** @var array<string, int> */
    private array $customerCache = [];

    /** @var array<string, int> */
    private array $productCategoryCache = [];

    /** @var array<string, int> */
    private array $productCache = [];

    /** @var array<int, array{id: int, name: string, name_mm: ?string, region_id: ?int, aliases: array<int, string>}> */
    private array $townshipLookup = [];

    /** @var array<int, array{id: int, name: string, name_mm: ?string, aliases: array<int, string>}> */
    private array $regionLookup = [];

    public function import(string $filePath, string $originalFileName, ?int $uploadedBy = null, string $sourceSystemCode = 'pos_default'): ImportBatch
    {
        @set_time_limit(0);

        $sourceSystem = SourceSystem::firstOrCreate(
            ['code' => $sourceSystemCode],
            ['name' => 'Default POS Export']
        );

        $fileHash = is_file($filePath) ? hash_file('sha256', $filePath) : null;

        $batch = ImportBatch::create([
            'source_system_id' => $sourceSystem->id,
            'uploaded_by' => $uploadedBy,
            'file_name' => $originalFileName,
            'file_hash' => $fileHash,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $this->warmCaches();

        $errors = [];
        $totalRows = 0;
        $skippedRows = 0;
        $invoices = [];

        try {
            $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
            $readerType = in_array($ext, ['csv', 'txt'], true) ? 'csv' : ($ext ?: '');

            // Pre-flight check: abort before inserting anything if source_sale_id is blank or any branch name is unmatched
            $this->validateBeforeImport($filePath, $readerType);

            $reader = $this->makeReader($filePath, $readerType);
            $rowIndex = 1;
            $insertedSales = 0;
            $updatedSales = 0;
            $insertedItems = 0;
            $updatedItems = 0;
            $currentInvoiceKey = null;

            foreach ($reader->getRows() as $rawRow) {
                $rowIndex++;
                $totalRows++;

                $row = $this->normalizeHeaders($rawRow);
                $saleNumber = $this->extractString($row, ['sale_number', 'sale_number_အရောင်းအမှတ်', 'အရောင်းအမှတ်']);
                $sourceSaleId = $this->extractString($row, ['source_sale_id']);

                if ($saleNumber === '' && $sourceSaleId === '') {
                    $skippedRows++;
                    $errors[] = "Row {$rowIndex}: Missing sale number.";
                    continue;
                }

                $saleAtRaw = $this->extractString($row, ['sale_date', 'sale_at', 'date']);
                $saleAt = $this->parseDateTime($saleAtRaw);
                if (!$saleAt) {
                    $skippedRows++;
                    $errors[] = "Row {$rowIndex} ({$saleNumber}): Invalid sale date '{$saleAtRaw}'.";
                    continue;
                }

                $invoiceKey = $sourceSaleId !== '' ? $sourceSaleId : $saleNumber;

                if ($currentInvoiceKey !== null && $invoiceKey !== $currentInvoiceKey && count($invoices) >= self::CHUNK_INVOICE_LIMIT) {
                    $this->persistInvoicesChunk(
                        $invoices,
                        $sourceSystem,
                        $batch,
                        $insertedSales,
                        $updatedSales,
                        $insertedItems,
                        $updatedItems
                    );
                    $invoices = [];
                }
                $currentInvoiceKey = $invoiceKey;

                if (!isset($invoices[$invoiceKey])) {
                    $invoices[$invoiceKey] = [
                        'source_sale_id' => $invoiceKey,
                        'sale_number' => $saleNumber !== '' ? $saleNumber : $invoiceKey,
                        'sale_at' => $saleAt,
                        'customer_name' => $this->extractString($row, ['customer_name', 'customer_name_ကုန်သည်အမည်', 'ကုန်သည်အမည်']),
                        'customer_address' => $this->extractString($row, ['customer_address', 'address']),
                        'customer_phone' => $this->extractString($row, ['customer_phone', 'phone']),
                        'duty_staff' => $this->extractString($row, ['တာဝန်ခံ', 'duty_staff']),
                        'input_staff' => $this->extractString($row, ['စာရင်းသွင်းသူ', 'input_staff']),
                        'gold_price' => $this->parseDecimal($this->extractRaw($row, ['ပေါက်ဈေး', 'ပေါက်စျေး', 'gold_price']), 2),
                        'branch' => $this->extractString($row, ['branch']),
                        'sale_invoice_total' => $this->parseDecimal($this->extractRaw($row, ['sale_invoice_total', 'invoice_total']), 2),
                        'remark' => $this->extractString($row, ['မှတ်ချက်', 'remark']),
                        'items' => [],
                    ];
                }

                $invoices[$invoiceKey]['items'][] = [
                    'row_index' => $rowIndex,
                    'source_sale_item_id' => $this->extractString($row, ['source_sale_item_id']),
                    'product_name' => $this->extractString($row, ['product_name']),
                    'gold_type' => $this->extractString($row, ['ရွှေအမျိုးအစား', 'gold_type']),
                    'item_name' => $this->extractString($row, ['ပစ္စည်းအမျိုးအမည်', 'item_name']),
                    'goldsmith' => $this->extractString($row, ['ပန်းထိမ်ဆရာ', 'goldsmith']),
                    'length' => $this->extractString($row, ['အတိုင်းအရှည်', 'length']),
                    'category' => $this->extractString($row, ['ပစ္စည်းအမျိုးအစား', 'category']),
                    'barcode' => $this->extractString($row, ['barcode']),
                    'barcode_registered_at' => $this->parseDate($this->extractString($row, [
                        'barcode_creation_date_register_at',
                        'barcode_creation_date',
                        'register_at',
                    ])),
                    'quantity' => max(1, (int) round($this->parseDecimal($this->extractRaw($row, ['qty', 'quantity']), 0))),
                    'item_weight_g' => $this->parseDecimal($this->extractRaw($row, ['အထည်ချိန်_gram', 'အထည်ချိန်', 'item_weight_g']), 4),
                    'stone_weight_g' => $this->parseDecimal($this->extractRaw($row, ['ကျောက်ချိန်_gram', 'ကျောက်ချိန်', 'stone_weight_g']), 4),
                    'gold_weight_g' => $this->parseDecimal($this->extractRaw($row, ['ရွှေချိန်_gram', 'ရွှေချိန်', 'gold_weight_g']), 4),
                    'decrease_weight_g' => $this->parseDecimal($this->extractRaw($row, ['အလျော့တွက်_gram', 'အလျော့တွက်', 'decrease_weight_g']), 4),
                    'item_total_weight_g' => $this->parseDecimal($this->extractRaw($row, ['ပေါင်းရွှေချိန်_gram', 'ပေါင်းရွှေချိန်', 'item_total_weight_g']), 4),
                    'gold_amount' => $this->parseDecimal($this->extractRaw($row, ['ပေါင်းရွှေဖိုးသင့်ငွေ', 'gold_amount']), 2),
                    'stone_amount' => $this->parseDecimal($this->extractRaw($row, ['ကျောက်ဖိုး', 'stone_amount']), 2),
                    'labor_fee' => $this->parseDecimal($this->extractRaw($row, ['လက်ခ', 'labor_fee']), 2),
                    'polishing_fee' => $this->parseDecimal($this->extractRaw($row, ['အရောင်တင်ခ', 'polishing_fee']), 2),
                    'other_fee' => $this->parseDecimal($this->extractRaw($row, ['အခြားငွေ', 'other_fee']), 2),
                    'profit_loss_amount' => $this->parseDecimal($this->extractRaw($row, ['အမြတ်အလျော့', 'profit_loss_amount']), 2),
                    'item_amount' => $this->parseDecimal($this->extractRaw($row, ['item_amount']), 2),
                    'item_total_amount' => $this->parseDecimal($this->extractRaw($row, ['total_amount', 'item_total_amount']), 2),
                ];
            }

            if ($invoices !== []) {
                $this->persistInvoicesChunk(
                    $invoices,
                    $sourceSystem,
                    $batch,
                    $insertedSales,
                    $updatedSales,
                    $insertedItems,
                    $updatedItems
                );
            }

            $batch->update([
                'status' => 'completed',
                'total_rows' => $totalRows,
                'inserted_sales' => $insertedSales,
                'updated_sales' => $updatedSales,
                'inserted_items' => $insertedItems,
                'updated_items' => $updatedItems,
                'skipped_rows' => $skippedRows,
                'error_count' => count($errors),
                'errors_summary' => array_slice($errors, 0, 50),
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $batch->update([
                'status' => 'failed',
                'total_rows' => $totalRows,
                'skipped_rows' => $skippedRows,
                'error_count' => count($errors) + 1,
                'errors_summary' => array_slice(array_merge($errors, [$e->getMessage()]), 0, 50),
                'finished_at' => now(),
            ]);

            throw $e;
        }

        return $batch->fresh();
    }

    private function makeReader(string $filePath, string $readerType): SimpleExcelReader
    {
        return $readerType !== ''
            ? SimpleExcelReader::create($filePath, $readerType)
            : SimpleExcelReader::create($filePath);
    }

    private function validateBeforeImport(string $filePath, string $readerType): void
    {
        $unmatched = [];
        $checked = [];
        $reader = $this->makeReader($filePath, $readerType);
        $rowIndex = 1;

        foreach ($reader->getRows() as $rawRow) {
            $rowIndex++;
            $row = $this->normalizeHeaders($rawRow);

            $sourceSaleId = $this->extractString($row, ['source_sale_id']);
            if ($sourceSaleId === '') {
                throw new InvalidArgumentException(
                    "Row {$rowIndex}: Missing source_sale_id. Import aborted to prevent duplicate data."
                );
            }

            $branchName = $this->extractString($row, ['branch']);
            if ($branchName === '') {
                continue;
            }

            $key = mb_strtolower($branchName);
            if (isset($checked[$key])) {
                continue;
            }
            $checked[$key] = true;

            if ($this->resolveBranchId($branchName) === null) {
                $unmatched[] = $branchName;
            }
        }

        if ($unmatched !== []) {
            throw new UnmatchedBranchesException($unmatched);
        }
    }

    private function persistInvoicesChunk(
        array $invoices,
        SourceSystem $sourceSystem,
        ImportBatch $batch,
        int &$insertedSales,
        int &$updatedSales,
        int &$insertedItems,
        int &$updatedItems
    ): void {
        DB::transaction(function () use (
            $invoices,
            $sourceSystem,
            $batch,
            &$insertedSales,
            &$updatedSales,
            &$insertedItems,
            &$updatedItems
        ) {
            $now = now()->format('Y-m-d H:i:s');
            $saleRows = [];
            $sourceSaleIds = [];

            foreach ($invoices as $invoice) {
                $customerId = $this->resolveCustomerId(
                    $invoice['customer_name'],
                    $invoice['customer_phone'],
                    $invoice['customer_address']
                );

                $branchId = $this->resolveBranchId($invoice['branch']);
                $dutyStaffId = $this->resolveStaffId($invoice['duty_staff']);
                $inputStaffId = $this->resolveStaffId($invoice['input_staff']);

                $computedInvoiceTotal = 0.0;
                $invoiceTotalWeight = 0.0;
                foreach ($invoice['items'] as $it) {
                    $computedInvoiceTotal += $it['item_total_amount'];
                    $invoiceTotalWeight += $it['item_total_weight_g'];
                }
                $invoiceTotal = $invoice['sale_invoice_total'] > 0
                    ? $invoice['sale_invoice_total']
                    : $computedInvoiceTotal;

                $normalizedPhoneSnapshot = $this->normalizePhone($invoice['customer_phone']);

                $sourceSaleIds[] = $invoice['source_sale_id'];
                $saleRows[] = [
                    'source_system_id' => $sourceSystem->id,
                    'source_sale_id' => $invoice['source_sale_id'],
                    'import_batch_id' => $batch->id,
                    'sale_number' => $invoice['sale_number'],
                    'sale_at' => $invoice['sale_at']->format('Y-m-d H:i:s'),
                    'customer_id' => $customerId,
                    'customer_name_snapshot' => $invoice['customer_name'] !== '' ? $invoice['customer_name'] : null,
                    'customer_phone_snapshot' => $normalizedPhoneSnapshot ?? ($invoice['customer_phone'] !== '' ? $invoice['customer_phone'] : null),
                    'customer_address_snapshot' => $invoice['customer_address'] !== '' ? $invoice['customer_address'] : null,
                    'branch_id' => $branchId,
                    'branch_name_raw' => $invoice['branch'] !== '' ? $invoice['branch'] : null,
                    'duty_staff_id' => $dutyStaffId,
                    'input_staff_id' => $inputStaffId,
                    'gold_price' => round($invoice['gold_price'], 2),
                    'invoice_total' => round($invoiceTotal, 2),
                    'invoice_total_weight_g' => round($invoiceTotalWeight, 4),
                    'remark' => $invoice['remark'] !== '' ? $invoice['remark'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $existingSaleCount = Sale::where('source_system_id', $sourceSystem->id)
                ->whereIn('source_sale_id', $sourceSaleIds)
                ->count();
            $updatedSales += $existingSaleCount;
            $insertedSales += max(0, count($saleRows) - $existingSaleCount);

            Sale::upsert(
                $saleRows,
                ['source_system_id', 'source_sale_id'],
                [
                    'import_batch_id',
                    'sale_number',
                    'sale_at',
                    'customer_id',
                    'customer_name_snapshot',
                    'customer_phone_snapshot',
                    'customer_address_snapshot',
                    'branch_id',
                    'branch_name_raw',
                    'duty_staff_id',
                    'input_staff_id',
                    'gold_price',
                    'invoice_total',
                    'invoice_total_weight_g',
                    'remark',
                    'updated_at',
                ]
            );

            $saleIdMap = Sale::where('source_system_id', $sourceSystem->id)
                ->whereIn('source_sale_id', $sourceSaleIds)
                ->pluck('id', 'source_sale_id')
                ->all();

            $itemRowsByKey = [];
            foreach ($invoices as $invoice) {
                $saleId = (int) ($saleIdMap[$invoice['source_sale_id']] ?? 0);
                if ($saleId === 0) {
                    continue;
                }

                $seenKeysInInvoice = [];
                foreach ($invoice['items'] as $idx => $itemData) {
                    $productId = $this->resolveProductId($itemData);

                    if ($itemData['source_sale_item_id'] !== '') {
                        $sourceSaleItemId = $itemData['source_sale_item_id'];
                    } else {
                        $baseKey = $itemData['barcode'] !== ''
                            ? "{$invoice['source_sale_id']}:{$itemData['barcode']}"
                            : "{$invoice['source_sale_id']}:row:" . ($idx + 1);

                        $seenKeysInInvoice[$baseKey] = ($seenKeysInInvoice[$baseKey] ?? 0) + 1;
                        $sourceSaleItemId = $seenKeysInInvoice[$baseKey] > 1
                            ? "{$baseKey}#{$seenKeysInInvoice[$baseKey]}"
                            : $baseKey;
                    }

                    $itemRowsByKey[$sourceSaleItemId] = [
                        'source_system_id' => $sourceSystem->id,
                        'source_sale_item_id' => $sourceSaleItemId,
                        'import_batch_id' => $batch->id,
                        'sale_id' => $saleId,
                        'product_id' => $productId,
                        'source_stock_id' => $itemData['barcode'] !== '' ? $itemData['barcode'] : null,
                        'barcode' => $itemData['barcode'] !== '' ? $itemData['barcode'] : null,
                        'barcode_registered_at' => $itemData['barcode_registered_at'],
                        'quantity' => $itemData['quantity'],
                        'item_weight_g' => $itemData['item_weight_g'],
                        'stone_weight_g' => $itemData['stone_weight_g'],
                        'gold_weight_g' => $itemData['gold_weight_g'],
                        'decrease_weight_g' => $itemData['decrease_weight_g'],
                        'item_total_weight_g' => $itemData['item_total_weight_g'],
                        'gold_amount' => $itemData['gold_amount'],
                        'stone_amount' => $itemData['stone_amount'],
                        'labor_fee' => $itemData['labor_fee'],
                        'polishing_fee' => $itemData['polishing_fee'],
                        'other_fee' => $itemData['other_fee'],
                        'profit_loss_amount' => $itemData['profit_loss_amount'],
                        'item_amount' => $itemData['item_amount'],
                        'item_total_amount' => $itemData['item_total_amount'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($itemRowsByKey !== []) {
                $itemKeys = array_keys($itemRowsByKey);
                $existingItemCount = SaleItem::where('source_system_id', $sourceSystem->id)
                    ->whereIn('source_sale_item_id', $itemKeys)
                    ->count();
                $updatedItems += $existingItemCount;
                $insertedItems += max(0, count($itemRowsByKey) - $existingItemCount);

                SaleItem::upsert(
                    array_values($itemRowsByKey),
                    ['source_system_id', 'source_sale_item_id'],
                    [
                        'import_batch_id',
                        'sale_id',
                        'product_id',
                        'source_stock_id',
                        'barcode',
                        'barcode_registered_at',
                        'quantity',
                        'item_weight_g',
                        'stone_weight_g',
                        'gold_weight_g',
                        'decrease_weight_g',
                        'item_total_weight_g',
                        'gold_amount',
                        'stone_amount',
                        'labor_fee',
                        'polishing_fee',
                        'other_fee',
                        'profit_loss_amount',
                        'item_amount',
                        'item_total_amount',
                        'updated_at',
                    ]
                );
            }
        });
    }

    private function warmCaches(): void
    {
        $this->branchCache = [];
        $this->branchIdSet = [];
        foreach (Branch::all(['id', 'name']) as $branch) {
            $id = (int) $branch->id;
            $this->branchCache[mb_strtolower(trim($branch->name))] = $id;
            $this->branchIdSet[$id] = true;
        }

        $this->branchAliases = [];
        foreach (config('jewelry_sales.branch_aliases', []) as $alias => $targetName) {
            $this->branchAliases[mb_strtolower(trim((string) $alias))] = mb_strtolower(trim((string) $targetName));
        }

        $this->branchIdMappings = app(BranchMappingStore::class)->all();

        $this->productCategoryCache = [];
        foreach (ProductCategory::all(['id', 'name']) as $cat) {
            $this->productCategoryCache[mb_strtolower(trim($cat->name))] = (int) $cat->id;
        }

        $this->staffCache = [];
        foreach (Staff::all(['id', 'name']) as $staff) {
            $this->staffCache[mb_strtolower(trim($staff->name))] = (int) $staff->id;
        }

        if (Region::count() === 0) {
            (new \Database\Seeders\MyanmarRegionTownshipSeeder())->run();
        }

        $this->regionLookup = [];
        $regionAliasMap = [
            'mandalay' => ['mdy', 'မန္တလေး'],
            'yangon' => ['ygn', 'ရန်ကုန်'],
            'sagaing' => ['sgg', 'စစ်ကိုင်း'],
            'shan' => ['ရှမ်း'],
            'naypyidaw' => ['npw', 'npt', 'နေပြည်တော်'],
            'magway' => ['မကွေး'],
            'bago' => ['ပဲခူး'],
            'ayeyarwady' => ['ဧရာဝတီ'],
        ];

        foreach (Region::all(['id', 'name', 'name_mm']) as $region) {
            $key = mb_strtolower($region->name);
            $aliases = $regionAliasMap[$key] ?? [];
            $aliases[] = $key;
            if ($region->name_mm) {
                $aliases[] = $region->name_mm;
            }
            $this->regionLookup[] = [
                'id' => (int) $region->id,
                'name' => $region->name,
                'name_mm' => $region->name_mm,
                'aliases' => array_values(array_unique(array_filter($aliases))),
            ];
        }

        $this->townshipLookup = [];
        $townshipAliasMap = [
            'tada-u' => ['တံတားဉီး', 'တံတားဦး', 'tada u', 'tadau'],
            'patheingyi' => ['ပုသိမ်ကြီး', 'အုန်းချော'],
            'kyaukse' => ['ကျောက်ဆည်'],
            'sagaing' => ['စစ်ကိုင်း'],
        ];

        foreach (Township::all(['id', 'region_id', 'name', 'name_mm']) as $twp) {
            $key = mb_strtolower($twp->name);
            $aliases = $townshipAliasMap[$key] ?? [];
            $aliases[] = $key;
            if ($twp->name_mm) {
                $aliases[] = $twp->name_mm;
            }
            $this->townshipLookup[] = [
                'id' => (int) $twp->id,
                'region_id' => $twp->region_id ? (int) $twp->region_id : null,
                'name' => $twp->name,
                'name_mm' => $twp->name_mm,
                'aliases' => array_values(array_unique(array_filter($aliases))),
            ];
        }
    }

    private function resolveBranchId(string $branchName): ?int
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $branchName) ?? '');
        if ($clean === '') {
            return null;
        }

        $key = mb_strtolower($clean);

        if (isset($this->branchIdMappings[$key])) {
            $mappedId = $this->branchIdMappings[$key];
            if (isset($this->branchIdSet[$mappedId])) {
                return $mappedId;
            }
        }

        $mappedKey = $this->branchAliases[$key] ?? $key;

        return $this->branchCache[$mappedKey] ?? null;
    }

    private function resolveStaffId(string $staffName): ?int
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $staffName) ?? '');
        if ($clean === '' || $clean === '-') {
            return null;
        }

        $key = mb_strtolower($clean);
        if (isset($this->staffCache[$key])) {
            return $this->staffCache[$key];
        }

        $staff = Staff::firstOrCreate(['name' => $clean], ['is_active' => true]);
        $this->staffCache[$key] = (int) $staff->id;

        return (int) $staff->id;
    }

    private function resolveCustomerId(string $nameRaw, string $phoneRaw, string $addressRaw): ?int
    {
        $nameClean = trim(preg_replace('/\s+/u', ' ', $nameRaw) ?? '');
        if ($nameClean === '' || $nameClean === '-') {
            return null;
        }

        $nameNormalized = mb_strtolower($nameClean);
        $phoneNormalized = $this->normalizePhone($phoneRaw);
        $addressClean = trim(str_replace(["\r", "\n"], ' ', $addressRaw));
        if ($addressClean === '-') {
            $addressClean = '';
        }

        $identityHash = hash('sha256', $nameNormalized . '|' . ($phoneNormalized ?? ''));

        if (isset($this->customerCache[$identityHash])) {
            return $this->customerCache[$identityHash];
        }

        [$regionId, $townshipId] = $this->extractRegionAndTownship($addressClean);

        $existing = Customer::where('identity_hash', $identityHash)->first();
        if (!$existing && $phoneNormalized !== null && trim($phoneRaw) !== '') {
            $legacyHash = hash('sha256', $nameNormalized . '|');
            $existing = Customer::where('identity_hash', $legacyHash)
                ->where('phone_raw', trim($phoneRaw))
                ->first();
        }
        if ($existing) {
            $updates = [];
            if ($existing->identity_hash !== $identityHash) {
                $updates['identity_hash'] = $identityHash;
            }
            if (!$existing->phone_normalized && $phoneNormalized !== null) {
                $updates['phone_normalized'] = $phoneNormalized;
            }
            if (!$existing->address_raw && $addressClean !== '') {
                $updates['address_raw'] = $addressClean;
            }
            if (!$existing->region_id && $regionId) {
                $updates['region_id'] = $regionId;
            }
            if (!$existing->township_id && $townshipId) {
                $updates['township_id'] = $townshipId;
            }
            if ($updates !== []) {
                $existing->update($updates);
            }

            $this->customerCache[$identityHash] = (int) $existing->id;
            return (int) $existing->id;
        }

        $customer = Customer::create([
            'identity_hash' => $identityHash,
            'name' => $nameClean,
            'name_normalized' => $nameNormalized,
            'phone_raw' => trim($phoneRaw) !== '' ? trim($phoneRaw) : null,
            'phone_normalized' => $phoneNormalized,
            'address_raw' => $addressClean !== '' ? $addressClean : null,
            'region_id' => $regionId,
            'township_id' => $townshipId,
        ]);

        $this->customerCache[$identityHash] = (int) $customer->id;
        return (int) $customer->id;
    }

    private function resolveProductId(array $itemData): ?int
    {
        $productName = trim($itemData['product_name']);
        if ($productName === '') {
            return null;
        }

        $goldType = trim($itemData['gold_type']);
        $itemName = trim($itemData['item_name']);
        $category = trim($itemData['category']);
        $goldsmith = trim($itemData['goldsmith']);
        $length = trim($itemData['length']);

        $keyParts = array_map(
            fn ($v) => mb_strtolower($v),
            [$productName, $goldType, $itemName, $category, $goldsmith, $length]
        );
        $sourceProductKey = hash('sha256', implode('|', $keyParts));

        $productCategoryId = $this->resolveProductCategoryId($category);

        if (isset($this->productCache[$sourceProductKey])) {
            return $this->productCache[$sourceProductKey];
        }

        $product = Product::firstOrCreate(
            ['source_product_key' => $sourceProductKey],
            [
                'product_name' => $productName,
                'gold_type' => $goldType !== '' ? $goldType : null,
                'item_name' => $itemName !== '' ? $itemName : null,
                'category' => $category !== '' ? $category : null,
                'product_category_id' => $productCategoryId,
                'goldsmith' => $goldsmith !== '' ? $goldsmith : null,
                'length' => $length !== '' ? $length : null,
            ]
        );

        if ($product->product_category_id === null && $productCategoryId !== null) {
            $product->update(['product_category_id' => $productCategoryId]);
        }

        $this->productCache[$sourceProductKey] = (int) $product->id;
        return (int) $product->id;
    }

    private function resolveProductCategoryId(string $categoryName): ?int
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $categoryName) ?? '');
        if ($clean === '' || $clean === '-') {
            return null;
        }

        $key = mb_strtolower($clean);
        if (isset($this->productCategoryCache[$key])) {
            return $this->productCategoryCache[$key];
        }

        $category = ProductCategory::firstOrCreate(['name' => $clean]);
        $this->productCategoryCache[$key] = (int) $category->id;

        return (int) $category->id;
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function extractRegionAndTownship(string $address): array
    {
        if ($address === '') {
            return [null, null];
        }

        $lower = mb_strtolower($address);
        $regionId = null;
        $townshipId = null;

        // Check explicit address -> region/township rules first (e.g., Amarapura, 105x79)
        foreach (config('jewelry_sales.address_township_rules', []) as $rule) {
            if (!empty($rule['pattern']) && preg_match($rule['pattern'], $address) === 1) {
                $targetRegion = mb_strtolower((string) ($rule['region'] ?? ''));
                $targetTownship = mb_strtolower((string) ($rule['township'] ?? ''));

                if ($targetRegion !== '') {
                    foreach ($this->regionLookup as $reg) {
                        if (mb_strtolower($reg['name']) === $targetRegion) {
                            $regionId = $reg['id'];
                            break;
                        }
                    }
                }

                if ($targetTownship !== '') {
                    foreach ($this->townshipLookup as $twp) {
                        if (mb_strtolower($twp['name']) === $targetTownship && ($regionId === null || $twp['region_id'] === $regionId)) {
                            $townshipId = $twp['id'];
                            if ($regionId === null && $twp['region_id']) {
                                $regionId = $twp['region_id'];
                            }
                            break;
                        }
                    }
                }

                if ($regionId !== null || $townshipId !== null) {
                    return [$regionId, $townshipId];
                }
            }
        }

        foreach ($this->townshipLookup as $twp) {
            foreach ($twp['aliases'] as $alias) {
                if (mb_stripos($lower, mb_strtolower($alias)) !== false) {
                    $townshipId = $twp['id'];
                    if ($twp['region_id']) {
                        $regionId = $twp['region_id'];
                    }
                    break 2;
                }
            }
        }

        if ($regionId === null) {
            foreach ($this->regionLookup as $reg) {
                foreach ($reg['aliases'] as $alias) {
                    if (mb_stripos($lower, mb_strtolower($alias)) !== false) {
                        $regionId = $reg['id'];
                        break 2;
                    }
                }
            }
        }

        // Auto-create missing Region / Township from address segments when not found in seeded tables
        $segments = array_values(array_filter(
            array_map(
                fn ($s) => trim(preg_replace('/\s+/u', ' ', (string) $s) ?? ''),
                preg_split('/[၊,]+/u', $address) ?: []
            ),
            fn ($s) => $s !== '' && $s !== '-'
        ));

        if ($segments !== []) {
            if ($regionId === null && $townshipId === null && count($segments) >= 2) {
                if ($this->isPlaceNameCandidate($segments[0])) {
                    $regionId = $this->findOrCreateRegion($segments[0]);
                }
                if ($this->isPlaceNameCandidate($segments[1])) {
                    $townshipId = $this->findOrCreateTownship($regionId, $segments[1]);
                }
            } elseif ($townshipId === null) {
                foreach ($segments as $seg) {
                    if (!$this->isPlaceNameCandidate($seg)) {
                        continue;
                    }
                    if ($regionId !== null && $this->matchesRegionId($seg, $regionId)) {
                        continue;
                    }
                    $townshipId = $this->findOrCreateTownship($regionId, $seg);
                    break;
                }
            }
        }

        return [$regionId, $townshipId];
    }

    private function isPlaceNameCandidate(string $segment): bool
    {
        if (mb_strlen($segment) < 2) {
            return false;
        }
        if (preg_match('/^[\d၀-၉]/u', $segment) === 1 || str_contains($segment, '/')) {
            return false;
        }

        return preg_match('/\p{L}/u', $segment) === 1;
    }

    private function matchesRegionId(string $segment, int $regionId): bool
    {
        $lower = mb_strtolower($segment);
        foreach ($this->regionLookup as $reg) {
            if ($reg['id'] !== $regionId) {
                continue;
            }
            foreach ($reg['aliases'] as $alias) {
                if (mb_stripos($lower, mb_strtolower($alias)) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function findOrCreateRegion(string $name): int
    {
        $lower = mb_strtolower($name);
        foreach ($this->regionLookup as $reg) {
            foreach ($reg['aliases'] as $alias) {
                if (mb_strtolower($alias) === $lower) {
                    return $reg['id'];
                }
            }
        }

        $region = Region::firstOrCreate(['name' => $name]);
        $this->regionLookup[] = [
            'id' => (int) $region->id,
            'name' => $region->name,
            'name_mm' => $region->name_mm,
            'aliases' => [$lower],
        ];

        return (int) $region->id;
    }

    private function findOrCreateTownship(?int $regionId, string $name): int
    {
        $lower = mb_strtolower($name);
        foreach ($this->townshipLookup as $twp) {
            if ($twp['region_id'] === $regionId && in_array($lower, array_map('mb_strtolower', $twp['aliases']), true)) {
                return $twp['id'];
            }
        }

        $township = Township::firstOrCreate([
            'region_id' => $regionId,
            'name' => $name,
        ]);

        $this->townshipLookup[] = [
            'id' => (int) $township->id,
            'region_id' => $regionId,
            'name' => $township->name,
            'name_mm' => $township->name_mm,
            'aliases' => [$lower],
        ];

        return (int) $township->id;
    }

    public function normalizePhone(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $ascii = str_replace(
            ['၀', '၁', '၂', '၃', '၄', '၅', '၆', '၇', '၈', '၉'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $raw
        );

        $digits = preg_replace('/\D+/', '', $ascii) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '959') && strlen($digits) >= 11) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && (strlen($digits) === 9 || strlen($digits) === 10)) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    private function normalizeHeaders(array $rawRow): array
    {
        $normalized = [];
        foreach ($rawRow as $key => $value) {
            $cleanKey = trim((string) $key);
            // Strip BOM if present
            $cleanKey = preg_replace('/^\xEF\xBB\xBF/', '', $cleanKey) ?? $cleanKey;
            // Remove embedded line breaks from wrapped spreadsheet headers (e.g. "စာရင်းသွ\nင်းသူ")
            $cleanKey = str_replace(["\r", "\n"], '', $cleanKey);
            $slug = mb_strtolower(preg_replace('/[\s\(\)\/\-]+/u', '_', $cleanKey) ?? $cleanKey);
            $slug = trim($slug, '_');
            $normalized[$slug] = $value;
        }

        return $normalized;
    }

    private function extractRaw(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            $k = mb_strtolower($key);
            if (array_key_exists($k, $row)) {
                return $row[$k];
            }
        }

        return null;
    }

    private function extractString(array $row, array $keys): string
    {
        $val = $this->extractRaw($row, $keys);
        if ($val === null) {
            return '';
        }
        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d H:i:s');
        }

        return trim((string) $val);
    }

    private function parseDecimal(mixed $value, int $scale = 4): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return round((float) $value, $scale);
        }

        $str = trim(str_replace(',', '', (string) $value));
        if ($str === '' || $str === '-') {
            return 0.0;
        }
        if (str_starts_with($str, '.')) {
            $str = '0' . $str;
        }

        return is_numeric($str) ? round((float) $str, $scale) : 0.0;
    }

    private function parseDateTime(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
