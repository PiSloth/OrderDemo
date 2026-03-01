<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('jewelry_items', 'profit_loss')) {
            DB::statement("ALTER TABLE `jewelry_items` MODIFY `profit_loss` DECIMAL(18,2) NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('jewelry_items', 'profit_loss')) {
            DB::statement("ALTER TABLE `jewelry_items` MODIFY `profit_loss` BIGINT NULL");
        }
    }
};
