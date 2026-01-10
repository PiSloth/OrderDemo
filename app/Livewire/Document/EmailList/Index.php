<?php

namespace App\Livewire\Document\EmailList;

use App\Models\EmailList;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $archived = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedArchived(): void
    {
        $this->resetPage();
    }

    public function archive(int $id): void
    {
        EmailList::query()->findOrFail($id)->delete();
        session()->flash('success', 'Email entry archived.');
    }

    public function restore(int $id): void
    {
        EmailList::withTrashed()->findOrFail($id)->restore();
        session()->flash('success', 'Email entry restored.');
    }

    public function deletePermanently(int $id): void
    {
        EmailList::withTrashed()->findOrFail($id)->forceDelete();
        session()->flash('success', 'Email entry deleted permanently.');
    }

    public function render()
    {
        $query = EmailList::query()->with('department');

        if ($this->archived) {
            $query->onlyTrashed();
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $emailLists = $query
            ->orderBy('user_name')
            ->paginate(20);

        return view('livewire.document.email-list.index', [
            'emailLists' => $emailLists,
        ]);
    }
}
