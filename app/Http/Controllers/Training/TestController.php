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
    public function builder(Training $training): Response
    {
        $training->load(['category']);

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

        return Inertia::render('Training/Tests/Builder', [
            'training' => $training,
            'test' => $test,
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
            'questions.*.question' => ['required', 'string'],
            'questions.*.question_type' => ['required', 'string', 'in:MULTIPLE_CHOICE,TRUE_FALSE'],
            'questions.*.marks' => ['required', 'numeric', 'min:0.1'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*.id' => ['nullable'],
            'questions.*.options.*.answer' => ['required', 'string'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($test, $validated) {
            $test->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'passing_score' => $validated['passing_score'],
                'attempt_limit' => $validated['attempt_limit'],
                'status' => $validated['status'],
            ]);

            $existingQuestionIds = [];

            foreach ($validated['questions'] as $qIndex => $qData) {
                $questionId = !empty($qData['id']) && is_numeric($qData['id']) ? (int) $qData['id'] : null;

                $question = $questionId ? TestQuestion::find($questionId) : null;
                if (!$question || $question->test_id !== $test->id) {
                    $question = new TestQuestion(['test_id' => $test->id]);
                }

                $question->question = $qData['question'];
                $question->question_type = $qData['question_type'];
                $question->marks = $qData['marks'];
                $question->sort_order = $qIndex + 1;
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

                // Delete removed options
                TestOption::where('test_question_id', $question->id)->whereNotIn('id', $existingOptionIds)->delete();
            }

            // Delete removed questions
            TestQuestion::where('test_id', $test->id)->whereNotIn('id', $existingQuestionIds)->delete();
        });

        return back()->with('message', 'Test questions and options saved successfully.');
    }
}
