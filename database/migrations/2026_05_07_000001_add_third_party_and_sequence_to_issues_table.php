<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->boolean('is_third_party_resolver')->default(false)->after('assigned_user_id');
            $table->unsignedInteger('resolution_sequence')->nullable()->after('is_third_party_resolver');
            $table->dateTime('started_at')->nullable()->after('issue_at');
            $table->foreignId('follow_up_updated_by')->nullable()->after('follow_up_date')->constrained('users')->nullOnDelete();

            $table->index(['is_third_party_resolver', 'resolution_sequence']);
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['is_third_party_resolver', 'resolution_sequence']);
            $table->dropConstrainedForeignId('follow_up_updated_by');
            $table->dropColumn(['is_third_party_resolver', 'resolution_sequence', 'started_at']);
        });
    }
};

