<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('software_account_pics', function (Blueprint $table) {
            $table->foreignId('assigned_by')
                ->nullable()
                ->after('assigned_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Backfill assigned_by from software_account_pic_history
        $histories = DB::table('software_account_pic_history')
            ->whereIn('action', ['assigned', 'reactivated'])
            ->whereNotNull('performed_by')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($histories as $hist) {
            DB::table('software_account_pics')
                ->where('id', $hist->software_account_pic_id)
                ->whereNull('assigned_by')
                ->update(['assigned_by' => $hist->performed_by]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_account_pics', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn('assigned_by');
        });
    }
};
