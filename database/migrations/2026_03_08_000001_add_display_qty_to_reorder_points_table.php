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
        Schema::table('reorder_points', function (Blueprint $table) {
            $table->integer('display_qty')->default(0)->after('safty_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reorder_points', function (Blueprint $table) {
            $table->dropColumn('display_qty');
        });
    }
};
