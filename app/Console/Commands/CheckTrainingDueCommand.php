<?php

namespace App\Console\Commands;

use App\Services\Training\TrainingAssignmentService;
use Illuminate\Console\Command;

class CheckTrainingDueCommand extends Command
{
    protected $signature = 'training:check-due';
    protected $description = 'Check training retraining intervals and overdue assignment records';

    public function handle(TrainingAssignmentService $service): int
    {
        $this->info('Checking training retraining intervals and overdue records...');

        $results = $service->checkRetrainingDues();

        $this->info("Generated Retraining Assignments: {$results['generated_retraining']}");
        $this->info("Overdue Records Updated: {$results['overdue_count']}");

        return self::SUCCESS;
    }
}
