<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_dependency_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained('kpi_task_templates')->cascadeOnDelete();
            $table->string('name');
            $table->string('frequency', 20)->default('daily');
            $table->time('reminder_start_time')->default('08:45:00');
            $table->time('cutoff_time')->nullable();
            $table->foreignId('first_approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('final_approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['task_template_id', 'is_active'], 'kpi_dep_groups_template_active_idx');
        });

        Schema::create('kpi_dependency_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependency_group_id')->constrained('kpi_dependency_groups')->cascadeOnDelete();
            $table->foreignId('task_assignment_id')->constrained('kpi_task_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['dependency_group_id', 'task_assignment_id'], 'kpi_dep_group_members_assignment_unique');
            $table->unique(['dependency_group_id', 'user_id'], 'kpi_dep_group_members_user_unique');
        });

        Schema::create('kpi_dependency_group_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependency_group_id')->constrained('kpi_dependency_groups')->cascadeOnDelete();
            $table->string('period_type', 20)->default('daily');
            $table->date('run_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 40)->default('pending');
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('first_confirmed_at')->nullable();
            $table->dateTime('fully_confirmed_at')->nullable();
            $table->dateTime('locked_at')->nullable();
            $table->dateTime('cutoff_at')->nullable();
            $table->unsignedTinyInteger('required_member_count')->default(0);
            $table->unsignedTinyInteger('confirmed_member_count')->default(0);
            $table->unsignedTinyInteger('reopened_count')->default(0);
            $table->string('final_outcome', 40)->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['dependency_group_id', 'period_type', 'run_date'], 'kpi_dep_group_runs_daily_unique');
            $table->index(['status', 'run_date'], 'kpi_dep_group_runs_status_run_date_idx');
        });

        Schema::create('kpi_dependency_group_run_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependency_group_run_id')->constrained('kpi_dependency_group_runs')->cascadeOnDelete();
            $table->foreignId('task_assignment_id')->constrained('kpi_task_assignments')->cascadeOnDelete();
            $table->foreignId('task_instance_id')->nullable()->constrained('kpi_task_instances')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('member_status', 40)->default('pending');
            $table->string('role_type', 20)->nullable();
            $table->boolean('is_required')->default(true);
            $table->dateTime('acted_at')->nullable();
            $table->text('comment')->nullable();
            $table->text('rejection_comment')->nullable();
            $table->timestamps();

            $table->unique(['dependency_group_run_id', 'task_assignment_id'], 'kpi_dep_group_run_members_assignment_unique');
            $table->unique(['dependency_group_run_id', 'user_id'], 'kpi_dep_group_run_members_user_unique');
            $table->index(['user_id', 'member_status'], 'kpi_dep_group_run_members_user_status_idx');
        });

        Schema::create('kpi_dependency_group_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependency_group_run_id')->unique()->constrained('kpi_dependency_group_runs')->cascadeOnDelete();
            $table->foreignId('submitted_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('submitted_at');
            $table->dateTime('locked_at')->nullable();
            $table->dateTime('reopened_at')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->text('employee_remark')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_dependency_group_submission_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependency_group_submission_id')->constrained('kpi_dependency_group_submissions')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('title')->nullable();
            $table->text('remark')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('kpi_dependency_group_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependency_group_run_id')->constrained('kpi_dependency_group_runs')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role_label')->nullable();
            $table->string('status', 20)->default('pending');
            $table->dateTime('acted_at')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->unique(['dependency_group_run_id', 'step_order'], 'kpi_dep_group_approval_step_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_dependency_group_approval_steps');
        Schema::dropIfExists('kpi_dependency_group_submission_images');
        Schema::dropIfExists('kpi_dependency_group_submissions');
        Schema::dropIfExists('kpi_dependency_group_run_members');
        Schema::dropIfExists('kpi_dependency_group_runs');
        Schema::dropIfExists('kpi_dependency_group_members');
        Schema::dropIfExists('kpi_dependency_groups');
    }
};
