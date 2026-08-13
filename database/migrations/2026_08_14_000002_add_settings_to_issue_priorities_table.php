<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('issue_priorities', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('level');
        });

        // Set default settings according to the updated Level 1 -> Level 4 hierarchy
        // Level 1 = Critical (Continuous 24/7 Clock)
        // Level 2 = High (1 Business Day = 8.5 office hours)
        // Level 3 = Normal (2 Business Days = 17 office hours)
        // Level 4 = Schedule / Request (Manual Schedule, no fixed hours)

        DB::table('issue_priorities')->where('level', 1)->update([
            'settings' => json_encode([
                'clock_type' => 'continuous_24h',
                'target_hours' => 24,
                'fail_points' => 10,
                'is_manual_schedule' => false,
            ]),
        ]);

        DB::table('issue_priorities')->where('level', 2)->update([
            'settings' => json_encode([
                'clock_type' => 'office_hours',
                'target_hours' => 8.5,
                'fail_points' => 5,
                'is_manual_schedule' => false,
            ]),
        ]);

        DB::table('issue_priorities')->where('level', 3)->update([
            'settings' => json_encode([
                'clock_type' => 'office_hours',
                'target_hours' => 17,
                'fail_points' => 1,
                'is_manual_schedule' => false,
            ]),
        ]);

        DB::table('issue_priorities')->where('level', 4)->update([
            'settings' => json_encode([
                'clock_type' => 'manual_schedule',
                'target_hours' => null,
                'fail_points' => 1,
                'is_manual_schedule' => true,
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('issue_priorities', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
