<?php

namespace App\Services\Kpi;

use App\Models\Kpi\KpiExclusionRequest;
use App\Models\Kpi\KpiHoliday;
use App\Models\Kpi\KpiTaskInstance;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KpiAvailabilityService
{
    public function holidayMapForUser(int $userId, CarbonInterface $periodStart, CarbonInterface $periodEnd): Collection
    {
        return KpiHoliday::query()
            ->with('user')
            ->where('is_active', true)
            ->whereBetween('holiday_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->where(function (Builder $query) use ($userId): void {
                $query->where('user_id', $userId)
                    ->orWhereNull('user_id');
            })
            ->orderBy('holiday_date')
            ->get()
            ->keyBy(fn(KpiHoliday $holiday) => $holiday->holiday_date->toDateString());
    }

    public function exclusionMapsForUser(int $userId, CarbonInterface $periodStart, CarbonInterface $periodEnd): array
    {
        $requests = KpiExclusionRequest::query()
            ->with('assignment.template')
            ->where('user_id', $userId)
            ->whereBetween('requested_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('requested_date')
            ->get();

        $dayRequests = [];
        $taskRequests = [];

        foreach ($requests as $request) {
            $dateKey = $request->requested_date?->toDateString();

            if (!$dateKey) {
                continue;
            }

            if ($request->request_type === 'day') {
                $dayRequests[$dateKey] = $request;
                continue;
            }

            if ($request->request_type === 'task' && $request->task_assignment_id) {
                $taskRequests[$request->task_assignment_id][$dateKey] = $request;
            }
        }

        return [
            'day' => $dayRequests,
            'task' => $taskRequests,
            'requests' => $requests,
        ];
    }

    public function applyApprovedExclusionRequest(KpiExclusionRequest $request): void
    {
        if ($request->status !== 'approved' || !$request->requested_date) {
            return;
        }

        $date = $request->requested_date->toDateString();

        $query = KpiTaskInstance::query()
            ->where('user_id', $request->user_id)
            ->where('period_type', 'daily')
            ->whereDate('task_date', $date);

        if ($request->request_type === 'task') {
            if (!$request->task_assignment_id) {
                return;
            }

            $query->where('task_assignment_id', $request->task_assignment_id);
        }

        $this->excludeOpenInstances(
            $query,
            $request->request_type === 'day' ? 'approved_day_exclusion' : 'approved_task_exclusion'
        );
    }

    public function applyHoliday(KpiHoliday $holiday): void
    {
        if (!$holiday->is_active || !$holiday->holiday_date) {
            return;
        }

        $query = KpiTaskInstance::query()
            ->where('period_type', 'daily')
            ->whereDate('task_date', $holiday->holiday_date->toDateString());

        if ($holiday->user_id) {
            $query->where('user_id', $holiday->user_id);
        }

        $this->excludeOpenInstances($query, 'holiday');
    }

    public function syncDailyInstance(KpiTaskInstance $instance): void
    {
        if ($instance->period_type !== 'daily' || !$instance->task_date) {
            return;
        }

        if (in_array($instance->status, ['passed', 'failed_late'], true)) {
            return;
        }

        $instance->loadMissing(['user']);

        $date = $instance->task_date->toDateString();
        $userId = $instance->user_id;

        $holidayExists = KpiHoliday::query()
            ->where('is_active', true)
            ->whereDate('holiday_date', $date)
            ->where(function (Builder $query) use ($userId): void {
                $query->where('user_id', $userId)
                    ->orWhereNull('user_id');
            })
            ->exists();

        if ($holidayExists) {
            $this->excludeInstance($instance, 'holiday');
            return;
        }

        $dayExclusionExists = KpiExclusionRequest::query()
            ->where('user_id', $instance->user_id)
            ->where('request_type', 'day')
            ->where('status', 'approved')
            ->whereDate('requested_date', $date)
            ->exists();

        if ($dayExclusionExists) {
            $this->excludeInstance($instance, 'approved_day_exclusion');
            return;
        }

        $taskExclusionExists = KpiExclusionRequest::query()
            ->where('user_id', $instance->user_id)
            ->where('request_type', 'task')
            ->where('status', 'approved')
            ->where('task_assignment_id', $instance->task_assignment_id)
            ->whereDate('requested_date', $date)
            ->exists();

        if ($taskExclusionExists) {
            $this->excludeInstance($instance, 'approved_task_exclusion');
        }
    }

    protected function excludeOpenInstances(Builder $query, string $reason): void
    {
        $query
            ->whereNotIn('status', ['passed', 'failed_late'])
            ->get()
            ->each(fn(KpiTaskInstance $instance) => $this->excludeInstance($instance, $reason));
    }

    protected function excludeInstance(KpiTaskInstance $instance, string $reason): void
    {
        $instance->update([
            'status' => 'excluded',
            'final_outcome' => 'excluded',
            'finalized_at' => $instance->finalized_at ?? now(),
            'failure_reason' => $reason,
        ]);
    }
}
