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
        Schema::create('software_property_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_platform_id')
                ->nullable()
                ->constrained('software_platforms')
                ->restrictOnDelete();
            $table->string('code', 100);
            $table->string('label', 150);
            $table->string('data_type', 30);
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('evidence_required')->default(false);
            $table->json('evidence_types')->nullable();
            $table->unsignedInteger('evidence_max_age_days')->nullable();
            $table->unsignedInteger('review_interval_days')->nullable();
            $table->boolean('review_on_change')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->json('validation_rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Generated column ensures global (0) and platform-specific property codes are uniquely indexed
            $table->unsignedBigInteger('platform_scope_id')
                ->storedAs('COALESCE(software_platform_id, 0)');
            $table->unique(['platform_scope_id', 'code'], 'software_prop_def_scope_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_property_definitions');
    }
};
