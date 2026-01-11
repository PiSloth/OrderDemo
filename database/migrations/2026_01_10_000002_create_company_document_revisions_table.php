<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_document_revisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_document_id')
                ->constrained('company_documents')
                ->cascadeOnDelete();

            $table->unsignedInteger('version');
            $table->string('title');
            $table->string('document_type')->index();
            $table->foreignId('department_id')->constrained('departments');
            $table->date('announced_at')->nullable()->index();
            $table->longText('body');

            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_document_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_document_revisions');
    }
};
