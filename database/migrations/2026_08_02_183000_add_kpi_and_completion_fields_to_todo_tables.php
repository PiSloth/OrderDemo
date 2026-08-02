<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add KPI configuration columns to todo_due_times
        Schema::table('todo_due_times', function (Blueprint $table) {
            if (!Schema::hasColumn('todo_due_times', 'generate_kpi_instance')) {
                $table->boolean('generate_kpi_instance')->default(false)->after('duration');
            }
            if (!Schema::hasColumn('todo_due_times', 'kpi_group_id')) {
                $table->foreignId('kpi_group_id')->nullable()->constrained('kpi_groups')->nullOnDelete()->after('generate_kpi_instance');
            }
            if (!Schema::hasColumn('todo_due_times', 'kpi_task_template_id')) {
                $table->foreignId('kpi_task_template_id')->nullable()->constrained('kpi_task_templates')->nullOnDelete()->after('kpi_group_id');
            }
        });

        // 2. Add KPI link and completion tracking columns to todo_lists
        Schema::table('todo_lists', function (Blueprint $table) {
            if (!Schema::hasColumn('todo_lists', 'kpi_task_instance_id')) {
                $table->foreignId('kpi_task_instance_id')->nullable()->constrained('kpi_task_instances')->nullOnDelete()->after('requested_by_branch_id');
            }
            if (!Schema::hasColumn('todo_lists', 'closed_by_user_id')) {
                $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('kpi_task_instance_id');
            }
            if (!Schema::hasColumn('todo_lists', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('closed_by_user_id');
            }
        });

        // 3. Add todo_list_id link to kpi_task_instances
        Schema::table('kpi_task_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_task_instances', 'todo_list_id')) {
                $table->foreignId('todo_list_id')->nullable()->constrained('todo_lists')->cascadeOnDelete()->after('task_template_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_task_instances', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_task_instances', 'todo_list_id')) {
                $table->dropForeign(['todo_list_id']);
                $table->dropColumn('todo_list_id');
            }
        });

        Schema::table('todo_lists', function (Blueprint $table) {
            if (Schema::hasColumn('todo_lists', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
            if (Schema::hasColumn('todo_lists', 'closed_by_user_id')) {
                $table->dropForeign(['closed_by_user_id']);
                $table->dropColumn('closed_by_user_id');
            }
            if (Schema::hasColumn('todo_lists', 'kpi_task_instance_id')) {
                $table->dropForeign(['kpi_task_instance_id']);
                $table->dropColumn('kpi_task_instance_id');
            }
        });

        Schema::table('todo_due_times', function (Blueprint $table) {
            if (Schema::hasColumn('todo_due_times', 'kpi_task_template_id')) {
                $table->dropForeign(['kpi_task_template_id']);
                $table->dropColumn('kpi_task_template_id');
            }
            if (Schema::hasColumn('todo_due_times', 'kpi_group_id')) {
                $table->dropForeign(['kpi_group_id']);
                $table->dropColumn('kpi_group_id');
            }
            if (Schema::hasColumn('todo_due_times', 'generate_kpi_instance')) {
                $table->dropColumn('generate_kpi_instance');
            }
        });
    }
};
