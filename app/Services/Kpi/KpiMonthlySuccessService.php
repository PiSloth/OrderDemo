<?php

namespace App\Services\Kpi;

use App\Models\Kpi\KpiTaskInstance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class KpiMonthlySuccessService
{
    public function summarize(Collection $instances, ?Carbon $asOf = null): array
    {
        $today = ($asOf ?? now())->copy()->startOfDay();

        $mustDo = 0;
        $passed = 0;
        $late = 0;
        $absent = 0;
        $excluded = 0;
        $pending = 0;

        foreach ($instances as $instance) {
            if (!$instance instanceof KpiTaskInstance) {
                continue;
            }

            $anchorDate = $this->anchorDate($instance);

            if (!$anchorDate) {
                continue;
            }

            if ($instance->status === 'excluded') {
                $excluded++;
                continue;
            }

            $mustDo++;

            if ($anchorDate->copy()->startOfDay()->gt($today) && !$this->isFinalized($instance)) {
                $passed++;
                continue;
            }

            if ($instance->status === 'passed') {
                $passed++;
                continue;
            }

            if ($instance->status === 'failed_late') {
                $late++;
                continue;
            }

            if ($instance->status === 'failed_missed') {
                $absent++;
                continue;
            }

            $pending++;
        }

        return [
            'must_do_count' => $mustDo,
            'passed_count' => $passed,
            'late_count' => $late,
            'absent_count' => $absent,
            'excluded_count' => $excluded,
            'pending_count' => $pending,
            'score' => $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0,
        ];
    }

    protected function anchorDate(KpiTaskInstance $instance): ?Carbon
    {
        return $instance->task_date
            ?? $instance->period_start
            ?? $instance->period_end
            ?? $instance->due_at
            ?? $instance->submitted_at;
    }

    protected function isFinalized(KpiTaskInstance $instance): bool
    {
        return in_array($instance->status, ['passed', 'failed_late', 'failed_missed', 'excluded'], true);
    }
}
