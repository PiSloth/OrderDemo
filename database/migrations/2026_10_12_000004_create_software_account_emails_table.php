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
        Schema::create('software_account_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_account_id')
                ->constrained('software_accounts')
                ->cascadeOnDelete();
            $table->foreignId('email_id')
                ->constrained('email_lists')
                ->restrictOnDelete();
            $table->string('purpose', 30);
            $table->boolean('is_primary')->default(false);
            $table->string('status', 20)->default('active');
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['software_account_id', 'email_id', 'purpose'], 'soft_acc_emails_acc_email_purp_unique');

            // Enforces at most one active primary email per software account at DB level
            $table->unsignedTinyInteger('primary_scope')
                ->nullable()
                ->storedAs("CASE WHEN is_primary = 1 AND status = 'active' THEN 1 ELSE NULL END");
            $table->unique(['software_account_id', 'primary_scope'], 'soft_acc_emails_primary_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_account_emails');
    }
};
