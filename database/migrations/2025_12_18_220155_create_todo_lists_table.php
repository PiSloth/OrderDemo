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
        Schema::create('todo_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_due_time_id')->constrained('todo_due_times')->onDelete('cascade');
            $table->foreignId('todo_status_id')->nullable()->constrained('todo_statuses')->onDelete('cascade');
            $table->string('task');
            $table->timestamp('due_date');
            $table->foreignId('assigned_user_id')->constrained('users')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('requested_by_branch_id')->constrained('branches')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_lists');
    }
};
