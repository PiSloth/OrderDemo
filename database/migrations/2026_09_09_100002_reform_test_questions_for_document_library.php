<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('test_id')->nullable()->change();
            $table->foreignId('company_document_id')->nullable()->after('test_id')->constrained('company_documents')->nullOnDelete();
            $table->foreignId('training_id')->nullable()->after('company_document_id')->constrained('trainings')->nullOnDelete();
            $table->string('scope', 30)->default('catalog')->after('training_id'); // 'document', 'catalog', 'global'
            $table->string('document_section_reference')->nullable()->after('scope');
            $table->foreignId('created_by')->nullable()->after('sort_order')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();

            $table->index(['company_document_id', 'scope'], 'tq_doc_scope_idx');
        });

        Schema::create('test_has_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('test_question_id')->constrained('test_questions')->cascadeOnDelete();
            $table->decimal('marks', 5, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();

            $table->unique(['test_id', 'test_question_id'], 'thq_test_question_unique');
        });

        // Backfill existing test questions into test_has_questions
        $questions = DB::table('test_questions')->whereNotNull('test_id')->get();
        foreach ($questions as $q) {
            DB::table('test_has_questions')->insertOrIgnore([
                'test_id' => $q->test_id,
                'test_question_id' => $q->id,
                'marks' => $q->marks,
                'sort_order' => $q->sort_order,
                'is_mandatory' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Set training_id based on tests table if available
            $test = DB::table('tests')->where('id', $q->test_id)->first();
            if ($test) {
                DB::table('test_questions')->where('id', $q->id)->update([
                    'training_id' => $test->training_id,
                    'scope' => 'catalog',
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('test_has_questions');

        Schema::table('test_questions', function (Blueprint $table) {
            $table->dropForeign(['company_document_id']);
            $table->dropForeign(['training_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            $table->dropIndex('tq_doc_scope_idx');

            $table->dropColumn([
                'company_document_id',
                'training_id',
                'scope',
                'document_section_reference',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
