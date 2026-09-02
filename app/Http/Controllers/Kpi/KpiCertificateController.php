<?php

namespace App\Http\Controllers\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskSubmission;
use App\Models\User;
use App\Services\Kpi\KpiMonthlyAuditService;
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
        KpiMonthlyAuditService $auditService
    ): Response {
        $users = $this->accessibleUsersQuery()->orderBy('name')->get();
        Gate::authorize('kpiViewCertificateDepartment', [$this->resolveSelectedUser($users, (int) $request->query('user_id', Auth::id()))]);

        $month = $request->query('month', now()->format('Y-m'));
        $selectedUserId = (int) $request->query('user_id', Auth::id());
        $selectedSubmissionId = (int) $request->query('submission_id', 0) ?: null;

        $monthStart = $this->parseMonth($month);
        $monthEnd = $monthStart->copy()->endOfMonth();

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

        $auditData = $auditService->buildAuditMatrix($selectedUser->id, $monthStart, $monthEnd);
        $certificateGroups = $auditService->buildCertificateGroups($auditData['rows']);
        $overall = $auditData['overall'];

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
                'groups' => $certificateGroups->map(fn ($g) => $this->serializeGroup($g))->values(),
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
                'file_url' => asset('storage/' . ltrim((string) $img->image_path, '/')),
                'title' => $img->title ?? 'Evidence image',
                'label' => $img->title,
                'remark' => $img->remark,
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

    protected function buildAppendixRows(int $userId, Carbon $monthStart, Carbon $monthEnd): array
    {
        $submissions = KpiTaskSubmission::query()
            ->with(['instance.template.group', 'instance.assignment.template.group', 'approvalSteps.approver'])
            ->whereHas('instance', fn (Builder $q) => $q->where(function (Builder $userQ) use ($userId) {
                $userQ->where('user_id', $userId)
                    ->orWhereHas('assignment', fn (Builder $aQ) => $aQ->where('user_id', $userId));
            })->where(function (Builder $subQ) use ($monthStart, $monthEnd) {
                $subQ->where(function (Builder $sub) use ($monthStart, $monthEnd) {
                    $sub->whereDate('period_start', '<=', $monthEnd->toDateString())
                        ->whereDate('period_end', '>=', $monthStart->toDateString());
                })
                ->orWhereBetween('task_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->orWhereBetween('due_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()])
                ->orWhereBetween('submitted_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()]);
            }))
            ->get();

        return $submissions
            ->flatMap(function (KpiTaskSubmission $submission) {
                $template = $submission->instance?->template ?? $submission->instance?->assignment?->template;
                return $submission->approvalSteps->filter(fn ($step) => filled($step->remark))->map(fn ($step) => [
                    'submission_id' => $submission->id,
                    'template_title' => (string) ($template?->title ?? '-'),
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
            ->with(['images', 'instance.template.group', 'instance.assignment.template.group', 'approvalSteps.approver'])
            ->whereHas('instance', fn (Builder $q) => $q->where(function (Builder $userQ) use ($userId) {
                $userQ->where('user_id', $userId)
                    ->orWhereHas('assignment', fn (Builder $aQ) => $aQ->where('user_id', $userId));
            })->where('status', 'passed')->where(function (Builder $subQ) use ($monthStart, $monthEnd) {
                $subQ->where(function (Builder $sub) use ($monthStart, $monthEnd) {
                    $sub->whereDate('period_start', '<=', $monthEnd->toDateString())
                        ->whereDate('period_end', '>=', $monthStart->toDateString());
                })
                ->orWhereBetween('task_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->orWhereBetween('due_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()])
                ->orWhereBetween('submitted_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()]);
            }))
            ->orderByDesc('submitted_at')
            ->get();

        return $submissions
            ->map(function (KpiTaskSubmission $submission) {
                $template = $submission->instance?->template ?? $submission->instance?->assignment?->template;
                $group = $submission->instance?->group ?? $template?->group ?? $submission->instance?->assignment?->template?->group;
                $remarks = $submission->approvalSteps->filter(fn ($step) => filled($step->remark))->map(fn ($step) => trim((string) $step->remark) . ' (' . ($step->approver?->name ?? 'Approver') . ')')->values();
                return [
                    'group_name' => (string) ($group?->name ?? 'No KPI Group'),
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
