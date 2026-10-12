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
        Schema::create('software_account_property_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_account_id')
                ->constrained('software_accounts')
                ->restrictOnDelete();
            $table->foreignId('property_definition_id')
                ->constrained('software_property_definitions')
                ->restrictOnDelete();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->foreignId('changed_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('changed_at');
            $table->text('change_reason')->nullable();
            $table->string('review_status', 20)->default('not_required');
            $table->char('request_id', 36)->nullable();
            $table->timestamps();

            $table->index(['software_account_id', 'property_definition_id', 'changed_at'], 'soft_prop_hist_acc_def_time_idx');
            $table->index(['changed_by', 'changed_at'], 'soft_prop_hist_user_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_account_property_history');
    }
};
