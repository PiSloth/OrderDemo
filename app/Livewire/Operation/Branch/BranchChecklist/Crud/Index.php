<?php

namespace App\Livewire\Operation\Branch\BranchChecklist\Crud;

use App\Models\Branch;
use App\Models\BranchChecklist;
use App\Models\Department;
use App\Models\Location;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.operation')]
#[Title('Checklist Config')]
class Index extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $description = '';
    public bool $is_active = true;
    public $branch_id = null;
    public $department_id = null;
    public $location_id = null;

    public function mount(): void
    {
        $this->location_id = Auth()->user()->location_id;
        $this->department_id = Auth()->user()->department_id;
        // dd($this->location_id, $this->department_id);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $item = BranchChecklist::findOrFail($id);
        $this->editingId = $item->id;
        $this->title = $item->title;
        $this->description = (string) ($item->description ?? '');
        $this->is_active = (bool) $item->is_active;
        $this->branch_id = $item->branch_id;
        $this->department_id = $item->department_id;
        $this->location_id = $item->location_id;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        BranchChecklist::updateOrCreate(['id' => $this->editingId], $data);

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        BranchChecklist::findOrFail($id)->delete();
    }

    protected function resetForm(): void
    {
        $this->reset('showForm', 'editingId', 'title', 'description', 'branch_id', 'department_id', 'location_id');
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.operation.branch.branch-checklist.crud.index', [
            'items' => BranchChecklist::query()->with(['branch', 'department', 'location'])->latest('id')->paginate(10),
            'branches' => Branch::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
        ]);
    }
}
