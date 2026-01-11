<?php

namespace App\Livewire\Document\Library;

use App\Models\CompanyDocument;
use App\Models\Department;
use App\Models\CompanyDocumentType;
use App\Services\CompanyDocumentService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.document-library')]
class Edit extends Component
{
    public CompanyDocument $document;

    public string $title = '';
    public string $company_document_type_id = '';
    public string $new_document_type = '';
    public string $department_id = '';
    public ?string $announced_at = null;
    public string $body = '';

    public $departments;
    public $documentTypes;

    public function mount(CompanyDocument $document): void
    {
        $this->document = $document;
        $this->title = $document->title;
        $this->company_document_type_id = (string) $document->company_document_type_id;
        $this->department_id = (string) $document->department_id;
        $this->announced_at = $document->announced_at?->format('Y-m-d');
        $this->body = $document->body;

        $this->departments = Department::query()->orderBy('name')->get();
        $this->documentTypes = CompanyDocumentType::query()->orderBy('name')->get();
    }

    public function save(CompanyDocumentService $service): void
    {
        try {
            $validated = $this->validate([
                'title' => ['required', 'string', 'max:255'],
                'company_document_type_id' => ['nullable', 'integer', 'exists:company_document_types,id', 'required_without:new_document_type'],
                'new_document_type' => ['nullable', 'string', 'max:80', 'required_without:company_document_type_id'],
                'department_id' => ['required', 'integer', 'exists:departments,id'],
                'announced_at' => ['nullable', 'date'],
                'body' => ['required', 'string'],
            ]);

            if (empty($validated['company_document_type_id'])) {
                $type = CompanyDocumentType::firstOrCreate(['name' => trim($validated['new_document_type'])]);
                $validated['company_document_type_id'] = $type->id;
            }

            unset($validated['new_document_type']);

            $service->updateDocument($this->document, $validated, auth()->id());

            session()->flash('success', 'Document updated.');
            $this->redirectRoute('document.library.show', ['document' => $this->document->id], navigate: true);
        } catch (ValidationException $e) {
            $this->dispatch('document-save-failed');
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('document-save-failed');
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.document.library.edit');
    }
}
