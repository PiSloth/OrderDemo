<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('group_key'); // 'type', 'branch', 'process', 'risk_level'
            $table->string('code')->unique(); // e.g. 'TYPE_FINDING', 'BR_B1', 'PROC_PAWN'
            $table->string('title'); // Display title
            $table->string('icon')->nullable(); // MUI Icon identifier
            $table->string('color_hex', 7)->default('#3B82F6');
            $table->json('default_template')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_taxonomies');
    }
};
