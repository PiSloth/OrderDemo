<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_checklist_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_list_id')->constrained('branch_checklists')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('remark')->nullable();
            $table->boolean('is_done')->default(false);
            $table->date('checked_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_checklist_histories');
    }
};
