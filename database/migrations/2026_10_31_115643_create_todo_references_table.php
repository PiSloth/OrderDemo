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
        Schema::create('todo_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_list_id')
                ->constrained('todo_lists')
                ->cascadeOnDelete();
            
            // Polymorphic relation to any reference model (Review, Account, Order, KPI, etc.)
            $table->morphs('reference');

            // Module identifier for grouping & cross-module queries (e.g., digital_asset_review, sale_kpi, order)
            $table->string('module', 50)->nullable()->index();

            // Traceability: Why this todo happened
            $table->text('reason')->nullable();

            // Action category: remediation, verification, investigation, follow_up, general
            $table->string('action_type', 50)->default('remediation');

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'todo_refs_reference_idx');
            $table->index(['todo_list_id', 'module'], 'todo_refs_task_module_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_references');
    }
};
