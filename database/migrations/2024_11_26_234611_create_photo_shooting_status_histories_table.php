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
        Schema::create('photo_shooting_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psi_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('photo_shooting_status_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_shooting_status_histories');
    }
};
