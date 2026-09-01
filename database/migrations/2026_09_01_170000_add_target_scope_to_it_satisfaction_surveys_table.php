<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('it_satisfaction_surveys', function (Blueprint $table) {
            $table->json('target_scope')->nullable()->after('is_mandatory');
        });
    }

    public function down(): void
    {
        Schema::table('it_satisfaction_surveys', function (Blueprint $table) {
            $table->dropColumn('target_scope');
        });
    }
};
