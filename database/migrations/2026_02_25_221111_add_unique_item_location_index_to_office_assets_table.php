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
            $table->unique(
                ['asset_category_id', 'branch_id', 'department_id', 'name'],
                'office_assets_unique_item_location'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_assets', function (Blueprint $table) {
            $table->dropUnique('office_assets_unique_item_location');
        });
    }
};
