<?php

namespace App\Exceptions;

use RuntimeException;

class KpiWorkbookImportException extends RuntimeException
{
    public function __construct(
        public readonly array $errors,
        public readonly string $errorReportFile,
        string $message = 'KPI workbook import failed.'
    ) {
        parent::__construct($message);
    }
}
