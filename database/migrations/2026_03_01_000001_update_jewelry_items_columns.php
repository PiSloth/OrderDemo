<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make barcode nullable (unique index can remain; MySQL allows multiple NULLs in UNIQUE).
        if (Schema::hasColumn('jewelry_items', 'barcode')) {
            DB::statement("ALTER TABLE `jewelry_items` MODIFY `barcode` VARCHAR(255) NULL");
        }

        // Rename existing columns to the new names.
        if (Schema::hasColumn('jewelry_items', 'kyauk_gram') && !Schema::hasColumn('jewelry_items', 'kyauk_weight')) {
            DB::statement("ALTER TABLE `jewelry_items` CHANGE `kyauk_gram` `kyauk_weight` DECIMAL(12,3) NOT NULL");
        }

        if (Schema::hasColumn('jewelry_items', 'l_gram') && !Schema::hasColumn('jewelry_items', 'goldsmith_deduction')) {
            DB::statement("ALTER TABLE `jewelry_items` CHANGE `l_gram` `goldsmith_deduction` DECIMAL(12,3) NOT NULL");
        }

        if (Schema::hasColumn('jewelry_items', 'l_mmk') && !Schema::hasColumn('jewelry_items', 'goldsmith_labor_fee')) {
            DB::statement("ALTER TABLE `jewelry_items` CHANGE `l_mmk` `goldsmith_labor_fee` INT UNSIGNED NOT NULL");
        }

        // Add new columns.
        Schema::table('jewelry_items', function (Blueprint $table) {
            if (!Schema::hasColumn('jewelry_items', 'gold_weight')) {
                $table->decimal('gold_weight', 12, 3)->nullable()->after('quality');
            }

            if (!Schema::hasColumn('jewelry_items', 'stone_price')) {
                $table->unsignedBigInteger('stone_price')->nullable()->after('goldsmith_labor_fee');
            }

            if (!Schema::hasColumn('jewelry_items', 'profit_loss')) {
                $table->bigInteger('profit_loss')->nullable()->after('stone_price');
            }

            if (!Schema::hasColumn('jewelry_items', 'profit_labor_fee')) {
                $table->unsignedBigInteger('profit_labor_fee')->nullable()->after('profit_loss');
            }
        });
    }

    public function down(): void
    {
        // Drop new columns.
        Schema::table('jewelry_items', function (Blueprint $table) {
            if (Schema::hasColumn('jewelry_items', 'profit_labor_fee')) {
                $table->dropColumn('profit_labor_fee');
            }

            if (Schema::hasColumn('jewelry_items', 'profit_loss')) {
                $table->dropColumn('profit_loss');
            }

            if (Schema::hasColumn('jewelry_items', 'stone_price')) {
                $table->dropColumn('stone_price');
            }

            if (Schema::hasColumn('jewelry_items', 'gold_weight')) {
                $table->dropColumn('gold_weight');
            }
        });

        // Rename columns back.
        if (Schema::hasColumn('jewelry_items', 'kyauk_weight') && !Schema::hasColumn('jewelry_items', 'kyauk_gram')) {
            DB::statement("ALTER TABLE `jewelry_items` CHANGE `kyauk_weight` `kyauk_gram` DECIMAL(12,3) NOT NULL");
        }

        if (Schema::hasColumn('jewelry_items', 'goldsmith_deduction') && !Schema::hasColumn('jewelry_items', 'l_gram')) {
            DB::statement("ALTER TABLE `jewelry_items` CHANGE `goldsmith_deduction` `l_gram` DECIMAL(12,3) NOT NULL");
        }

        if (Schema::hasColumn('jewelry_items', 'goldsmith_labor_fee') && !Schema::hasColumn('jewelry_items', 'l_mmk')) {
            DB::statement("ALTER TABLE `jewelry_items` CHANGE `goldsmith_labor_fee` `l_mmk` INT UNSIGNED NOT NULL");
        }

        // Make barcode NOT NULL again.
        if (Schema::hasColumn('jewelry_items', 'barcode')) {
            DB::statement("ALTER TABLE `jewelry_items` MODIFY `barcode` VARCHAR(255) NOT NULL");
        }
    }
};
