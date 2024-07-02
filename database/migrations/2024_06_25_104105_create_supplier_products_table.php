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
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('quality_id')->constrained();
            $table->foreignId('design_id')->constrained();
            $table->string('detail');
            $table->string('color')->nullable();
            $table->integer('weight');
            $table->string('weight_in_kpy');
            $table->integer('youktwat')->nullable();
            $table->string('youktwat_in_kpy')->nullable();
            $table->integer('laukkha')->nullable();
            $table->date('min_ar_date')->nullable();
            $table->date('max_ar_date')->nullable();
            $table->string('product_remark')->nullable();
            $table->string('remark')->nullable();
            $table->boolean('is_reject')->default(false);
            $table->string('reject_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
