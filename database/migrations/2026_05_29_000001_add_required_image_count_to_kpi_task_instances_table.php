<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_task_instances', function (Blueprint $table): void {
            $table->unsignedTinyInteger('required_image_count')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_task_instances', function (Blueprint $table): void {
            $table->dropColumn('required_image_count');
        });
    }
};
