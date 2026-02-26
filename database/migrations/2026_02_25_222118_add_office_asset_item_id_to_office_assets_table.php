<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('office_assets', function (Blueprint $table) {
            $table->foreignId('office_asset_item_id')
                ->nullable()
                ->after('id');
        });

        // Backfill master items based on current office_assets rows (category + name)
        $groups = DB::table('office_assets')
            ->select('asset_category_id', 'name', DB::raw('MAX(photo) as photo'))
            ->whereNotNull('asset_category_id')
            ->whereNotNull('name')
            ->groupBy('asset_category_id', 'name')
            ->get();

        foreach ($groups as $group) {
            $existingId = DB::table('office_asset_items')
                ->where('asset_category_id', $group->asset_category_id)
                ->where('name', $group->name)
                ->value('id');

            $itemId = $existingId;
            if (!$itemId) {
                $itemId = DB::table('office_asset_items')->insertGetId([
                    'asset_category_id' => $group->asset_category_id,
                    'name' => $group->name,
                    'photo' => $group->photo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('office_assets')
                ->where('asset_category_id', $group->asset_category_id)
                ->where('name', $group->name)
                ->update([
                    'office_asset_item_id' => $itemId,
                ]);
        }

        Schema::table('office_assets', function (Blueprint $table) {
            $table->foreign('office_asset_item_id')
                ->references('id')
                ->on('office_asset_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_assets', function (Blueprint $table) {
            $table->dropForeign(['office_asset_item_id']);
            $table->dropColumn('office_asset_item_id');
        });
    }
};
