<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todo_due_times', function (Blueprint $table) {
            $table->json('kpi_assigned_user_ids')->nullable()->after('kpi_assigned_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('todo_due_times', function (Blueprint $table) {
            $table->dropColumn('kpi_assigned_user_ids');
        });
    }
};
