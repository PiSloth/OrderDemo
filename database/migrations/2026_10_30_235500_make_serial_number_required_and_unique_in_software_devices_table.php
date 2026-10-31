<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill any existing devices that have null or empty serial_number
        $devices = DB::table('software_devices')
            ->whereNull('serial_number')
            ->orWhere('serial_number', '')
            ->get();

        foreach ($devices as $d) {
            DB::table('software_devices')
                ->where('id', $d->id)
                ->update(['serial_number' => 'SN-DEV-' . str_pad((string)$d->id, 5, '0', STR_PAD_LEFT)]);
        }

        Schema::table('software_devices', function (Blueprint $table) {
            $table->string('serial_number', 150)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_devices', function (Blueprint $table) {
            $table->dropUnique(['serial_number']);
            $table->string('serial_number', 150)->nullable()->change();
        });
    }
};
