<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->boolean('is_sla_failed')->default(false)->after('due_date');
            $table->unsignedInteger('fail_points')->default(0)->after('is_sla_failed');
            $table->index(['is_sla_failed', 'fail_points']);
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['is_sla_failed', 'fail_points']);
            $table->dropColumn(['is_sla_failed', 'fail_points']);
        });
    }
};
