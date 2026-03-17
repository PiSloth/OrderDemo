<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whiteboard_contents', function (Blueprint $table) {
            $table->dateTime('received_mail_at')->nullable()->after('flag_id');
        });
    }

    public function down(): void
    {
        Schema::table('whiteboard_contents', function (Blueprint $table) {
            $table->dropColumn('received_mail_at');
        });
    }
};
