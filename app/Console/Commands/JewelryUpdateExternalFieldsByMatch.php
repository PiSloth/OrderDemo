<?php

namespace App\Console\Commands;

use App\Services\Jewelry\JewelryExcelImportService;
use Illuminate\Console\Command;

class JewelryUpdateExternalFieldsByMatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example:
     * php artisan jewelry:update-external-fields-by-match storage/app/imports/file.xlsx --branch=1
     *
     * @var string
     */
    protected $signature = 'jewelry:update-external-fields-by-match {path : Path to xlsx/csv/ods file} {--branch= : Optional branch_id filter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update jewelry_items.external_id and jewelry_items.lot_serial by matching imported rows on weights + PO Ref + stone price /2 rule.';

    public function handle(JewelryExcelImportService $service): int
    {
        $path = (string) $this->argument('path');
        $branch = $this->option('branch');
        $branchId = is_null($branch) || $branch === '' ? null : (int) $branch;

        $result = $service->updateExternalIdAndLotSerialByMatch($path, $branchId);

        $updated = (int) ($result['updated'] ?? 0);
        $errors = (array) ($result['errors'] ?? []);
        $warnings = (array) ($result['warnings'] ?? []);
        $notFound = (array) ($result['not_found'] ?? []);

        if (!empty($errors)) {
            $this->error('Import finished with errors.');
            foreach ($errors as $e) {
                $this->line('- ' . (string) $e);
            }
        }

        foreach ($notFound as $m) {
            $this->warn((string) $m);
        }

        foreach ($warnings as $w) {
            $this->warn((string) $w);
        }

        $this->info("Updated {$updated} item(s).");

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }
}
