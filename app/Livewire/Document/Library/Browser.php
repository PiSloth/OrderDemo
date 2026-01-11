<?php

namespace App\Livewire\Document\Library;

use App\Models\CompanyDocument;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.document-library')]
class Browser extends Component
{
    #[Url]
    public string $mode = 'department';

    #[Url(as: 'doc')]
    public string $doc = '';

    #[Url]
    public string $search = '';

    public function mount($document = null): void
    {
        if ($document instanceof CompanyDocument) {
            $this->doc = (string) $document->id;
        }

        if (!in_array($this->mode, ['department', 'type'], true)) {
            $this->mode = 'department';
        }
    }

    public function openDocument(int $documentId): void
    {
        $this->doc = (string) $documentId;
    }

    private function documentsQuery()
    {
        return CompanyDocument::query()
            ->with(['department', 'type', 'author', 'lastEditor'])
            ->when(trim($this->search) !== '', function ($q) {
                $search = trim($this->search);
                $q->where(function ($qq) use ($search) {
                    $qq->where('title', 'like', "%{$search}%")
                        ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('type', fn($tq) => $tq->where('name', 'like', "%{$search}%"));
                });
            });
    }

    private function groupedByDepartment(Collection $docs): Collection
    {
        return $docs
            ->groupBy(fn($d) => $d->department?->name ?? 'Unknown')
            ->map(fn($group) => $group->groupBy(fn($d) => $d->type?->name ?? 'Unknown'))
            ->sortKeys();
    }

    private function groupedByType(Collection $docs): Collection
    {
        return $docs
            ->groupBy(fn($d) => $d->type?->name ?? 'Unknown')
            ->map(fn($group) => $group->groupBy(fn($d) => $d->department?->name ?? 'Unknown'))
            ->sortKeys();
    }

    public function render()
    {
        $docs = $this->documentsQuery()
            ->orderBy('title')
            ->get();

        $selected = null;
        if ($this->doc !== '') {
            $selected = CompanyDocument::query()
                ->with([
                    'department',
                    'type',
                    'author',
                    'lastEditor',
                    'revisions.editor',
                    'revisions.department',
                    'revisions.type',
                ])
                ->find((int) $this->doc);
        }

        return view('livewire.document.library.browser', [
            'docs' => $docs,
            'treeByDepartment' => $this->groupedByDepartment($docs),
            'treeByType' => $this->groupedByType($docs),
            'selected' => $selected,
        ]);
    }
}
