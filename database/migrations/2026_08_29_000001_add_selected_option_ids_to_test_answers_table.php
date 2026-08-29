<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_answers', function (Blueprint $table) {
            $table->json('selected_option_ids')->nullable()->after('selected_option_id');
        });
    }

    public function down(): void
    {
        Schema::table('test_answers', function (Blueprint $table) {
            $table->dropColumn('selected_option_ids');
        });
    }
};
