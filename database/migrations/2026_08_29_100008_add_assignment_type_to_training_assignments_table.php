<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_assignments', function (Blueprint $table) {
            $table->string('assignment_type')->default('FULL_TRAINING')->after('training_trigger_id'); // FULL_TRAINING, TEST_ONLY
        });
    }

    public function down(): void
    {
        Schema::table('training_assignments', function (Blueprint $table) {
            $table->dropColumn('assignment_type');
        });
    }
};
