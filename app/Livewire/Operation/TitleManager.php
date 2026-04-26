<?php

namespace App\Livewire\Operation;

use App\Models\NoteTitle;
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
        $this->reset('editingId', 'formName', 'formRemark');
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $title = NoteTitle::findOrFail($id);

        $this->resetValidation();
        $this->editingId = $title->id;
        $this->formName = $title->name;
        $this->formRemark = (string) ($title->remark ?? '');
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
        $this->reset('editingId', 'formName', 'formRemark');
    }

    public function save(): void
    {
        $titleId = $this->editingId;
        $validated = $this->validate([
            'formName' => ['required', 'string', 'max:255', 'unique:note_titles,name,' . ($titleId ?? 'NULL') . ',id'],
            'formRemark' => ['nullable', 'string', 'max:255'],
        ]);

        if ($titleId) {
            $title = NoteTitle::findOrFail($titleId);
            $title->update([
                'name' => $validated['formName'],
                'remark' => $validated['formRemark'] !== '' ? $validated['formRemark'] : null,
            ]);

            $this->notification([
                'title' => 'Success',
                'description' => 'Title updated successfully.',
                'icon' => 'success',
            ]);
        } else {
            NoteTitle::create([
                'name' => $validated['formName'],
                'remark' => $validated['formRemark'] !== '' ? $validated['formRemark'] : null,
                'created_by' => Auth::id(),
                'is_active' => true,
            ]);

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
                ->with('creator')
                ->when($titleTerm !== '', function ($query) use ($titleTerm) {
                    $query->where('name', 'like', '%' . $titleTerm . '%');
                })
                ->when($remarkTerm !== '', function ($query) use ($remarkTerm) {
                    $query->where('remark', 'like', '%' . $remarkTerm . '%');
                })
                ->latest()
                ->paginate(12),
        ]);
    }
}
