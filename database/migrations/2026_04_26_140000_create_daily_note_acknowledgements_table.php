<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_note_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('daily_notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(['note_id', 'user_id'], 'daily_note_acknowledgements_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_note_acknowledgements');
    }
};
