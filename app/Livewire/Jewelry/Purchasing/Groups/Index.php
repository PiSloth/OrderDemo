<?php

namespace App\Livewire\Jewelry\Purchasing\Groups;

use App\Models\Branch;
use App\Models\GroupNumber;
use App\Models\ItemCategory;
use App\Models\JewelryItem;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Services\Jewelry\JewelryExcelImportService;
use Spatie\SimpleExcel\SimpleExcelWriter;

#[Layout('components.layouts.app')]
#[Title('Jewelry Groups')]
class Index extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $new_number = '';
    public string $new_po_reference = '';

    public $importFile;
    public array $importErrors = [];
    public int $importedCount = 0;

    public $updateFile;
    public int $updatedCount = 0;

    public $externalFile;
    public int $externalUpdatedCount = 0;

    public ?int $branchId = null;

    public string $search = '';

    /** Export filters (multi-select) */
    public array $exportPoRefs = [];
    public array $exportBarcodes = [];
    public array $exportProductNames = [];

    /** Export option list searches */
    public string $exportPoRefSearch = '';
    public string $exportBarcodeSearch = '';
    public string $exportProductNameSearch = '';

    /** @var array<int,array{id:int,name:string}> */
    public array $branches = [];

    // Category CRUD
    public string $category_name = '';
    public ?int $editing_category_id = null;

    // Product -> Category mapping
    public string $mapping_product_name = '';
    public ?int $mapping_category_id = null;

    public function mount(): void
    {
        $this->branches = Branch::query()
            ->where('is_jewelry_shop', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => ['id' => (int) $b->id, 'name' => (string) $b->name])
            ->all();
    }

    public function updatedBranchId($value): void
    {
        $this->branchId = $value ? (int) $value : null;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function resetExport(): void
    {
        $this->exportPoRefs = [];
        $this->exportBarcodes = [];
        $this->exportProductNames = [];
        $this->exportPoRefSearch = '';
        $this->exportBarcodeSearch = '';
        $this->exportProductNameSearch = '';
        $this->resetValidation([
            'exportPoRefs',
            'exportBarcodes',
            'exportProductNames',
            'exportPoRefSearch',
            'exportBarcodeSearch',
            'exportProductNameSearch',
        ]);
    }

    public function removeExportPoRef(string $value): void
    {
        $value = trim($value);
        $this->exportPoRefs = array_values(array_filter(
            (array) $this->exportPoRefs,
            fn($v) => trim((string) $v) !== $value
        ));
    }

    public function removeExportBarcode(string $value): void
    {
        $value = trim($value);
        $this->exportBarcodes = array_values(array_filter(
            (array) $this->exportBarcodes,
            fn($v) => trim((string) $v) !== $value
        ));
    }

    public function removeExportProductName(string $value): void
    {
        $value = trim($value);
        $this->exportProductNames = array_values(array_filter(
            (array) $this->exportProductNames,
            fn($v) => trim((string) $v) !== $value
        ));
    }

    private function exportItemsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $branchId = $this->branchId;

        $poRefs = array_values(array_filter(array_map(fn($v) => trim((string) $v), (array) $this->exportPoRefs), fn($v) => $v !== ''));
        $barcodes = array_values(array_filter(array_map(fn($v) => trim((string) $v), (array) $this->exportBarcodes), fn($v) => $v !== ''));
        $productNames = array_values(array_filter(array_map(fn($v) => trim((string) $v), (array) $this->exportProductNames), fn($v) => $v !== ''));

        return JewelryItem::query()
            ->join('group_numbers', 'group_numbers.id', '=', 'jewelry_items.group_number_id')
            ->when(!is_null($branchId), fn($q) => $q->where('jewelry_items.branch_id', (int) $branchId))
            ->when(!empty($poRefs), fn($q) => $q->whereIn('group_numbers.po_reference', $poRefs))
            ->when(!empty($barcodes), fn($q) => $q->whereIn('jewelry_items.barcode', $barcodes))
            ->when(!empty($productNames), fn($q) => $q->whereIn('jewelry_items.product_name', $productNames))
            ->orderByDesc('jewelry_items.id');
    }

    private function formatHalfStone(?int $stonePrice): string
    {
        $stonePrice = (int) ($stonePrice ?? 0);
        if ($stonePrice === 0) {
            return '';
        }

        $half = ((float) $stonePrice) / 2;
        return fmod($half, 1.0) == 0.0
            ? (string) ((int) $half)
            : rtrim(rtrim(number_format($half, 1, '.', ''), '0'), '.');
    }

    private function zeroIfBlank(?string $value): string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? '0' : $value;
    }

    private function formatLaborFee(?int $profitLaborFee, ?int $stonePrice): string
    {
        $profitLaborFee = (int) ($profitLaborFee ?? 0);
        $halfStone = ((float) ((int) ($stonePrice ?? 0))) / 2;
        $sum = ((float) $profitLaborFee) + $halfStone;

        if ($sum == 0.0) {
            return '0';
        }

        return fmod($sum, 1.0) == 0.0
            ? (string) ((int) $sum)
            : rtrim(rtrim(number_format($sum, 1, '.', ''), '0'), '.');
    }

    public function exportFilteredItems()
    {
        $hasAnyExportFilter = !empty($this->exportPoRefs) || !empty($this->exportBarcodes) || !empty($this->exportProductNames);
        if (!$hasAnyExportFilter) {
            $this->addError('exportFilters', 'Please select at least one filter (PO Ref / Barcode / Product name) to export.');
            return null;
        }

        $tempFilePath = tempnam(sys_get_temp_dir(), 'jewelry_items_') . '.xlsx';

        $writer = SimpleExcelWriter::create($tempFilePath)
            ->addHeader([
                'ID',
                'Lot/Serial Number',
                'Purchase Order',
                'Barcode',
                'Addition Wastage(g)',
                'Labor Fee(s)',
            ]);

        $this->exportItemsQuery()
            ->select([
                'jewelry_items.id as id',
                'jewelry_items.external_id',
                'jewelry_items.lot_serial',
                'group_numbers.po_reference as po_reference',
                'jewelry_items.barcode',
                'jewelry_items.profit_loss',
                'jewelry_items.profit_labor_fee',
                'jewelry_items.stone_price',
            ])
            ->chunkById(1000, function ($chunk) use ($writer) {
                foreach ($chunk as $r) {
                    $profitLoss = (float) ($r->profit_loss ?? 0);
                    $profitLossStr = $profitLoss == 0.0 ? '0' : number_format($profitLoss, 2, '.', '');

                    $writer->addRow([
                        'ID' => $this->zeroIfBlank((string) ($r->external_id ?? '')),
                        'Lot/Serial Number' => $this->zeroIfBlank((string) ($r->lot_serial ?? '')),
                        'Purchase Order' => $this->zeroIfBlank((string) ($r->po_reference ?? '')),
                        'Barcode' => $this->zeroIfBlank((string) ($r->barcode ?? '')),
                        'Addition Wastage(g)' => $this->zeroIfBlank($profitLossStr),
                        'Labor Fee(s)' => $this->zeroIfBlank($this->formatLaborFee($r->profit_labor_fee ?? null, $r->stone_price ?? null)),
                    ]);
                }
            }, 'jewelry_items.id', 'id');

        $writer->close();

        $filename = Carbon::now()->format('Ymd_His') . '-jewelry-items.xlsx';
        return Response::download($tempFilePath, $filename)->deleteFileAfterSend(true);
    }

    public function createCategory(): void
    {
        $validated = $this->validate([
            'category_name' => ['required', 'string', 'max:255', Rule::unique('item_categories', 'name')],
        ]);

        ItemCategory::create([
            'name' => trim((string) $validated['category_name']),
        ]);

        $this->resetCategoryForm();
        session()->flash('success', 'Category created.');
    }

    public function editCategory(int $categoryId): void
    {
        $cat = ItemCategory::query()->findOrFail($categoryId);
        $this->editing_category_id = (int) $cat->id;
        $this->category_name = (string) $cat->name;
    }

    public function updateCategory(): void
    {
        if (is_null($this->editing_category_id)) {
            return;
        }

        $categoryId = (int) $this->editing_category_id;
        $validated = $this->validate([
            'category_name' => ['required', 'string', 'max:255', Rule::unique('item_categories', 'name')->ignore($categoryId)],
        ]);

        ItemCategory::query()
            ->whereKey($categoryId)
            ->update([
                'name' => trim((string) $validated['category_name']),
            ]);

        $this->resetCategoryForm();
        session()->flash('success', 'Category updated.');
    }

    public function deleteCategory(int $categoryId): void
    {
        ItemCategory::query()->whereKey($categoryId)->delete();

        if ((int) $this->editing_category_id === (int) $categoryId) {
            $this->resetCategoryForm();
        }

        session()->flash('success', 'Category deleted.');
    }

    public function cancelEditCategory(): void
    {
        $this->resetCategoryForm();
    }

    private function resetCategoryForm(): void
    {
        $this->category_name = '';
        $this->editing_category_id = null;
        $this->resetValidation(['category_name']);
    }

    public function updatedMappingProductName($value): void
    {
        $this->mapping_product_name = trim((string) $value);
        $this->mapping_category_id = null;

        if ($this->mapping_product_name === '') {
            return;
        }

        $branchId = $this->branchId;

        $existing = JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->where('product_name', $this->mapping_product_name)
            ->select('item_category_id', DB::raw('COUNT(*) as c'))
            ->groupBy('item_category_id')
            ->orderByDesc('c')
            ->value('item_category_id');

        $this->mapping_category_id = $existing ? (int) $existing : null;
    }

    public function saveProductCategoryMapping(): void
    {
        $branchId = $this->branchId;

        $validated = $this->validate([
            'mapping_product_name' => ['required', 'string', 'max:255'],
            'mapping_category_id' => ['required', 'integer', Rule::exists('item_categories', 'id')],
        ]);

        $productName = trim((string) $validated['mapping_product_name']);
        $categoryId = (int) $validated['mapping_category_id'];

        $q = JewelryItem::query()->where('product_name', $productName);
        if (!is_null($branchId)) {
            $q->where('branch_id', (int) $branchId);
        }

        $q->update([
            'item_category_id' => $categoryId,
            'updated_at' => now(),
        ]);

        session()->flash('success', 'Product category mapping saved.');
    }

    public function create(): void
    {
        $validated = $this->validate([
            'new_number' => ['required', 'string', 'max:255', Rule::unique('group_numbers', 'number')],
            'new_po_reference' => ['nullable', 'string', 'max:255'],
        ]);

        GroupNumber::create([
            'number' => trim($validated['new_number']),
            'po_reference' => $validated['new_po_reference'] !== '' ? trim($validated['new_po_reference']) : null,
            'purchase_by' => auth()->id(),
            'is_purchase' => false,
            'purchase_status' => 'not_started',
        ]);

        $this->new_number = '';
        $this->new_po_reference = '';
        session()->flash('success', 'Group created.');
        $this->resetPage();
    }

    public function importNewGroup(): void
    {
        $this->resetValidation();
        $this->importErrors = [];
        $this->importedCount = 0;

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,csv,ods'],
        ]);

        $path = method_exists($this->importFile, 'getRealPath') ? $this->importFile->getRealPath() : null;
        $path = $path ?: (method_exists($this->importFile, 'getPathname') ? $this->importFile->getPathname() : null);
        if (!$path) {
            $this->addError('importFile', 'Could not read uploaded file.');
            return;
        }

        $service = app(JewelryExcelImportService::class);
        $userId = (int) auth()->id();

        $newGroupId = null;
        $createdGroupNumbers = [];
        $groupsCount = 0;

        try {
            DB::transaction(function () use ($service, $path, $userId, &$newGroupId, &$createdGroupNumbers, &$groupsCount) {
                $tmpNumber = 'TMP-' . (string) Str::uuid();

                $group = GroupNumber::create([
                    'number' => $tmpNumber,
                    'po_reference' => null,
                    'purchase_by' => null,
                    'is_purchase' => false,
                    'purchase_status' => 'not_started',
                ]);

                $group->update([
                    'number' => 'JV-' . str_pad((string) $group->id, 6, '0', STR_PAD_LEFT),
                ]);

                $result = $service->importIntoGroup($group, $path, $userId);
                if (!empty($result['errors'])) {
                    $this->importErrors = $result['errors'];
                    throw new \RuntimeException($result['errors'][0] ?? 'Import failed.');
                }

                $this->importedCount = (int) ($result['inserted'] ?? 0);
                $newGroupId = $group->id;

                $groups = $result['groups'] ?? [];
                $groupsCount = is_array($groups) ? count($groups) : 0;
                $newGroups = is_array($groups) ? array_values(array_filter($groups, fn($g) => !empty($g['is_new']))) : [];
                $createdGroupNumbers = array_values(array_filter(array_map(fn($g) => (string) ($g['number'] ?? ''), $newGroups), fn($v) => $v !== ''));
            });
        } catch (\Throwable $e) {
            if (empty($this->importErrors)) {
                $this->addError('importFile', $e->getMessage() ?: 'Import failed.');
            }
            return;
        }

        $this->importFile = null;

        if ($groupsCount > 1) {
            $suffix = empty($createdGroupNumbers) ? '' : (' New groups: ' . implode(', ', $createdGroupNumbers));
            session()->flash('success', "Imported {$this->importedCount} items across {$groupsCount} groups. First group created." . $suffix);
        } else {
            session()->flash('success', "Imported {$this->importedCount} items into new group.");
        }

        $this->dispatch('jewelry-import-success');

        // No redirect after import (per requirement).
    }

    public function updateExistingByBarcode(): void
    {
        $this->resetValidation();
        $this->importErrors = [];
        $this->updatedCount = 0;

        $this->validate([
            'updateFile' => ['required', 'file', 'mimes:xlsx,csv,ods'],
        ]);

        $path = method_exists($this->updateFile, 'getRealPath') ? $this->updateFile->getRealPath() : null;
        $path = $path ?: (method_exists($this->updateFile, 'getPathname') ? $this->updateFile->getPathname() : null);
        if (!$path) {
            $this->addError('updateFile', 'Could not read uploaded file.');
            return;
        }

        $service = app(JewelryExcelImportService::class);

        try {
            $result = $service->updateExistingByBarcode($path, $this->branchId);
        } catch (\Throwable $e) {
            $this->addError('updateFile', $e->getMessage() ?: 'Update failed.');
            return;
        }

        $this->updatedCount = (int) ($result['updated'] ?? 0);

        $messages = [];
        $messages = array_merge($messages, (array) ($result['errors'] ?? []));
        $messages = array_merge($messages, (array) ($result['not_found'] ?? []));
        $messages = array_merge($messages, (array) ($result['warnings'] ?? []));
        $messages = array_values(array_filter(array_map(fn($v) => trim((string) $v), $messages), fn($v) => $v !== ''));
        $this->importErrors = $messages;

        $this->updateFile = null;

        $notFoundCount = is_array($result['not_found'] ?? null) ? count($result['not_found']) : 0;
        $suffix = $notFoundCount > 0 ? " ({$notFoundCount} barcodes not found)" : '';
        session()->flash('success', "Updated {$this->updatedCount} item(s) by barcode{$suffix}.");

        $this->dispatch('jewelry-update-success');
    }

    public function updateExternalByMatch(): void
    {
        $this->resetValidation();
        $this->importErrors = [];
        $this->externalUpdatedCount = 0;

        $this->validate([
            'externalFile' => ['required', 'file', 'mimes:xlsx,csv,ods'],
        ]);

        $path = method_exists($this->externalFile, 'getRealPath') ? $this->externalFile->getRealPath() : null;
        $path = $path ?: (method_exists($this->externalFile, 'getPathname') ? $this->externalFile->getPathname() : null);
        if (!$path) {
            $this->addError('externalFile', 'Could not read uploaded file.');
            return;
        }

        $service = app(JewelryExcelImportService::class);

        try {
            $result = $service->updateExternalIdAndLotSerialByMatch($path, $this->branchId);
        } catch (\Throwable $e) {
            $this->addError('externalFile', $e->getMessage() ?: 'Update failed.');
            return;
        }

        $this->externalUpdatedCount = (int) ($result['updated'] ?? 0);

        $messages = [];
        $messages = array_merge($messages, (array) ($result['errors'] ?? []));
        $messages = array_merge($messages, (array) ($result['not_found'] ?? []));
        $messages = array_merge($messages, (array) ($result['warnings'] ?? []));
        $messages = array_values(array_filter(array_map(fn($v) => trim((string) $v), $messages), fn($v) => $v !== ''));
        $this->importErrors = $messages;

        $this->externalFile = null;

        $notFoundCount = is_array($result['not_found'] ?? null) ? count($result['not_found']) : 0;
        $suffix = $notFoundCount > 0 ? " ({$notFoundCount} row(s) not matched)" : '';
        session()->flash('success', "Updated {$this->externalUpdatedCount} item(s) external fields{$suffix}.");

        $this->dispatch('jewelry-external-success');
    }

    public function render()
    {
        $branchId = $this->branchId;

        $search = trim($this->search);

        $groupsQuery = GroupNumber::query()
            ->with(['purchaseBy'])
            ->when(!is_null($branchId), function ($q) use ($branchId) {
                $q->whereExists(function ($sub) use ($branchId) {
                    $sub->selectRaw('1')
                        ->from('jewelry_items')
                        ->whereColumn('jewelry_items.group_number_id', 'group_numbers.id')
                        ->where('jewelry_items.branch_id', (int) $branchId);
                });
            })
            ->when($search !== '', function ($q) use ($search, $branchId) {
                $like = "%{$search}%";

                $q->where(function ($w) use ($like, $branchId) {
                    $w->where('po_reference', 'like', $like)
                        ->orWhereExists(function ($sub) use ($like, $branchId) {
                            $sub->selectRaw('1')
                                ->from('jewelry_items')
                                ->whereColumn('jewelry_items.group_number_id', 'group_numbers.id')
                                ->when(!is_null($branchId), fn($sq) => $sq->where('jewelry_items.branch_id', (int) $branchId))
                                ->where(function ($iw) use ($like) {
                                    $iw->where('jewelry_items.product_name', 'like', $like)
                                        ->orWhere('jewelry_items.barcode', 'like', $like);
                                });
                        });
                });
            })
            ->withCount([
                'jewelryItems as items_count' => function ($q) use ($branchId) {
                    $q->when(!is_null($branchId), fn($qq) => $qq->where('branch_id', (int) $branchId));
                },
                'jewelryItems as registered_items_count' => function ($q) use ($branchId) {
                    $q->where('is_register', true)
                        ->when(!is_null($branchId), fn($qq) => $qq->where('branch_id', (int) $branchId));
                },
            ])
            ->orderByRaw("(`purchase_status` = 'processing') DESC")
            ->orderByRaw("CASE WHEN `purchase_status` = 'processing' THEN `registered_items_count` ELSE -1 END DESC")
            ->orderByDesc('id')
            ->paginate(20);

        $categories = ItemCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $productNames = JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->where('product_name', '!=', '')
            ->select('product_name')
            ->distinct()
            ->orderBy('product_name')
            ->limit(500)
            ->pluck('product_name')
            ->map(fn($v) => (string) $v)
            ->all();

        $poRefSearch = trim($this->exportPoRefSearch);
        $barcodeSearch = trim($this->exportBarcodeSearch);
        $productNameSearch = trim($this->exportProductNameSearch);

        $exportPoRefOptions = GroupNumber::query()
            ->whereNotNull('po_reference')
            ->where('po_reference', '!=', '')
            ->when($poRefSearch !== '', function ($q) use ($poRefSearch) {
                $q->where('po_reference', 'like', "%{$poRefSearch}%");
            })
            ->when(!is_null($branchId), function ($q) use ($branchId) {
                $q->whereExists(function ($sub) use ($branchId) {
                    $sub->selectRaw('1')
                        ->from('jewelry_items')
                        ->whereColumn('jewelry_items.group_number_id', 'group_numbers.id')
                        ->where('jewelry_items.branch_id', (int) $branchId);
                });
            })
            ->select('po_reference')
            ->distinct()
            ->orderBy('po_reference')
            ->limit(500)
            ->pluck('po_reference')
            ->map(fn($v) => (string) $v)
            ->all();

        $exportBarcodeOptions = JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->when($barcodeSearch !== '', function ($q) use ($barcodeSearch) {
                $q->where('barcode', 'like', "%{$barcodeSearch}%");
            })
            ->select('barcode')
            ->distinct()
            ->orderBy('barcode')
            ->limit(500)
            ->pluck('barcode')
            ->map(fn($v) => (string) $v)
            ->all();

        $exportProductNameOptions = JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->where('product_name', '!=', '')
            ->when($productNameSearch !== '', function ($q) use ($productNameSearch) {
                $q->where('product_name', 'like', "%{$productNameSearch}%");
            })
            ->select('product_name')
            ->distinct()
            ->orderBy('product_name')
            ->limit(500)
            ->pluck('product_name')
            ->map(fn($v) => (string) $v)
            ->all();

        $exportPreviewItems = [];
        $exportPreviewHasMore = false;
        $hasAnyExportFilter = !empty($this->exportPoRefs) || !empty($this->exportBarcodes) || !empty($this->exportProductNames);
        if ($hasAnyExportFilter) {
            $preview = $this->exportItemsQuery()
                ->limit(201)
                ->get([
                    'jewelry_items.id',
                    'jewelry_items.product_name',
                    'jewelry_items.barcode',
                    'group_numbers.po_reference as po_reference',
                ]);

            $exportPreviewHasMore = $preview->count() > 200;
            $exportPreviewItems = $exportPreviewHasMore ? $preview->take(200)->values()->all() : $preview->all();
        }

        $qualityNames = JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->whereNotNull('quality')
            ->where('quality', '!=', '')
            ->select('quality')
            ->distinct()
            ->orderBy('quality')
            ->limit(500)
            ->pluck('quality')
            ->map(fn($v) => (string) $v)
            ->all();

        return view('livewire.jewelry.purchasing.groups.index', [
            'groups' => $groupsQuery,
            'branches' => $this->branches,
            'categories' => $categories,
            'productNames' => $productNames,
            'qualityNames' => $qualityNames,
            'exportPoRefOptions' => $exportPoRefOptions,
            'exportBarcodeOptions' => $exportBarcodeOptions,
            'exportProductNameOptions' => $exportProductNameOptions,
            'exportPreviewItems' => $exportPreviewItems,
            'exportPreviewHasMore' => $exportPreviewHasMore,
        ]);
    }
}
