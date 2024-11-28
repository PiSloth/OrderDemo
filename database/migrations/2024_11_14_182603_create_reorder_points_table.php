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
        Schema::create('reorder_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psi_stock_id')->constrained();
            $table->integer('safty_day')->default(3);
            $table->integer('reorder_point')->default(0);
            $table->date('reorder_due_date')->nullable();
            $table->foreignId('psi_stock_status_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reorder_points');
    }
};
