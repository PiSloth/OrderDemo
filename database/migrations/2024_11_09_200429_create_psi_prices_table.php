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
        Schema::create('psi_prices', function (Blueprint $table) {
            $table->id(); // create one every edit existing price or create new one
            $table->foreignId('user_id')->constrained();
            $table->foreignId('psi_product_id')->constrained();
            $table->foreignId('supplier_id')->constrained();
            $table->float('youktwat')->nullable();
            $table->string('youktwat_in_kpy')->nullable();
            $table->integer('laukkha')->nullable();
            $table->integer('lead_day');
            $table->string('product_remark')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psi_prices');
    }
};
