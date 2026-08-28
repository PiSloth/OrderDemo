<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\CompanyDocument;
use App\Models\Department;
use App\Models\OfficePosition;
use App\Models\Training\Training;
use App\Models\Training\TrainingCategory;
use App\Models\Training\TrainingScope;
use App\Models\Training\TrainingTrigger;
use App\Services\Training\TrainingAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $status = $request->input('status');

        $query = Training::query()
            ->with([
                'category',
                'scopes.department',
                'scopes.officePosition',
                'companyDocuments:id,title,department_id,company_document_type_id',
                'test',
            ])
            ->withCount(['assignments', 'sessions', 'companyDocuments']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('training_category_id', $categoryId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $trainings = $query->orderBy('title')->paginate(12)->withQueryString();

        $categories = TrainingCategory::query()->orderBy('name')->get(['id', 'name']);
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $officePositions = OfficePosition::query()->orderBy('name')->get(['id', 'name']);
        $allDocuments = CompanyDocument::query()->orderBy('title')->get(['id', 'title', 'department_id', 'company_document_type_id']);

        // Data for PDF matrix report export
        $activeUsers = \App\Models\User::query()
            ->where('suspended', false)
            ->with([
                'department:id,name',
                'officePosition:id,name',
                'trainingAssignments.training:id,code,title,passing_score,retrain_interval,retrain_unit',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department_id', 'office_position_id']);

        $allActiveTrainings = Training::query()
            ->where('status', 'active')
            ->with(['scopes.department:id,name', 'scopes.officePosition:id,name', 'category:id,name'])
            ->get(['id', 'code', 'title', 'training_category_id', 'passing_score', 'retrain_interval', 'retrain_unit']);

        return Inertia::render('Training/Trainings/Index', [
            'trainings' => $trainings,
            'allActiveTrainings' => $allActiveTrainings,
            'activeUsers' => $activeUsers,
            'categories' => $categories,
            'departments' => $departments,
            'officePositions' => $officePositions,
            'allDocuments' => $allDocuments,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:trainings,code'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'training_category_id' => ['nullable', 'integer', 'exists:training_categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:100'],
            'retrain_interval' => ['required', 'numeric', 'min:0'],
            'retrain_unit' => ['required', 'string', 'in:day,month,year'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'string', 'in:active,draft,archived'],
            'scopes' => ['nullable', 'array'],
            'scopes.*.department_id' => ['required', 'integer', 'exists:departments,id'],
            'scopes.*.office_position_id' => ['nullable', 'integer', 'exists:office_positions,id'],
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['integer', 'exists:company_documents,id'],
        ]);

        if (empty($validated['training_category_id']) && !empty($validated['new_category_name'])) {
            $cat = TrainingCategory::firstOrCreate(['name' => trim($validated['new_category_name'])]);
            $validated['training_category_id'] = $cat->id;
        }

        unset($validated['new_category_name']);

        $scopes = $validated['scopes'] ?? [];
        $documentIds = $validated['document_ids'] ?? [];
        unset($validated['scopes'], $validated['document_ids']);

        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $training = Training::create($validated);

        // Sync scopes
        foreach ($scopes as $scope) {
            TrainingScope::firstOrCreate([
                'training_id' => $training->id,
                'department_id' => $scope['department_id'],
                'office_position_id' => !empty($scope['office_position_id']) ? $scope['office_position_id'] : null,
            ]);
        }

        // Sync linked company documents
        if (!empty($documentIds)) {
            $training->companyDocuments()->sync($documentIds);
        }

        return back()->with('message', 'Training catalog created successfully.');
    }

    public function update(Request $request, Training $training): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:trainings,code,' . $training->id],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'training_category_id' => ['nullable', 'integer', 'exists:training_categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:100'],
            'retrain_interval' => ['required', 'numeric', 'min:0'],
            'retrain_unit' => ['required', 'string', 'in:day,month,year'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'string', 'in:active,draft,archived'],
            'scopes' => ['nullable', 'array'],
            'scopes.*.department_id' => ['required', 'integer', 'exists:departments,id'],
            'scopes.*.office_position_id' => ['nullable', 'integer', 'exists:office_positions,id'],
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['integer', 'exists:company_documents,id'],
        ]);

        if (empty($validated['training_category_id']) && !empty($validated['new_category_name'])) {
            $cat = TrainingCategory::firstOrCreate(['name' => trim($validated['new_category_name'])]);
            $validated['training_category_id'] = $cat->id;
        }

        unset($validated['new_category_name']);

        $scopes = $validated['scopes'] ?? [];
        $documentIds = $validated['document_ids'] ?? [];
        unset($validated['scopes'], $validated['document_ids']);

        $validated['updated_by'] = $request->user()->id;

        $training->update($validated);

        // Sync scopes
        TrainingScope::where('training_id', $training->id)->delete();
        foreach ($scopes as $scope) {
            TrainingScope::firstOrCreate([
                'training_id' => $training->id,
                'department_id' => $scope['department_id'],
                'office_position_id' => !empty($scope['office_position_id']) ? $scope['office_position_id'] : null,
            ]);
        }

        // Sync linked company documents
        $training->companyDocuments()->sync($documentIds);

        return back()->with('message', 'Training catalog updated successfully.');
    }

    public function destroy(Training $training): RedirectResponse
    {
        $training->delete();

        return back()->with('message', 'Training catalog deleted successfully.');
    }

    public function triggerAssign(Request $request, Training $training, TrainingAssignmentService $assignmentService): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $trigger = TrainingTrigger::create([
            'training_id' => $training->id,
            'trigger_type' => 'MANUAL',
            'reason' => $validated['reason'] ?? 'Manual assignment triggered by administrator',
            'status' => 'ACTIVE',
            'created_by' => $request->user()->id,
        ]);

        $count = $assignmentService->assignByScope($training, $trigger);

        return back()->with('message', "Training assigned to {$count} employee(s) in scope.");
    }
}
