<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_scopes', function (Blueprint $table) {
            $table->dropForeign(['office_position_id']);
        });

        Schema::table('training_scopes', function (Blueprint $table) {
            $table->foreignId('office_position_id')->nullable()->change()->constrained('office_positions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('training_scopes', function (Blueprint $table) {
            $table->dropForeign(['office_position_id']);
        });

        Schema::table('training_scopes', function (Blueprint $table) {
            $table->foreignId('office_position_id')->nullable(false)->change()->constrained('office_positions')->cascadeOnDelete();
        });
    }
};
