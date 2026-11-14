<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_system_id')->constrained('source_systems')->restrictOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('source_sale_id', 100);
            $table->string('sale_number', 100)->index();
            $table->dateTime('sale_at')->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name_snapshot')->nullable();
            $table->string('customer_phone_snapshot')->nullable();
            $table->text('customer_address_snapshot')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('branch_name_raw')->nullable();
            $table->foreignId('duty_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('input_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->decimal('gold_price', 16, 2)->default(0);
            $table->decimal('invoice_total', 18, 2)->default(0);
            $table->decimal('invoice_total_weight_g', 14, 4)->default(0);
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->unique(['source_system_id', 'source_sale_id'], 'sales_source_system_sale_unique');
            $table->index(['branch_id', 'sale_at']);
            $table->index(['customer_id', 'sale_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_system_id')->constrained('source_systems')->restrictOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('source_sale_item_id', 150);
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('source_stock_id')->nullable();
            $table->string('barcode')->nullable()->index();
            $table->date('barcode_registered_at')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('item_weight_g', 14, 4)->default(0);
            $table->decimal('stone_weight_g', 14, 4)->default(0);
            $table->decimal('gold_weight_g', 14, 4)->default(0);
            $table->decimal('decrease_weight_g', 14, 4)->default(0);
            $table->decimal('item_total_weight_g', 14, 4)->default(0);
            $table->decimal('gold_amount', 16, 2)->default(0);
            $table->decimal('stone_amount', 16, 2)->default(0);
            $table->decimal('labor_fee', 16, 2)->default(0);
            $table->decimal('polishing_fee', 16, 2)->default(0);
            $table->decimal('other_fee', 16, 2)->default(0);
            $table->decimal('profit_loss_amount', 16, 2)->default(0);
            $table->decimal('item_amount', 16, 2)->default(0);
            $table->decimal('item_total_amount', 16, 2)->default(0);
            $table->timestamps();

            $table->unique(['source_system_id', 'source_sale_item_id'], 'sale_items_source_system_item_unique');
            $table->index(['sale_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
