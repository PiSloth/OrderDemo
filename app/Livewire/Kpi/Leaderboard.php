<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskInstance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Leaderboard extends Component
{
    public string $period = 'month';
    public array $summaryCards = [];
    public array $departmentLeaderboard = [];
    public array $companyLeaderboard = [];
    public ?array $myDepartmentRank = null;
    public ?array $myCompanyRank = null;
    public bool $canViewCompanyLeaderboard = false;

    public function mount(): void
    {
        $this->loadLeaderboard();
    }

    public function updatedPeriod(): void
    {
        if (!in_array($this->period, ['week', 'month'], true)) {
            $this->period = 'month';
        }

        $this->loadLeaderboard();
    }

    public function loadLeaderboard(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        [$periodStart, $periodEnd, $label] = $this->periodBounds();

        $this->departmentLeaderboard = $this->buildLeaderboard(
            departmentId: $user->department_id,
            currentUserId: $user->id,
            periodStart: $periodStart,
            periodEnd: $periodEnd
        );

        $this->canViewCompanyLeaderboard = Gate::allows('kpiViewCompanyLeaderboard');
        $this->companyLeaderboard = $this->canViewCompanyLeaderboard
            ? $this->buildLeaderboard(
                departmentId: null,
                currentUserId: $user->id,
                periodStart: $periodStart,
                periodEnd: $periodEnd
            )
            : [];

        $this->myDepartmentRank = collect($this->departmentLeaderboard)->firstWhere('is_current_user', true);
        $this->myCompanyRank = $this->canViewCompanyLeaderboard
            ? collect($this->companyLeaderboard)->firstWhere('is_current_user', true)
            : null;

        $this->summaryCards = [
            [
                'label' => $label . ' Department Rank',
                'value' => $this->myDepartmentRank ? '#' . $this->myDepartmentRank['rank'] : '-',
                'hint' => $this->myDepartmentRank
                    ? number_format($this->myDepartmentRank['completion_rate'], 2) . '% completion'
                    : 'No department data yet',
            ],
            [
                'label' => $label . ' Department On-Time',
                'value' => $this->myDepartmentRank ? number_format($this->myDepartmentRank['on_time_rate'], 2) . '%' : '-',
                'hint' => $this->myDepartmentRank
                    ? number_format($this->myDepartmentRank['pass_rate'], 2) . '% pass rate'
                    : 'No department data yet',
            ],
            [
                'label' => $label . ' Company Rank',
                'value' => $this->myCompanyRank ? '#' . $this->myCompanyRank['rank'] : ($this->canViewCompanyLeaderboard ? '-' : 'Locked'),
                'hint' => $this->canViewCompanyLeaderboard
                    ? ($this->myCompanyRank ? number_format($this->myCompanyRank['completion_rate'], 2) . '% completion' : 'No company data yet')
                    : 'Company ranking is limited by role',
            ],
            [
                'label' => $label . ' Company On-Time',
                'value' => $this->myCompanyRank ? number_format($this->myCompanyRank['on_time_rate'], 2) . '%' : ($this->canViewCompanyLeaderboard ? '-' : 'Locked'),
                'hint' => $this->canViewCompanyLeaderboard
                    ? ($this->myCompanyRank ? number_format($this->myCompanyRank['pass_rate'], 2) . '% pass rate' : 'No company data yet')
                    : 'Assistant Manager and above only',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.kpi.leaderboard');
    }

    protected function buildLeaderboard(?int $departmentId, int $currentUserId, string $periodStart, string $periodEnd): array
    {
        $instances = KpiTaskInstance::query()
            ->with(['user.department', 'user.position'])
            ->whereHas('user', function (Builder $query) use ($departmentId) {
                $query
                    ->where('suspended', false)
                    ->when($departmentId, fn (Builder $departmentQuery) => $departmentQuery->where('department_id', $departmentId));
            })
            ->where(function (Builder $query) use ($periodStart, $periodEnd) {
                $query
                    ->whereBetween('period_start', [$periodStart, $periodEnd])
                    ->orWhereBetween('period_end', [$periodStart, $periodEnd])
                    ->orWhere(function (Builder $overlap) use ($periodStart, $periodEnd) {
                        $overlap
                            ->where('period_start', '<', $periodStart)
                            ->where('period_end', '>', $periodEnd);
                    });
            })
            ->get()
            ->where('status', '!=', 'excluded');

        return $instances
            ->groupBy('user_id')
            ->map(function (Collection $userInstances) use ($currentUserId) {
                $user = $userInstances->first()?->user;
                $metrics = $this->calculateMetrics($userInstances);

                return [
                    'user_id' => $user?->id,
                    'name' => $user?->name ?? 'Unknown',
                    'profile_photo_url' => $user?->profile_photo_url ?? asset('images/admin-icon.png'),
                    'department' => $user?->department?->name ?? '-',
                    'completion_rate' => $metrics['completion_rate'],
                    'on_time_rate' => $metrics['on_time_rate'],
                    'pass_rate' => $metrics['pass_rate'],
                    'must_do_count' => $metrics['must_do_count'],
                    'passed_count' => $metrics['passed_count'],
                    'failed_count' => $metrics['failed_count'],
                    'is_current_user' => $user?->id === $currentUserId,
                ];
            })
            ->filter(fn (array $entry) => $entry['must_do_count'] > 0)
            ->sort(function (array $left, array $right) {
                return [$right['completion_rate'], $right['on_time_rate'], $right['pass_rate'], $right['passed_count']]
                    <=> [$left['completion_rate'], $left['on_time_rate'], $left['pass_rate'], $left['passed_count']];
            })
            ->values()
            ->map(function (array $entry, int $index) {
                $entry['rank'] = $index + 1;

                return $entry;
            })
            ->all();
    }

    protected function calculateMetrics(Collection $instances): array
    {
        $eligibleInstances = $instances->where('status', '!=', 'excluded')->values();
        $mustDoCount = $eligibleInstances->count();
        $passedCount = $eligibleInstances->where('status', 'passed')->count();
        $completedCount = $eligibleInstances->whereIn('status', ['passed', 'failed_late'])->count();
        $failedCount = $eligibleInstances->whereIn('status', ['failed_late', 'failed_missed'])->count();
        $onTimeCount = $eligibleInstances->where('is_on_time', true)->count();

        return [
            'must_do_count' => $mustDoCount,
            'passed_count' => $passedCount,
            'failed_count' => $failedCount,
            'completion_rate' => $this->calculateRate($completedCount, $mustDoCount),
            'pass_rate' => $this->calculateRate($passedCount, $mustDoCount),
            'on_time_rate' => $this->calculateRate($onTimeCount, $mustDoCount),
        ];
    }

    protected function calculateRate(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0;
        }

        return round(($numerator / $denominator) * 100, 2);
    }

    protected function periodBounds(): array
    {
        if ($this->period === 'week') {
            return [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
                'This Week',
            ];
        }

        return [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
            'This Month',
        ];
    }
}
