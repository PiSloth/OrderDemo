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
        Schema::table('asset_batches', function (Blueprint $table) {
            $table->decimal('total_cost', 12, 2)->default(0)->after('maximum_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_batches', function (Blueprint $table) {
            $table->dropColumn('total_cost');
        });
    }
};
