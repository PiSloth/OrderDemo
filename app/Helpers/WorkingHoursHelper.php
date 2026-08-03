<?php

namespace App\Helpers;

use App\Models\Kpi\KpiExclusionRequest;
use App\Models\Kpi\KpiHoliday;
use Carbon\Carbon;

class WorkingHoursHelper
{
    public const WORK_END_HOUR = 17; // 17:00 PM (5:00 PM)

    /**
     * Check if a specific user has a holiday or a holiday/exclusion request (pending or approved) on a date.
     */
    public static function isUserOnHoliday(int $userId, Carbon $date): bool
    {
        $dateStr = $date->toDateString();

        // 1. Active company-wide or user-specific holiday
        $holidayExists = KpiHoliday::query()
            ->where('is_active', true)
            ->whereDate('holiday_date', $dateStr)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereNull('user_id');
            })
            ->exists();

        if ($holidayExists) {
            return true;
        }

        // 2. Pending or approved exclusion / holiday request for this user
        $requestExists = KpiExclusionRequest::query()
            ->where('user_id', $userId)
            ->whereDate('requested_date', $dateStr)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        return $requestExists;
    }

    /**
     * Check if ALL target users are on holiday on a given date.
     */
    public static function areAllUsersOnHoliday(array $targetUserIds, Carbon $date): bool
    {
        if (empty($targetUserIds)) {
            return false;
        }

        foreach ($targetUserIds as $userId) {
            if (!self::isUserOnHoliday((int) $userId, $date)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get work start time for a given date.
     * Every Monday working hour starts at 9:45 AM (09:45).
     * Tue-Fri working hour starts at 9:00 AM (09:00).
     */
    public static function getWorkStart(Carbon $date): array
    {
        return [
            'hour' => 9,
            'minute' => $date->isMonday() ? 45 : 0,
        ];
    }

    /**
     * Set date to work start time for that day.
     */
    public static function setWorkStart(Carbon $date): Carbon
    {
        $start = self::getWorkStart($date);
        return $date->setTime($start['hour'], $start['minute'], 0);
    }

    /**
     * Calculate cutoff due date based strictly on working hours:
     * - Monday: 09:45 AM to 17:00 PM
     * - Tue-Fri: 09:00 AM to 17:00 PM
     * - Sat-Sun: Weekend (skip to Monday 09:45 AM)
     * - Days where ALL target users are on holiday are skipped.
     *
     * @param float $durationHours
     * @param Carbon|null $startDate
     * @param array $targetUserIds
     * @return Carbon
     */
    public static function calculateDueDate(float $durationHours, ?Carbon $startDate = null, array $targetUserIds = []): Carbon
    {
        $current = $startDate ? $startDate->copy() : Carbon::now();
        $remainingMinutes = max(0, (int) round($durationHours * 60));

        $isWeekend = fn(Carbon $date) => $date->isWeekend();

        $areAllOnHoliday = function (Carbon $date) use ($targetUserIds): bool {
            if (empty($targetUserIds)) {
                return false;
            }
            return self::areAllUsersOnHoliday($targetUserIds, $date);
        };

        $moveToNextWorkingDayStart = function (Carbon $date) use ($isWeekend, $areAllOnHoliday) {
            $date->addDay();
            while ($isWeekend($date) || $areAllOnHoliday($date)) {
                $date->addDay();
            }
            self::setWorkStart($date);
        };

        if ($isWeekend($current) || $areAllOnHoliday($current)) {
            $moveToNextWorkingDayStart($current);
        } else {
            $start = self::getWorkStart($current);
            $startMinutes = $start['hour'] * 60 + $start['minute'];
            $currentMinutes = $current->hour * 60 + $current->minute;
            $endMinutes = self::WORK_END_HOUR * 60;

            if ($currentMinutes < $startMinutes) {
                self::setWorkStart($current);
            } elseif ($currentMinutes >= $endMinutes) {
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
