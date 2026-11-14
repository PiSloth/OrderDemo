<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('identity_hash', 64)->unique();
            $table->string('name');
            $table->string('name_normalized')->index();
            $table->string('phone_raw')->nullable();
            $table->string('phone_normalized', 32)->nullable()->index();
            $table->text('address_raw')->nullable();
            $table->foreignId('township_id')->nullable()->constrained('townships')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->timestamps();

            $table->index(['region_id', 'township_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
