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
        Schema::create('software_account_pic_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_account_pic_id')
                ->constrained('software_account_pics')
                ->restrictOnDelete();
            $table->foreignId('software_account_id')
                ->constrained('software_accounts')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('action', 30);
            $table->foreignId('performed_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->dateTime('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['software_account_id', 'occurred_at'], 'soft_pic_hist_acc_time_idx');
            $table->index(['user_id', 'occurred_at'], 'soft_pic_hist_user_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_account_pic_history');
    }
};
