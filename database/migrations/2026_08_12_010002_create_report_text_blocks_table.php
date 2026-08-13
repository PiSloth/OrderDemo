<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_text_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->integer('sequence_order')->default(0);
            $table->string('block_type', 50)->default('paragraph');
            
            // Taxonomy Attributes with specified max length for composite index
            $table->string('category_type', 100)->nullable();
            $table->string('branch_code', 100)->nullable();
            $table->string('process_code', 100)->nullable();
            $table->string('risk_level', 50)->nullable();
            
            // Rich Text Content
            $table->text('plain_text')->nullable();
            $table->longText('html_content')->nullable();
            $table->json('json_content')->nullable();
            
            $table->timestamps();

            $table->index(['report_id', 'sequence_order']);
            $table->index(['category_type', 'branch_code', 'process_code', 'risk_level'], 'idx_rpt_blocks_meta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_text_blocks');
    }
};
