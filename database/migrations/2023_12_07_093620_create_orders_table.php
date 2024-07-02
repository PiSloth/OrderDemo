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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('grade_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('status_id')->constrained();
            $table->foreignId('design_id')->constrained();
            $table->foreignId('quality_id')->constrained();
            $table->foreignId('priority_id')->constrained();
            $table->string('detail');
            $table->string('weight');
            $table->string('size');
            $table->integer('qty');
            // $table->integer('netweight');
            $table->integer('counterstock');
            $table->integer('sell_rate')->default(0);
            $table->text('note');
            $table->integer('instockqty')->nullable();
            $table->date('estimatetime')->nullable();
            $table->integer('arqty')->nullable();
            $table->integer('ar_total_weight')->nullable();
            $table->integer('closeqty')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
