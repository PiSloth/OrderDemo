<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scopes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scope_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('scopes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['scope_id', 'user_id']);
        });

        Schema::create('note_title_scope', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('scopes')->cascadeOnDelete();
            $table->foreignId('note_title_id')->constrained('note_titles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['scope_id', 'note_title_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_title_scope');
        Schema::dropIfExists('scope_user');
        Schema::dropIfExists('scopes');
    }
};

