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
        Schema::create('psi_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_psi_product_id')->constrained();
            $table->foreignId('psi_price_id')->constrained(); // to know which supplier and youktwat
            $table->integer('order_qty');
            $table->integer('arrival_qty')->nullable();
            $table->integer('qc_passed_qty')->nullable();
            $table->integer('error_qty')->nullable();
            $table->integer('transfer_qty')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('psi_status_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psi_orders');
    }
};
