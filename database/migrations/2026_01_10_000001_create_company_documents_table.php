<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('document_type')->index();
            $table->foreignId('department_id')->constrained('departments');
            $table->date('announced_at')->nullable()->index();
            $table->longText('body');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_documents');
    }
};
