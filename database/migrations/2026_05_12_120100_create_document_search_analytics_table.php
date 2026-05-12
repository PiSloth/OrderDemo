<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_search_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('query', 255)->nullable();
            $table->unsignedInteger('results_count')->default(0);
            $table->json('filters')->nullable();
            $table->string('sort', 40)->nullable();
            $table->unsignedBigInteger('clicked_document_id')->nullable();
            $table->timestamp('searched_at')->useCurrent();
            $table->index(['user_id', 'searched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_search_analytics');
    }
};

