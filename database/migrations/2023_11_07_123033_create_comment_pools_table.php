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
        Schema::create('comment_pools', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('order_id');
            $table->integer('user_id');
            $table->integer('status_id');
            $table->text('meeting_note')->nullable();
            $table->text('reason')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_pools');
    }
};
