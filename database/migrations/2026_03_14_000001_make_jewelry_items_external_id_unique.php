<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('jewelry_items', 'external_id')) {
            return;
        }

        // Normalize blanks to NULL so uniqueness works as intended.
        DB::table('jewelry_items')
            ->whereNotNull('external_id')
            ->whereRaw("TRIM(external_id) = ''")
            ->update(['external_id' => null]);

        // Refuse to add the unique index if duplicates already exist.
        $dupes = DB::table('jewelry_items')
            ->select('external_id', DB::raw('COUNT(*) as c'))
            ->whereNotNull('external_id')
            ->groupBy('external_id')
            ->having('c', '>', 1)
            ->limit(10)
            ->get();

        if ($dupes->isNotEmpty()) {
            $sample = $dupes
                ->map(fn($r) => (string) ($r->external_id ?? '') . ' (' . (int) ($r->c ?? 0) . ')')
                ->implode(', ');

            throw new RuntimeException('Cannot add UNIQUE constraint: duplicate external_id values exist. Sample: ' . $sample);
        }

        // Replace the non-unique index with a unique index.
        try {
            Schema::table('jewelry_items', function (Blueprint $table) {
                $table->dropIndex(['external_id']);
            });
        } catch (Throwable $e) {
            // Ignore if the old index name differs or doesn't exist.
        }

        Schema::table('jewelry_items', function (Blueprint $table) {
            $table->unique('external_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('jewelry_items', 'external_id')) {
            return;
        }

        try {
            Schema::table('jewelry_items', function (Blueprint $table) {
                $table->dropUnique(['external_id']);
            });
        } catch (Throwable $e) {
            // Ignore.
        }

        // Restore a normal index for lookups.
        Schema::table('jewelry_items', function (Blueprint $table) {
            $table->index('external_id');
        });
    }
};
