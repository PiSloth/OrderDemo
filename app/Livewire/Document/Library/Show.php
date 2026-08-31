<?php

namespace App\Livewire\Document\Library;

use App\Models\CompanyDocument;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public CompanyDocument $document;

    public function mount(CompanyDocument $document): void
    {
        $this->document = $document->load([
            'department',
            'type',
            'author',
            'lastEditor',
            'revisions.editor',
            'revisions.type',
            'revisions.department',
            'trainings.category',
            'trainings.scopes.department',
            'trainings.scopes.officePosition',
        ]);
    }

    public function render()
    {
        return view('livewire.document.library.show');
    }
}
