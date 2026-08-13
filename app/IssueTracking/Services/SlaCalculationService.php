<?php

namespace App\IssueTracking\Services;

use App\IssueTracking\Models\IssuePriority;
use Carbon\Carbon;

class SlaCalculationService
{
    /**
     * Get the developer office hours schedule (in 24h format).
     */
    public function getOfficeHoursConfig(): array
    {
        return [
            // 1 = Mon, 2 = Tue, 3 = Wed, 4 = Thu, 5 = Fri
            1 => [['start' => '08:30', 'end' => '17:00']],
            2 => [['start' => '08:30', 'end' => '17:00']],
            3 => [['start' => '08:30', 'end' => '17:00']],
            4 => [['start' => '08:30', 'end' => '17:00']],
            5 => [['start' => '08:30', 'end' => '17:00']],
            // 6 = Saturday
            6 => [['start' => '09:00', 'end' => '12:30']],
            // 0 = Sunday (Off)
            0 => [],
        ];
    }

    /**
     * Get Target Hours / Minutes configuration for given Priority.
     */
    public function getTargetOfficeMinutes(IssuePriority $priority): array
    {
        $clockType = $priority->clock_type;
        $targetHours = $priority->target_hours;

        // Manual schedule (Level 4 or manual schedule flag): return manual schedule indicator
        if ($clockType === 'manual_schedule' || $priority->is_manual_schedule) {
            return [
                'is_manual_schedule' => true,
                'is_continuous_24h' => false,
                'minutes' => null
            ];
        }

        // Continuous 24/7 clock (Level 1 or 24/7 flag)
        if ($clockType === 'continuous_24h' || $priority->level === 1) {
            $hours = $targetHours ?? 24.0;
            return [
                'is_manual_schedule' => false,
                'is_continuous_24h' => true,
                'minutes' => (int) round($hours * 60)
            ];
        }

        // Office hours schedule (Level 2, 3, etc.)
        $hours = $targetHours ?? ($priority->level === 2 ? 8.5 : 17.0);

        return [
            'is_manual_schedule' => false,
            'is_continuous_24h' => false,
            'minutes' => (int) round($hours * 60)
        ];
    }

    /**
     * Get Fail Points based on Priority settings.
     */
    public function getFailPoints(IssuePriority $priority): int
    {
        return $priority->fail_points;
    }

    /**
     * Calculate Due Date timestamp based on start time and priority level/settings.
     * Returns null if the priority uses manual scheduling (Level 4).
     */
    public function calculateDueDate(Carbon $startAt, IssuePriority $priority): ?Carbon
    {
        $config = $this->getTargetOfficeMinutes($priority);

        // If priority is marked for manual schedule (Level 4), auto SLA due date is null
        if ($config['is_manual_schedule'] || $config['minutes'] === null) {
            return null;
        }

        // Continuous 24h clock (Level 1 / 24/7 clock): ignores office hours & Sundays
        if ($config['is_continuous_24h']) {
            return $startAt->copy()->addMinutes($config['minutes']);
        }

        // Office hours schedule (Level 2, Level 3): calculates using office working hours
        return $this->addOfficeMinutes($startAt->copy(), $config['minutes']);
    }

    /**
     * Add office minutes to Carbon datetime based on office hours schedule.
     */
    public function addOfficeMinutes(Carbon $current, int $remainingMinutes): Carbon
    {
        $schedule = $this->getOfficeHoursConfig();

        while ($remainingMinutes > 0) {
            $dayOfWeek = $current->dayOfWeek; // 0 = Sun, 1 = Mon...
            $slots = $schedule[$dayOfWeek] ?? [];

            if (empty($slots)) {
                // Non-business day (e.g. Sunday): jump to next morning 08:30
                $current->addDay()->setTime(8, 30, 0);
                continue;
            }

            $slotAvailable = false;

            foreach ($slots as $slot) {
                [$startH, $startM] = explode(':', $slot['start']);
                [$endH, $endM] = explode(':', $slot['end']);

                $slotStart = $current->copy()->setTime((int)$startH, (int)$startM, 0);
                $slotEnd = $current->copy()->setTime((int)$endH, (int)$endM, 0);

                if ($current->gte($slotEnd)) {
                    // Past today's slot
                    continue;
                }

                if ($current->lt($slotStart)) {
                    // Before slot start, advance to start of slot
                    $current = $slotStart->copy();
                }

                $availableInSlot = $current->diffInMinutes($slotEnd, false);

                if ($availableInSlot <= 0) {
                    continue;
                }

                $slotAvailable = true;

                if ($remainingMinutes <= $availableInSlot) {
                    $current->addMinutes($remainingMinutes);
                    $remainingMinutes = 0;
                    break;
                } else {
                    $remainingMinutes -= $availableInSlot;
                    $current = $slotEnd->copy();
                }
            }

            if (!$slotAvailable || $remainingMinutes > 0) {
                // Move to start of next day (Mon-Fri 08:30, Sat 09:00)
                $current->addDay();
                $nextDayOfWeek = $current->dayOfWeek;
                $nextSlots = $schedule[$nextDayOfWeek] ?? [];

                if (!empty($nextSlots)) {
                    [$startH, $startM] = explode(':', $nextSlots[0]['start']);
                    $current->setTime((int)$startH, (int)$startM, 0);
                } else {
                    $current->setTime(8, 30, 0);
                }
            }
        }

        return $current;
    }
}
