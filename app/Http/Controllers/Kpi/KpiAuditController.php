<?php

namespace App\Http\Controllers\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Kpi\KpiExclusionRequest;
use App\Models\Kpi\KpiHoliday;
use App\Models\Kpi\KpiTaskApprovalStep;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KpiAuditController extends Controller
{
    public function index(
        Request $request,
        KpiAvailabilityService $availability,
        KpiRuleEvaluationService $ruleEvaluator,
        KpiMonthlySuccessService $monthlySuccessService
    ): Response {
        $month = $request->query('month', now()->format('Y-m'));
        $monthStart = $this->parseMonth($month);
        $monthEnd = $monthStart->copy()->endOfMonth();
        $evaluationEnd = $this->evaluationEnd($monthStart, $monthEnd);

        $accessibleUsers = $this->accessibleUsersQuery()->orderBy('name')->get(['id', 'name', 'email', 'department_id']);
        
        $selectedUserId = (int) $request->query('userId', Auth::id());
        if ($accessibleUsers->isNotEmpty() && !$accessibleUsers->contains('id', $selectedUserId)) {
            $selectedUserId = $accessibleUsers->first()->id;
        }

        $selectedUser = $accessibleUsers->firstWhere('id', $selectedUserId);

        if (!$selectedUser) {
            return Inertia::render('Kpi/Audit', [
                'month' => $month,
                'users' => [],
                'selectedUser' => null,
                'days' => [],
                'rows' => [],
                'groupSummaries' => [],
                'groupCards' => ['passed' => 0, 'failed' => 0, 'not_set' => 0],
                'isSuperAdmin' => Gate::allows('isSuperAdmin'),
            ]);
        }

        $days = collect(range(1, $monthEnd->day))
            ->map(fn (int $day) => $monthStart->copy()->day($day)->format('Y-m-d'));

        $assignments = KpiTaskAssignment::query()
            ->with(['template.group.department'])
            ->where('user_id', $selectedUserId)
            ->where('is_active', true)
            ->whereHas('template', fn (Builder $query) => $query->where('is_active', true))
            ->where(function (Builder $query) use ($monthEnd): void {
                $query->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $monthEnd->toDateString());
            })
            ->where(function (Builder $query) use ($monthStart): void {
                $query->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $monthStart->toDateString());
            })
            ->get()
            ->sortBy(fn (KpiTaskAssignment $assignment) => sprintf(
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
                'group',
            ])
            ->where('user_id', $selectedUserId)
            ->whereDate('period_start', '<=', $monthEnd->toDateString())
            ->whereDate('period_end', '>=', $monthStart->toDateString())
            ->get()
            ->groupBy('task_assignment_id');

        $assignments = $assignments
            ->sortBy(function (KpiTaskAssignment $assignment) use ($instances) {
                $assignmentInstances = $instances->get($assignment->id, collect());
                $instanceGroup = $assignmentInstances->first(fn ($inst) => !empty($inst->kpi_group_id) && !empty($inst->group))?->group;
                $groupName = (string) optional($instanceGroup ?? $assignment->template?->group)->name;
                return sprintf('%s|%s', $groupName, (string) optional($assignment->template)->title);
            })
            ->values();

        $holidayMap = $availability->holidayMapForUser($selectedUserId, $monthStart, $monthEnd);
        $exclusionMaps = $availability->exclusionMapsForUser($selectedUserId, $monthStart, $monthEnd);

        $daysCarbon = collect(range(1, $monthEnd->day))->map(fn (int $day) => $monthStart->copy()->day($day));

        $rows = $assignments->map(function (KpiTaskAssignment $assignment) use ($instances, $daysCarbon, $holidayMap, $exclusionMaps, $evaluationEnd, $ruleEvaluator, $monthlySuccessService): array {
            $assignmentInstances = $instances->get($assignment->id, collect());
            $instanceGroup = $assignmentInstances->first(fn ($inst) => !empty($inst->kpi_group_id) && !empty($inst->group))?->group;
            $effectiveGroup = $instanceGroup ?? $assignment->template?->group;

            $cells = $daysCarbon->map(fn (Carbon $day) => $this->buildCell($assignment, $assignmentInstances, $day, $holidayMap, $exclusionMaps));
            $summary = $this->buildSummary($assignment, $assignmentInstances, $cells, $evaluationEnd, $monthlySuccessService);
            $ruleEvaluation = $ruleEvaluator->evaluateTemplate($assignment->template?->rule, [
                'pass_rate' => (float) ($summary['percentage'] ?? 0),
                'failed_count' => (int) ($summary['failed'] ?? 0),
                'total_spend_cost' => 0,
            ]);

            return [
                'assignment' => [
                    'id' => $assignment->id,
                    'task_template_id' => $assignment->task_template_id,
                    'starts_on' => $assignment->starts_on?->toDateString(),
                    'ends_on' => $assignment->ends_on?->toDateString(),
                    'template' => $assignment->template ? [
                        'id' => $assignment->template->id,
                        'kpi_group_id' => $effectiveGroup?->id ?? $assignment->template->kpi_group_id,
                        'title' => $assignment->template->title,
                        'description' => $assignment->template->description,
                        'guideline' => $assignment->template->guideline,
                        'frequency' => $assignment->template->frequency,
                        'monthly_required_count' => (int) $assignment->template->monthly_required_count,
                        'cutoff_time' => $assignment->template->cutoff_time,
                        'requires_images' => (bool) $assignment->template->requires_images,
                        'requires_table' => (bool) $assignment->template->requires_table,
                        'min_images' => (int) $assignment->template->min_images,
                        'max_images' => $assignment->template->max_images,
                        'image_remark_required' => (bool) $assignment->template->image_remark_required,
                        'is_active' => (bool) $assignment->template->is_active,
                        'rule' => $assignment->template->rule ? [
                            'rule_type' => $assignment->template->rule->rule_type,
                            'target_percentage' => $assignment->template->rule->target_percentage,
                            'max_fail_count' => $assignment->template->rule->max_fail_count,
                            'max_cost_amount' => $assignment->template->rule->max_cost_amount,
                        ] : null,
                        'group' => $effectiveGroup ? [
                            'id' => $effectiveGroup->id,
                            'name' => $effectiveGroup->name,
                        ] : null,
                    ] : null,
                ],
                'cells' => $cells->all(),
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

        // Task assignments for exclusion request dropdown (daily/weekly only)
        $taskAssignmentsForRequest = KpiTaskAssignment::query()
            ->with(['template.group'])
            ->where('user_id', $selectedUserId)
            ->where('is_active', true)
            ->whereHas('template', fn (Builder $q) => $q->whereIn('frequency', ['daily', 'weekly']))
            ->where(fn (Builder $q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $monthEnd->toDateString()))
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $monthStart->toDateString()))
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->template?->title ?? '-',
                'group' => $a->template?->group?->name,
                'frequency' => $a->template?->frequency,
            ]);

        // Pending exclusion requests (for inbox badge + inbox modal)
        $pendingExclusionsQuery = KpiExclusionRequest::query()
            ->with(['user.department', 'assignment.template.group'])
            ->where('status', 'pending');

        if (Gate::allows('kpiViewCompanyLeaderboard') || Gate::allows('kpiManageTemplates')) {
            // all pending
        } elseif ($selectedUser && $selectedUser->department_id) {
            $deptId = Auth::user()?->department_id;
            $pendingExclusionsQuery->whereHas('user', fn (Builder $q) => $q->where('department_id', $deptId));
        } else {
            $pendingExclusionsQuery->whereRaw('1 = 0');
        }

        $pendingExclusions = Gate::allows('kpiApproveExclusions')
            ? $pendingExclusionsQuery
                ->orderBy('requested_date')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'user_id' => $r->user_id,
                    'user_name' => $r->user?->name ?? '-',
                    'user_dept' => $r->user?->department?->name ?? '-',
                    'request_type' => $r->request_type,
                    'requested_date' => $r->requested_date?->toDateString(),
                    'task_title' => $r->assignment?->template?->title,
                    'reason' => $r->reason,
                    'created_at' => $r->created_at?->toDateString(),
                ])
            : collect();

        // Approved exclusion requests (for Inbox Modal management)
        $approvedExclusionsQuery = KpiExclusionRequest::query()
            ->with(['user.department', 'assignment.template.group'])
            ->where('status', 'approved');

        if (Gate::allows('kpiViewCompanyLeaderboard') || Gate::allows('kpiManageTemplates')) {
            // all
        } elseif ($selectedUser && $selectedUser->department_id) {
            $deptId = Auth::user()?->department_id;
            $approvedExclusionsQuery->whereHas('user', fn (Builder $q) => $q->where('department_id', $deptId));
        }

        $approvedExclusions = (Gate::allows('kpiApproveExclusions') || Gate::allows('kpiManageHolidays'))
            ? $approvedExclusionsQuery
                ->orderByDesc('requested_date')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'user_id' => $r->user_id,
                    'user_name' => $r->user?->name ?? '-',
                    'user_dept' => $r->user?->department?->name ?? '-',
                    'request_type' => $r->request_type,
                    'requested_date' => $r->requested_date?->toDateString(),
                    'task_title' => $r->assignment?->template?->title,
                    'reason' => $r->reason,
                    'reviewer_remark' => $r->reviewer_remark,
                    'status' => 'approved',
                    'item_type' => 'exclusion',
                ])
            : collect();

        // Approved Holidays (for Inbox Modal management)
        $approvedHolidaysQuery = KpiHoliday::query()
            ->with(['user.department']);

        if (Gate::allows('kpiViewCompanyLeaderboard') || Gate::allows('kpiManageTemplates')) {
            // all
        } elseif ($selectedUser && $selectedUser->department_id) {
            $deptId = Auth::user()?->department_id;
            $approvedHolidaysQuery->whereHas('user', fn (Builder $q) => $q->where('department_id', $deptId));
        }

        $approvedHolidays = (Gate::allows('kpiApproveExclusions') || Gate::allows('kpiManageHolidays'))
            ? $approvedHolidaysQuery
                ->orderByDesc('holiday_date')
                ->get()
                ->map(fn ($h) => [
                    'id' => $h->id,
                    'user_id' => $h->user_id,
                    'user_name' => $h->user?->name ?? 'All Employees / System',
                    'user_dept' => $h->user?->department?->name ?? 'Company-wide',
                    'request_type' => 'holiday',
                    'requested_date' => $h->holiday_date?->toDateString(),
                    'task_title' => $h->name,
                    'reason' => $h->remark ?? $h->name,
                    'status' => 'approved',
                    'item_type' => 'holiday',
                ])
            : collect();

        return Inertia::render('Kpi/Audit', [
            'month' => $month,
            'users' => $accessibleUsers,
            'selectedUser' => $selectedUser,
            'days' => $days->all(),
            'rows' => $rows->all(),
            'groupSummaries' => $groupSummaries->values()->all(),
            'groupCards' => $groupCards,
            'isSuperAdmin' => Gate::allows('isSuperAdmin'),
            'canManageTemplates' => Gate::allows('kpiManageTemplates') || Gate::allows('isSuperAdmin'),
            'canApproveExclusions' => Gate::allows('kpiApproveExclusions'),
            'canManageHolidays' => Gate::allows('kpiManageHolidays'),
            'canApproveTasks' => Gate::allows('kpiApproveTasks'),
            'authUserId' => Auth::id(),
            'taskAssignments' => $taskAssignmentsForRequest->values(),
            'pendingExclusions' => $pendingExclusions->values(),
            'pendingExclusionsCount' => $pendingExclusions->count(),
            'approvedExclusions' => $approvedExclusions->values(),
            'approvedHolidays' => $approvedHolidays->values(),
            'kpiGroups' => \App\Models\Kpi\KpiGroup::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function destroyExclusionRequest(int $id): RedirectResponse
    {
        if (!Gate::allows('kpiApproveExclusions') && !Gate::allows('kpiManageHolidays') && !Gate::allows('isSuperAdmin')) {
            abort(403);
        }

        $exclusionRequest = KpiExclusionRequest::find($id);
        if (!$exclusionRequest) {
            return redirect()->back()->with('message', 'Exclusion request already removed or not found.');
        }

        $exclusionRequest->delete();

        return redirect()->back()->with('success', 'Exclusion request deleted.');
    }

    public function destroyHoliday(int $id): RedirectResponse
    {
        if (!Gate::allows('kpiManageHolidays') && !Gate::allows('kpiApproveExclusions') && !Gate::allows('isSuperAdmin')) {
            abort(403);
        }

        $holiday = KpiHoliday::find($id);
        if (!$holiday) {
            return redirect()->back()->with('message', 'Holiday already removed or not found.');
        }

        $holiday->delete();

        return redirect()->back()->with('success', 'Holiday deleted.');
    }

    public function storeExclusionRequest(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $validated = $request->validate([
            'request_type'           => ['required', Rule::in(['day', 'task'])],
            'requested_date'         => ['required', 'date'],
            'task_assignment_id'     => ['nullable'],
            'reason'                 => ['required', 'string', 'max:1000'],
            'target_user_id'         => ['nullable', 'exists:users,id'],
        ]);

        // Managers can submit on behalf of others
        $targetUserId = Gate::allows('kpiApproveExclusions') && !empty($validated['target_user_id'])
            ? (int) $validated['target_user_id']
            : $user->id;

        $assignmentId = null;
        $assignmentFrequency = null;
        $requestedDate = Carbon::parse($validated['requested_date']);
        $weekStart = $requestedDate->copy()->startOfWeek();
        $weekEnd   = $requestedDate->copy()->endOfWeek();

        if ($validated['request_type'] === 'task') {
            if (empty($validated['task_assignment_id'])) {
                throw ValidationException::withMessages(['task_assignment_id' => 'Choose a task for task-level exclusion.']);
            }
            $assignment = KpiTaskAssignment::query()
                ->with('template')
                ->where('id', (int) $validated['task_assignment_id'])
                ->where('user_id', $targetUserId)
                ->firstOrFail();

            if (!in_array($assignment->template?->frequency, ['daily', 'weekly'], true)) {
                throw ValidationException::withMessages(['task_assignment_id' => 'Task-level exclusion is only available for daily and weekly tasks.']);
            }
            $assignmentId = $assignment->id;
            $assignmentFrequency = $assignment->template?->frequency;
        }

        $duplicate = KpiExclusionRequest::query()
            ->where('user_id', $targetUserId)
            ->where('request_type', $validated['request_type'])
            ->when(
                $validated['request_type'] === 'task' && $assignmentFrequency === 'weekly',
                fn (Builder $q) => $q->whereBetween('requested_date', [$weekStart->toDateString(), $weekEnd->toDateString()]),
                fn (Builder $q) => $q->whereDate('requested_date', $validated['requested_date'])
            )
            ->when($assignmentId, fn (Builder $q) => $q->where('task_assignment_id', $assignmentId), fn (Builder $q) => $q->whereNull('task_assignment_id'))
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['requested_date' => 'An active exclusion request already exists for this date and scope.']);
        }

        KpiExclusionRequest::query()->create([
            'user_id'            => $targetUserId,
            'task_assignment_id' => $assignmentId,
            'request_type'       => $validated['request_type'],
            'requested_date'     => $validated['requested_date'],
            'reason'             => trim($validated['reason']),
            'status'             => 'pending',
        ]);

        return redirect()->back()->with('success', 'Exclusion request submitted.');
    }

    public function approveExclusionRequest(Request $request, int $id, KpiAvailabilityService $availability): RedirectResponse
    {
        Gate::authorize('kpiApproveExclusions');

        $exclusionRequest = KpiExclusionRequest::query()->where('status', 'pending')->findOrFail($id);
        $remark = trim((string) ($request->input('reviewer_remark', '')));

        $exclusionRequest->update([
            'status'               => 'approved',
            'reviewed_by_user_id'  => Auth::id(),
            'reviewed_at'          => now(),
            'reviewer_remark'      => $remark !== '' ? $remark : null,
        ]);

        $availability->applyApprovedExclusionRequest($exclusionRequest->fresh());

        return redirect()->back()->with('success', 'Request approved.');
    }

    public function rejectExclusionRequest(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('kpiApproveExclusions');

        $exclusionRequest = KpiExclusionRequest::query()->where('status', 'pending')->findOrFail($id);
        $remark = trim((string) ($request->input('reviewer_remark', '')));

        $exclusionRequest->update([
            'status'              => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at'         => now(),
            'reviewer_remark'     => $remark !== '' ? $remark : null,
        ]);

        return redirect()->back()->with('success', 'Request rejected.');
    }

    public function storeHoliday(Request $request, KpiAvailabilityService $availability): RedirectResponse
    {
        Gate::authorize('kpiManageHolidays');

        $validated = $request->validate([
            'holiday_date' => ['required', 'date'],
            'name'         => ['required', 'string', 'max:255'],
            'user_id'      => ['required', 'exists:users,id'],
            'remark'       => ['nullable', 'string', 'max:500'],
        ]);

        $exists = KpiHoliday::query()
            ->whereDate('holiday_date', $validated['holiday_date'])
            ->where('user_id', $validated['user_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['holiday_date' => 'A holiday already exists for this user on this date.']);
        }

        $holiday = KpiHoliday::query()->create([
            'holiday_date' => $validated['holiday_date'],
            'name'         => trim($validated['name']),
            'user_id'      => (int) $validated['user_id'],
            'remark'       => filled($validated['remark'] ?? null) ? trim($validated['remark']) : null,
            'is_active'    => true,
        ]);

        $availability->applyHoliday($holiday);

        return redirect()->back()->with('success', 'Holiday added.');
    }

    public function updateInstanceStatus(Request $request, KpiTaskInstance $instance)
    {
        Gate::authorize('isSuperAdmin');

        $validated = $request->validate([
            'status' => ['required', 'in:pending,rejected,waiting_first_approval,waiting_final_approval,passed,failed_late,failed_missed,excluded'],
            'failure_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($instance, $validated): void {
            $taskInstance = KpiTaskInstance::query()->whereKey($instance->id)->lockForUpdate()->firstOrFail();
            $now = now();
            $status = $validated['status'];
            $reason = trim((string) $validated['failure_reason']);

            $taskInstance->update([
                'status' => $status,
                'final_outcome' => in_array($status, ['passed', 'failed_late', 'failed_missed', 'excluded'], true) ? $status : null,
                'finalized_at' => in_array($status, ['passed', 'failed_late', 'failed_missed', 'excluded'], true) ? $now : null,
                'failure_reason' => $reason,
            ]);
        });

        return redirect()->back()->with('message', 'Task instance status updated by Super Admin.');
    }

    public function approveStep(Request $request, int $stepId): RedirectResponse
    {
        Gate::authorize('kpiApproveTasks');

        $remark = trim((string) $request->input('remark', ''));

        DB::transaction(function () use ($stepId, $remark): void {
            $step = KpiTaskApprovalStep::query()
                ->where('id', $stepId)
                ->where('approver_user_id', Auth::id())
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();

            $submission = $step->submission()->with(['instance', 'approvalSteps'])->firstOrFail();
            $allSteps = $submission->approvalSteps->sortBy('step_order')->values();

            // Ensure prior steps approved
            $blockedByPrior = $allSteps
                ->where('step_order', '<', $step->step_order ?? $step->step)
                ->contains(fn ($s) => $s->status !== 'approved');

            if ($blockedByPrior) {
                throw ValidationException::withMessages(['step' => 'A previous approval step is still pending.']);
            }

            $now = now();
            $step->update(['status' => 'approved', 'acted_at' => $now, 'remark' => $remark ?: null]);

            $nextPending = $allSteps->first(
                fn ($s) => ($s->step_order ?? $s->step) > ($step->step_order ?? $step->step) && $s->status === 'pending'
            );

            if ($nextPending) {
                $submission->update(['status' => 'waiting_final_approval', 'first_approved_at' => $submission->first_approved_at ?: $now]);
                $submission->instance->update(['status' => 'waiting_final_approval', 'failure_reason' => null]);
                return;
            }

            $finalStatus = $submission->is_late ? 'failed_late' : 'passed';
            $submission->update(['status' => 'approved', 'first_approved_at' => $submission->first_approved_at ?: $now, 'final_approved_at' => $now, 'rejection_reason' => null]);
            $submission->instance->update(['status' => $finalStatus, 'final_outcome' => $finalStatus, 'finalized_at' => $now, 'failure_reason' => $submission->is_late ? 'Approved after cutoff time.' : null]);
            \App\Models\TodoList::syncKpiApproval($submission->instance);
        });

        return redirect()->back()->with('message', 'Submission approved.');
    }

    public function rejectStep(Request $request, int $stepId): RedirectResponse
    {
        Gate::authorize('kpiApproveTasks');

        $remark = trim((string) $request->input('remark', ''));
        if ($remark === '') {
            throw ValidationException::withMessages(['remark' => 'Remark is required when rejecting.']);
        }

        DB::transaction(function () use ($stepId, $remark): void {
            $step = KpiTaskApprovalStep::query()
                ->where('id', $stepId)
                ->where('approver_user_id', Auth::id())
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();

            $submission = $step->submission()->with(['instance', 'approvalSteps'])->firstOrFail();
            $now = now();

            $step->update(['status' => 'rejected', 'acted_at' => $now, 'remark' => $remark]);

            $submission->approvalSteps()
                ->where('step_order', '>', $step->step_order ?? $step->step)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'acted_at' => $now, 'remark' => 'Stopped because an earlier approver rejected the submission.']);

            $submission->update(['status' => 'rejected', 'rejection_reason' => $remark]);
            $submission->instance->update(['status' => 'rejected', 'failure_reason' => $remark, 'final_outcome' => null, 'finalized_at' => null]);
        });

        return redirect()->back()->with('message', 'Submission rejected.');
    }

    protected function parseMonth(string $month): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
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
                'markers' => [],
                'label' => '--',
                'classes' => 'bg-slate-100 text-slate-400 dark:bg-slate-950 dark:text-slate-600',
            ];
        }

        $holiday = $holidayMap->get($dateKey);
        $dayRequest = $exclusionMaps['day'][$dateKey] ?? null;
        $taskRequest = $exclusionMaps['task'][$assignment->id][$dateKey] ?? null;

        $markers = $instances
            ->map(fn (KpiTaskInstance $instance) => $this->markerForInstanceOnDate($instance, $dateKey))
            ->filter()
            ->values();

        if ($holiday || $dayRequest || $taskRequest) {
            return [
                'date' => $dateKey,
                'markers' => $markers->all(),
                'label' => $markers->isEmpty() ? ($holiday?->name ?? ($dayRequest ? 'Day exclusion' : 'Task exclusion')) : null,
                'classes' => 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
            ];
        }

        return [
            'date' => $dateKey,
            'markers' => $markers->all(),
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
                'instance' => $this->serializeInstance($instance),
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
                'instance' => $this->serializeInstance($instance),
            ],
            'failed_late', 'failed_missed' => [
                'type' => 'failed',
                'label' => 'Failed',
                'classes' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                'instance' => $this->serializeInstance($instance),
            ],
            'waiting_first_approval', 'waiting_final_approval' => [
                'type' => 'pending',
                'label' => 'Pending Approval',
                'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                'instance' => $this->serializeInstance($instance),
            ],
            'rejected' => [
                'type' => 'rejected',
                'label' => 'Rejected',
                'classes' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                'instance' => $this->serializeInstance($instance),
            ],
            'pending' => [
                'type' => 'pending',
                'label' => 'Pending',
                'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                'instance' => $this->serializeInstance($instance),
            ],
            default => null,
        };
    }

    protected function serializeInstance(KpiTaskInstance $instance): array
    {
        $latest = $instance->latestSubmission;
        $template = $instance->template;

        return [
            'id' => $instance->id,
            'status' => $instance->status,
            'failure_reason' => $instance->failure_reason,
            'task_date' => $instance->task_date?->toDateString(),
            'due_at' => $instance->due_at?->toIso8601String(),
            'submitted_at' => $instance->submitted_at?->toIso8601String(),
            'finalized_at' => $instance->finalized_at?->toIso8601String(),
            'template' => $template ? [
                'id' => $template->id,
                'title' => $template->title,
                'description' => $template->description,
                'guideline' => $template->guideline,
                'frequency' => $template->frequency,
                'group' => $template->group ? [
                    'id' => $template->group->id,
                    'name' => $template->group->name,
                ] : null,
            ] : null,
            'latest_submission' => $latest ? [
                'id' => $latest->id,
                'status' => $latest->status,
                'submitted_at' => $latest->submitted_at?->toIso8601String(),
                'remarks' => $latest->remarks,
                'submitted_by' => $latest->submittedBy ? [
                    'id' => $latest->submittedBy->id,
                    'name' => $latest->submittedBy->name,
                ] : null,
                'images' => $latest->images ? $latest->images->map(fn ($img) => [
                    'id' => $img->id,
                    'image_path' => $img->image_path,
                    'file_url' => asset('storage/' . $img->image_path),
                    'url' => asset('storage/' . $img->image_path),
                    'title' => $img->title,
                    'label' => $img->title,
                    'remark' => $img->remark,
                ])->all() : [],
                'approval_steps' => $latest->approvalSteps ? $latest->approvalSteps->map(fn ($step) => [
                    'id' => $step->id,
                    'step' => $step->step,
                    'step_order' => $step->step_order ?? $step->step,
                    'status' => $step->status,
                    'remarks' => $step->remarks ?? $step->remark,
                    'approver_user_id' => $step->approver_user_id,
                    'approver' => $step->approver ? [
                        'id' => $step->approver->id,
                        'name' => $step->approver->name,
                    ] : null,
                ])->all() : [],
            ] : null,
        ];
    }

    protected function buildSummary(
        KpiTaskAssignment $assignment,
        Collection $instances,
        Collection $cells,
        Carbon $evaluationEnd,
        KpiMonthlySuccessService $monthlySuccessService
    ): array {
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

            if ($date->gt($today) && empty($cell['markers'])) {
                $passed++;
                continue;
            }

            if (!empty($cell['markers'])) {
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
            ->groupBy(fn (array $row) => (int) ($row['assignment']['template']['group']['id'] ?? 0))
            ->map(function (Collection $groupRows): array {
                $group = $groupRows->first()['assignment']['template']['group'] ?? null;
                $passed = $groupRows->sum(fn (array $row) => (int) ($row['summary']['passed'] ?? 0));
                $failed = $groupRows->sum(fn (array $row) => (int) ($row['summary']['failed'] ?? 0));
                $pending = $groupRows->sum(fn (array $row) => (int) ($row['summary']['pending'] ?? 0));
                $excluded = $groupRows->sum(fn (array $row) => (int) ($row['summary']['excluded'] ?? 0));
                $mustDo = $groupRows->sum(fn (array $row) => (int) ($row['summary']['must_do'] ?? 0));
                $templatePassCount = $groupRows->where('rule_evaluation.passes_rule', true)->count();
                $templateTotalCount = $groupRows->count();
                $allTemplatesPass = $templatePassCount === $templateTotalCount && $templateTotalCount > 0;

                return [
                    'group' => $group,
                    'group_name' => $group['name'] ?? 'No KPI Group',
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
                $groupModel = isset($summary['group']['id']) ? \App\Models\Kpi\KpiGroup::find($summary['group']['id']) : null;
                $groupEvaluation = $groupModel
                    ? $ruleEvaluator->evaluateGroup($groupModel, [
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

    protected function evaluationEnd(Carbon $monthStart, Carbon $monthEnd): Carbon
    {
        $today = now()->startOfDay();

        if ($today->lt($monthStart)) {
            return $monthStart->copy()->subDay();
        }

        return $today->lt($monthEnd) ? $today : $monthEnd->copy()->startOfDay();
    }
}
