<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('it_satisfaction_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('badge_text')->default('IT Satisfaction Survey');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('rating_scale')->default(5);
            $table->boolean('is_mandatory')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('it_satisfaction_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('it_satisfaction_surveys')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_name');
            $table->unsignedTinyInteger('rating'); // 1 to 5
            $table->json('aspect_ratings')->nullable();
            $table->text('feedback')->nullable();
            $table->dateTime('submitted_at');
            $table->timestamps();

            $table->unique(['survey_id', 'user_id'], 'unique_survey_user_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_satisfaction_ratings');
        Schema::dropIfExists('it_satisfaction_surveys');
    }
};
