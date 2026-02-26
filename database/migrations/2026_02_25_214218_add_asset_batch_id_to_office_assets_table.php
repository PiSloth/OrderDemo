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
        Schema::table('office_assets', function (Blueprint $table) {
            $table->foreignId('asset_batch_id')->nullable()->constrained('asset_batches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_assets', function (Blueprint $table) {
            $table->dropForeign(['asset_batch_id']);
            $table->dropColumn('asset_batch_id');
        });
    }
};
