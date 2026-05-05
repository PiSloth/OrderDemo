<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('issue_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_erp')->default(false);
            $table->timestamps();
        });

        Schema::create('issue_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('level');
            $table->timestamps();
        });

        Schema::create('issue_importance_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('level');
            $table->timestamps();
        });

        Schema::create('issue_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('issue_root_causes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->foreignId('issue_category_id')->constrained('issue_categories');
            $table->foreignId('issue_priority_id')->constrained('issue_priorities');
            $table->foreignId('issue_importance_id')->constrained('issue_importance_levels');
            $table->string('issue_by')->nullable();
            $table->dateTime('issue_at');
            $table->foreignId('created_by')->constrained('users');
            $table->text('proposed_solution')->nullable();
            $table->foreignId('resolution_department_id')->constrained('departments');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('due_date')->nullable();
            $table->foreignId('issue_status_id')->constrained('issue_statuses');
            $table->dateTime('follow_up_date')->nullable();
            $table->unsignedInteger('follow_up_interval')->default(1);
            $table->dateTime('closed_date')->nullable();
            $table->timestamps();

            $table->index(['due_date', 'closed_date']);
        });

        Schema::create('issue_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('issue_status_id')->constrained('issue_statuses');
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('issue_root_cause_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('root_cause_id')->constrained('issue_root_causes');
            $table->timestamps();
            $table->unique(['issue_id', 'root_cause_id']);
        });

        Schema::create('issue_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_discussion')->default(true);
            $table->boolean('is_log_note')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('issue_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->string('image_path');
            $table->timestamps();
        });

        Schema::create('issue_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->string('action');
            $table->text('description');
            $table->foreignId('performed_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_activity_logs');
        Schema::dropIfExists('issue_images');
        Schema::dropIfExists('issue_messages');
        Schema::dropIfExists('issue_root_cause_logs');
        Schema::dropIfExists('issue_status_histories');
        Schema::dropIfExists('issues');
        Schema::dropIfExists('issue_root_causes');
        Schema::dropIfExists('issue_statuses');
        Schema::dropIfExists('issue_importance_levels');
        Schema::dropIfExists('issue_priorities');
        Schema::dropIfExists('issue_categories');
    }
};

