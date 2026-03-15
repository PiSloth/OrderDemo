<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whiteboard_contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->text('propose_solution')->nullable();
            $table->foreignId('report_by')->nullable()->constrained('email_lists')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('content_type_id')->constrained('whiteboard_content_types')->cascadeOnDelete();
            $table->dateTime('propose_decision_due_at')->nullable();
            $table->foreignId('flag_id')->nullable()->constrained('whiteboard_flags')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['content_type_id', 'flag_id']);
            $table->index('propose_decision_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whiteboard_contents');
    }
};
