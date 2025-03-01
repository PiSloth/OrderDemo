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
        Schema::create('psi_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_psi_product_id')->unique()->constrained();
            $table->integer('inventory_balance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psi_stocks');
    }
};
