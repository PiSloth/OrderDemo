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
        // Drop the user_department table
        Schema::dropIfExists('user_department');

        // Add department_id and location_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->after('branch_id');
            $table->foreignId('location_id')->nullable()->constrained()->after('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the added columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });

        // Recreate the user_department table
        Schema::create('user_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->string('role')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            // Ensure a user can only be assigned to a department once
            $table->unique(['user_id', 'department_id']);
        });
    }
};
