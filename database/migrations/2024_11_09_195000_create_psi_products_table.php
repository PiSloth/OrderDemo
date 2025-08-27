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
        Schema::create('psi_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->double('weight');
            $table->double('length')->nullable();
            $table->foreignId('shape_id')->constrained();
            $table->foreignId('uom_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('quality_id')->constrained();
            $table->foreignId('design_id')->constrained();
            $table->foreignId('manufacture_technique_id')->constrained();
            $table->string('reference')->nullable();
            $table->boolean('is_suspended')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psi_products');
    }
};
