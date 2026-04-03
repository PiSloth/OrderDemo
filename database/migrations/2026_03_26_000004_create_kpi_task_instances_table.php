<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_task_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignment_id')->constrained('kpi_task_assignments')->cascadeOnDelete();
            $table->foreignId('task_template_id')->constrained('kpi_task_templates')->cascadeOnDelete();
            $table->foreignId('kpi_group_id')->constrained('kpi_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('period_type', 20);
            $table->date('task_date')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedTinyInteger('period_index')->default(1);
            $table->dateTime('due_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('status', 40)->default('pending');
            $table->string('final_outcome', 40)->nullable();
            $table->boolean('is_on_time')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->string('dependency_group_key')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'period_type', 'period_start', 'period_end'], 'kpi_instances_user_period_idx');
            $table->unique(
                ['task_assignment_id', 'period_type', 'period_start', 'period_end', 'period_index'],
                'kpi_task_instances_period_unique'
            );
        });

        Schema::create('kpi_task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_instance_id')->constrained('kpi_task_instances')->cascadeOnDelete();
            $table->foreignId('submitted_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('sequence')->default(1);
            $table->string('status', 30)->default('submitted');
            $table->text('employee_remark')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('first_approved_at')->nullable();
            $table->dateTime('final_approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_task_submission_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_submission_id')->constrained('kpi_task_submissions')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('title')->nullable();
            $table->text('remark')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('kpi_task_submission_table_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_submission_id')->constrained('kpi_task_submissions')->cascadeOnDelete();
            $table->unsignedInteger('row_order')->default(0);
            $table->timestamps();
        });

        Schema::create('kpi_task_submission_table_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_submission_table_row_id');
            $table->foreign('task_submission_table_row_id', 'kpi_sub_tbl_cells_row_fk')
                ->references('id')
                ->on('kpi_task_submission_table_rows')
                ->cascadeOnDelete();

            $table->foreignId('evidence_field_id')->nullable();
            $table->foreign('evidence_field_id', 'kpi_sub_tbl_cells_field_fk')
                ->references('id')
                ->on('kpi_task_template_evidence_fields')
                ->nullOnDelete();
            $table->string('column_name');
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 14, 2)->nullable();
            $table->date('value_date')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_task_submission_table_cells');
        Schema::dropIfExists('kpi_task_submission_table_rows');
        Schema::dropIfExists('kpi_task_submission_images');
        Schema::dropIfExists('kpi_task_submissions');
        Schema::dropIfExists('kpi_task_instances');
    }
};
