<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\CompanyDocument;
use App\Models\CompanyDocumentType;
use App\Models\Department;
use App\Models\User;
use App\Services\CompanyDocumentService;
use App\Services\Document\DocumentSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DocumentLibraryController extends Controller
{
    public function index(Request $request, DocumentSearchService $searchService): Response
    {
        $user = $request->user();
        $mode = in_array($request->input('mode'), ['department', 'type'], true) ? $request->input('mode') : 'department';
        $docId = $request->input('doc');
        $search = (string) $request->input('q', $request->input('search', ''));
        $department = (string) $request->input('department', '');
        $category = (string) $request->input('category', '');
        $creator = (string) $request->input('creator', '');
        $announcementOnly = $request->boolean('announcementOnly', false);
        $version = (string) $request->input('version', '');
        $publishedFrom = (string) $request->input('publishedFrom', '');
        $publishedTo = (string) $request->input('publishedTo', '');
        $sort = in_array($request->input('sort'), ['relevance', 'newest', 'oldest', 'title_asc', 'title_desc'], true)
            ? $request->input('sort')
            : 'relevance';
        $page = (int) $request->input('page', 1);

        // Fetch tree documents (lightweight attributes)
        $docs = CompanyDocument::query()
            ->visibleTo($user)
            ->with(['department:id,name', 'type:id,name', 'author:id,name'])
            ->orderBy('title')
            ->get(['id', 'title', 'department_id', 'company_document_type_id', 'created_by', 'announced_at']);

        $treeByDepartment = $this->groupedByDepartment($docs);
        $treeByType = $this->groupedByType($docs);

        // Fetch selected document if specified, or default to first document if available and no search
        $selected = null;
        if (!empty($docId)) {
            $selected = CompanyDocument::query()
                ->visibleTo($user)
                ->with([
                    'department',
                    'type',
                    'author',
                    'lastEditor',
                    'revisions' => fn($q) => $q->with(['editor:id,name', 'department:id,name', 'type:id,name'])->orderByDesc('version'),
                ])
                ->find((int) $docId);
        }

        // Run search & pagination
        $searchPayload = $searchService->search(
            user: $user,
            query: $search,
            filters: [
                'department_id' => $department,
                'company_document_type_id' => $category,
                'created_by' => $creator,
                'announcement_only' => $announcementOnly,
                'version' => $version,
                'published_from' => $publishedFrom,
                'published_to' => $publishedTo,
            ],
            sort: $sort,
            page: $page,
            perPage: 12,
        );

        $filterOptions = Cache::remember('document_library_filter_meta_v1', now()->addMinutes(10), function (): array {
            return [
                'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
                'categories' => CompanyDocumentType::query()->orderBy('name')->get(['id', 'name']),
                'creators' => User::query()->orderBy('name')->get(['id', 'name']),
            ];
        });

        $suggestions = $searchService->suggestions($user, $search, 6);

        return Inertia::render('Document/Library/Index', [
            'treeByDepartment' => $treeByDepartment,
            'treeByType' => $treeByType,
            'selectedDocument' => $selected,
            'searchResults' => $searchPayload['results'],
            'searchPaginator' => $searchPayload['paginator'],
            'searchMeta' => $searchPayload['meta'],
            'filterOptions' => $filterOptions,
            'suggestions' => $suggestions,
            'filters' => [
                'mode' => $mode,
                'doc' => $docId ? (string) $docId : ($selected ? (string) $selected->id : ''),
                'q' => $search,
                'department' => $department,
                'category' => $category,
                'creator' => $creator,
                'announcementOnly' => $announcementOnly,
                'version' => $version,
                'publishedFrom' => $publishedFrom,
                'publishedTo' => $publishedTo,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(CompanyDocument $document, Request $request): RedirectResponse
    {
        return redirect()->route('document.library.index', array_merge($request->query(), ['doc' => $document->id]));
    }

    public function create(): Response
    {
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $documentTypes = CompanyDocumentType::query()->orderBy('name')->get(['id', 'name']);
        $trainings = \App\Models\Training\Training::query()
            ->where('status', 'active')
            ->with(['category:id,name', 'scopes.department:id,name', 'scopes.officePosition:id,name'])
            ->orderBy('title')
            ->get(['id', 'code', 'title', 'training_category_id', 'passing_score', 'retrain_interval', 'retrain_unit']);

        return Inertia::render('Document/Library/Create', [
            'departments' => $departments,
            'documentTypes' => $documentTypes,
            'trainings' => $trainings,
        ]);
    }

    public function store(Request $request, CompanyDocumentService $service, \App\Services\Training\TrainingAssignmentService $assignmentService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company_document_type_id' => ['nullable', 'integer', 'exists:company_document_types,id', 'required_without:new_document_type'],
            'new_document_type' => ['nullable', 'string', 'max:80', 'required_without:company_document_type_id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'announced_at' => ['nullable', 'date'],
            'body' => ['required', 'string'],
            'training_ids' => ['nullable', 'array'],
            'training_ids.*' => ['integer', 'exists:trainings,id'],
            'training_required' => ['nullable', 'boolean'],
            'training_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($validated['company_document_type_id']) && !empty($validated['new_document_type'])) {
            $type = CompanyDocumentType::firstOrCreate(['name' => trim($validated['new_document_type'])]);
            $validated['company_document_type_id'] = $type->id;
        }

        $trainingIds = $validated['training_ids'] ?? [];
        $trainingRequired = (bool) ($validated['training_required'] ?? false);
        $trainingReason = $validated['training_reason'] ?? null;

        unset($validated['new_document_type'], $validated['training_ids'], $validated['training_required'], $validated['training_reason']);

        $document = $service->createDocument($validated, $request->user()->id);

        if (!empty($trainingIds)) {
            $document->trainings()->sync($trainingIds);
        }

        if ($trainingRequired && !empty($trainingIds)) {
            $totalAssigned = 0;
            foreach ($trainingIds as $tId) {
                $training = \App\Models\Training\Training::find($tId);
                if (!$training) continue;

                $trigger = \App\Models\Training\TrainingTrigger::create([
                    'training_id' => $training->id,
                    'trigger_type' => 'WORKFLOW_CHANGE',
                    'source_type' => CompanyDocument::class,
                    'source_id' => $document->id,
                    'reason' => $trainingReason ?: 'New Document published: ' . $document->title,
                    'status' => 'ACTIVE',
                    'created_by' => $request->user()->id,
                ]);

                $totalAssigned += $assignmentService->assignByScope($training, $trigger);
            }

            return redirect()
                ->route('document.library.index', ['doc' => $document->id])
                ->with('message', "Document created and announced across {$totalAssigned} employee(s) in training scope.");
        }

        return redirect()
            ->route('document.library.index', ['doc' => $document->id])
            ->with('message', 'Document created successfully.');
    }

    public function edit(CompanyDocument $document): Response
    {
        $document->load([
            'department',
            'type',
            'author',
            'lastEditor',
            'trainings:id,code,title,passing_score,status,training_category_id',
            'revisions' => fn($q) => $q->with(['editor:id,name', 'department:id,name', 'type:id,name'])->orderByDesc('version'),
        ]);

        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $documentTypes = CompanyDocumentType::query()->orderBy('name')->get(['id', 'name']);
        $trainings = \App\Models\Training\Training::query()
            ->where('status', 'active')
            ->with(['category:id,name', 'scopes.department:id,name', 'scopes.officePosition:id,name'])
            ->orderBy('title')
            ->get(['id', 'code', 'title', 'training_category_id', 'passing_score', 'retrain_interval', 'retrain_unit']);

        return Inertia::render('Document/Library/Edit', [
            'document' => $document,
            'departments' => $departments,
            'documentTypes' => $documentTypes,
            'trainings' => $trainings,
        ]);
    }

    public function update(Request $request, CompanyDocument $document, CompanyDocumentService $service, \App\Services\Training\TrainingAssignmentService $assignmentService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company_document_type_id' => ['nullable', 'integer', 'exists:company_document_types,id', 'required_without:new_document_type'],
            'new_document_type' => ['nullable', 'string', 'max:80', 'required_without:company_document_type_id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'announced_at' => ['nullable', 'date'],
            'body' => ['required', 'string'],
            'training_required' => ['nullable', 'boolean'],
            'training_ids' => ['nullable', 'array'],
            'training_ids.*' => ['integer', 'exists:trainings,id'],
            'training_id' => ['nullable', 'integer', 'exists:trainings,id'],
            'training_reason' => ['nullable', 'string', 'max:500'],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($validated['company_document_type_id']) && !empty($validated['new_document_type'])) {
            $type = CompanyDocumentType::firstOrCreate(['name' => trim($validated['new_document_type'])]);
            $validated['company_document_type_id'] = $type->id;
        }

        $trainingRequired = (bool) ($validated['training_required'] ?? false);
        $trainingIds = $validated['training_ids'] ?? [];
        if (!empty($validated['training_id']) && !in_array($validated['training_id'], $trainingIds)) {
            $trainingIds[] = $validated['training_id'];
        }
        $trainingReason = $validated['training_reason'] ?? $validated['change_summary'] ?? null;

        unset($validated['new_document_type'], $validated['training_required'], $validated['training_ids'], $validated['training_id'], $validated['training_reason'], $validated['change_summary']);

        $service->updateDocument($document, $validated, $request->user()->id);

        // Sync linked trainings
        $document->trainings()->sync($trainingIds);

        $latestRevision = $document->revisions()->orderByDesc('version')->first();

        // Handle training triggers if training required or trainings linked
        if ($trainingRequired && !empty($trainingIds)) {
            $totalAssigned = 0;
            foreach ($trainingIds as $tId) {
                $training = \App\Models\Training\Training::find($tId);
                if (!$training) continue;

                $trigger = \App\Models\Training\TrainingTrigger::create([
                    'training_id' => $training->id,
                    'trigger_type' => 'WORKFLOW_CHANGE',
                    'source_type' => \App\Models\CompanyDocumentRevision::class,
                    'source_id' => $latestRevision?->id ?? $document->id,
                    'source_version_id' => $latestRevision?->version,
                    'reason' => $trainingReason ?: 'Document updated: ' . $document->title,
                    'status' => 'ACTIVE',
                    'created_by' => $request->user()->id,
                ]);

                $totalAssigned += $assignmentService->assignByScope($training, $trigger);
            }

            return redirect()
                ->route('document.library.index', ['doc' => $document->id])
                ->with('message', "Document revision updated. Training trigger announced and assigned to {$totalAssigned} employee(s) in scope.");
        }

        return redirect()
            ->route('document.library.index', ['doc' => $document->id])
            ->with('message', 'Document updated successfully.');
    }

    public function destroy(CompanyDocument $document): RedirectResponse
    {
        $document->delete();

        return redirect()
            ->route('document.library.index')
            ->with('message', 'Document deleted successfully.');
    }

    public function suggestions(Request $request, DocumentSearchService $searchService): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $suggestions = $searchService->suggestions($request->user(), $query, 8);

        return response()->json($suggestions);
    }

    public function searchApi(Request $request, DocumentSearchService $searchService): JsonResponse
    {
        $user = $request->user();
        $query = (string) $request->input('q', '');
        $department = (string) $request->input('department', '');
        $category = (string) $request->input('category', '');
        $announcementOnly = $request->boolean('announcementOnly', false);
        $sort = (string) $request->input('sort', 'relevance');
        $page = (int) $request->input('page', 1);

        $searchPayload = $searchService->search(
            user: $user,
            query: $query,
            filters: [
                'department_id' => $department,
                'company_document_type_id' => $category,
                'announcement_only' => $announcementOnly,
            ],
            sort: $sort,
            page: $page,
            perPage: 15,
        );

        return response()->json([
            'results' => $searchPayload['results'],
            'meta' => $searchPayload['meta'],
        ]);
    }

    private function groupedByDepartment(Collection $docs): array
    {
        return $docs
            ->groupBy(fn($d) => $d->department?->name ?? 'General / Unassigned')
            ->map(fn($group) => $group->groupBy(fn($d) => $d->type?->name ?? 'General'))
            ->sortKeys()
            ->toArray();
    }

    private function groupedByType(Collection $docs): array
    {
        return $docs
            ->groupBy(fn($d) => $d->type?->name ?? 'General')
            ->map(fn($group) => $group->groupBy(fn($d) => $d->department?->name ?? 'General / Unassigned'))
            ->sortKeys()
            ->toArray();
    }
}
