<?php

namespace App\Http\Controllers\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskSubmission;
use App\Models\User;
use App\Services\Kpi\KpiAvailabilityService;
use App\Services\Kpi\KpiMonthlySuccessService;
use App\Services\Kpi\KpiRuleEvaluationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class KpiCertificateController extends Controller
{
    public function index(
        Request $request,
        KpiRuleEvaluationService $ruleEvaluator,
        KpiMonthlySuccessService $monthlySuccessService,
        KpiAvailabilityService $availability
    ): Response {
        $users = $this->accessibleUsersQuery()->orderBy('name')->get();
        Gate::authorize('kpiViewCertificateDepartment', [$this->resolveSelectedUser($users, (int) $request->query('user_id', Auth::id()))]);

        $month = $request->query('month', now()->format('Y-m'));
        $selectedUserId = (int) $request->query('user_id', Auth::id());
        $selectedSubmissionId = (int) $request->query('submission_id', 0) ?: null;

        $monthStart = $this->parseMonth($month);
        $monthEnd = $monthStart->copy()->endOfMonth();
        $evaluationEnd = $this->evaluationEnd($monthStart, $monthEnd);

        $selectedUser = $this->resolveSelectedUser($users, $selectedUserId);

        if (!$selectedUser) {
            return Inertia::render('Kpi/Certificate', [
                'month' => $month,
                'users' => [],
                'selectedUser' => null,
                'certificate' => null,
                'appendixRows' => [],
                'passedEvidenceRows' => [],
                'selectedSubmission' => null,
                'isSuperAdmin' => Gate::allows('isSuperAdmin'),
                'canManageTemplates' => Gate::allows('kpiManageTemplates'),
            ]);
        }

        $assignments = KpiTaskAssignment::query()
            ->with(['template.group.department', 'template.rule'])
            ->where('user_id', $selectedUser->id)
            ->where('is_active', true)
            ->whereHas('template', fn (Builder $q) => $q->where('is_active', true))
            ->where(fn (Builder $q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $monthEnd->toDateString()))
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $monthStart->toDateString()))
            ->get()
            ->sortBy(fn (KpiTaskAssignment $a) => sprintf('%s|%s', optional($a->template?->group)->name, optional($a->template)->title))
            ->values();

        $instances = KpiTaskInstance::query()
            ->with(['latestSubmission', 'template.group'])
            ->where('user_id', $selectedUser->id)
            ->whereDate('period_start', '<=', $monthEnd->toDateString())
            ->whereDate('period_end', '>=', $monthStart->toDateString())
            ->get()
            ->groupBy('task_assignment_id');

        $days = collect(range(1, $monthEnd->day))
            ->map(fn (int $day) => $monthStart->copy()->day($day));

        $holidayMap = $availability->holidayMapForUser($selectedUser->id, $monthStart, $monthEnd);
        $exclusionMaps = $availability->exclusionMapsForUser($selectedUser->id, $monthStart, $monthEnd);

        $groupedRows = $this->buildGroupedRows($assignments, $instances, $ruleEvaluator, $monthlySuccessService, $days, $holidayMap, $exclusionMaps, $evaluationEnd);
        $overall = $this->buildOverallMetrics($groupedRows);

        $selectedSubmission = $selectedSubmissionId
            ? $this->findVisibleSubmission($selectedSubmissionId, false)
            : null;

        return Inertia::render('Kpi/Certificate', [
            'month' => $month,
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'department' => $u->department ? ['name' => $u->department->name] : null])->values(),
            'selectedUser' => [
                'id' => $selectedUser->id,
                'name' => $selectedUser->name,
                'email' => $selectedUser->email,
                'department' => $selectedUser->department ? ['name' => $selectedUser->department->name] : null,
            ],
            'certificate' => [
                'month' => $monthStart->format('F Y'),
                'month_raw' => $month,
                'overall' => $overall,
                'groups' => $groupedRows->map(fn ($g) => $this->serializeGroup($g))->values(),
            ],
            'appendixRows' => $this->buildAppendixRows($selectedUser->id, $monthStart, $monthEnd),
            'passedEvidenceRows' => $this->buildPassedEvidenceRows($selectedUser->id, $monthStart, $monthEnd),
            'selectedSubmission' => $selectedSubmission ? $this->serializeSubmission($selectedSubmission) : null,
            'isSuperAdmin' => Gate::allows('isSuperAdmin'),
            'canManageTemplates' => Gate::allows('kpiManageTemplates'),
        ]);
    }

    protected function serializeGroup(array $g): array
    {
        return [
            'no' => $g['no'],
            'group_name' => $g['group_name'],
            'template_count' => $g['template_count'],
            'show_group_result' => $g['show_group_result'],
            'group_result' => $g['group_result'],
            'summary' => $g['summary'],
            'templates' => collect($g['templates'])->map(fn ($t) => [
                'title' => $t['title'],
                'result' => $t['result'],
                'summary' => $t['summary'],
            ])->values()->all(),
        ];
    }

    protected function serializeSubmission(KpiTaskSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'status' => $submission->status,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'remarks' => $submission->remarks,
            'submitted_by' => $submission->submittedBy ? ['id' => $submission->submittedBy->id, 'name' => $submission->submittedBy->name] : null,
            'instance' => $submission->instance ? [
                'id' => $submission->instance->id,
                'task_date' => $submission->instance->task_date?->toDateString(),
                'due_at' => $submission->instance->due_at?->toIso8601String(),
                'user' => $submission->instance->user ? ['name' => $submission->instance->user->name] : null,
                'template' => $submission->instance->template ? [
                    'title' => $submission->instance->template->title,
                    'group' => $submission->instance->template->group ? ['name' => $submission->instance->template->group->name] : null,
                ] : null,
            ] : null,
            'images' => $submission->images ? $submission->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => asset('storage/' . ltrim((string) $img->image_path, '/')),
                'title' => $img->title ?? 'Evidence image',
            ])->values()->all() : [],
            'approval_steps' => $submission->approvalSteps ? $submission->approvalSteps->sortBy('step_order')->map(fn ($step) => [
                'id' => $step->id,
                'step' => $step->step,
                'step_order' => $step->step_order,
                'status' => $step->status,
                'remark' => $step->remark,
                'acted_at' => $step->acted_at?->toIso8601String(),
                'approver' => $step->approver ? ['name' => $step->approver->name] : null,
            ])->values()->all() : [],
        ];
    }

    protected function parseMonth(string $month): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    protected function resolveSelectedUser(Collection $users, int $selectedUserId): ?User
    {
        $found = $users->firstWhere('id', $selectedUserId);
        return $found ?? $users->first();
    }

    protected function accessibleUsersQuery(): Builder
    {
        $user = Auth::user();
        $query = User::query()->with(['department'])->where('suspended', false);

        if (!$user) return $query->whereRaw('1 = 0');
        if (Gate::allows('kpiViewCompanyLeaderboard')) return $query;
        if (Gate::allows('kpiManageTemplates') || Gate::allows('kpiManageAssignments') || Gate::allows('kpiApproveExclusions') || Gate::allows('kpiApproveTasks')) {
            if ($user->department_id) return $query->where('department_id', $user->department_id);
            return $query->whereKey($user->id);
        }

        return $query->whereKey($user->id);
    }

    protected function evaluationEnd(Carbon $monthStart, Carbon $monthEnd): Carbon
    {
        $today = now()->startOfDay();
        if ($today->lt($monthStart)) return $monthStart->copy()->subDay();
        return $today->lt($monthEnd) ? $today : $monthEnd->copy()->startOfDay();
    }

    protected function buildGroupedRows(
        Collection $assignments,
        Collection $instancesByAssignment,
        KpiRuleEvaluationService $ruleEvaluator,
        KpiMonthlySuccessService $monthlySuccessService,
        Collection $days,
        Collection $holidayMap,
        array $exclusionMaps,
        Carbon $evaluationEnd
    ): Collection {
        $templateRows = $assignments->map(function (KpiTaskAssignment $assignment) use ($instancesByAssignment, $ruleEvaluator, $monthlySuccessService, $days, $holidayMap, $exclusionMaps, $evaluationEnd): array {
            $instances = $instancesByAssignment->get($assignment->id, collect());
            $cells = $days->map(fn (Carbon $day) => $this->buildCell($assignment, $instances, $day, $holidayMap, $exclusionMaps));
            $summary = $this->buildSummary($assignment, $instances, $cells, $evaluationEnd, $monthlySuccessService);
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
                    ? $ruleEvaluator->evaluateGroup($group, ['pass_rate' => $score, 'failed_count' => $lateCount + $absentCount, 'total_spend_cost' => 0])
                    : $ruleEvaluator->evaluateRule(null, []);

                $allTemplatePass = $rows->every(fn (array $row) => $row['rule_evaluation']['passes_rule'] === true);
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
                    'summary' => ['passed_count' => $passed, 'must_do_count' => $mustDo, 'late_count' => $lateCount, 'absent_count' => $absentCount, 'score' => $score],
                    'templates' => $rows->values()->all(),
                ];
            });
    }

    protected function buildSummary(KpiTaskAssignment $assignment, Collection $instances, Collection $cells, Carbon $evaluationEnd, KpiMonthlySuccessService $monthlySuccessService): array
    {
        if ($assignment->template?->frequency === 'daily') {
            return $this->buildDailySummary($cells, $evaluationEnd);
        }

        $eligibleInstances = $instances->filter(function (KpiTaskInstance $instance) use ($evaluationEnd): bool {
            $anchorDate = $instance->task_date ?? $instance->period_start ?? $instance->period_end ?? $instance->due_at ?? $instance->submitted_at;
            return $anchorDate ? $anchorDate->copy()->startOfDay()->lte($evaluationEnd) : false;
        })->values();

        $summary = $monthlySuccessService->summarize($eligibleInstances);
        return [
            'passed_count' => $summary['passed_count'],
            'late_count' => $summary['late_count'],
            'absent_count' => $summary['absent_count'],
            'excluded_count' => $summary['excluded_count'],
            'pending_count' => $summary['pending_count'],
            'must_do_count' => $summary['must_do_count'],
            'score' => $summary['score'],
        ];
    }

    protected function buildDailySummary(Collection $cells, Carbon $evaluationEnd): array
    {
        $passed = 0; $failed = 0; $excluded = 0; $pending = 0;
        $today = now()->startOfDay();

        foreach ($cells as $cell) {
            $date = Carbon::parse($cell['date'])->startOfDay();
            if ($cell['label'] === '--') continue;
            if (str_contains((string) $cell['classes'], 'bg-slate-200')) { $excluded++; continue; }
            if ($date->gt($today) && empty($cell['markers'])) { $passed++; continue; }

            if (!empty($cell['markers'])) {
                foreach ($cell['markers'] as $marker) {
                    if ($marker['type'] === 'approved') { $passed++; }
                    elseif ($marker['type'] === 'failed') { $failed++; }
                    elseif (in_array($marker['type'], ['pending', 'rejected'], true) && $date->lte($evaluationEnd)) { $pending++; }
                }
                continue;
            }

            if ($cell['label'] === 'X') { $failed++; }
            elseif ($cell['label'] === '.' && $date->lte($evaluationEnd)) { $pending++; }
        }

        $mustDo = $passed + $failed + $pending;
        return ['passed_count' => $passed, 'late_count' => $failed, 'absent_count' => 0, 'excluded_count' => $excluded, 'pending_count' => $pending, 'must_do_count' => $mustDo, 'score' => $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0];
    }

    protected function buildCell(KpiTaskAssignment $assignment, Collection $instances, Carbon $day, Collection $holidayMap, array $exclusionMaps): array
    {
        $dateKey = $day->toDateString();

        if (!$this->assignmentIsActiveOnDate($assignment, $day)) {
            return ['date' => $dateKey, 'markers' => [], 'label' => '--', 'classes' => 'bg-slate-100'];
        }

        $holiday = $holidayMap->get($dateKey);
        $dayRequest = $exclusionMaps['day'][$dateKey] ?? null;
        $taskRequest = $exclusionMaps['task'][$assignment->id][$dateKey] ?? null;

        $markers = $instances->map(fn (KpiTaskInstance $instance) => $this->markerForInstanceOnDate($instance, $dateKey))->filter()->values();

        if ($holiday || $dayRequest || $taskRequest) {
            return ['date' => $dateKey, 'markers' => $markers->all(), 'label' => $markers->isEmpty() ? ($holiday?->name ?? ($dayRequest ? 'Day exclusion' : 'Task exclusion')) : null, 'classes' => 'bg-slate-200'];
        }

        return ['date' => $dateKey, 'markers' => $markers->all(), 'label' => $markers->isEmpty() ? $this->defaultCellLabel($assignment, $day) : null, 'classes' => $this->defaultCellClasses($assignment, $day, $markers->isEmpty())];
    }

    protected function markerForInstanceOnDate(KpiTaskInstance $instance, string $dateKey): ?array
    {
        $status = (string) $instance->status;
        $latestSubmissionDate = $instance->latestSubmission?->submitted_at?->toDateString() ?? $instance->submitted_at?->toDateString();
        $anchorDate = $instance->due_at?->toDateString() ?? $instance->task_date?->toDateString() ?? $instance->period_end?->toDateString() ?? $latestSubmissionDate;

        if ($instance->due_at && Carbon::parse($instance->due_at)->lt(now()) && in_array($status, ['pending', 'rejected'], true)) {
            return Carbon::parse($instance->due_at)->toDateString() === $dateKey ? ['type' => 'overdue'] : null;
        }

        $markDate = match ($status) {
            'passed' => $latestSubmissionDate ?? $anchorDate,
            'failed_late' => $latestSubmissionDate ?? $anchorDate,
            'failed_missed' => $anchorDate,
            'waiting_first_approval', 'waiting_final_approval' => $latestSubmissionDate,
            'rejected' => $latestSubmissionDate ?? $anchorDate,
            default => null,
        };

        if ($markDate !== $dateKey) return null;

        return match ($status) {
            'passed' => ['type' => 'approved'],
            'failed_late', 'failed_missed' => ['type' => 'failed'],
            'waiting_first_approval', 'waiting_final_approval' => ['type' => 'pending'],
            'rejected' => ['type' => 'rejected'],
            'pending' => ['type' => 'pending'],
            default => null,
        };
    }

    protected function assignmentIsActiveOnDate(KpiTaskAssignment $assignment, Carbon $day): bool
    {
        if ($assignment->starts_on && $day->lt($assignment->starts_on)) return false;
        if ($assignment->ends_on && $day->gt($assignment->ends_on)) return false;
        return true;
    }

    protected function defaultCellLabel(KpiTaskAssignment $assignment, Carbon $day): ?string
    {
        if ($assignment->template?->frequency !== 'daily') return null;
        return $day->lt(now()->startOfDay()) ? 'X' : '.';
    }

    protected function defaultCellClasses(KpiTaskAssignment $assignment, Carbon $day, bool $isEmpty): string
    {
        if (!$isEmpty || $assignment->template?->frequency !== 'daily') return 'bg-white';
        return $day->lt(now()->startOfDay()) ? 'bg-rose-50' : 'bg-amber-50';
    }

    protected function buildOverallMetrics(Collection $groups): array
    {
        $mustDo = $groups->sum('summary.must_do_count');
        $passed = $groups->sum('summary.passed_count');
        $percentage = $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0;
        return ['must_do_count' => $mustDo, 'passed_count' => $passed, 'percentage' => $percentage, 'kpi_score' => $percentage];
    }

    protected function buildAppendixRows(int $userId, Carbon $monthStart, Carbon $monthEnd): array
    {
        $submissions = KpiTaskSubmission::query()
            ->with(['instance.template.group', 'approvalSteps.approver'])
            ->whereHas('instance', fn (Builder $q) => $q->where('user_id', $userId)->whereDate('period_start', '<=', $monthEnd->toDateString())->whereDate('period_end', '>=', $monthStart->toDateString()))
            ->get();

        return $submissions
            ->flatMap(function (KpiTaskSubmission $submission) {
                return $submission->approvalSteps->filter(fn ($step) => filled($step->remark))->map(fn ($step) => [
                    'submission_id' => $submission->id,
                    'template_title' => (string) ($submission->instance?->template?->title ?? '-'),
                    'remark' => (string) $step->remark,
                    'remark_by' => (string) ($step->approver?->name ?? 'Approver'),
                    'submission_status' => (string) $submission->status,
                    'is_rejected' => $submission->status === 'rejected' || $step->status === 'rejected',
                ]);
            })
            ->groupBy('template_title')
            ->map(fn (Collection $rows, string $templateTitle) => ['template_title' => $templateTitle, 'rowspan' => $rows->count(), 'rows' => $rows->values()->all()])
            ->sortBy('template_title')
            ->values()
            ->all();
    }

    protected function buildPassedEvidenceRows(int $userId, Carbon $monthStart, Carbon $monthEnd): array
    {
        $submissions = KpiTaskSubmission::query()
            ->with(['images', 'instance.template.group', 'approvalSteps.approver'])
            ->whereHas('instance', fn (Builder $q) => $q->where('user_id', $userId)->where('status', 'passed')->whereDate('period_start', '<=', $monthEnd->toDateString())->whereDate('period_end', '>=', $monthStart->toDateString()))
            ->orderByDesc('submitted_at')
            ->get();

        return $submissions
            ->map(function (KpiTaskSubmission $submission) {
                $template = $submission->instance?->template;
                $remarks = $submission->approvalSteps->filter(fn ($step) => filled($step->remark))->map(fn ($step) => trim((string) $step->remark) . ' (' . ($step->approver?->name ?? 'Approver') . ')')->values();
                return [
                    'group_name' => (string) ($template?->group?->name ?? 'No KPI Group'),
                    'template_title' => (string) ($template?->title ?? '-'),
                    'frequency' => (string) ($template?->frequency ?? '-'),
                    'requested_date' => ($submission->submitted_at ?? $submission->created_at)?->toDateString(),
                    'approve_remark' => $remarks->isNotEmpty() ? $remarks->implode(' | ') : '-',
                    'images' => $submission->images->map(fn ($img) => ['url' => asset('storage/' . ltrim((string) $img->image_path, '/')), 'title' => $img->title ?? 'Evidence image'])->values()->all(),
                ];
            })
            ->groupBy('group_name')
            ->map(fn (Collection $groupRows, string $groupName) => [
                'group_name' => $groupName,
                'templates' => $groupRows->groupBy('template_title')->map(fn (Collection $rows, string $templateTitle) => ['template_title' => $templateTitle, 'rows' => $rows->values()->all()])->values()->all(),
            ])
            ->sortBy('group_name')
            ->values()
            ->all();
    }

    protected function findVisibleSubmission(int $submissionId, bool $fail = true): ?KpiTaskSubmission
    {
        $visibleUserIds = $this->accessibleUsersQuery()->pluck('users.id');
        $query = KpiTaskSubmission::query()
            ->with(['images', 'submittedBy', 'approvalSteps.approver', 'instance.template.group', 'instance.user'])
            ->whereKey($submissionId)
            ->whereHas('instance', fn (Builder $q) => $q->whereIn('user_id', $visibleUserIds));

        return $fail ? $query->firstOrFail() : $query->first();
    }
}
