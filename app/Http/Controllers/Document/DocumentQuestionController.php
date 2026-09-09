<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\CompanyDocument;
use App\Models\Training\TestOption;
use App\Models\Training\TestQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentQuestionController extends Controller
{
    /**
     * Get all test questions associated with a document.
     */
    public function index(Request $request, CompanyDocument $document): JsonResponse
    {
        abort_unless($request->user()->can('test-question.view'), 403, 'Unauthorized to view test questions.');

        $questions = $document->questions()
            ->with(['options', 'creator:id,name', 'updater:id,name'])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'document_id' => $document->id,
            'title' => $document->title,
            'questions' => $questions,
        ]);
    }

    /**
     * Store a single question for this document.
     */
    public function store(Request $request, CompanyDocument $document): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('test-question.create'), 403, 'Unauthorized to create test questions.');

        $validated = $request->validate([
            'question' => ['required', 'string'],
            'question_type' => ['required', 'string', 'in:MULTIPLE_CHOICE,TRUE_FALSE,MULTI_SELECT'],
            'marks' => ['required', 'numeric', 'min:0.1'],
            'document_section_reference' => ['nullable', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.answer' => ['required', 'string'],
            'options.*.is_correct' => ['required', 'boolean'],
        ]);

        $hasCorrect = collect($validated['options'])->contains(fn($opt) => !empty($opt['is_correct']));
        if (!$hasCorrect) {
            return back()->withErrors(['options' => 'Every question must have at least one correct answer.']);
        }

        $question = DB::transaction(function () use ($document, $validated, $request) {
            $maxOrder = (int) $document->questions()->max('sort_order');

            $q = TestQuestion::create([
                'company_document_id' => $document->id,
                'scope' => 'document',
                'question' => $validated['question'],
                'question_type' => $validated['question_type'],
                'marks' => $validated['marks'],
                'document_section_reference' => $validated['document_section_reference'] ?? null,
                'sort_order' => $maxOrder + 1,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($validated['options'] as $idx => $opt) {
                TestOption::create([
                    'test_question_id' => $q->id,
                    'answer' => $opt['answer'],
                    'is_correct' => (bool) $opt['is_correct'],
                    'sort_order' => $idx + 1,
                ]);
            }

            return $q->load('options');
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Question created successfully.',
                'question' => $question,
            ], 201);
        }

        return back()->with('message', 'Assessment question created successfully.');
    }

    /**
     * Update an existing test question.
     */
    public function update(Request $request, TestQuestion $question): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('test-question.update'), 403, 'Unauthorized to update test questions.');

        $validated = $request->validate([
            'question' => ['required', 'string'],
            'question_type' => ['required', 'string', 'in:MULTIPLE_CHOICE,TRUE_FALSE,MULTI_SELECT'],
            'marks' => ['required', 'numeric', 'min:0.1'],
            'document_section_reference' => ['nullable', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => ['nullable'],
            'options.*.answer' => ['required', 'string'],
            'options.*.is_correct' => ['required', 'boolean'],
        ]);

        $hasCorrect = collect($validated['options'])->contains(fn($opt) => !empty($opt['is_correct']));
        if (!$hasCorrect) {
            return back()->withErrors(['options' => 'Every question must have at least one correct answer.']);
        }

        DB::transaction(function () use ($question, $validated, $request) {
            $question->update([
                'question' => $validated['question'],
                'question_type' => $validated['question_type'],
                'marks' => $validated['marks'],
                'document_section_reference' => $validated['document_section_reference'] ?? null,
                'updated_by' => $request->user()->id,
            ]);

            $existingOptionIds = [];
            foreach ($validated['options'] as $idx => $optData) {
                $optionId = !empty($optData['id']) && is_numeric($optData['id']) ? (int) $optData['id'] : null;

                $option = $optionId ? TestOption::find($optionId) : null;
                if (!$option || $option->test_question_id !== $question->id) {
                    $option = new TestOption(['test_question_id' => $question->id]);
                }

                $option->answer = $optData['answer'];
                $option->is_correct = (bool) $optData['is_correct'];
                $option->sort_order = $idx + 1;
                $option->save();

                $existingOptionIds[] = $option->id;
            }

            // Remove deleted options
            TestOption::where('test_question_id', $question->id)
                ->whereNotIn('id', $existingOptionIds)
                ->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Question updated successfully.',
                'question' => $question->fresh(['options']),
            ]);
        }

        return back()->with('message', 'Assessment question updated successfully.');
    }

    /**
     * Delete a question.
     */
    public function destroy(Request $request, TestQuestion $question): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('test-question.delete'), 403, 'Unauthorized to delete test questions.');

        $question->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Question deleted successfully.']);
        }

        return back()->with('message', 'Assessment question deleted successfully.');
    }

    /**
     * Bulk save / sync all questions analyzed for a document.
     */
    public function sync(Request $request, CompanyDocument $document): RedirectResponse|JsonResponse
    {
        abort_unless(
            $request->user()->can('test-question.create') || $request->user()->can('test-question.update'),
            403,
            'Unauthorized to modify test questions.'
        );

        $validated = $request->validate([
            'questions' => ['present', 'array'],
            'questions.*.id' => ['nullable'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.question_type' => ['required', 'string', 'in:MULTIPLE_CHOICE,TRUE_FALSE,MULTI_SELECT'],
            'questions.*.marks' => ['required', 'numeric', 'min:0.1'],
            'questions.*.document_section_reference' => ['nullable', 'string', 'max:255'],
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

        DB::transaction(function () use ($document, $validated, $request) {
            $existingQuestionIds = [];

            foreach ($validated['questions'] as $qIndex => $qData) {
                $questionId = !empty($qData['id']) && is_numeric($qData['id']) ? (int) $qData['id'] : null;

                $question = $questionId ? TestQuestion::find($questionId) : null;
                if (!$question || $question->company_document_id !== $document->id) {
                    $question = new TestQuestion([
                        'company_document_id' => $document->id,
                        'scope' => 'document',
                        'created_by' => $request->user()->id,
                    ]);
                }

                $question->question = $qData['question'];
                $question->question_type = $qData['question_type'];
                $question->marks = $qData['marks'];
                $question->document_section_reference = $qData['document_section_reference'] ?? null;
                $question->sort_order = $qIndex + 1;
                $question->updated_by = $request->user()->id;
                $question->save();

                $existingQuestionIds[] = $question->id;

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

                TestOption::where('test_question_id', $question->id)
                    ->whereNotIn('id', $existingOptionIds)
                    ->delete();
            }

            // Delete questions removed from this document
            TestQuestion::where('company_document_id', $document->id)
                ->whereNotIn('id', $existingQuestionIds)
                ->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Document test questions synchronized successfully.',
                'questions' => $document->questions()->with('options')->orderBy('sort_order')->get(),
            ]);
        }

        return back()->with('message', 'Document test questions updated successfully.');
    }
}
