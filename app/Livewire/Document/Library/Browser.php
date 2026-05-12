<?php

namespace App\Livewire\Document\Library;

use App\Models\CompanyDocument;
use App\Models\CompanyDocumentType;
use App\Models\Department;
use App\Models\User;
use App\Services\Document\DocumentSearchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.document-library')]
class Browser extends Component
{
    use WithPagination;

    #[Url]
    public string $mode = 'department';

    #[Url(as: 'doc')]
    public string $doc = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $department = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $creator = '';

    #[Url]
    public bool $announcementOnly = false;

    #[Url]
    public string $version = '';

    #[Url]
    public string $publishedFrom = '';

    #[Url]
    public string $publishedTo = '';

    #[Url]
    public string $sort = 'relevance';

    public array $recentSearches = [];

    public function mount($document = null): void
    {
        if ($document instanceof CompanyDocument) {
            $this->doc = (string) $document->id;
        }

        if (!in_array($this->mode, ['department', 'type'], true)) {
            $this->mode = 'department';
        }

        if (!in_array($this->sort, ['relevance', 'newest', 'oldest', 'title_asc', 'title_desc'], true)) {
            $this->sort = 'relevance';
        }

        $this->recentSearches = session()->get('document_search_recent', []);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->rememberRecentSearch();
        $this->dispatch('document-search-finished');
    }

    public function updatedDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedCreator(): void
    {
        $this->resetPage();
    }

    public function updatedAnnouncementOnly(): void
    {
        $this->resetPage();
    }

    public function updatedVersion(): void
    {
        $this->resetPage();
    }

    public function updatedPublishedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedPublishedTo(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function applySuggestion(string $value): void
    {
        $this->search = trim($value);
        $this->rememberRecentSearch();
        $this->resetPage();
        $this->dispatch('document-search-finished');
    }

    public function clearFilters(): void
    {
        $this->department = '';
        $this->category = '';
        $this->creator = '';
        $this->announcementOnly = false;
        $this->version = '';
        $this->publishedFrom = '';
        $this->publishedTo = '';
        $this->sort = 'relevance';
        $this->resetPage();
        $this->dispatch('document-search-finished');
    }

    public function clearRecentSearches(): void
    {
        $this->recentSearches = [];
        session()->forget('document_search_recent');
    }

    public function openDocument(int $documentId): void
    {
        $this->doc = (string) $documentId;
        $this->dispatch('document-selected');
        $this->dispatch('document-open-finished');
    }

    private function documentsQuery()
    {
        return CompanyDocument::query()
            ->visibleTo(auth()->user())
            ->with(['department', 'type', 'author', 'lastEditor'])
            ->when(trim($this->search) !== '', function ($q) {
                $search = trim($this->search);
                $q->where(function ($qq) use ($search) {
                    $qq->where('title', 'like', "%{$search}%")
                        ->orWhere('content_text', 'like', "%{$search}%")
                        ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('type', fn($tq) => $tq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('author', fn($aq) => $aq->where('name', 'like', "%{$search}%"));
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

    public function render(DocumentSearchService $searchService)
    {
        $docs = $this->documentsQuery()->orderBy('title')->get();
        $selected = null;

        if ($this->doc !== '') {
            $selected = CompanyDocument::query()
                ->visibleTo(auth()->user())
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

        $searchPayload = $searchService->search(
            user: auth()->user(),
            query: $this->search,
            filters: [
                'department_id' => $this->department,
                'company_document_type_id' => $this->category,
                'created_by' => $this->creator,
                'announcement_only' => $this->announcementOnly,
                'version' => $this->version,
                'published_from' => $this->publishedFrom,
                'published_to' => $this->publishedTo,
            ],
            sort: $this->sort,
            page: (int) $this->getPage(),
            perPage: 12,
        );

        $filterOptions = Cache::remember('document_library_filter_meta_v1', now()->addMinutes(10), function (): array {
            return [
                'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
                'categories' => CompanyDocumentType::query()->orderBy('name')->get(['id', 'name']),
                'creators' => User::query()->orderBy('name')->get(['id', 'name']),
            ];
        });

        $suggestions = $searchService->suggestions(auth()->user(), $this->search, 6);

        return view('livewire.document.library.browser', [
            'docs' => $docs,
            'treeByDepartment' => $this->groupedByDepartment($docs),
            'treeByType' => $this->groupedByType($docs),
            'selected' => $selected,
            'searchResults' => $searchPayload['results'],
            'searchPaginator' => $searchPayload['paginator'],
            'searchMeta' => $searchPayload['meta'],
            'filterOptions' => $filterOptions,
            'suggestions' => $suggestions,
        ]);
    }

    private function rememberRecentSearch(): void
    {
        $value = trim($this->search);

        if ($value === '') {
            return;
        }

        $recent = collect($this->recentSearches)
            ->prepend($value)
            ->map(fn(string $item): string => trim($item))
            ->filter()
            ->unique()
            ->take(8)
            ->values()
            ->all();

        $this->recentSearches = $recent;
        session()->put('document_search_recent', $recent);
    }
}
