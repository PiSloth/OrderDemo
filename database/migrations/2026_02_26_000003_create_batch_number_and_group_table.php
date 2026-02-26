<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_number_and_group', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_number_id')->constrained('group_numbers')->cascadeOnDelete();
            $table->unsignedInteger('batch_id');

            $table->boolean('is_post')->default(false);
            $table->foreignId('post_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['group_number_id', 'batch_id']);
            $table->index(['group_number_id', 'is_post']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_number_and_group');
    }
};
