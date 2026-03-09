<?php

namespace App\Livewire\Jewelry\Purchasing\Groups;

use App\Models\Branch;
use App\Models\GroupNumber;
use App\Models\ItemCategory;
use App\Models\JewelryItem;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Services\Jewelry\JewelryExcelImportService;

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

    public ?int $branchId = null;

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

    public function render()
    {
        $branchId = $this->branchId;

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

        return view('livewire.jewelry.purchasing.groups.index', [
            'groups' => $groupsQuery,
            'branches' => $this->branches,
            'categories' => $categories,
            'productNames' => $productNames,
        ]);
    }
}
