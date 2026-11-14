<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_mm')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_category_id')
                ->nullable()
                ->after('category')
                ->constrained('product_categories')
                ->nullOnDelete();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(['item_total_weight_g', 'sale_id'], 'sale_items_weight_sale_idx');
            $table->index(['product_id', 'sale_id'], 'sale_items_product_sale_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['customer_name_snapshot', 'sale_at'], 'sales_cust_name_date_idx');
            $table->index(['customer_phone_snapshot', 'sale_at'], 'sales_cust_phone_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_cust_name_date_idx');
            $table->dropIndex('sales_cust_phone_date_idx');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_weight_sale_idx');
            $table->dropIndex('sale_items_product_sale_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_category_id');
        });

        Schema::dropIfExists('product_categories');
    }
};
