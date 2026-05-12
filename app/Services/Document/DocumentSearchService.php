<?php

namespace App\Services\Document;

use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class DocumentSearchService
{
    public function __construct(
        private readonly DocumentSnippetService $snippetService
    ) {
    }

    public function search(?User $user, string $query, array $filters, string $sort, int $page, int $perPage): array
    {
        $query = trim($query);
        $keywords = $this->snippetService->keywords($query);

        if ($this->canUseScout($query, $filters, $sort)) {
            try {
                return $this->searchWithScout($user, $query, $filters, $sort, $page, $perPage, $keywords);
            } catch (\Throwable $e) {
                Log::warning('Document search fell back to DB query after Scout failure.', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->searchWithDatabase($user, $query, $filters, $sort, $page, $perPage, $keywords);
    }

    private function searchWithDatabase(?User $user, string $query, array $filters, string $sort, int $page, int $perPage, array $keywords): array
    {
        $builder = CompanyDocument::query()
            ->visibleTo($user)
            ->with(['department', 'type', 'author'])
            ->withCount('revisions');

        $this->applyFilters($builder, $filters);

        if ($query !== '') {
            $builder->where(function (Builder $q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content_text', 'like', "%{$query}%")
                    ->orWhereHas('department', fn(Builder $dq) => $dq->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('type', fn(Builder $tq) => $tq->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('author', fn(Builder $aq) => $aq->where('name', 'like', "%{$query}%"));
            });
        }

        $this->applySorting($builder, $sort, $query);

        $paginator = $builder->paginate($perPage, ['*'], 'page', max(1, $page));

        $results = collect($paginator->items())->map(function (CompanyDocument $doc) use ($query, $keywords): array {
            $text = (string) ($doc->content_text ?? '');
            $snippet = $this->snippetService->makeSnippet($text, $query);
            $highlightedTitle = $this->snippetService->highlight($doc->title, $keywords);

            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'highlighted_title' => $highlightedTitle,
                'category' => $doc->type?->name,
                'department' => $doc->department?->name,
                'creator' => $doc->author?->name,
                'version' => max(1, (int) $doc->revisions_count),
                'is_announcement' => $doc->announced_at !== null,
                'published_at' => optional($doc->announced_at ?? $doc->created_at)?->format('Y-m-d'),
                'snippet' => $snippet,
            ];
        })->all();

        $paginator->setCollection(collect($results));

        return [
            'results' => $results,
            'paginator' => $paginator,
            'meta' => [
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'has_query' => $query !== '',
            ],
        ];
    }

    private function searchWithScout(?User $user, string $query, array $filters, string $sort, int $page, int $perPage, array $keywords): array
    {
        $builder = CompanyDocument::search($query);

        if (!empty($filters['department_id'])) {
            $builder->where('department_id', (int) $filters['department_id']);
        }
        if (!empty($filters['company_document_type_id'])) {
            $builder->where('category_id', (int) $filters['company_document_type_id']);
        }
        if (!empty($filters['created_by'])) {
            $builder->where('creator_id', (int) $filters['created_by']);
        }
        if (!empty($filters['announcement_only'])) {
            $builder->where('is_announcement', true);
        }

        $rawPaginator = $builder->paginate($perPage, 'page', $page);
        $ids = collect($rawPaginator->items())->pluck('id')->map(fn($id) => (int) $id)->all();

        $models = CompanyDocument::query()
            ->visibleTo($user)
            ->with(['department', 'type', 'author'])
            ->withCount('revisions')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ordered = collect($ids)
            ->map(fn(int $id) => $models->get($id))
            ->filter()
            ->values();

        $results = $ordered->map(function (CompanyDocument $doc) use ($query, $keywords): array {
            $snippet = $this->snippetService->makeSnippet((string) ($doc->content_text ?? ''), $query);
            $highlightedTitle = $this->snippetService->highlight($doc->title, $keywords);

            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'highlighted_title' => $highlightedTitle,
                'category' => $doc->type?->name,
                'department' => $doc->department?->name,
                'creator' => $doc->author?->name,
                'version' => max(1, (int) $doc->revisions_count),
                'is_announcement' => $doc->announced_at !== null,
                'published_at' => optional($doc->announced_at ?? $doc->created_at)?->format('Y-m-d'),
                'snippet' => $snippet,
            ];
        })->all();

        $rawPaginator->setCollection(collect($results));

        return [
            'results' => $results,
            'paginator' => $rawPaginator,
            'meta' => [
                'total' => $rawPaginator->total(),
                'from' => $rawPaginator->firstItem(),
                'to' => $rawPaginator->lastItem(),
                'current_page' => $rawPaginator->currentPage(),
                'last_page' => $rawPaginator->lastPage(),
                'has_query' => $query !== '',
            ],
        ];
    }

    public function suggestions(?User $user, string $query, int $limit = 6): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        return CompanyDocument::query()
            ->visibleTo($user)
            ->where('title', 'like', "%{$query}%")
            ->orderByDesc('announced_at')
            ->orderBy('title')
            ->limit($limit)
            ->pluck('title')
            ->map(fn(string $title): string => trim($title))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function applyFilters(Builder $builder, array $filters): void
    {
        $builder
            ->when(!empty($filters['department_id']), fn(Builder $q) => $q->where('department_id', (int) $filters['department_id']))
            ->when(!empty($filters['company_document_type_id']), fn(Builder $q) => $q->where('company_document_type_id', (int) $filters['company_document_type_id']))
            ->when(!empty($filters['created_by']), fn(Builder $q) => $q->where('created_by', (int) $filters['created_by']))
            ->when(!empty($filters['announcement_only']), fn(Builder $q) => $q->whereNotNull('announced_at'))
            ->when(!empty($filters['version']), fn(Builder $q) => $q->has('revisions', '>=', (int) $filters['version']))
            ->when(!empty($filters['published_from']), fn(Builder $q) => $q->whereDate('announced_at', '>=', $filters['published_from']))
            ->when(!empty($filters['published_to']), fn(Builder $q) => $q->whereDate('announced_at', '<=', $filters['published_to']));
    }

    private function applySorting(Builder $builder, string $sort, string $query): void
    {
        if ($sort === 'newest') {
            $builder->orderByDesc('announced_at')->orderByDesc('updated_at');
            return;
        }

        if ($sort === 'oldest') {
            $builder->orderBy('announced_at')->orderBy('updated_at');
            return;
        }

        if ($sort === 'title_asc') {
            $builder->orderBy('title');
            return;
        }

        if ($sort === 'title_desc') {
            $builder->orderByDesc('title');
            return;
        }

        if ($query !== '') {
            $builder
                ->orderByRaw('CASE WHEN title = ? THEN 1 ELSE 0 END DESC', [$query])
                ->orderByRaw('CASE WHEN title LIKE ? THEN 1 WHEN title LIKE ? THEN 0.6 ELSE 0 END DESC', ["{$query}%", "%{$query}%"])
                ->orderByRaw('CASE WHEN announced_at IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByRaw('CASE WHEN content_text LIKE ? THEN 1 ELSE 0 END DESC', ["%{$query}%"])
                ->orderByDesc('updated_at');

            return;
        }

        $builder->orderByDesc('updated_at');
    }

    private function canUseScout(string $query, array $filters, string $sort): bool
    {
        if ($query === '') {
            return false;
        }

        if (config('scout.driver') === null || config('scout.driver') === 'null') {
            return false;
        }

        // Current Scout path supports these simple equality filters and relevance sort.
        if (!empty($filters['version']) || !empty($filters['published_from']) || !empty($filters['published_to'])) {
            return false;
        }

        return $sort === 'relevance';
    }
}
