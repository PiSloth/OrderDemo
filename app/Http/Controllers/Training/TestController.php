<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Training\Test;
use App\Models\Training\TestOption;
use App\Models\Training\TestQuestion;
use App\Models\Training\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TestController extends Controller
{
    public function builder(Request $request, Training $training): Response
    {
        $training->load([
            'category',
            'companyDocuments' => fn($q) => $q->with([
                'questions' => fn($qu) => $qu->with('options')->orderBy('sort_order'),
            ]),
        ]);

        $test = Test::query()
            ->where('training_id', $training->id)
            ->with(['questions.options'])
            ->first();

        if (!$test) {
            $test = Test::create([
                'training_id' => $training->id,
                'title' => $training->title . ' - Knowledge Check',
                'description' => 'Assessment for ' . $training->title,
                'passing_score' => $training->passing_score,
                'attempt_limit' => 3,
                'status' => 'active',
            ]);
            $test->load(['questions.options']);
        }

        $globalQuestions = TestQuestion::query()
            ->where('scope', 'global')
            ->with('options')
            ->orderBy('sort_order')
            ->get();

        $allDocuments = \App\Models\CompanyDocument::query()
            ->select(['id', 'title', 'company_document_type_id'])
            ->orderBy('title')
            ->get();

        $user = $request->user();

        return Inertia::render('Training/Tests/Builder', [
            'training' => $training,
            'test' => $test,
            'globalQuestions' => $globalQuestions,
            'allDocuments' => $allDocuments,
            'can' => [
                'test_question_create' => (bool) $user?->can('test-question.create'),
                'test_question_update' => (bool) $user?->can('test-question.update'),
                'test_question_delete' => (bool) $user?->can('test-question.delete'),
                'test_question_view' => (bool) $user?->can('test-question.view'),
            ],
        ]);
    }

    public function saveBuilder(Request $request, Test $test): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'attempt_limit' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:active,draft,inactive'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable'],
            'questions.*.scope' => ['nullable', 'string', 'in:document,catalog,global'],
            'questions.*.company_document_id' => ['nullable', 'integer', 'exists:company_documents,id'],
            'questions.*.document_section_reference' => ['nullable', 'string', 'max:255'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.question_type' => ['required', 'string', 'in:MULTIPLE_CHOICE,TRUE_FALSE,MULTI_SELECT'],
            'questions.*.marks' => ['required', 'numeric', 'min:0.1'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*.id' => ['nullable'],
            'questions.*.options.*.answer' => ['required', 'string'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
        ]);

        foreach ($validated['questions'] as $qData) {
            $hasCorrect = collect($qData['options'])->contains(fn($opt) => !empty($opt['is_correct']));
            if (!$hasCorrect) {
                return back()->withErrors(['questions' => 'Every question must have at least one marked correct answer.']);
            }
        }

        DB::transaction(function () use ($test, $validated, $request) {
            $test->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'passing_score' => $validated['passing_score'],
                'attempt_limit' => $validated['attempt_limit'],
                'status' => $validated['status'],
            ]);

            $syncedPivotData = [];
            $catalogQuestionIdsKept = [];

            foreach ($validated['questions'] as $qIndex => $qData) {
                $questionId = !empty($qData['id']) && is_numeric($qData['id']) ? (int) $qData['id'] : null;
                $scope = $qData['scope'] ?? 'catalog';

                $question = $questionId ? TestQuestion::find($questionId) : null;

                if ($scope === 'document' || $scope === 'global') {
                    // Document or global question: if already exists, attach or update
                    if (!$question) {
                        // Creating a document question on the fly if doc id provided
                        $question = new TestQuestion([
                            'company_document_id' => $qData['company_document_id'] ?? null,
                            'scope' => $scope,
                            'created_by' => $request->user()->id,
                        ]);
                    } else {
                        // Convert question to document or global scope
                        $question->scope = $scope;
                        $question->company_document_id = ($scope === 'document') ? ($qData['company_document_id'] ?? $question->company_document_id) : null;
                        if ($scope === 'document') {
                            $question->test_id = null; // Unbind from single test
                        }
                    }
                    $question->question = $qData['question'];
                    $question->question_type = $qData['question_type'];
                    $question->marks = $qData['marks'];
                    $question->document_section_reference = $qData['document_section_reference'] ?? null;
                    $question->updated_by = $request->user()->id;
                    $question->save();

                    // If document question, ensure the training references this document
                    if ($scope === 'document' && !empty($question->company_document_id)) {
                        $test->training->companyDocuments()->syncWithoutDetaching([$question->company_document_id]);
                    }
                } else {
                    // Catalog-owned question
                    if (!$question) {
                        $question = new TestQuestion([
                            'test_id' => $test->id,
                            'training_id' => $test->training_id,
                            'scope' => 'catalog',
                            'created_by' => $request->user()->id,
                        ]);
                    } else {
                        $question->scope = 'catalog';
                        $question->training_id = $test->training_id;
                        $question->test_id = $test->id;
                        $question->company_document_id = null;
                    }

                    $question->question = $qData['question'];
                    $question->question_type = $qData['question_type'];
                    $question->marks = $qData['marks'];
                    $question->sort_order = $qIndex + 1;
                    $question->updated_by = $request->user()->id;
                    $question->save();

                    $catalogQuestionIdsKept[] = $question->id;
                }

                // Options synchronization
                $existingOptionIds = [];
                foreach ($qData['options'] as $oIndex => $oData) {
                    $optionId = !empty($oData['id']) && is_numeric($oData['id']) ? (int) $oData['id'] : null;

                    $option = $optionId ? TestOption::find($optionId) : null;
                    if (!$option || $option->test_question_id !== $question->id) {
                        $option = new TestOption(['test_question_id' => $question->id]);
                    }

                    $option->answer = $oData['answer'];
                    $option->is_correct = (bool) $oData['is_correct'];
                    $option->sort_order = $oIndex + 1;
                    $option->save();

                    $existingOptionIds[] = $option->id;
                }

                TestOption::where('test_question_id', $question->id)->whereNotIn('id', $existingOptionIds)->delete();

                // Prepare pivot data for test_has_questions
                $syncedPivotData[$question->id] = [
                    'marks' => $qData['marks'],
                    'sort_order' => $qIndex + 1,
                    'is_mandatory' => true,
                ];
            }

            // Sync pivot table test_has_questions
            $test->questions()->sync($syncedPivotData);

            // Only delete catalog-scoped questions belonging to this test that were removed
            TestQuestion::where('test_id', $test->id)
                ->where('scope', 'catalog')
                ->whereNotIn('id', $catalogQuestionIdsKept)
                ->delete();
        });

        return back()->with('message', 'Test questions and options saved successfully.');
    }
}
