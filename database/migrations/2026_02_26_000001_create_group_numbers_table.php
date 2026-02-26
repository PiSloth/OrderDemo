<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_numbers', function (Blueprint $table) {
            $table->id();

            $table->string('number');
            $table->foreignId('purchase_by')->constrained('users');

            $table->boolean('is_purchase')->default(false);
            $table->string('purchase_status')->default('not_started');
            $table->string('po_reference')->nullable();

            $table->unsignedTinyInteger('entry_skill_grade')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->unique('number');
            $table->index(['purchase_status', 'is_purchase']);
            $table->index('finished_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_numbers');
    }
};
