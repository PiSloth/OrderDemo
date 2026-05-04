<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskSubmission;
use App\Models\User;
use App\Services\Kpi\KpiRuleEvaluationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Certificate extends Component
{
    public string $month = '';
    public int $selectedUserId = 0;
    public ?int $selectedSubmissionId = null;

    public function mount(): void
    {
        Gate::authorize('kpiViewCertificateDepartment', [$this->resolveSelectedUser($this->accessibleUsersQuery()->get())]);

        $this->month = request()->query('month', now()->format('Y-m'));
        $this->selectedUserId = (int) request()->query('user_id', Auth::id());
    }

    public function render(KpiRuleEvaluationService $ruleEvaluator)
    {
        $monthStart = $this->monthStart();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $users = $this->accessibleUsersQuery()->orderBy('name')->get();
        $selectedUser = $this->resolveSelectedUser($users);

        if (!$selectedUser) {
            return view('livewire.kpi.certificate', [
                'users' => $users,
                'selectedUser' => null,
                'certificate' => null,
            ]);
        }

        $assignments = KpiTaskAssignment::query()
            ->with(['template.group.department', 'template.rule'])
            ->where('user_id', $selectedUser->id)
            ->where('is_active', true)
            ->whereHas('template', fn(Builder $query) => $query->where('is_active', true))
            ->where(function (Builder $query) use ($monthEnd): void {
                $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', $monthEnd->toDateString());
            })
            ->where(function (Builder $query) use ($monthStart): void {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $monthStart->toDateString());
            })
            ->get()
            ->sortBy(fn(KpiTaskAssignment $assignment) => sprintf(
                '%s|%s',
                (string) optional($assignment->template?->group)->name,
                (string) optional($assignment->template)->title
            ))
            ->values();

        $instances = KpiTaskInstance::query()
            ->with(['latestSubmission', 'template.group'])
            ->where('user_id', $selectedUser->id)
            ->whereDate('period_start', '<=', $monthEnd->toDateString())
            ->whereDate('period_end', '>=', $monthStart->toDateString())
            ->get()
            ->groupBy('task_assignment_id');

        $groupedRows = $this->buildGroupedRows($assignments, $instances, $ruleEvaluator);
        $overall = $this->buildOverallMetrics($groupedRows);

        return view('livewire.kpi.certificate', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'certificate' => [
                'month' => $monthStart,
                'overall' => $overall,
                'groups' => $groupedRows,
            ],
            'appendixRows' => $this->buildAppendixRows($selectedUser->id, $monthStart, $monthEnd),
            'passedEvidenceRows' => $this->buildPassedEvidenceRows($selectedUser->id, $monthStart, $monthEnd),
            'selectedSubmission' => $this->getSelectedSubmissionProperty(),
        ]);
    }

    public function openSubmissionDetail(int $submissionId): void
    {
        $submission = $this->findVisibleSubmission($submissionId);
        $this->selectedSubmissionId = $submission->id;
    }

    public function closeSubmissionDetail(): void
    {
        $this->selectedSubmissionId = null;
    }

    public function getSelectedSubmissionProperty(): ?KpiTaskSubmission
    {
        if (!$this->selectedSubmissionId) {
            return null;
        }

        return $this->findVisibleSubmission($this->selectedSubmissionId, false);
    }

    protected function buildGroupedRows(Collection $assignments, Collection $instancesByAssignment, KpiRuleEvaluationService $ruleEvaluator): Collection
    {
        $templateRows = $assignments->map(function (KpiTaskAssignment $assignment) use ($instancesByAssignment, $ruleEvaluator): array {
            $instances = $instancesByAssignment->get($assignment->id, collect());
            $summary = $this->buildSummary($instances);
            $ruleEvaluation = $ruleEvaluator->evaluateTemplate($assignment->template?->rule, [
                'pass_rate' => $summary['score'],
                'failed_count' => $summary['late_count'] + $summary['absent_count'],
                'total_spend_cost' => 0,
            ]);

            return [
                'assignment' => $assignment,
                'group_id' => (int) ($assignment->template?->group?->id ?? 0),
                'title' => (string) ($assignment->template?->title ?? '-'),
                'summary' => $summary,
                'rule_evaluation' => $ruleEvaluation,
                'result' => $ruleEvaluation['passes_rule'] ? 'Pass' : 'Fail',
            ];
        });

        return $templateRows
            ->groupBy('group_id')
            ->values()
            ->map(function (Collection $rows, int $index) use ($ruleEvaluator): array {
                $first = $rows->first();
                $group = $first['assignment']->template?->group;
                $templateCount = $rows->count();
                $passed = $rows->sum('summary.passed_count');
                $mustDo = $rows->sum('summary.must_do_count');
                $lateCount = $rows->sum('summary.late_count');
                $absentCount = $rows->sum('summary.absent_count');
                $score = $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0;

                $groupEvaluation = $group
                    ? $ruleEvaluator->evaluateGroup($group, [
                        'pass_rate' => $score,
                        'failed_count' => $lateCount + $absentCount,
                        'total_spend_cost' => 0,
                    ])
                    : $ruleEvaluator->evaluateRule(null, []);

                $allTemplatePass = $rows->every(fn(array $row) => $row['rule_evaluation']['passes_rule'] === true);
                $groupPass = $templateCount > 1
                    ? (($groupEvaluation['passes_rule'] === true) && $allTemplatePass)
                    : ($rows->first()['rule_evaluation']['passes_rule'] === true);

                return [
                    'no' => $index + 1,
                    'group_name' => $group?->name ?? 'No KPI Group',
                    'template_count' => $templateCount,
                    'show_group_result' => $templateCount > 1,
                    'group_rule' => $groupEvaluation,
                    'group_result' => $groupPass ? 'Pass' : 'Fail',
                    'summary' => [
                        'passed_count' => $passed,
                        'must_do_count' => $mustDo,
                        'late_count' => $lateCount,
                        'absent_count' => $absentCount,
                        'score' => $score,
                    ],
                    'templates' => $rows->values()->all(),
                ];
            });
    }

    protected function buildSummary(Collection $instances): array
    {
        $eligible = $instances->where('status', '!=', 'excluded')->values();
        $mustDo = $eligible->count();
        $passed = $eligible->where('status', 'passed')->count();
        $late = $eligible->where('status', 'failed_late')->count();
        $absent = $eligible->where('status', 'failed_missed')->count();

        return [
            'must_do_count' => $mustDo,
            'passed_count' => $passed,
            'late_count' => $late,
            'absent_count' => $absent,
            'score' => $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0,
        ];
    }

    protected function buildOverallMetrics(Collection $groups): array
    {
        $mustDo = $groups->sum('summary.must_do_count');
        $passed = $groups->sum('summary.passed_count');
        $percentage = $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0;

        return [
            'must_do_count' => $mustDo,
            'passed_count' => $passed,
            'percentage' => $percentage,
            'kpi_score' => $percentage,
        ];
    }

    protected function buildAppendixRows(int $userId, Carbon $monthStart, Carbon $monthEnd): Collection
    {
        $submissions = KpiTaskSubmission::query()
            ->with([
                'instance.template.group',
                'approvalSteps.approver',
            ])
            ->whereHas('instance', function (Builder $query) use ($userId, $monthStart, $monthEnd): void {
                $query
                    ->where('user_id', $userId)
                    ->whereDate('period_start', '<=', $monthEnd->toDateString())
                    ->whereDate('period_end', '>=', $monthStart->toDateString());
            })
            ->get();

        $remarkRows = $submissions
            ->map(function (KpiTaskSubmission $submission): ?array {
                $remarks = $submission->approvalSteps
                    ->filter(fn($step) => filled($step->remark))
                    ->map(fn($step) => [
                        'submission_id' => $submission->id,
                        'template_title' => (string) ($submission->instance?->template?->title ?? '-'),
                        'remark' => (string) $step->remark,
                        'remark_by' => (string) ($step->approver?->name ?? 'Approver'),
                        'submission_status' => (string) $submission->status,
                        'is_rejected' => $submission->status === 'rejected' || $step->status === 'rejected',
                    ])
                    ->values();

                if ($remarks->isEmpty()) {
                    return null;
                }

                return [
                    'template_title' => (string) ($submission->instance?->template?->title ?? '-'),
                    'remarks' => $remarks,
                ];
            })
            ->filter()
            ->values();

        return $remarkRows
            ->flatMap(fn(array $group) => $group['remarks'])
            ->groupBy('template_title')
            ->map(fn(Collection $rows, string $templateTitle) => [
                'template_title' => $templateTitle,
                'rowspan' => $rows->count(),
                'rows' => $rows->values(),
            ])
            ->sortBy('template_title')
            ->values();
    }

    protected function buildPassedEvidenceRows(int $userId, Carbon $monthStart, Carbon $monthEnd): Collection
    {
        $submissions = KpiTaskSubmission::query()
            ->with([
                'images',
                'instance.template.group',
                'approvalSteps.approver',
            ])
            ->whereHas('instance', function (Builder $query) use ($userId, $monthStart, $monthEnd): void {
                $query
                    ->where('user_id', $userId)
                    ->where('status', 'passed')
                    ->whereDate('period_start', '<=', $monthEnd->toDateString())
                    ->whereDate('period_end', '>=', $monthStart->toDateString());
            })
            ->orderByDesc('submitted_at')
            ->get();

        return $submissions
            ->map(function (KpiTaskSubmission $submission): array {
                $template = $submission->instance?->template;
                $groupName = (string) ($template?->group?->name ?? 'No KPI Group');
                $remarks = $submission->approvalSteps
                    ->filter(fn($step) => filled($step->remark))
                    ->map(fn($step) => trim((string) $step->remark) . ' (' . ($step->approver?->name ?? 'Approver') . ')')
                    ->values();

                return [
                    'group_name' => $groupName,
                    'template_title' => (string) ($template?->title ?? '-'),
                    'frequency' => (string) ($template?->frequency ?? '-'),
                    'requested_date' => $submission->submitted_at ?? $submission->created_at,
                    'approve_remark' => $remarks->isNotEmpty() ? $remarks->implode(' | ') : '-',
                    'images' => $submission->images->map(function ($image): array {
                        return [
                            'url' => asset('storage/' . ltrim((string) $image->image_path, '/')),
                            'title' => (string) ($image->title ?? 'Evidence image'),
                        ];
                    })->values(),
                ];
            })
            ->groupBy('group_name')
            ->map(function (Collection $groupRows, string $groupName): array {
                return [
                    'group_name' => $groupName,
                    'templates' => $groupRows
                        ->groupBy('template_title')
                        ->map(fn(Collection $templateRows, string $templateTitle) => [
                            'template_title' => $templateTitle,
                            'rows' => $templateRows->values(),
                        ])
                        ->values(),
                ];
            })
            ->sortBy('group_name')
            ->values();
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

    protected function resolveSelectedUser(Collection $users): ?User
    {
        $found = $users->firstWhere('id', $this->selectedUserId);

        if ($found) {
            return $found;
        }

        $first = $users->first();
        if ($first) {
            $this->selectedUserId = $first->id;
        }

        return $first;
    }

    protected function accessibleUsersQuery(): Builder
    {
        $user = Auth::user();
        $query = User::query()->with(['department'])->where('suspended', false);

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
}
