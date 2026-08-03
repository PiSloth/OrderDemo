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
        Schema::table('kpi_task_instances', function (Blueprint $table) {
            $table->unsignedBigInteger('task_assignment_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpi_task_instances', function (Blueprint $table) {
            $table->unsignedBigInteger('task_assignment_id')->nullable(false)->change();
        });
    }
};
