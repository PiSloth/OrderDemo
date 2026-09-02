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
use App\Services\Kpi\KpiMonthlyAuditService;
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
        KpiMonthlyAuditService $auditService
    ): Response {
        $month = $request->query('month', now()->format('Y-m'));
        $monthStart = $this->parseMonth($month);
        $monthEnd = $monthStart->copy()->endOfMonth();

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

        $auditData = $auditService->buildAuditMatrix($selectedUserId, $monthStart, $monthEnd);
        $days = $auditData['days'];
        $rows = $auditData['rows'];
        $groupSummaries = $auditData['group_summaries'];
        $groupCards = $auditData['group_cards'];

        // Task assignments for exclusion request dropdown (daily/weekly only)
        $taskAssignmentsForRequest = KpiTaskAssignment::query()
            ->with(['template.group'])
            ->where('user_id', $selectedUserId)
            ->where('is_active', true)
            ->whereHas('template', fn(Builder $q) => $q->whereIn('frequency', ['daily', 'weekly']))
            ->where(fn(Builder $q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $monthEnd->toDateString()))
            ->where(fn(Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $monthStart->toDateString()))
            ->get()
            ->map(fn($a) => [
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
            $pendingExclusionsQuery->whereHas('user', fn(Builder $q) => $q->where('department_id', $deptId));
        } else {
            $pendingExclusionsQuery->whereRaw('1 = 0');
        }

        $pendingExclusions = Gate::allows('kpiApproveExclusions')
            ? $pendingExclusionsQuery
            ->orderBy('requested_date')
            ->get()
            ->map(fn($r) => [
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
            $approvedExclusionsQuery->whereHas('user', fn(Builder $q) => $q->where('department_id', $deptId));
        }

        $approvedExclusions = (Gate::allows('kpiApproveExclusions') || Gate::allows('kpiManageHolidays'))
            ? $approvedExclusionsQuery
            ->orderByDesc('requested_date')
            ->get()
            ->map(fn($r) => [
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
            $approvedHolidaysQuery->whereHas('user', fn(Builder $q) => $q->where('department_id', $deptId));
        }

        $approvedHolidays = (Gate::allows('kpiApproveExclusions') || Gate::allows('kpiManageHolidays'))
            ? $approvedHolidaysQuery
            ->orderByDesc('holiday_date')
            ->get()
            ->map(fn($h) => [
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
            'days' => is_array($days) ? $days : $days->all(),
            'rows' => is_array($rows) ? $rows : $rows->all(),
            'groupSummaries' => is_array($groupSummaries) ? $groupSummaries : $groupSummaries->values()->all(),
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
                fn(Builder $q) => $q->whereBetween('requested_date', [$weekStart->toDateString(), $weekEnd->toDateString()]),
                fn(Builder $q) => $q->whereDate('requested_date', $validated['requested_date'])
            )
            ->when($assignmentId, fn(Builder $q) => $q->where('task_assignment_id', $assignmentId), fn(Builder $q) => $q->whereNull('task_assignment_id'))
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

            KpiTaskInstance::syncStatusForTodoList($taskInstance, [
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
                ->contains(fn($s) => $s->status !== 'approved');

            if ($blockedByPrior) {
                throw ValidationException::withMessages(['step' => 'A previous approval step is still pending.']);
            }

            $now = now();
            $step->update(['status' => 'approved', 'acted_at' => $now, 'remark' => $remark ?: null]);

            $nextPending = $allSteps->first(
                fn($s) => ($s->step_order ?? $s->step) > ($step->step_order ?? $step->step) && $s->status === 'pending'
            );

            if ($nextPending) {
                $submission->update(['status' => 'waiting_final_approval', 'first_approved_at' => $submission->first_approved_at ?: $now]);
                $submission->instance->update(['status' => 'waiting_final_approval', 'failure_reason' => null]);
                return;
            }

            $finalStatus = $submission->is_late ? 'failed_late' : 'passed';
            $submission->update(['status' => 'approved', 'first_approved_at' => $submission->first_approved_at ?: $now, 'final_approved_at' => $now, 'rejection_reason' => null]);
            KpiTaskInstance::syncStatusForTodoList($submission->instance, [
                'status' => $finalStatus,
                'final_outcome' => $finalStatus,
                'finalized_at' => $now,
                'failure_reason' => $submission->is_late ? 'Approved after cutoff time.' : null,
            ]);
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
}
