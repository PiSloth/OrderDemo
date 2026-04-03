<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskApprovalStep;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\User;
use App\Services\Kpi\KpiTaskInstanceGenerator;
use App\Services\Kpi\KpiRuleEvaluationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Dashboard extends Component
{
    public array $summaryCards = [];
    public array $groupStats = [];
    public $todayTasks;
    public $pendingApprovals;
    public array $departmentLeaderboard = [];
    public array $companyLeaderboard = [];
    public bool $canViewCompanyLeaderboard = false;

    public function mount(KpiTaskInstanceGenerator $generator): void
    {
        $user = Auth::user();

        if ($user) {
            $generator->generateForUser($user);
        }

        $this->todayTasks = collect();
        $this->pendingApprovals = collect();

        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $today = now()->toDateString();

        $currentMonthInstances = KpiTaskInstance::query()
            ->with(['group', 'template'])
            ->where('user_id', $user->id)
            ->where(function (Builder $query) use ($monthStart, $monthEnd) {
                $query
                    ->whereBetween('period_start', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('period_end', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhere(function (Builder $overlap) use ($monthStart, $monthEnd) {
                        $overlap
                            ->where('period_start', '<', $monthStart->toDateString())
                            ->where('period_end', '>', $monthEnd->toDateString());
                    });
            })
            ->get();

        $todayTasks = KpiTaskInstance::query()
            ->with(['group', 'template'])
            ->where('user_id', $user->id)
            ->where('period_type', 'daily')
            ->whereDate('task_date', $today)
            ->orderBy('due_at')
            ->get();

        $pendingApprovals = KpiTaskApprovalStep::query()
            ->with([
                'submission.instance.template.group',
                'submission.instance.user',
                'submission.approvalSteps',
            ])
            ->where('approver_user_id', $user->id)
            ->where('status', 'pending')
            ->get()
            ->filter(fn (KpiTaskApprovalStep $step) => $this->isActionableApprovalStep($step))
            ->sortBy('created_at')
            ->values();

        $metrics = $this->calculateMetrics($currentMonthInstances);

        $this->summaryCards = [
            [
                'label' => 'Month Pass Rate',
                'value' => $this->formatRate($metrics['pass_rate']),
                'hint' => $metrics['passed_count'] . ' passed of ' . $metrics['must_do_count'] . ' must-do tasks',
            ],
            [
                'label' => 'Month Completion',
                'value' => $this->formatRate($metrics['completion_rate']),
                'hint' => $metrics['completed_count'] . ' completed of ' . $metrics['must_do_count'],
            ],
            [
                'label' => 'Month On-Time',
                'value' => $this->formatRate($metrics['on_time_rate']),
                'hint' => $metrics['on_time_count'] . ' on-time submissions this month',
            ],
            [
                'label' => 'Pending Approvals',
                'value' => (string) $pendingApprovals->count(),
                'hint' => 'Queue items waiting for your review',
            ],
        ];

        $ruleEvaluator = app(KpiRuleEvaluationService::class);

        $this->groupStats = $currentMonthInstances
            ->where('status', '!=', 'excluded')
            ->groupBy('kpi_group_id')
            ->map(function (Collection $instances) use ($ruleEvaluator) {
                $group = $instances->first()?->group;
                $metrics = $this->calculateMetrics($instances);
                $ruleEvaluation = $group
                    ? $ruleEvaluator->evaluateGroup($group, $metrics)
                    : null;

                return [
                    'group_name' => $group?->name ?? 'No KPI Group',
                    'must_do_count' => $metrics['must_do_count'],
                    'passed_count' => $metrics['passed_count'],
                    'failed_count' => $metrics['failed_count'],
                    'completion_rate' => $metrics['completion_rate'],
                    'pass_rate' => $metrics['pass_rate'],
                    'rule_type' => $ruleEvaluation['rule_type'] ?? null,
                    'target_display' => $ruleEvaluation['target_display'] ?? '-',
                    'actual_display' => $ruleEvaluation['actual_display'] ?? '-',
                    'passes_rule' => $ruleEvaluation['passes_rule'] ?? null,
                    'rule_summary' => $ruleEvaluation['summary'] ?? '-',
                ];
            })
            ->sortByDesc('pass_rate')
            ->values()
            ->all();

        $this->todayTasks = $todayTasks;
        $this->pendingApprovals = $pendingApprovals->take(5)->values();
        $this->departmentLeaderboard = $this->buildLeaderboard($user->department_id, $user->id);
        $this->canViewCompanyLeaderboard = Gate::allows('kpiViewCompanyLeaderboard');
        $this->companyLeaderboard = $this->canViewCompanyLeaderboard
            ? $this->buildLeaderboard(null, $user->id)
            : [];
    }

    public function render()
    {
        return view('livewire.kpi.dashboard');
    }

    protected function buildLeaderboard(?int $departmentId, int $currentUserId): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $instances = KpiTaskInstance::query()
            ->with(['user.department', 'user.position'])
            ->whereHas('user', function (Builder $query) use ($departmentId) {
                $query
                    ->where('suspended', false)
                    ->when($departmentId, fn (Builder $departmentQuery) => $departmentQuery->where('department_id', $departmentId));
            })
            ->where(function (Builder $query) use ($monthStart, $monthEnd) {
                $query
                    ->whereBetween('period_start', [$monthStart, $monthEnd])
                    ->orWhereBetween('period_end', [$monthStart, $monthEnd])
                    ->orWhere(function (Builder $overlap) use ($monthStart, $monthEnd) {
                        $overlap
                            ->where('period_start', '<', $monthStart)
                            ->where('period_end', '>', $monthEnd);
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
                    'completion_rate' => $metrics['completion_rate'],
                    'on_time_rate' => $metrics['on_time_rate'],
                    'pass_rate' => $metrics['pass_rate'],
                    'must_do_count' => $metrics['must_do_count'],
                    'is_current_user' => $user?->id === $currentUserId,
                ];
            })
            ->filter(fn (array $entry) => $entry['must_do_count'] > 0)
            ->sort(function (array $left, array $right) {
                return [$right['completion_rate'], $right['on_time_rate'], $right['pass_rate']]
                    <=> [$left['completion_rate'], $left['on_time_rate'], $left['pass_rate']];
            })
            ->values()
            ->take(5)
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
            'completed_count' => $completedCount,
            'failed_count' => $failedCount,
            'on_time_count' => $onTimeCount,
            'total_spend_cost' => 0,
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

    protected function formatRate(float $rate): string
    {
        return number_format($rate, 2) . '%';
    }

    protected function isActionableApprovalStep(KpiTaskApprovalStep $step): bool
    {
        $submission = $step->submission;

        if (!$submission || $submission->status === 'rejected') {
            return false;
        }

        return $submission->approvalSteps
            ->where('step_order', '<', $step->step_order)
            ->every(fn (KpiTaskApprovalStep $previousStep) => $previousStep->status === 'approved');
    }
}
