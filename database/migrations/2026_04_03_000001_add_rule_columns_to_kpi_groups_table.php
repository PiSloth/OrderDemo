<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_groups', function (Blueprint $table) {
            $table->string('rule_type', 40)->nullable()->after('description');
            $table->decimal('target_percentage', 5, 2)->nullable()->after('rule_type');
            $table->unsignedInteger('max_fail_count')->nullable()->after('target_percentage');
            $table->decimal('max_cost_amount', 12, 2)->nullable()->after('max_fail_count');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_groups', function (Blueprint $table) {
            $table->dropColumn([
                'rule_type',
                'target_percentage',
                'max_fail_count',
                'max_cost_amount',
            ]);
        });
    }
};
