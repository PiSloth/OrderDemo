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
        Schema::create('over_due_date_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_psi_product_id')->constrained();
            $table->foreignId('focus_sale_id')->constrained();
            $table->date('due_date');
            $table->date('ordered_date');
            $table->integer('sale_loss');
            //calculate days between due date and sale date and calcute sale loss
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('over_due_date_orders');
    }
};
