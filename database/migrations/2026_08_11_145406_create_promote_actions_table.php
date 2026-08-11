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
        Schema::create('promote_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('target_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('action_by')->constrained('departments')->cascadeOnDelete(); // department id
            $table->date('start_at');
            $table->date('end_at');
            $table->json('reference')->nullable(); // JSON object for {todo_list_id: X, kpi_task_id: Y}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promote_actions');
    }
};
