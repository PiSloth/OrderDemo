<?php

use App\Models\CompanyDocument;
use App\Models\CompanyDocumentRevision;
use App\Models\CompanyDocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Be defensive: this migration might be re-run after a partial failure.

        if (!Schema::hasColumn('company_documents', 'company_document_type_id')) {
            Schema::table('company_documents', function (Blueprint $table) {
                $table->foreignId('company_document_type_id')
                    ->nullable()
                    ->after('title')
                    ->constrained('company_document_types');
            });
        }

        if (!Schema::hasColumn('company_document_revisions', 'company_document_type_id')) {
            Schema::table('company_document_revisions', function (Blueprint $table) {
                $table->foreignId('company_document_type_id')
                    ->nullable()
                    ->after('title')
                    ->constrained('company_document_types');
            });
        }

        // Backfill types from existing string column if it still exists.
        if (Schema::hasColumn('company_documents', 'document_type')) {
            $distinctTypes = DB::table('company_documents')
                ->select('document_type')
                ->whereNotNull('document_type')
                ->where('document_type', '!=', '')
                ->distinct()
                ->orderBy('document_type')
                ->pluck('document_type');

            foreach ($distinctTypes as $typeName) {
                CompanyDocumentType::firstOrCreate(['name' => $typeName]);
            }
        }

        $typeMap = CompanyDocumentType::query()->pluck('id', 'name');

        if (Schema::hasColumn('company_documents', 'document_type')) {
            CompanyDocument::query()
                ->select(['id', 'document_type'])
                ->whereNotNull('document_type')
                ->where('document_type', '!=', '')
                ->chunkById(500, function ($chunk) use ($typeMap) {
                    foreach ($chunk as $doc) {
                        $typeId = $typeMap[$doc->document_type] ?? null;
                        if ($typeId) {
                            DB::table('company_documents')
                                ->where('id', $doc->id)
                                ->update(['company_document_type_id' => $typeId]);
                        }
                    }
                });
        }

        if (Schema::hasColumn('company_document_revisions', 'document_type')) {
            CompanyDocumentRevision::query()
                ->select(['id', 'document_type'])
                ->whereNotNull('document_type')
                ->where('document_type', '!=', '')
                ->chunkById(500, function ($chunk) use ($typeMap) {
                    foreach ($chunk as $rev) {
                        $typeId = $typeMap[$rev->document_type] ?? null;
                        if ($typeId) {
                            DB::table('company_document_revisions')
                                ->where('id', $rev->id)
                                ->update(['company_document_type_id' => $typeId]);
                        }
                    }
                });
        }

        // If any rows are still null (e.g., empty old type), create a default.
        $defaultTypeId = CompanyDocumentType::firstOrCreate(['name' => 'General'])->id;
        DB::table('company_documents')->whereNull('company_document_type_id')->update(['company_document_type_id' => $defaultTypeId]);
        DB::table('company_document_revisions')->whereNull('company_document_type_id')->update(['company_document_type_id' => $defaultTypeId]);

        // Make FK required going forward.
        Schema::table('company_documents', function (Blueprint $table) {
            $table->foreignId('company_document_type_id')->nullable(false)->change();
        });

        Schema::table('company_document_revisions', function (Blueprint $table) {
            $table->foreignId('company_document_type_id')->nullable(false)->change();
        });

        // Remove old string columns + indexes, but first free the department FK from using a composite index.
        if (Schema::hasColumn('company_documents', 'document_type')) {
            // Ensure department_id has its own index so we can safely drop the composite index.
            if (!$this->indexExists('company_documents', 'company_documents_department_id_index')) {
                Schema::table('company_documents', function (Blueprint $table) {
                    $table->index('department_id');
                });
            }

            // If the department FK uses the composite index, drop and recreate the FK.
            if ($this->foreignKeyExists('company_documents', 'department_id')) {
                Schema::table('company_documents', function (Blueprint $table) {
                    $table->dropForeign(['department_id']);
                });
            }

            Schema::table('company_documents', function (Blueprint $table) {
                if ($this->indexExists('company_documents', 'company_documents_document_type_index')) {
                    $table->dropIndex(['document_type']);
                }
                if ($this->indexExists('company_documents', 'company_documents_department_id_document_type_index')) {
                    $table->dropIndex(['department_id', 'document_type']);
                }
                $table->dropColumn('document_type');
            });

            // Recreate department FK.
            if (!$this->foreignKeyExists('company_documents', 'department_id')) {
                Schema::table('company_documents', function (Blueprint $table) {
                    $table->foreign('department_id')->references('id')->on('departments');
                });
            }

            if (!$this->indexExists('company_documents', 'company_documents_department_id_company_document_type_id_index')) {
                Schema::table('company_documents', function (Blueprint $table) {
                    $table->index(['department_id', 'company_document_type_id']);
                });
            }
        }

        if (Schema::hasColumn('company_document_revisions', 'document_type')) {
            Schema::table('company_document_revisions', function (Blueprint $table) {
                if ($this->indexExists('company_document_revisions', 'company_document_revisions_document_type_index')) {
                    $table->dropIndex(['document_type']);
                }
                $table->dropColumn('document_type');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = (string) (DB::connection()->getDatabaseName());
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$dbName, $table, $indexName]
        );

        return !empty($rows);
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        $dbName = (string) (DB::connection()->getDatabaseName());

        $rows = DB::select(
            'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            [$dbName, $table, $column]
        );

        return !empty($rows);
    }

    public function down(): void
    {
        // Recreate string columns.
        Schema::table('company_documents', function (Blueprint $table) {
            $table->string('document_type')->index()->after('title');
        });

        Schema::table('company_document_revisions', function (Blueprint $table) {
            $table->string('document_type')->index()->after('title');
        });

        // Restore values from FK.
        $typeMap = CompanyDocumentType::query()->pluck('name', 'id');

        CompanyDocument::query()->select(['id', 'company_document_type_id'])->chunkById(500, function ($chunk) use ($typeMap) {
            foreach ($chunk as $doc) {
                $name = $typeMap[$doc->company_document_type_id] ?? null;
                if ($name) {
                    DB::table('company_documents')->where('id', $doc->id)->update(['document_type' => $name]);
                }
            }
        });

        CompanyDocumentRevision::query()->select(['id', 'company_document_type_id'])->chunkById(500, function ($chunk) use ($typeMap) {
            foreach ($chunk as $rev) {
                $name = $typeMap[$rev->company_document_type_id] ?? null;
                if ($name) {
                    DB::table('company_document_revisions')->where('id', $rev->id)->update(['document_type' => $name]);
                }
            }
        });

        Schema::table('company_documents', function (Blueprint $table) {
            $table->dropIndex(['department_id', 'company_document_type_id']);
            $table->dropConstrainedForeignId('company_document_type_id');
            $table->index(['department_id', 'document_type']);
        });

        Schema::table('company_document_revisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_document_type_id');
        });

        Schema::dropIfExists('company_document_types');
    }
};
