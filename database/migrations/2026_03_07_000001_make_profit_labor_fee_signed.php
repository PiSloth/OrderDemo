<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jewelry_items') || !Schema::hasColumn('jewelry_items', 'profit_labor_fee')) {
            return;
        }

        // Needs to allow negative values (e.g. -500) during import.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `jewelry_items` MODIFY `profit_labor_fee` BIGINT NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('jewelry_items') || !Schema::hasColumn('jewelry_items', 'profit_labor_fee')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `jewelry_items` MODIFY `profit_labor_fee` BIGINT UNSIGNED NULL');
        }
    }
};
