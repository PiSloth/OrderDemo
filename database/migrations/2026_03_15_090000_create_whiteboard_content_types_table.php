<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whiteboard_content_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 32)->default('slate');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whiteboard_content_types');
    }
};
