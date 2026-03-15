<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE whiteboard_decisions MODIFY decision LONGTEXT NOT NULL');

            return;
        }

        Schema::table('whiteboard_decisions', function (Blueprint $table) {
            $table->text('decision')->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE whiteboard_decisions MODIFY decision VARCHAR(255) NOT NULL');

            return;
        }

        Schema::table('whiteboard_decisions', function (Blueprint $table) {
            $table->string('decision')->change();
        });
    }
};
