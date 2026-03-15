<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whiteboard_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('whiteboard_contents')->cascadeOnDelete();
            $table->foreignId('email_list_id')->constrained('email_lists')->cascadeOnDelete();
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->foreignId('read_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['content_id', 'email_list_id']);
            $table->index(['email_list_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whiteboard_reports');
    }
};
