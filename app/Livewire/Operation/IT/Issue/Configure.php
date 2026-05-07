<?php

namespace App\Livewire\Operation\IT\Issue;

use App\IssueTracking\Models\IssueCategory;
use App\IssueTracking\Models\IssueImportanceLevel;
use App\IssueTracking\Models\IssuePriority;
use App\IssueTracking\Models\IssueRootCause;
use App\IssueTracking\Models\IssueStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.operation')]
#[Title('Issue Configure')]
class Configure extends Component
{
    public string $tab = 'categories';

    public string $newName = '';
    public ?int $newLevel = null;
    public string $newCode = '';
    public bool $newIsErp = false;

    public ?int $editId = null;
    public string $editName = '';
    public ?int $editLevel = null;
    public string $editCode = '';
    public bool $editIsErp = false;
    public array $defaultCategoryNames = ['Sale Module', 'Purchase Module', 'Inventory Module', 'Finance Module','Shwetatar Module', 'Accounting  Module', 'HR Module', 'Payroll Module', 'Report Module', 'Other Module'];


    // public function mount (): void
    // {
    //     // Optionally seed default data on mount
    //     $this->seedDefaults();
    // }

    public function seedDefaults(): void
    {

        $initialCount = IssueCategory::count() + IssuePriority::count() + IssueImportanceLevel::count() + IssueStatus::count() + IssueRootCause::count();
        if ($initialCount > 0) {
            session()->flash('message', 'Default data already exists. No new data created.');
            return;
        }

        foreach ([['Low', 1], ['Medium', 2], ['High', 3], ['Critical', 4]] as [$name, $level]) {
            IssuePriority::firstOrCreate(['name' => $name], ['level' => $level]);
        }
        foreach ([['Minor', 1], ['Major', 2], ['Business Critical', 3]] as [$name, $level]) {
            IssueImportanceLevel::firstOrCreate(['name' => $name], ['level' => $level]);
        }
        foreach ([['OPEN', 'Open'], ['ASSIGNED', 'Assigned'], ['IN_PROGRESS', 'In Progress'], ['PENDING', 'Pending'], ['DONE', 'Done'], ['CLOSED', 'Closed']] as [$code, $name]) {
            IssueStatus::firstOrCreate(['code' => $code], ['name' => $name]);
        }
        foreach (['User Fault', 'SOP Unclear', 'SOP Careless', 'System Fault'] as $name) {
            IssueRootCause::firstOrCreate(['name' => $name]);
        }

        foreach ($this->defaultCategoryNames as $name) {
            IssueCategory::firstOrCreate(['name' => $name], ['is_erp' => true]);
        }
        // IssueCategory::firstOrCreate(['name' => 'ERP'], ['is_erp' => true]);
        // IssueCategory::firstOrCreate(['name' => 'IT Support'], ['is_erp' => false]);

        session()->flash('message', 'Default setup data created.');
    }

    public function createItem(): void
    {
        match ($this->tab) {
            'categories' => IssueCategory::create(['name' => $this->newName, 'is_erp' => $this->newIsErp]),
            'priorities' => IssuePriority::create(['name' => $this->newName, 'level' => (int) $this->newLevel]),
            'importance-levels' => IssueImportanceLevel::create(['name' => $this->newName, 'level' => (int) $this->newLevel]),
            'statuses' => IssueStatus::create(['name' => $this->newName, 'code' => $this->newCode]),
            'root-causes' => IssueRootCause::create(['name' => $this->newName]),
            default => null,
        };

        $this->reset(['newName', 'newLevel', 'newCode', 'newIsErp']);
    }

    public function startEdit(string $entity, int $id): void
    {
        $row = $this->modelFor($entity)::findOrFail($id);
        $this->editId = $row->id;
        $this->editName = $row->name;
        $this->editLevel = $row->level ?? null;
        $this->editCode = $row->code ?? '';
        $this->editIsErp = (bool) ($row->is_erp ?? false);
        $this->tab = $entity;
    }

    public function updateItem(string $entity): void
    {
        $row = $this->modelFor($entity)::findOrFail($this->editId);
        $data = ['name' => $this->editName];
        if ($entity === 'categories') { $data['is_erp'] = $this->editIsErp; }
        if (in_array($entity, ['priorities', 'importance-levels'], true)) { $data['level'] = (int) $this->editLevel; }
        if ($entity === 'statuses') { $data['code'] = $this->editCode; }
        $row->update($data);

        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId', 'editName', 'editLevel', 'editCode', 'editIsErp']);
    }

    public function deleteItem(string $entity, int $id): void
    {
        $this->modelFor($entity)::findOrFail($id)->delete();
    }

    private function modelFor(string $entity): string
    {
        return match ($entity) {
            'categories' => IssueCategory::class,
            'priorities' => IssuePriority::class,
            'importance-levels' => IssueImportanceLevel::class,
            'statuses' => IssueStatus::class,
            'root-causes' => IssueRootCause::class,
            default => IssueCategory::class,
        };
    }

    public function render()
    {
        return view('livewire.operation.it.issue.configure', [
            'categories' => IssueCategory::query()->orderByDesc('is_erp')->orderBy('name')->get(),
            'priorities' => IssuePriority::query()->orderBy('level')->get(),
            'importanceLevels' => IssueImportanceLevel::query()->orderBy('level')->get(),
            'statuses' => IssueStatus::query()->orderBy('id')->get(),
            'rootCauses' => IssueRootCause::query()->orderBy('name')->get(),
        ]);
    }
}
