<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_message_id')->constrained('note_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['note_message_id', 'user_id'], 'note_message_reads_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_message_reads');
    }
};
