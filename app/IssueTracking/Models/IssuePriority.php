<?php

namespace App\IssueTracking\Models;

use Illuminate\Database\Eloquent\Model;

class IssuePriority extends Model
{
    protected $fillable = ['name', 'level', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Get clock_type ('continuous_24h', 'office_hours', 'manual_schedule').
     */
    public function getClockTypeAttribute(): string
    {
        if (isset($this->settings['clock_type'])) {
            return $this->settings['clock_type'];
        }

        if ($this->level === 1) {
            return 'continuous_24h';
        }
        if ($this->level === 4) {
            return 'manual_schedule';
        }

        return 'office_hours';
    }

    /**
     * Get SLA target hours (float|null).
     */
    public function getTargetHoursAttribute(): ?float
    {
        if (array_key_exists('target_hours', $this->settings ?? [])) {
            return $this->settings['target_hours'] !== null ? (float)$this->settings['target_hours'] : null;
        }

        return match ($this->level) {
            1 => 24.0,
            2 => 8.5,
            3 => 17.0,
            default => null,
        };
    }

    /**
     * Get SLA fail points multiplier.
     */
    public function getFailPointsAttribute(): int
    {
        if (isset($this->settings['fail_points'])) {
            return (int)$this->settings['fail_points'];
        }

        return match ($this->level) {
            1 => 10,
            2 => 5,
            default => 1,
        };
    }

    /**
     * Determine if this priority uses manual schedule / due date.
     */
    public function getIsManualScheduleAttribute(): bool
    {
        if (isset($this->settings['is_manual_schedule'])) {
            return (bool)$this->settings['is_manual_schedule'];
        }

        return $this->clock_type === 'manual_schedule' || $this->level === 4;
    }
}
