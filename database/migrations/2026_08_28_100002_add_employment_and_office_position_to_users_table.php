<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('employment_start_date')->nullable()->after('location_id');
            $table->foreignId('office_position_id')->nullable()->after('position_id')->constrained('office_positions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_position_id');
            $table->dropColumn('employment_start_date');
        });
    }
};
