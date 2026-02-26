<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();

        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$dbName, $table, $indexName]
        );

        return !empty($rows);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL can refuse dropping a composite unique index if it is the only index
        // satisfying a FK column's index requirement. Ensure FK column indexes exist first.
        if (!$this->indexExists('office_assets', 'office_assets_asset_category_id_index')) {
            Schema::table('office_assets', function (Blueprint $table) {
                $table->index('asset_category_id');
            });
        }

        if (!$this->indexExists('office_assets', 'office_assets_branch_id_index')) {
            Schema::table('office_assets', function (Blueprint $table) {
                $table->index('branch_id');
            });
        }

        if (!$this->indexExists('office_assets', 'office_assets_department_id_index')) {
            Schema::table('office_assets', function (Blueprint $table) {
                $table->index('department_id');
            });
        }

        Schema::table('office_assets', function (Blueprint $table) {
            // Old constraint (category+name+branch+department)
            $table->dropUnique('office_assets_unique_item_location');

            // New constraint (item+branch+department)
            $table->unique(
                ['office_asset_item_id', 'branch_id', 'department_id'],
                'office_assets_unique_item_location_v2'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_assets', function (Blueprint $table) {
            $table->dropUnique('office_assets_unique_item_location_v2');

            $table->unique(
                ['asset_category_id', 'branch_id', 'department_id', 'name'],
                'office_assets_unique_item_location'
            );
        });
    }
};
