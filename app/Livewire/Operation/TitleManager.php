<?php

namespace App\Livewire\Operation;

use App\Models\NoteTitle;
use App\Models\Scope;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\Actions;

#[Layout('components.layouts.operation')]
#[Title('Operation Titles')]
class TitleManager extends Component
{
    use WithPagination;
    use Actions;

    public bool $showModal = false;
    public ?int $editingId = null;
    public string $formName = '';
    public string $formRemark = '';
    public string $searchTitle = '';
    public string $searchRemark = '';
    public array $formScopeIds = [];

    // public function mount(): void
    // {
    //     // abort_unless(Gate::allows('manageOperationTitles'), 403);
    // }

    public function updatingSearchTitle(): void
    {
        $this->resetPage();
    }

    public function updatingSearchRemark(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset('editingId', 'formName', 'formRemark', 'formScopeIds');
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $title = NoteTitle::findOrFail($id);

        $this->resetValidation();
        $this->editingId = $title->id;
        $this->formName = $title->name;
        $this->formRemark = (string) ($title->remark ?? '');
        $this->formScopeIds = $title->scopes()->pluck('scopes.id')->map(fn($id) => (int) $id)->all();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
        $this->reset('editingId', 'formName', 'formRemark', 'formScopeIds');
    }

    public function save(): void
    {
        $titleId = $this->editingId;
        $validated = $this->validate([
            'formName' => ['required', 'string', 'max:255', 'unique:note_titles,name,' . ($titleId ?? 'NULL') . ',id'],
            'formRemark' => ['nullable', 'string', 'max:255'],
            'formScopeIds' => ['array'],
            'formScopeIds.*' => ['integer', 'exists:scopes,id'],
        ]);

        $scopeIds = collect($validated['formScopeIds'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($titleId) {
            $title = NoteTitle::findOrFail($titleId);
            $title->update([
                'name' => $validated['formName'],
                'remark' => $validated['formRemark'] !== '' ? $validated['formRemark'] : null,
            ]);
            $title->scopes()->sync($scopeIds);

            $this->notification([
                'title' => 'Success',
                'description' => 'Title updated successfully.',
                'icon' => 'success',
            ]);
        } else {
            $title = NoteTitle::create([
                'name' => $validated['formName'],
                'remark' => $validated['formRemark'] !== '' ? $validated['formRemark'] : null,
                'created_by' => Auth::id(),
                'is_active' => true,
            ]);
            $title->scopes()->sync($scopeIds);

            $this->notification([
                'title' => 'Success',
                'description' => 'Title created successfully.',
                'icon' => 'success',
            ]);
        }

        $this->closeModal();
    }

    public function toggleActive(int $id): void
    {
        $title = NoteTitle::findOrFail($id);
        $title->update([
            'is_active' => !$title->is_active,
        ]);

        $this->notification([
            'title' => 'Success',
            'description' => 'Title status updated.',
            'icon' => 'success',
        ]);
    }

    public function delete(int $id): void
    {
        NoteTitle::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->closeModal();
        }

        $this->notification([
            'title' => 'Success',
            'description' => 'Title deleted successfully.',
            'icon' => 'success',
        ]);
    }

    public function render()
    {
        $titleTerm = trim($this->searchTitle);
        $remarkTerm = trim($this->searchRemark);

        return view('livewire.operation.title-manager', [
            'titles' => NoteTitle::query()
                ->withCount('dailyNotes')
                ->with(['creator', 'scopes'])
                ->when($titleTerm !== '', function ($query) use ($titleTerm) {
                    $query->where('name', 'like', '%' . $titleTerm . '%');
                })
                ->when($remarkTerm !== '', function ($query) use ($remarkTerm) {
                    $query->where('remark', 'like', '%' . $remarkTerm . '%');
                })
                ->whereHas('creator', function ($query) {
                    $user = Auth::user();
                    $query->where('department_id', $user->department_id);
                })
                ->latest()
                ->paginate(12),
            'scopeOptions' => Scope::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
