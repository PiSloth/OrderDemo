<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_systems', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('source_systems')->insert([
            'code' => 'pos_default',
            'name' => 'Default POS Export',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_mm')->nullable();
            $table->timestamps();
        });

        Schema::create('townships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('name');
            $table->string('name_mm')->nullable();
            $table->timestamps();

            $table->unique(['region_id', 'name']);
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_system_id')->constrained('source_systems')->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('file_hash', 64)->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('inserted_sales')->default(0);
            $table->unsignedInteger('updated_sales')->default(0);
            $table->unsignedInteger('inserted_items')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('errors_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('townships');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('source_systems');
    }
};
