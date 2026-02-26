<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jewelry_items', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->after('group_number_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->index('branch_id');
            $table->index(['branch_id', 'is_register']);
        });
    }

    public function down(): void
    {
        Schema::table('jewelry_items', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'is_register']);
            $table->dropIndex(['branch_id']);
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
