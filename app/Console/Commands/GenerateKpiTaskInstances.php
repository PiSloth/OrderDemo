<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Kpi\KpiTaskInstanceGenerator;
use Illuminate\Console\Command;

class GenerateKpiTaskInstances extends Command
{
    protected $signature = 'kpi:generate-instances {--user_id=}';

    protected $description = 'Generate KPI task instances for active assignments.';

    public function handle(KpiTaskInstanceGenerator $generator): int
    {
        $userId = $this->option('user_id');

        if ($userId) {
            $user = User::find($userId);

            if (!$user) {
                $this->error('User not found.');

                return self::FAILURE;
            }

            $created = $generator->generateForUser($user);
            $this->info("Generated {$created} KPI task instance(s) for user {$user->name}.");

            return self::SUCCESS;
        }

        $created = $generator->generateForAll();
        $this->info("Generated {$created} KPI task instance(s).");

        return self::SUCCESS;
    }
}
