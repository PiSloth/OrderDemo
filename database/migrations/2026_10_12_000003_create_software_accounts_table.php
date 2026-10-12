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
        Schema::create('software_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_platform_id')
                ->constrained('software_platforms')
                ->restrictOnDelete();
            $table->string('account_name', 255);
            $table->string('username', 255);
            $table->json('properties');
            $table->string('lifecycle_status', 20)->default('active')->index();
            $table->string('security_status', 20)->default('unknown')->index();
            $table->dateTime('last_reviewed_at')->nullable();
            $table->dateTime('next_review_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['software_platform_id', 'username']);
            $table->index(['lifecycle_status', 'security_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_accounts');
    }
};
