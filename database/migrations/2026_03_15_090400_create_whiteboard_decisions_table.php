<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whiteboard_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('whiteboard_contents')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision');
            $table->dateTime('appointment_at')->nullable();
            $table->string('invited_person')->nullable();
            $table->timestamps();

            $table->index(['content_id', 'appointment_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whiteboard_decisions');
    }
};
