<?php

namespace App\Livewire\Jewelry\Purchasing\Groups;

use App\Models\Branch;
use App\Models\GroupNumber;
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

    public ?int $branchId = null;

    /** @var array<int,array{id:int,name:string}> */
    public array $branches = [];

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

        try {
            DB::transaction(function () use ($service, $path, $userId, &$newGroupId) {
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
            });
        } catch (\Throwable $e) {
            if (empty($this->importErrors)) {
                $this->addError('importFile', $e->getMessage() ?: 'Import failed.');
            }
            return;
        }

        $this->importFile = null;
        session()->flash('success', "Imported {$this->importedCount} items into new group.");

        $this->dispatch('jewelry-import-success');

        // No redirect after import (per requirement).
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
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.jewelry.purchasing.groups.index', [
            'groups' => $groupsQuery,
            'branches' => $this->branches,
        ]);
    }
}
