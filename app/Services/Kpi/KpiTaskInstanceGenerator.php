<?php

namespace App\Services\Kpi;

use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class KpiTaskInstanceGenerator
{
    public function __construct(
        protected KpiAvailabilityService $availability,
    ) {
    }

    public function generateForUser(User $user, ?CarbonInterface $anchor = null): int
    {
        $anchor = $anchor ? Carbon::instance($anchor) : now();

        $assignments = KpiTaskAssignment::query()
            ->with(['template.group'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $created = 0;

        foreach ($assignments as $assignment) {
            $created += $this->generateForAssignment($assignment, $anchor);
        }

        return $created;
    }

    public function generateForAll(?CarbonInterface $anchor = null): int
    {
        $anchor = $anchor ? Carbon::instance($anchor) : now();

        $assignments = KpiTaskAssignment::query()
            ->with(['template.group'])
            ->where('is_active', true)
            ->get();

        $created = 0;

        foreach ($assignments as $assignment) {
            $created += $this->generateForAssignment($assignment, $anchor);
        }

        return $created;
    }

    public function generateForAssignment(KpiTaskAssignment $assignment, ?CarbonInterface $anchor = null): int
    {
        $anchor = $anchor ? Carbon::instance($anchor) : now();
        $assignment->loadMissing(['template.group']);

        if (!$assignment->is_active || !$assignment->template || !$assignment->template->is_active) {
            return 0;
        }

        if (!$this->assignmentIsEligibleOn($assignment, $anchor)) {
            return 0;
        }

        return match ($assignment->template->frequency) {
            'daily' => $this->generateDaily($assignment, $anchor),
            'weekly' => $this->generateWeekly($assignment, $anchor),
            'monthly' => $this->generateMonthly($assignment, $anchor),
            default => 0,
        };
    }

    protected function generateDaily(KpiTaskAssignment $assignment, Carbon $anchor): int
    {
        $date = $anchor->copy()->startOfDay();

        if (!$this->assignmentIsEligibleOn($assignment, $date)) {
            return 0;
        }

        return $this->createInstance(
            $assignment,
            'daily',
            $date,
            $date,
            1,
            $date
        ) ? 1 : 0;
    }

    protected function generateWeekly(KpiTaskAssignment $assignment, Carbon $anchor): int
    {
        $periodStart = $anchor->copy()->startOfWeek();
        $periodEnd = $anchor->copy()->endOfWeek();

        if (!$this->periodIntersectsAssignment($assignment, $periodStart, $periodEnd)) {
            return 0;
        }

        return $this->createInstance(
            $assignment,
            'weekly',
            $periodStart,
            $periodEnd,
            1,
            $periodStart
        ) ? 1 : 0;
    }

    protected function generateMonthly(KpiTaskAssignment $assignment, Carbon $anchor): int
    {
        $periodStart = $anchor->copy()->startOfMonth();
        $periodEnd = $anchor->copy()->endOfMonth();

        if (!$this->periodIntersectsAssignment($assignment, $periodStart, $periodEnd)) {
            return 0;
        }

        $count = max(1, (int) $assignment->template->monthly_required_count);
        $created = 0;

        for ($index = 1; $index <= $count; $index++) {
            $created += $this->createInstance(
                $assignment,
                'monthly',
                $periodStart,
                $periodEnd,
                $index,
                $this->monthlyAnchorDate($periodStart, $index, $count)
            ) ? 1 : 0;
        }

        return $created;
    }

    protected function createInstance(
        KpiTaskAssignment $assignment,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $periodIndex,
        ?Carbon $taskDate = null
    ): bool {
        $attributes = [
            'task_assignment_id' => $assignment->id,
            'task_template_id' => $assignment->task_template_id,
            'kpi_group_id' => $assignment->template->kpi_group_id,
            'user_id' => $assignment->user_id,
            'period_type' => $periodType,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'period_index' => $periodIndex,
        ];

        $instance = KpiTaskInstance::query()->firstOrCreate(
            $attributes,
            [
                'task_date' => $taskDate?->toDateString(),
                'due_at' => $this->makeDueAt($assignment, $periodEnd),
                'status' => 'pending',
                'final_outcome' => null,
                'is_on_time' => null,
                'failure_reason' => null,
            ]
        );

        if ($instance->wasRecentlyCreated) {
            $this->availability->syncDailyInstance($instance);

            return true;
        }

        if (!$instance->task_date && $taskDate) {
            $instance->task_date = $taskDate->toDateString();
        }

        if (!$instance->due_at) {
            $instance->due_at = $this->makeDueAt($assignment, $periodEnd);
        }

        if ($instance->isDirty()) {
            $instance->save();
        }

        $this->availability->syncDailyInstance($instance);

        return false;
    }

    protected function makeDueAt(KpiTaskAssignment $assignment, Carbon $periodEnd): Carbon
    {
        $cutoff = $assignment->template->cutoff_time ?: '23:59:59';
        $cutoff = strlen($cutoff) === 5 ? $cutoff . ':00' : $cutoff;

        return $periodEnd->copy()->setTimeFromTimeString($cutoff);
    }

    protected function assignmentIsEligibleOn(KpiTaskAssignment $assignment, CarbonInterface $date): bool
    {
        if (!$assignment->is_active) {
            return false;
        }

        if ($assignment->starts_on && $date->lt($assignment->starts_on)) {
            return false;
        }

        if ($assignment->ends_on && $date->gt($assignment->ends_on)) {
            return false;
        }

        return true;
    }

    protected function periodIntersectsAssignment(
        KpiTaskAssignment $assignment,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd
    ): bool {
        if (!$assignment->is_active) {
            return false;
        }

        if ($assignment->ends_on && $assignment->ends_on->lt($periodStart)) {
            return false;
        }

        if ($assignment->starts_on && $assignment->starts_on->gt($periodEnd)) {
            return false;
        }

        return true;
    }

    protected function monthlyAnchorDate(Carbon $periodStart, int $index, int $count): Carbon
    {
        if ($count <= 1) {
            return $periodStart->copy();
        }

        $daysInMonth = $periodStart->daysInMonth;
        $offset = (int) floor(($daysInMonth / $count) * ($index - 1));

        return $periodStart->copy()->addDays($offset);
    }
}
