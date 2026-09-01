<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('it_satisfaction_surveys', function (Blueprint $table) {
            $table->json('criteria')->nullable()->after('target_scope');
        });
    }

    public function down(): void
    {
        Schema::table('it_satisfaction_surveys', function (Blueprint $table) {
            $table->dropColumn('criteria');
        });
    }
};
