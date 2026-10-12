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
        Schema::create('software_account_authorization_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('software_account_id');
            $table->foreign('software_account_id', 'soft_grants_acc_id_fk')
                ->references('id')
                ->on('software_accounts')
                ->cascadeOnDelete();
            $table->string('application_name', 150);
            $table->string('client_id_snapshot', 200)->nullable();
            $table->json('granted_scopes')->nullable();
            $table->string('grant_status', 20)->default('active')->index();
            $table->dateTime('granted_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('last_verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->index(['software_account_id', 'grant_status'], 'soft_grants_acc_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_account_authorization_grants');
    }
};
