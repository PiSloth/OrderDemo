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
        Schema::create('arrival_due_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_psi_product_id')->constrained();
            $table->date('arrival_due_date');
            $table->date('acutal_arrival_date');
            $table->integer('sale_loss'); // (daily Sale Focus * late day) - stockbalance
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_due_dates');
    }
};
