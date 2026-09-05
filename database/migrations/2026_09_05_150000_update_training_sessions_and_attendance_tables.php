<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->default(1)->after('retrain_unit');
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->foreignId('parent_session_id')->nullable()->after('training_id')->constrained('training_sessions')->nullOnDelete();
            $table->date('start_date')->nullable()->after('scheduled_at');
            $table->date('end_date')->nullable()->after('start_date');
            $table->unsignedInteger('duration_days')->default(1)->after('end_date');
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
        });

        Schema::table('training_session_participants', function (Blueprint $table) {
            $table->json('daily_attendance')->nullable()->after('attendance_status');
        });
    }

    public function down(): void
    {
        Schema::table('training_session_participants', function (Blueprint $table) {
            $table->dropColumn('daily_attendance');
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_session_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['start_date', 'end_date', 'duration_days', 'approved_at', 'approval_notes']);
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
