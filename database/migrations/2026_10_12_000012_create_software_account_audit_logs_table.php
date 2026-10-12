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
        Schema::create('software_account_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_account_id')
                ->nullable()
                ->constrained('software_accounts')
                ->nullOnDelete();
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('outcome', 20)->default('success');
            $table->text('reason')->nullable();
            $table->char('request_id', 36)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['software_account_id', 'occurred_at'], 'soft_audit_acc_time_idx');
            $table->index(['actor_user_id', 'occurred_at'], 'soft_audit_user_time_idx');
            $table->index(['action', 'occurred_at'], 'soft_audit_action_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_account_audit_logs');
    }
};
