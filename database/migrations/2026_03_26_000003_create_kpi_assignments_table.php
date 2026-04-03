<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained('kpi_task_templates')->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kpi_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained('kpi_task_templates')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_assignment_id')->nullable()->constrained('kpi_role_assignments')->nullOnDelete();
            $table->foreignId('first_approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('final_approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_source', 20)->default('manual');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('calendar_push_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('kpi_task_calendar_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignment_id')->unique()->constrained('kpi_task_assignments')->cascadeOnDelete();
            $table->boolean('daily_reminder_enabled')->default(true);
            $table->time('reminder_start_time')->default('08:45:00');
            $table->unsignedSmallInteger('reminder_interval_minutes')->default(60);
            $table->boolean('weekly_monthly_refresh_enabled')->default(true);
            $table->time('weekly_monthly_refresh_time')->default('09:15:00');
            $table->boolean('push_until_finalized')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_task_calendar_controls');
        Schema::dropIfExists('kpi_task_assignments');
        Schema::dropIfExists('kpi_role_assignments');
    }
};
