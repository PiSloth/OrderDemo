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
        Schema::create('software_account_pics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_account_id')
                ->constrained('software_accounts')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('status', 20)->default('active');
            $table->dateTime('assigned_at');
            $table->dateTime('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            // Prevents duplicate active assignments for the same account and user
            $table->unsignedBigInteger('active_user_scope')
                ->nullable()
                ->storedAs("CASE WHEN status = 'active' THEN user_id ELSE NULL END");
            $table->unique(['software_account_id', 'active_user_scope'], 'soft_acc_pics_active_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_account_pics');
    }
};
