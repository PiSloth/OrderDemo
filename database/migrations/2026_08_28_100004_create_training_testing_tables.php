<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('passing_score', 5, 2)->default(80.00);
            $table->unsignedInteger('attempt_limit')->default(3);
            $table->string('status')->default('active'); // active, draft, inactive
            $table->timestamps();
        });

        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->text('question');
            $table->string('question_type')->default('MULTIPLE_CHOICE'); // MULTIPLE_CHOICE, TRUE_FALSE
            $table->decimal('marks', 5, 2)->default(1.00);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('test_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_question_id')->constrained('test_questions')->cascadeOnDelete();
            $table->text('answer');
            $table->boolean('is_correct')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_assignment_id')->constrained('training_assignments')->cascadeOnDelete();
            $table->foreignId('training_session_id')->nullable()->constrained('training_sessions')->nullOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->dateTime('started_at');
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->default(0);
            $table->decimal('max_score', 5, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('result')->default('IN_PROGRESS'); // IN_PROGRESS, PASSED, FAILED
            $table->timestamps();
        });

        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained('test_attempts')->cascadeOnDelete();
            $table->foreignId('test_question_id')->constrained('test_questions')->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('test_options')->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->decimal('marks_obtained', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_answers');
        Schema::dropIfExists('test_attempts');
        Schema::dropIfExists('test_options');
        Schema::dropIfExists('test_questions');
        Schema::dropIfExists('tests');
    }
};
