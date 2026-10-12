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
        Schema::create('software_account_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_account_id')
                ->constrained('software_accounts')
                ->cascadeOnDelete();
            $table->foreignId('software_device_id')
                ->constrained('software_devices')
                ->restrictOnDelete();
            $table->string('relationship_type', 30)->default('registered');
            $table->dateTime('first_seen_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['software_account_id', 'software_device_id'], 'soft_acc_dev_acc_dev_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_account_devices');
    }
};
