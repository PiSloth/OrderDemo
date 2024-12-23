<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('product_hashtags', function (Blueprint $table) {
    //         $table->id();
    //         $table->foreignId('psi_product_id')->index()->constrained()->onDelete('cascade');
    //         $table->foreignId('hashtag_id')->constrained();
    //         $table->foreignId('user_id')->constrained();
    //         $table->timestamps();
    //     });
    // }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_hashtags');
    }
};
