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

            // Gold-only weight (optional for backward compatibility).
            $table->decimal('gold_weight', 12, 3)->nullable();

            // If provided, barcode should be unique per item.
            $table->string('barcode')->nullable()->unique();

            // Use decimals for stable equality checks during batching.
            $table->decimal('total_weight', 12, 3);

            // Renamed fields (previously: kyauk_gram, l_gram, l_mmk)
            $table->decimal('kyauk_weight', 12, 3);
            $table->decimal('goldsmith_deduction', 12, 3);
            $table->unsignedInteger('goldsmith_labor_fee');

            // Additional money fields.
            $table->unsignedBigInteger('stone_price')->nullable();
            $table->decimal('profit_loss', 18, 2)->nullable();
            $table->unsignedBigInteger('profit_labor_fee')->nullable();

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
