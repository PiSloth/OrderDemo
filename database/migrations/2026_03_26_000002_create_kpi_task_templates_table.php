<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_task_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_group_id')->constrained('kpi_groups')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('guideline')->nullable();
            $table->string('frequency', 20);
            $table->unsignedTinyInteger('monthly_required_count')->default(1);
            $table->time('cutoff_time')->nullable();
            $table->time('reminder_start_time')->default('08:45:00');
            $table->boolean('requires_images')->default(false);
            $table->boolean('requires_table')->default(false);
            $table->unsignedTinyInteger('min_images')->default(0);
            $table->unsignedTinyInteger('max_images')->nullable();
            $table->boolean('image_remark_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kpi_task_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->unique()->constrained('kpi_task_templates')->cascadeOnDelete();
            $table->string('rule_type', 40);
            $table->decimal('target_percentage', 5, 2)->nullable();
            $table->unsignedInteger('max_fail_count')->nullable();
            $table->decimal('max_cost_amount', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_task_template_evidence_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained('kpi_task_templates')->cascadeOnDelete();
            $table->string('field_key');
            $table->string('label');
            $table->string('field_type', 30);
            $table->boolean('is_required')->default(false);
            $table->json('select_options')->nullable();
            $table->json('unit_options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_task_template_evidence_fields');
        Schema::dropIfExists('kpi_task_rules');
        Schema::dropIfExists('kpi_task_templates');
    }
};
