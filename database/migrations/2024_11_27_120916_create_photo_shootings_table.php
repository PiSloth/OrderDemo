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
        Schema::create('photo_shootings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psi_order_id')->constrained();
            $table->foreignId('photo_shooting_status_id')->constrained();
            $table->date('schedule_date')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_shootings');
    }
};
