<?php

namespace App\Http\Controllers\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskCalendarControl;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskSubmission;
use App\Models\Kpi\KpiTaskSubmissionImage;
use App\Models\Kpi\KpiTaskTemplate;
use App\Models\User;
use App\Services\Kpi\KpiSubmissionImageResizer;
use App\Services\Kpi\KpiTaskInstanceGenerator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KpiAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $canManageInstances = Gate::allows('isSuperAdmin');

        $selectedUserId = (int) $request->query('selectedUserId', 0);
        $isActive = $request->query('isActive', '1'); // '1', '0', or 'all'

        $instanceUserId = (int) $request->query('instanceUserId', 0);
        $instanceStatusFilter = (string) $request->query('instanceStatusFilter', 'all');
        $instanceDateFilter = (string) $request->query('instanceDateFilter', '');
        $instanceSearch = trim((string) $request->query('instanceSearch', ''));

        $departments = Department::orderBy('name')->get(['id', 'name']);

        $templates = KpiTaskTemplate::query()
            ->with(['group.department', 'rule'])
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->get();

        $users = User::query()
            ->with(['position', 'department', 'branch'])
            ->where('suspended', false)
            ->orderBy('name')
            ->get();

        $assignmentsQuery = KpiTaskAssignment::query()
            ->with([
                'template.group.department',
                'user.position',
                'user.department',
                'user.branch',
                'firstApprover.position',
                'finalApprover.position',
                'calendarControl',
            ])
            ->withCount('instances')
            ->orderBy('user_id')
            ->orderBy(
                KpiTaskTemplate::select('frequency')
                    ->whereColumn('kpi_task_templates.id', 'kpi_task_assignments.task_template_id')
            )
            ->orderByDesc('is_active');

        if ($isActive === '1' || $isActive === true || $isActive === 'true') {
            $assignmentsQuery->where('is_active', true);
        } elseif ($isActive === '0' || $isActive === false || $isActive === 'false') {
            $assignmentsQuery->where('is_active', false);
        }

        if ($selectedUserId > 0) {
            $assignmentsQuery->where('user_id', $selectedUserId);
        }

        $assignments = $assignmentsQuery->get();

        $instances = collect();
        if ($canManageInstances) {
            $instancesQuery = KpiTaskInstance::query()
                ->with([
                    'template.group',
                    'user.position',
                    'assignment',
                    'submissions' => fn ($q) => $q->with('images')->latest('sequence')->latest('id'),
                ])
                ->orderByDesc('period_start')
                ->orderByDesc('id');

            if ($instanceUserId > 0) {
                $instancesQuery->where('user_id', $instanceUserId);
            }

            if ($instanceStatusFilter !== 'all' && $instanceStatusFilter !== '') {
                $instancesQuery->where('status', $instanceStatusFilter);
            }

            if ($instanceDateFilter !== '') {
                $instancesQuery->whereDate('task_date', $instanceDateFilter);
            }

            if ($instanceSearch !== '') {
                $instancesQuery->where(function ($nestedQuery) use ($instanceSearch): void {
                    $like = '%' . $instanceSearch . '%';
                    $nestedQuery->where('status', 'like', $like)
                        ->orWhere('period_type', 'like', $like)
                        ->orWhereHas('template', function ($templateQuery) use ($like): void {
                            $templateQuery->where('title', 'like', $like)
                                ->orWhereHas('group', function ($groupQuery) use ($like): void {
                                    $groupQuery->where('name', 'like', $like);
                                });
                        })
                        ->orWhereHas('user', function ($userQuery) use ($like): void {
                            $userQuery->where('name', 'like', $like);
                        });
                });
            }

            $instances = $instancesQuery->limit(300)->get()->map(function ($instance) {
                $latestSubmission = $instance->submissions->first();
                return [
                    'id' => $instance->id,
                    'status' => $instance->status,
                    'period_type' => $instance->period_type,
                    'task_date' => $instance->task_date?->toDateString(),
                    'period_start' => $instance->period_start?->toDateString(),
                    'period_end' => $instance->period_end?->toDateString(),
                    'due_at' => $instance->due_at ? Carbon::parse($instance->due_at)->format('Y-m-d\TH:i') : null,
                    'is_on_time' => $instance->is_on_time,
                    'required_image_count' => $instance->required_image_count,
                    'user' => $instance->user ? [
                        'id' => $instance->user->id,
                        'name' => $instance->user->name,
                        'position' => $instance->user->position ? ['name' => $instance->user->position->name] : null,
                    ] : null,
                    'template' => $instance->template ? [
                        'id' => $instance->template->id,
                        'title' => $instance->template->title,
                        'frequency' => $instance->template->frequency,
                        'requires_images' => (bool) $instance->template->requires_images,
                        'min_images' => (int) ($instance->template->min_images ?? 0),
                        'max_images' => $instance->template->max_images !== null ? (int) $instance->template->max_images : null,
                        'group' => $instance->template->group ? [
                            'id' => $instance->template->group->id,
                            'name' => $instance->template->group->name,
                        ] : null,
                    ] : null,
                    'latest_submission' => $latestSubmission ? [
                        'id' => $latestSubmission->id,
                        'is_late' => (bool) $latestSubmission->is_late,
                        'status' => $latestSubmission->status,
                        'images' => $latestSubmission->images->map(fn ($img) => [
                            'id' => $img->id,
                            'title' => $img->title ?? '',
                            'remark' => $img->remark ?? '',
                            'url' => asset('storage/' . ltrim((string) $img->image_path, '/')),
                        ])->values()->all(),
                    ] : null,
                ];
            });
        }

        return Inertia::render('Kpi/Assignments', [
            'assignments' => $assignments,
            'templates' => $templates,
            'departments' => $departments,
            'users' => $users,
            'instances' => $instances,
            'filters' => [
                'selectedUserId' => $selectedUserId,
                'isActive' => $isActive,
                'instanceUserId' => $instanceUserId,
                'instanceStatusFilter' => $instanceStatusFilter,
                'instanceDateFilter' => $instanceDateFilter,
                'instanceSearch' => $instanceSearch,
            ],
            'canManageInstances' => $canManageInstances,
            'instanceStatusOptions' => [
                'all' => 'All statuses',
                'pending' => 'Pending',
                'rejected' => 'Rejected',
                'waiting_first_approval' => 'Waiting first approval',
                'waiting_final_approval' => 'Waiting final approval',
                'passed' => 'Passed',
                'failed_late' => 'Failed late',
                'failed_missed' => 'Failed missed',
                'excluded' => 'Excluded',
            ],
        ]);
    }

    public function store(Request $request, KpiTaskInstanceGenerator $generator): RedirectResponse
    {
        Gate::authorize('kpiManageAssignments');

        $validated = $this->validateAssignment($request);

        if ($validated['is_active']) {
            $this->ensureNoDuplicateActiveAssignment($validated['task_template_id'], $validated['user_id']);
        }

        $assignment = KpiTaskAssignment::create([
            'task_template_id' => $validated['task_template_id'],
            'user_id' => $validated['user_id'],
            'first_approver_user_id' => $validated['first_approver_user_id'],
            'final_approver_user_id' => $validated['final_approver_user_id'],
            'assignment_source' => 'manual',
            'starts_on' => $validated['starts_on'],
            'ends_on' => $validated['ends_on'],
            'is_active' => $validated['is_active'],
            'calendar_push_enabled' => $validated['calendar_push_enabled'],
        ]);

        $this->saveCalendarControl($assignment, $validated);

        try {
            $generator->generateForAssignment($assignment->fresh(['template.group']));
        } catch (\Throwable $exception) {
            report($exception);
            $templateName = (string) ($assignment->template?->title ?? 'Unknown template');

            throw ValidationException::withMessages([
                'assignmentGenerator' => "Failed to create task instance for template: {$templateName}.",
            ]);
        }

        return redirect()->back()->with('message', 'Employee task assignment created.');
    }

    public function update(Request $request, KpiTaskAssignment $assignment, KpiTaskInstanceGenerator $generator): RedirectResponse
    {
        Gate::authorize('kpiManageAssignments');

        $validated = $this->validateAssignment($request);

        if ($validated['is_active']) {
            $this->ensureNoDuplicateActiveAssignment(
                $validated['task_template_id'],
                $validated['user_id'],
                $assignment->id
            );
        }

        $assignment->update([
            'task_template_id' => $validated['task_template_id'],
            'user_id' => $validated['user_id'],
            'first_approver_user_id' => $validated['first_approver_user_id'],
            'final_approver_user_id' => $validated['final_approver_user_id'],
            'starts_on' => $validated['starts_on'],
            'ends_on' => $validated['ends_on'],
            'is_active' => $validated['is_active'],
            'calendar_push_enabled' => $validated['calendar_push_enabled'],
        ]);

        $this->saveCalendarControl($assignment, $validated);

        try {
            $generator->generateForAssignment($assignment->fresh(['template.group']));
        } catch (\Throwable $exception) {
            report($exception);
            $templateName = (string) ($assignment->template?->title ?? 'Unknown template');

            throw ValidationException::withMessages([
                'assignmentGenerator' => "Failed to create task instance for template: {$templateName}.",
            ]);
        }

        return redirect()->back()->with('message', 'Employee task assignment updated.');
    }

    public function destroy(KpiTaskAssignment $assignment): RedirectResponse
    {
        Gate::authorize('kpiManageAssignments');

        $instancesCount = $assignment->instances()->count();
        if ($instancesCount > 0) {
            throw ValidationException::withMessages([
                'assignmentDelete' => 'This assignment already has task instances. Set it inactive instead of deleting it.',
            ]);
        }

        $assignment->delete();

        return redirect()->back()->with('message', 'Employee task assignment deleted.');
    }

    public function updateInstance(Request $request, KpiTaskInstance $instance, KpiSubmissionImageResizer $resizer): RedirectResponse
    {
        Gate::authorize('isSuperAdmin');

        $validated = $request->validate([
            'instanceStatus' => ['required', 'in:pending,rejected,waiting_first_approval,waiting_final_approval,passed,failed_late,failed_missed,excluded'],
            'instanceTaskDate' => ['nullable', 'date'],
            'instancePeriodStart' => ['required', 'date'],
            'instancePeriodEnd' => ['required', 'date', 'after_or_equal:instancePeriodStart'],
            'instanceDueAt' => ['nullable', 'date'],
            'instanceIsLate' => ['boolean'],
            'removeSubmissionImageIds' => ['nullable', 'array'],
            'removeSubmissionImageIds.*' => ['integer'],
            'newSubmissionPhotos' => ['nullable', 'array', 'max:20'],
            'newSubmissionPhotos.*' => ['nullable', 'image', 'max:10240'],
            'newSubmissionPhotoTitles' => ['nullable', 'array'],
            'newSubmissionPhotoRemarks' => ['nullable', 'array'],
        ], [], [
            'instanceStatus' => 'status',
            'instanceTaskDate' => 'task date',
            'instancePeriodStart' => 'period start',
            'instancePeriodEnd' => 'period end',
            'instanceDueAt' => 'due at',
            'newSubmissionPhotos.*' => 'submission image',
        ]);

        $storedPaths = [];
        $newPhotos = $request->file('newSubmissionPhotos', []);

        try {
            if (is_array($newPhotos)) {
                foreach ($newPhotos as $index => $photo) {
                    if ($photo) {
                        $storedPaths[$index] = $resizer->store($photo, 900);
                    }
                }
            }

            DB::transaction(function () use ($instance, $request, $validated, $storedPaths): void {
                $status = $validated['instanceStatus'];
                $editingSubmissionId = $request->input('editingSubmissionId');
                $shouldCreateSubmission = $editingSubmissionId || $storedPaths !== [];
                $submission = null;

                if ($editingSubmissionId) {
                    $submission = KpiTaskSubmission::query()
                        ->with('images')
                        ->where('id', $editingSubmissionId)
                        ->where('task_instance_id', $instance->id)
                        ->first();
                } elseif ($shouldCreateSubmission) {
                    $submission = KpiTaskSubmission::create([
                        'task_instance_id' => $instance->id,
                        'submitted_by_user_id' => Auth::id(),
                        'submitted_at' => now(),
                        'is_late' => (bool) ($validated['instanceIsLate'] ?? false),
                        'sequence' => (int) $instance->submissions()->max('sequence') + 1,
                        'status' => 'submitted',
                        'employee_remark' => null,
                    ]);
                }

                $instance->update([
                    'status' => $status,
                    'task_date' => !empty($validated['instanceTaskDate']) ? $validated['instanceTaskDate'] : null,
                    'period_start' => $validated['instancePeriodStart'],
                    'period_end' => $validated['instancePeriodEnd'],
                    'due_at' => !empty($validated['instanceDueAt']) ? Carbon::parse($validated['instanceDueAt']) : null,
                    'submitted_at' => $submission?->submitted_at ?? $instance->submitted_at,
                    'is_on_time' => ($validated['instanceIsLate'] ?? false)
                        ? false
                        : (in_array($status, ['passed', 'failed_late', 'failed_missed'], true) ? $status === 'passed' : $instance->is_on_time),
                ]);

                if (!$submission) {
                    return;
                }

                $submission->update([
                    'is_late' => (bool) ($validated['instanceIsLate'] ?? false),
                ]);

                $removeIds = collect($validated['removeSubmissionImageIds'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values();

                if ($removeIds->isNotEmpty()) {
                    $imagesToDelete = $submission->images()->whereIn('id', $removeIds)->get();

                    foreach ($imagesToDelete as $image) {
                        Storage::disk('public')->delete((string) $image->image_path);
                        $image->delete();
                    }
                }

                $titles = $request->input('newSubmissionPhotoTitles', []);
                $remarks = $request->input('newSubmissionPhotoRemarks', []);

                foreach ($storedPaths as $index => $path) {
                    if ($path === null) {
                        continue;
                    }

                    $submission->images()->create([
                        'image_path' => $path,
                        'title' => trim((string) ($titles[$index] ?? '')) ?: null,
                        'remark' => trim((string) ($remarks[$index] ?? '')) ?: null,
                        'sort_order' => (int) ($submission->images()->max('sort_order') ?? 0) + $index + 1,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return redirect()->back()->with('message', 'Task instance updated.');
    }

    public function destroyInstance(KpiTaskInstance $instance): RedirectResponse
    {
        Gate::authorize('isSuperAdmin');

        $instance->delete();

        return redirect()->back()->with('message', 'Task instance deleted.');
    }

    protected function validateAssignment(Request $request): array
    {
        $validated = $request->validate([
            'assignmentTemplateId' => ['required', 'exists:kpi_task_templates,id'],
            'assignmentUserId' => ['required', 'exists:users,id'],
            'assignmentFirstApproverId' => ['required', 'exists:users,id', 'different:assignmentUserId'],
            'assignmentFinalApproverId' => ['nullable', 'exists:users,id'],
            'assignmentStartsOn' => ['nullable', 'date'],
            'assignmentEndsOn' => ['nullable', 'date', 'after_or_equal:assignmentStartsOn'],
            'assignmentCalendarPushEnabled' => ['boolean'],
            'assignmentDailyReminderEnabled' => ['boolean'],
            'assignmentReminderStartTime' => ['required', 'date_format:H:i'],
            'assignmentReminderIntervalMinutes' => ['required', 'integer', 'min:15', 'max:240'],
            'assignmentWeeklyMonthlyRefreshEnabled' => ['boolean'],
            'assignmentWeeklyMonthlyRefreshTime' => ['required', 'date_format:H:i'],
            'assignmentPushUntilFinalized' => ['boolean'],
            'assignmentIsActive' => ['boolean'],
        ], [], [
            'assignmentTemplateId' => 'task template',
            'assignmentUserId' => 'employee',
            'assignmentFirstApproverId' => 'first approver',
            'assignmentFinalApproverId' => 'final approver',
            'assignmentStartsOn' => 'start date',
            'assignmentEndsOn' => 'end date',
        ]);

        if (
            !empty($validated['assignmentFinalApproverId']) &&
            (int) $validated['assignmentFinalApproverId'] === (int) $validated['assignmentUserId']
        ) {
            throw ValidationException::withMessages([
                'assignmentFinalApproverId' => 'Final approver cannot be the same user as the assigned employee.',
            ]);
        }

        if (
            !empty($validated['assignmentFinalApproverId']) &&
            (int) $validated['assignmentFinalApproverId'] === (int) $validated['assignmentFirstApproverId']
        ) {
            throw ValidationException::withMessages([
                'assignmentFinalApproverId' => 'Final approver must be different from the first approver.',
            ]);
        }

        return [
            'task_template_id' => (int) $validated['assignmentTemplateId'],
            'user_id' => (int) $validated['assignmentUserId'],
            'first_approver_user_id' => (int) $validated['assignmentFirstApproverId'],
            'final_approver_user_id' => !empty($validated['assignmentFinalApproverId']) ? (int) $validated['assignmentFinalApproverId'] : null,
            'starts_on' => !empty($validated['assignmentStartsOn']) ? $validated['assignmentStartsOn'] : null,
            'ends_on' => !empty($validated['assignmentEndsOn']) ? $validated['assignmentEndsOn'] : null,
            'calendar_push_enabled' => (bool) ($validated['assignmentCalendarPushEnabled'] ?? true),
            'daily_reminder_enabled' => (bool) ($validated['assignmentDailyReminderEnabled'] ?? true),
            'reminder_start_time' => $validated['assignmentReminderStartTime'],
            'reminder_interval_minutes' => (int) $validated['assignmentReminderIntervalMinutes'],
            'weekly_monthly_refresh_enabled' => (bool) ($validated['assignmentWeeklyMonthlyRefreshEnabled'] ?? true),
            'weekly_monthly_refresh_time' => $validated['assignmentWeeklyMonthlyRefreshTime'],
            'push_until_finalized' => (bool) ($validated['assignmentPushUntilFinalized'] ?? true),
            'is_active' => (bool) ($validated['assignmentIsActive'] ?? true),
        ];
    }

    protected function ensureNoDuplicateActiveAssignment(int $templateId, int $userId, ?int $ignoreId = null): void
    {
        $exists = KpiTaskAssignment::query()
            ->where('task_template_id', $templateId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'assignmentTemplateId' => 'This employee already has an active assignment for the selected task template.',
            ]);
        }
    }

    protected function saveCalendarControl(KpiTaskAssignment $assignment, array $validated): void
    {
        KpiTaskCalendarControl::query()->updateOrCreate(
            ['task_assignment_id' => $assignment->id],
            [
                'daily_reminder_enabled' => $validated['daily_reminder_enabled'],
                'reminder_start_time' => $validated['reminder_start_time'],
                'reminder_interval_minutes' => $validated['reminder_interval_minutes'],
                'weekly_monthly_refresh_enabled' => $validated['weekly_monthly_refresh_enabled'],
                'weekly_monthly_refresh_time' => $validated['weekly_monthly_refresh_time'],
                'push_until_finalized' => $validated['push_until_finalized'],
            ]
        );
    }
}
