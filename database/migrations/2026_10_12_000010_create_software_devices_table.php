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
        Schema::create('software_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')
                ->nullable()
                ->constrained('office_assets')
                ->nullOnDelete();
            $table->string('asset_code_snapshot', 100)->nullable();
            $table->string('ownership_type', 20)->default('company');
            $table->string('manufacturer', 100)->nullable();
            $table->string('model_name', 150)->nullable();
            $table->string('model_number', 150)->nullable();
            $table->string('serial_number', 150)->nullable();
            $table->string('imei', 30)->nullable();
            $table->string('hostname', 255)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->string('operating_system_version', 100)->nullable();
            $table->string('device_status', 20)->default('active')->index();
            $table->dateTime('first_verified_at')->nullable();
            $table->dateTime('last_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_devices');
    }
};
