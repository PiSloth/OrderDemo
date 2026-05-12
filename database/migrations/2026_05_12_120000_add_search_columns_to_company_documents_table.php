<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('company_documents', 'content_text')) {
                $table->longText('content_text')->nullable()->after('body');
            }
        });

        DB::table('company_documents')
            ->select(['id', 'body'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $row->body), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
                    DB::table('company_documents')
                        ->where('id', $row->id)
                        ->update(['content_text' => $text]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('company_documents', function (Blueprint $table) {
            if (Schema::hasColumn('company_documents', 'content_text')) {
                $table->dropColumn('content_text');
            }
        });
    }
};

