<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('group_numbers') || !Schema::hasColumn('group_numbers', 'purchase_by')) {
            return;
        }

        Schema::table('group_numbers', function (Blueprint $table) {
            $table->dropForeign(['purchase_by']);
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE group_numbers MODIFY purchase_by BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE group_numbers ALTER COLUMN purchase_by DROP NOT NULL');
        }

        Schema::table('group_numbers', function (Blueprint $table) {
            $table->foreign('purchase_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('group_numbers') || !Schema::hasColumn('group_numbers', 'purchase_by')) {
            return;
        }

        Schema::table('group_numbers', function (Blueprint $table) {
            $table->dropForeign(['purchase_by']);
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE group_numbers MODIFY purchase_by BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE group_numbers ALTER COLUMN purchase_by SET NOT NULL');
        }

        Schema::table('group_numbers', function (Blueprint $table) {
            $table->foreign('purchase_by')->references('id')->on('users');
        });
    }
};
