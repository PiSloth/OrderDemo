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
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_list_id')->constrained('todo_lists')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->text('comment');
            $table->enum('comment_type', ['normal', 'action_step'])->default('normal');
            $table->foreignId('parent_id')->nullable()->constrained('task_comments')->onDelete('cascade');
            $table->json('action_data')->nullable(); // For action step data like due date changes
            $table->enum('action_status', ['pending', 'accepted', 'rejected'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
