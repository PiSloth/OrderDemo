<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todo_due_times', function (Blueprint $table) {
            $table->foreignId('kpi_assigned_user_id')
                ->nullable()
                ->after('kpi_task_template_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('todo_due_times', function (Blueprint $table) {
            $table->dropForeign(['kpi_assigned_user_id']);
            $table->dropColumn('kpi_assigned_user_id');
        });
    }
};
