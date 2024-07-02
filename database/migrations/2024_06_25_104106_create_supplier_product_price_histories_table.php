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
        Schema::create('supplier_product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_product_id')->constrained();
            $table->integer('youktwat')->nullable();
            $table->string('youktwat_in_kpy')->nullable();
            $table->integer('laukkha')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_product_price_histories');
    }
};
