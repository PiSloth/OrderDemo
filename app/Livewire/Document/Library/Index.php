<?php

namespace App\Livewire\Document\Library;

use App\Models\CompanyDocument;
use App\Models\CompanyDocumentType;
use App\Models\Department;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $department_id = '';

    #[Url]
    public string $company_document_type_id = '';

    public $departments;
    public $documentTypes;

    public function mount(): void
    {
        $this->departments = Department::query()->orderBy('name')->get();
        $this->documentTypes = CompanyDocumentType::query()->orderBy('name')->get();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentId(): void
    {
        $this->resetPage();
    }

    public function updatedCompanyDocumentTypeId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = CompanyDocument::query()
            ->with(['department', 'author', 'type'])
            ->orderByDesc('announced_at')
            ->orderByDesc('updated_at');

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('type', function ($tq) use ($search) {
                        $tq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('department', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('author', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->department_id !== '') {
            $query->where('department_id', (int) $this->department_id);
        }

        if ($this->company_document_type_id !== '') {
            $query->where('company_document_type_id', (int) $this->company_document_type_id);
        }

        $documents = $query->paginate(15);

        return view('livewire.document.library.index', [
            'documents' => $documents,
        ]);
    }
}
