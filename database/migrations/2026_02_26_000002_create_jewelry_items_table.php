<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jewelry_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_number_id')->constrained('group_numbers')->cascadeOnDelete();

            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('product_name');
            $table->string('quality');
            $table->string('barcode')->unique();

            // Use decimals for stable equality checks during batching.
            $table->decimal('total_weight', 12, 3);
            $table->decimal('l_gram', 12, 3);
            $table->unsignedInteger('l_mmk');
            $table->decimal('kyauk_gram', 12, 3);

            // Batch IDs are generated per-group during import.
            $table->unsignedInteger('batch_id')->nullable();

            $table->boolean('is_register')->default(false);
            $table->foreignId('register_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['group_number_id', 'batch_id']);
            $table->index('is_register');
            $table->index('branch_id');
            $table->index(['branch_id', 'is_register']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jewelry_items');
    }
};
