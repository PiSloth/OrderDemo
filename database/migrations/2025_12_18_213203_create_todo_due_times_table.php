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
        Schema::create('todo_due_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_category_id')->constrained('todo_categories')->onDelete('cascade');
            $table->foreignId('todo_priority_id')->constrained('todo_priorities')->onDelete('cascade');
            $table->integer('duration');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_due_times');
    }
};
