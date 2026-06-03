<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskSubmission;
use App\Models\User;
use App\Services\Kpi\KpiAvailabilityService;
use App\Services\Kpi\KpiMonthlySuccessService;
use App\Services\Kpi\KpiRuleEvaluationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Audit extends Component
{
    public string $month = '';
    public int $selectedUserId;
    public ?int $selectedSubmissionId = null;
    public ?int $selectedInstanceId = null;
    public bool $isSuperAdmin = false;
    public string $auditInstanceStatus = 'pending';
    public string $auditInstanceReason = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->selectedUserId = auth()->user()->id;
        $this->isSuperAdmin = Gate::allows('isSuperAdmin');
    }


    public function openSubmissionDetail(int $submissionId): void
    {
        $submission = $this->findVisibleSubmission($submissionId);

        $this->selectedSubmissionId = $submission->id;
        $this->selectedInstanceId = null;
        $this->auditInstanceStatus = (string) ($submission->instance?->status ?? 'pending');
        $this->auditInstanceReason = (string) ($submission->instance?->failure_reason ?? '');
        $this->resetErrorBag();
    }

    public function openInstanceDetail(int $instanceId): void
    {
        $instance = $this->findVisibleInstance($instanceId);

        $this->selectedInstanceId = $instance->id;
        $this->selectedSubmissionId = null;
        $this->auditInstanceStatus = (string) $instance->status;
        $this->auditInstanceReason = (string) ($instance->failure_reason ?? '');
        $this->resetErrorBag();
    }

    public function closeSubmissionDetail(): void
    {
        $this->selectedSubmissionId = null;
        $this->selectedInstanceId = null;
        $this->auditInstanceStatus = 'pending';
        $this->auditInstanceReason = '';
        $this->resetErrorBag();
    }

    public function getSelectedInstanceProperty(): ?KpiTaskInstance
    {
        if (!$this->selectedInstanceId) {
            return null;
        }

        return $this->findVisibleInstance($this->selectedInstanceId, false);
    }

    public function saveAuditInstanceStatus(): void
    {
        Gate::authorize('isSuperAdmin');

        $submission = $this->getSelectedSubmissionProperty();
        $instance = $this->getSelectedInstanceProperty();

        if (!$submission && !$instance) {
            throw ValidationException::withMessages([
                'auditInstanceStatus' => 'Select a submission or task instance before changing status.',
            ]);
        }

        $validated = $this->validate([
            'auditInstanceStatus' => ['required', 'in:pending,rejected,waiting_first_approval,waiting_final_approval,passed,failed_late,failed_missed,excluded'],
            'auditInstanceReason' => ['required', 'string', 'max:1000'],
        ], [], [
            'auditInstanceStatus' => 'status',
            'auditInstanceReason' => 'reason',
        ]);

        DB::transaction(function () use ($submission, $instance, $validated): void {
            $taskInstance = $instance
                ? KpiTaskInstance::query()->whereKey($instance->id)->lockForUpdate()->firstOrFail()
                : KpiTaskInstance::query()
                    ->whereKey($submission->task_instance_id)
                    ->lockForUpdate()
                    ->firstOrFail();

            $now = now();
            $status = $validated['auditInstanceStatus'];
            $reason = trim((string) $validated['auditInstanceReason']);

            $taskInstance->update([
                'status' => $status,
                'final_outcome' => in_array($status, ['passed', 'failed_late', 'failed_missed', 'excluded'], true) ? $status : null,
                'finalized_at' => in_array($status, ['passed', 'failed_late', 'failed_missed', 'excluded'], true) ? $now : null,
                'failure_reason' => $reason,
            ]);
        });

        if ($submission) {
            $this->openSubmissionDetail($submission->id);
        } else {
            $this->openInstanceDetail($instance->id);
        }

        $this->resetErrorBag();

        session()->flash('message', 'Task status updated by Super Admin.');
    }

    public function getSelectedSubmissionProperty(): ?KpiTaskSubmission
    {
        if (!$this->selectedSubmissionId) {
            return null;
        }

        return $this->findVisibleSubmission($this->selectedSubmissionId, false);
    }

    public function render(
        KpiAvailabilityService $availability,
        KpiRuleEvaluationService $ruleEvaluator,
        KpiMonthlySuccessService $monthlySuccessService
    )
    {
        $monthStart = $this->monthStart();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $evaluationEnd = $this->evaluationEnd($monthStart, $monthEnd);

        $users = $this->accessibleUsersQuery()
            ->orderBy('name')
            ->get();

        if ($users->isEmpty()) {
            return view('livewire.kpi.audit', [
                'users' => collect(),
                'selectedUser' => null,
                'days' => collect(),
                'rows' => collect(),
                'groupSummaries' => collect(),
                'groupCards' => [],
                'legendItems' => $this->legendItems(),
                'employeeAsyncData' => $this->buildEmployeeAsyncData(collect()),
                'selectedSubmission' => null,
                'selectedInstance' => null,
                'pendingApprovalSubmissions' => collect(),
            ]);
        }

        // $selectedUser = $this->resolveSelectedUser($users);
        $selectedUser = User::findOrFail($this->selectedUserId);

        if (!$selectedUser) {
            return view('livewire.kpi.audit', [
                'users' => $users,
                'selectedUser' => null,
                'days' => collect(),
                'rows' => collect(),
                'groupSummaries' => collect(),
                'groupCards' => [],
                'legendItems' => $this->legendItems(),
                'employeeAsyncData' => $this->buildEmployeeAsyncData($users),
                'selectedSubmission' => null,
                'selectedInstance' => null,
                'pendingApprovalSubmissions' => collect(),
            ]);
        }

        $days = collect(range(1, $monthEnd->day))
            ->map(fn(int $day) => $monthStart->copy()->day($day));

        $assignments = KpiTaskAssignment::query()
            ->with(['template.group.department'])
            ->where('user_id', $this->selectedUserId)
            ->where('is_active', true)
            ->whereHas('template', fn(Builder $query) => $query->where('is_active', true))
            ->where(function (Builder $query) use ($monthEnd): void {
                $query
                    ->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $monthEnd->toDateString());
            })
            ->where(function (Builder $query) use ($monthStart): void {
                $query
                    ->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $monthStart->toDateString());
            })
            ->get()
            ->sortBy(fn(KpiTaskAssignment $assignment) => sprintf(
                '%s|%s',
                (string) optional($assignment->template?->group)->name,
                (string) optional($assignment->template)->title
            ))
            ->values();

        $instances = KpiTaskInstance::query()
            ->with([
                'latestSubmission.images',
                'latestSubmission.submittedBy',
                'latestSubmission.approvalSteps.approver',
                'template.group',
            ])
            ->where('user_id', $this->selectedUserId)
            ->whereDate('period_start', '<=', $monthEnd->toDateString())
            ->whereDate('period_end', '>=', $monthStart->toDateString())
            ->get()
            ->groupBy('task_assignment_id');

        $pendingApprovalSubmissions = $instances
            ->flatMap(fn(Collection $assignmentInstances) => $assignmentInstances)
            ->map(fn(KpiTaskInstance $instance) => $instance->latestSubmission)
            ->filter(fn($submission) => $submission && in_array((string) $submission->status, ['waiting_first_approval', 'waiting_final_approval'], true))
            ->sortBy(fn($submission) => $submission->submitted_at?->timestamp ?? 0)
            ->values();

        $holidayMap = $availability->holidayMapForUser($selectedUser->id, $monthStart, $monthEnd);
        $exclusionMaps = $availability->exclusionMapsForUser($selectedUser->id, $monthStart, $monthEnd);

        //add sort by kpy group name with natural sort and case insensitive
        $rows = $assignments->map(function (KpiTaskAssignment $assignment) use ($instances, $days, $holidayMap, $exclusionMaps, $evaluationEnd, $ruleEvaluator, $monthlySuccessService): array {
            $assignmentInstances = $instances->get($assignment->id, collect());
            $cells = $days->map(fn(Carbon $day) => $this->buildCell($assignment, $assignmentInstances, $day, $holidayMap, $exclusionMaps));
            $summary = $this->buildSummary($assignment, $assignmentInstances, $cells, $evaluationEnd, $monthlySuccessService);
            $ruleEvaluation = $ruleEvaluator->evaluateTemplate($assignment->template?->rule, $this->summaryMetrics($summary));

            return [
                'assignment' => $assignment,
                'cells' => $cells,
                'summary' => $summary,
                'rule_evaluation' => $ruleEvaluation,
            ];
        });
        $groupSummaries = $this->buildGroupSummaries($rows, $ruleEvaluator);
        $groupCards = [
            'passed' => $groupSummaries->where('passes_rule', true)->count(),
            'failed' => $groupSummaries->where('passes_rule', false)->count(),
            'not_set' => $groupSummaries->where('passes_rule', null)->count(),
        ];

        return view('livewire.kpi.audit', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'days' => $days,
            'rows' => $rows,
            'groupSummaries' => $groupSummaries,
            'groupCards' => $groupCards,
            'legendItems' => $this->legendItems(),
            'employeeAsyncData' => $this->buildEmployeeAsyncData($users),
            'selectedSubmission' => $this->getSelectedSubmissionProperty(),
            'selectedInstance' => $this->getSelectedInstanceProperty(),
            'pendingApprovalSubmissions' => $pendingApprovalSubmissions,
        ]);
    }

    protected function monthStart(): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        } catch (\Throwable) {
            $this->month = now()->format('Y-m');

            return now()->startOfMonth();
        }
    }

    protected function accessibleUsersQuery(): Builder
    {
        $user = Auth::user();

        $query = User::query()
            ->with(['department'])
            ->where('suspended', false);

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (Gate::allows('kpiViewCompanyLeaderboard')) {
            return $query;
        }

        if (
            Gate::allows('kpiManageTemplates')
            || Gate::allows('kpiManageAssignments')
            || Gate::allows('kpiApproveExclusions')
            || Gate::allows('kpiApproveTasks')
        ) {
            if ($user->department_id) {
                return $query->where('department_id', $user->department_id);
            }

            return $query->whereKey($user->id);
        }

        return $query->whereKey($user->id);
    }

    protected function buildCell(
        KpiTaskAssignment $assignment,
        Collection $instances,
        Carbon $day,
        Collection $holidayMap,
        array $exclusionMaps
    ): array {
        $dateKey = $day->toDateString();

        if (!$this->assignmentIsActiveOnDate($assignment, $day)) {
            return [
                'date' => $dateKey,
                'markers' => collect(),
                'label' => '--',
                'classes' => 'bg-slate-100 text-slate-400 dark:bg-slate-950 dark:text-slate-600',
            ];
        }

        $holiday = $holidayMap->get($dateKey);
        $dayRequest = $exclusionMaps['day'][$dateKey] ?? null;
        $taskRequest = $exclusionMaps['task'][$assignment->id][$dateKey] ?? null;

        if ($holiday || $dayRequest || $taskRequest) {
            return [
                'date' => $dateKey,
                'markers' => collect(),
                'label' => $holiday?->name
                    ?? ($dayRequest ? 'Day exclusion' : 'Task exclusion'),
                'classes' => 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
            ];
        }

        $markers = $instances
            ->map(fn(KpiTaskInstance $instance) => $this->markerForInstanceOnDate($instance, $dateKey))
            ->filter()
            ->values();

        return [
            'date' => $dateKey,
            'markers' => $markers,
            'label' => $markers->isEmpty() ? $this->defaultCellLabel($assignment, $day) : null,
            'classes' => $this->defaultCellClasses($assignment, $day, $markers->isEmpty()),
        ];
    }

    protected function markerForInstanceOnDate(KpiTaskInstance $instance, string $dateKey): ?array
    {
        $status = (string) $instance->status;
        $latestSubmissionDate = $instance->latestSubmission?->submitted_at?->toDateString()
            ?? $instance->submitted_at?->toDateString();
        $anchorDate = $instance->due_at?->toDateString()
            ?? $instance->task_date?->toDateString()
            ?? $instance->period_end?->toDateString()
            ?? $latestSubmissionDate;

        if (
            $instance->due_at
            && Carbon::parse($instance->due_at)->lt(now())
            && in_array($status, ['pending', 'rejected'], true)
        ) {
            $overdueDate = Carbon::parse($instance->due_at)->toDateString();

            if ($overdueDate !== $dateKey) {
                return null;
            }

            return [
                'type' => 'overdue',
                'label' => 'Overdue',
                'classes' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                'instance_id' => $instance->id,
            ];
        }

        $markDate = match ($status) {
            'passed' => $latestSubmissionDate ?? $anchorDate,
            'failed_late' => $latestSubmissionDate ?? $anchorDate,
            'failed_missed' => $anchorDate,
            'waiting_first_approval', 'waiting_final_approval' => $latestSubmissionDate,
            'rejected' => $latestSubmissionDate ?? $anchorDate,
            default => null,
        };

        if ($markDate !== $dateKey) {
            return null;
        }

        return match ($status) {
            'passed' => [
                'type' => 'approved',
                'label' => 'Approved',
                'classes' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                'submission_id' => $instance->latestSubmission?->id,
                'instance_id' => $instance->id,
            ],
            'failed_late', 'failed_missed' => [
                'type' => 'failed',
                'label' => 'Failed',
                'classes' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                'submission_id' => $instance->latestSubmission?->id,
                'instance_id' => $instance->id,
            ],
            'waiting_first_approval', 'waiting_final_approval' => [
                'type' => 'pending',
                'label' => 'Pending Approval',
                'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                'submission_id' => $instance->latestSubmission?->id,
                'instance_id' => $instance->id,
            ],
            'rejected' => [
                'type' => 'rejected',
                'label' => 'Rejected',
                'classes' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                'submission_id' => $instance->latestSubmission?->id,
                'instance_id' => $instance->id,
            ],
            'pending' => [
                'type' => 'pending',
                'label' => 'Pending',
                'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                'instance_id' => $instance->id,
            ],
            default => null,
        };
    }

    protected function legendItems(): array
    {
        return [
            ['type' => 'approved', 'label' => 'Approved task at submitted date', 'classes' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
            ['type' => 'failed', 'label' => 'Failed or missed task', 'classes' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'],
            ['type' => 'pending', 'label' => 'Open or waiting for approval', 'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
            ['type' => 'inactive', 'label' => 'Outside assignment date range', 'classes' => 'bg-slate-100 text-slate-500 dark:bg-slate-950 dark:text-slate-400'],
            ['type' => 'special', 'label' => 'Holiday / exclusion', 'classes' => 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400'],
        ];
    }

    protected function buildEmployeeAsyncData(Collection $users): array
    {
        return [
            'api' => route('users.index'),
            'method' => 'GET',
            'params' => [
                'user_ids' => $users->pluck('id')->values()->all(),
            ],
            'alwaysFetch' => false,
        ];
    }

    protected function defaultSelectedUserId(?Collection $allowedUserIds = null): ?int
    {
        $authId = Auth::id();

        if ($authId) {
            $allowedIds = $allowedUserIds
                ? $allowedUserIds->map(fn($id) => (string) $id)
                : $this->accessibleUsersQuery()->pluck('users.id')->map(fn($id) => (string) $id);

            if ($allowedIds->contains((string) $authId)) {
                return (int) $authId;
            }
        }

        return $this->accessibleUsersQuery()->value('users.id');
    }

    protected function buildSummary(
        KpiTaskAssignment $assignment,
        Collection $instances,
        Collection $cells,
        Carbon $evaluationEnd,
        KpiMonthlySuccessService $monthlySuccessService
    ): array
    {
        if ($assignment->template?->frequency === 'daily') {
            return $this->buildDailySummary($cells, $evaluationEnd);
        }

        $eligibleInstances = $instances
            ->filter(function (KpiTaskInstance $instance) use ($evaluationEnd): bool {
                $anchorDate = $instance->task_date
                    ?? $instance->period_start
                    ?? $instance->period_end
                    ?? $instance->due_at
                    ?? $instance->submitted_at;

                return $anchorDate ? $anchorDate->copy()->startOfDay()->lte($evaluationEnd) : false;
            })
            ->values();

        $summary = $monthlySuccessService->summarize($eligibleInstances);

        return [
            'passed' => $summary['passed_count'],
            'failed' => $summary['late_count'] + $summary['absent_count'],
            'excluded' => $summary['excluded_count'],
            'pending' => $summary['pending_count'],
            'must_do' => $summary['must_do_count'],
            'percentage' => $summary['score'],
        ];
    }

    protected function buildDailySummary(Collection $cells, Carbon $evaluationEnd): array
    {
        $passed = 0;
        $failed = 0;
        $excluded = 0;
        $pending = 0;
        $today = now()->startOfDay();

        foreach ($cells as $cell) {
            $date = Carbon::parse($cell['date'])->startOfDay();

            if ($cell['label'] === '--') {
                continue;
            }

            if (str_contains((string) $cell['classes'], 'bg-slate-200')) {
                $excluded++;
                continue;
            }

            if ($date->gt($today) && $cell['markers']->isEmpty()) {
                $passed++;
                continue;
            }

            if ($cell['markers']->isNotEmpty()) {
                foreach ($cell['markers'] as $marker) {
                    if ($marker['type'] === 'approved') {
                        $passed++;
                        continue;
                    }

                    if ($marker['type'] === 'failed') {
                        $failed++;
                        continue;
                    }

                    if (in_array($marker['type'], ['pending', 'rejected'], true) && $date->lte($evaluationEnd)) {
                        $pending++;
                    }
                }

                continue;
            }

            if ($cell['label'] === 'X') {
                $failed++;
                continue;
            }

            if ($cell['label'] === '.' && $date->lte($evaluationEnd)) {
                $pending++;
            }
        }

        $mustDo = $passed + $failed + $pending;

        return [
            'passed' => $passed,
            'failed' => $failed,
            'excluded' => $excluded,
            'pending' => $pending,
            'must_do' => $mustDo,
            'percentage' => $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0,
        ];
    }

    protected function buildGroupSummaries(Collection $rows, KpiRuleEvaluationService $ruleEvaluator): Collection
    {
        return $rows
            ->groupBy(fn(array $row) => (int) ($row['assignment']->template?->group?->id ?? 0))
            ->map(function (Collection $groupRows): array {
                $group = $groupRows->first()['assignment']->template?->group;
                $passed = $groupRows->sum(fn(array $row) => (int) ($row['summary']['passed'] ?? 0));
                $failed = $groupRows->sum(fn(array $row) => (int) ($row['summary']['failed'] ?? 0));
                $pending = $groupRows->sum(fn(array $row) => (int) ($row['summary']['pending'] ?? 0));
                $excluded = $groupRows->sum(fn(array $row) => (int) ($row['summary']['excluded'] ?? 0));
                $mustDo = $groupRows->sum(fn(array $row) => (int) ($row['summary']['must_do'] ?? 0));
                $templatePassCount = $groupRows->where('rule_evaluation.passes_rule', true)->count();
                $templateTotalCount = $groupRows->count();
                $allTemplatesPass = $templatePassCount === $templateTotalCount && $templateTotalCount > 0;

                return [
                    'group' => $group,
                    'group_name' => $group?->name ?? 'No KPI Group',
                    'passed' => $passed,
                    'failed' => $failed,
                    'pending' => $pending,
                    'excluded' => $excluded,
                    'must_do' => $mustDo,
                    'percentage' => $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0,
                    'template_total_count' => $templateTotalCount,
                    'template_pass_count' => $templatePassCount,
                    'all_templates_pass' => $allTemplatesPass,
                ];
            })
            ->map(function (array $summary) use ($ruleEvaluator): array {
                $groupEvaluation = $summary['group']
                    ? $ruleEvaluator->evaluateGroup($summary['group'], [
                        'pass_rate' => $summary['percentage'],
                        'failed_count' => $summary['failed'],
                        'total_spend_cost' => 0,
                    ])
                    : $ruleEvaluator->evaluateRule(null, []);

                return $summary + [
                    'group_rule_evaluation' => $groupEvaluation,
                    'passes_rule' => $groupEvaluation['passes_rule'] === null
                        ? null
                        : ($groupEvaluation['passes_rule'] && $summary['all_templates_pass']),
                ];
            })
            ->sortBy('group_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    protected function summaryMetrics(array $summary): array
    {
        return [
            'pass_rate' => (float) ($summary['percentage'] ?? 0),
            'failed_count' => (int) ($summary['failed'] ?? 0),
            'total_spend_cost' => 0,
        ];
    }

    protected function evaluationEnd(Carbon $monthStart, Carbon $monthEnd): Carbon
    {
        $today = now()->startOfDay();

        if ($today->lt($monthStart)) {
            return $monthStart->copy()->subDay();
        }

        return $today->lt($monthEnd) ? $today : $monthEnd->copy()->startOfDay();
    }

    protected function assignmentIsActiveOnDate(KpiTaskAssignment $assignment, Carbon $day): bool
    {
        if ($assignment->starts_on && $day->lt($assignment->starts_on)) {
            return false;
        }

        if ($assignment->ends_on && $day->gt($assignment->ends_on)) {
            return false;
        }

        return true;
    }

    protected function defaultCellLabel(KpiTaskAssignment $assignment, Carbon $day): ?string
    {
        if ($assignment->template?->frequency !== 'daily') {
            return null;
        }

        $today = now()->startOfDay();

        if ($day->lt($today)) {
            return 'X';
        }

        return '.';
    }

    protected function defaultCellClasses(KpiTaskAssignment $assignment, Carbon $day, bool $isEmpty): string
    {
        if (!$isEmpty) {
            return 'bg-white dark:bg-slate-900';
        }

        if ($assignment->template?->frequency !== 'daily') {
            return 'bg-white dark:bg-slate-900';
        }

        $today = now()->startOfDay();

        if ($day->lt($today)) {
            return 'bg-rose-50 text-rose-600 dark:bg-rose-950/20 dark:text-rose-300';
        }

        return 'bg-amber-50 text-amber-600 dark:bg-amber-950/20 dark:text-amber-300';
    }

    protected function findVisibleSubmission(int $submissionId, bool $fail = true): ?KpiTaskSubmission
    {
        $visibleUserIds = $this->accessibleUsersQuery()->pluck('users.id');

        $query = KpiTaskSubmission::query()
            ->with([
                'images',
                'submittedBy',
                'approvalSteps.approver',
                'instance.template.group',
                'instance.user',
            ])
            ->whereKey($submissionId)
            ->whereHas('instance', function (Builder $query) use ($visibleUserIds): void {
                $query->whereIn('user_id', $visibleUserIds);
            });

        return $fail ? $query->firstOrFail() : $query->first();
    }

    protected function findVisibleInstance(int $instanceId, bool $fail = true): ?KpiTaskInstance
    {
        $visibleUserIds = $this->accessibleUsersQuery()->pluck('users.id');

        $query = KpiTaskInstance::query()
            ->with([
                'latestSubmission.images',
                'latestSubmission.submittedBy',
                'latestSubmission.approvalSteps.approver',
                'template.group',
                'user',
            ])
            ->whereKey($instanceId)
            ->whereIn('user_id', $visibleUserIds);

        return $fail ? $query->firstOrFail() : $query->first();
    }
}
