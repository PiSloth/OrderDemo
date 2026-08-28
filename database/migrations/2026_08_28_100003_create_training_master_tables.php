<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('training_category_id')->nullable()->constrained('training_categories')->nullOnDelete();
            $table->double('retrain_interval', 8, 2)->default(0);
            $table->string('retrain_unit')->default('month'); // day, month, year
            $table->decimal('passing_score', 5, 2)->default(80.00);
            $table->string('status')->default('active'); // active, draft, archived
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('training_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('office_position_id')->constrained('office_positions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['training_id', 'department_id', 'office_position_id'], 'training_scope_unique');
        });

        Schema::create('training_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->string('trigger_type'); // NEW_USER, WORKFLOW_CHANGE, RETRAINING, MANUAL
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('source_version_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_trigger_id')->nullable()->constrained('training_triggers')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, IN_PROGRESS, COMPLETED, OVERDUE, EXPIRED
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_code');
            $table->string('title')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, OPEN, IN_PROGRESS, COMPLETED, CANCELLED
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('training_session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->foreignId('training_assignment_id')->constrained('training_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('attendance_status')->default('REGISTERED'); // REGISTERED, ATTENDED, ABSENT, EXCUSED
            $table->dateTime('attended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_session_participants');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('training_assignments');
        Schema::dropIfExists('training_triggers');
        Schema::dropIfExists('training_scopes');
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('training_categories');
    }
};
