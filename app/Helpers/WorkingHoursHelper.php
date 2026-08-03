<?php

namespace App\Helpers;

use Carbon\Carbon;

class WorkingHoursHelper
{
    public const WORK_START_HOUR = 9;  // 09:00 AM
    public const WORK_END_HOUR = 17;   // 17:00 PM (5:00 PM)

    /**
     * Calculate cutoff due date based strictly on working hours (09:00 AM - 17:00 PM, Mon-Fri).
     *
     * @param float $durationHours
     * @param Carbon|null $startDate
     * @return Carbon
     */
    public static function calculateDueDate(float $durationHours, ?Carbon $startDate = null): Carbon
    {
        $current = $startDate ? $startDate->copy() : Carbon::now();
        $remainingMinutes = max(0, (int) round($durationHours * 60));

        $isWeekend = fn(Carbon $date) => $date->isWeekend();

        $moveToNextWorkingDayStart = function (Carbon $date) use ($isWeekend) {
            $date->addDay()->setTime(self::WORK_START_HOUR, 0, 0);
            while ($isWeekend($date)) {
                $date->addDay();
            }
        };

        // If current time is on a weekend, jump to next Monday 09:00 AM
        if ($isWeekend($current)) {
            $moveToNextWorkingDayStart($current);
        } else {
            // Before 09:00 AM today -> set to 09:00 AM today
            if ($current->hour < self::WORK_START_HOUR) {
                $current->setTime(self::WORK_START_HOUR, 0, 0);
            } elseif ($current->hour >= self::WORK_END_HOUR) {
                // After 17:00 PM today -> set to next working day 09:00 AM
                $moveToNextWorkingDayStart($current);
            }
        }

        while ($remainingMinutes > 0) {
            $todayWorkEnd = $current->copy()->setTime(self::WORK_END_HOUR, 0, 0);
            $availableMinutesToday = max(0, $current->diffInMinutes($todayWorkEnd, false));

            if ($remainingMinutes <= $availableMinutesToday) {
                $current->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $availableMinutesToday;
                $moveToNextWorkingDayStart($current);
            }
        }

        return $current;
    }
}
