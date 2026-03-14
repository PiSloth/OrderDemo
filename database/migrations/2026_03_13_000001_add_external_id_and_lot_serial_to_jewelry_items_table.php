<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jewelry_items', function (Blueprint $table) {
            if (!Schema::hasColumn('jewelry_items', 'external_id')) {
                $table->string('external_id')->nullable()->after('barcode');
                $table->index('external_id');
            }

            if (!Schema::hasColumn('jewelry_items', 'lot_serial')) {
                $table->string('lot_serial')->nullable()->after('external_id');
                $table->index('lot_serial');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jewelry_items', function (Blueprint $table) {
            if (Schema::hasColumn('jewelry_items', 'lot_serial')) {
                $table->dropIndex(['lot_serial']);
                $table->dropColumn('lot_serial');
            }

            if (Schema::hasColumn('jewelry_items', 'external_id')) {
                $table->dropIndex(['external_id']);
                $table->dropColumn('external_id');
            }
        });
    }
};
