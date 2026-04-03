<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date');
            $table->string('name');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('remark')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['holiday_date', 'user_id'], 'kpi_holidays_date_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_holidays');
    }
};
