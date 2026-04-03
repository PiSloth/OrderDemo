<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_task_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_submission_id')->constrained('kpi_task_submissions')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role_label')->nullable();
            $table->string('status', 20)->default('pending');
            $table->dateTime('acted_at')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->unique(['task_submission_id', 'step_order'], 'kpi_submission_step_unique');
        });

        Schema::create('kpi_exclusion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_assignment_id')->nullable()->constrained('kpi_task_assignments')->nullOnDelete();
            $table->string('request_type', 20);
            $table->date('requested_date');
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('reviewer_remark')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_task_assignment_id')->constrained('kpi_task_assignments')->cascadeOnDelete();
            $table->foreignId('target_task_assignment_id')->constrained('kpi_task_assignments')->cascadeOnDelete();
            $table->string('mode', 30)->default('mirror_submit');
            $table->boolean('share_final_result')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['source_task_assignment_id', 'target_task_assignment_id'],
                'kpi_task_dependencies_unique'
            );
        });

        Schema::create('employee_kpi_period_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_group_id')->constrained('kpi_groups')->cascadeOnDelete();
            $table->string('period_type', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('must_do_count')->default(0);
            $table->unsignedInteger('passed_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('on_time_count')->default(0);
            $table->unsignedInteger('late_count')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('on_time_rate', 5, 2)->default(0);
            $table->decimal('score_rate', 5, 2)->default(0);
            $table->decimal('total_spend_cost', 14, 2)->default(0);
            $table->dateTime('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'kpi_group_id', 'period_type', 'period_start', 'period_end'],
                'employee_kpi_period_scores_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_kpi_period_scores');
        Schema::dropIfExists('kpi_task_dependencies');
        Schema::dropIfExists('kpi_exclusion_requests');
        Schema::dropIfExists('kpi_task_approval_steps');
    }
};
