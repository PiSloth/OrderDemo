<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('software_account_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_account_id')
                ->constrained('software_accounts')
                ->restrictOnDelete();
            $table->foreignId('property_definition_id')
                ->constrained('software_property_definitions')
                ->restrictOnDelete();
            $table->foreignId('property_history_id')
                ->nullable()
                ->constrained('software_account_property_history')
                ->nullOnDelete();
            $table->string('storage_disk', 50)->default('local');
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size_bytes');
            $table->char('sha256_hash', 64);
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['software_account_id', 'status'], 'soft_evidence_acc_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_account_evidence');
    }
};
