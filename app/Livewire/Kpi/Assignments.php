<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskCalendarControl;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskSubmission;
use App\Models\Kpi\KpiTaskSubmissionImage;
use App\Models\Kpi\KpiTaskTemplate;
use App\Models\User;
use App\Services\Kpi\KpiSubmissionImageResizer;
use App\Services\Kpi\KpiTaskInstanceGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.kpi')]
class Assignments extends Component
{
    use WithFileUploads;

    public $templates;
    public $users;
    public $assignments;
    public $instances;

    public int $selectedUserId = 0;
    public int $instanceUserId = 0;
    public string $instanceStatusFilter = 'all';
    public string $instanceDateFilter = '';
    public string $instanceSearch = '';
    public bool $canManageInstances = false;

    public ?int $editingInstanceId = null;
    public string $instanceStatus = 'pending';
    public string $instanceTaskDate = '';
    public string $instancePeriodStart = '';
    public string $instancePeriodEnd = '';
    public string $instanceDueAt = '';
    public ?int $editingSubmissionId = null;
    public bool $instanceIsLate = false;
    public bool $instanceRequiresImages = false;
    public int $instanceMinImages = 0;
    public ?int $instanceMaxImages = null;
    public string $instanceEvidenceSummary = '';
    public array $existingSubmissionImages = [];
    public array $removeSubmissionImageIds = [];
    public array $newSubmissionPhotos = [];
    public array $newSubmissionPhotoTitles = [];
    public array $newSubmissionPhotoRemarks = [];

    public ?int $editingAssignmentId = null;
    public string $assignmentTemplateId = '';
    public string $assignmentUserId = '';
    public string $assignmentFirstApproverId = '';
    public string $assignmentFinalApproverId = '';
    public string $assignmentStartsOn = '';
    public string $assignmentEndsOn = '';
    public bool $assignmentCalendarPushEnabled = true;
    public bool $assignmentDailyReminderEnabled = true;
    public string $assignmentReminderStartTime = '08:45';
    public int $assignmentReminderIntervalMinutes = 60;
    public bool $assignmentWeeklyMonthlyRefreshEnabled = true;
    public string $assignmentWeeklyMonthlyRefreshTime = '09:15';
    public bool $assignmentPushUntilFinalized = true;
    public bool $assignmentIsActive = true;
    public bool $is_active = true;

    public function mount(): void
    {
        $this->canManageInstances = Gate::allows('isSuperAdmin');
        $this->loadOptions();
        $this->selectedUserId = auth()->id();
        $this->instanceUserId = $this->canManageInstances ? 0 : auth()->id();
        $this->loadAssignments();
        $this->loadInstances();
    }

    public function updatedSelectedUserId(): void
    {
        $this->loadAssignments();
    }

    public function updatedInstanceUserId(): void
    {
        $this->loadInstances();
    }

    public function updatedInstanceStatusFilter(): void
    {
        $this->loadInstances();
    }

    public function updatedInstanceDateFilter(): void
    {
        $this->loadInstances();
    }

    public function updatedInstanceSearch(): void
    {
        $this->loadInstances();
    }

    public function clearInstanceFilters(): void
    {
        $this->instanceUserId = $this->canManageInstances ? 0 : auth()->id();
        $this->instanceStatusFilter = 'all';
        $this->instanceDateFilter = '';
        $this->instanceSearch = '';
        $this->loadInstances();
    }

    public function loadOptions(): void
    {
        $this->templates = KpiTaskTemplate::query()
            ->with('group.department')
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->get();

        $this->users = User::query()
            ->with(['position', 'department', 'branch'])
            ->orderBy('name')
            ->get();
    }

    public function updatedAssignmentTemplateId(): void
    {
        $this->assignmentUserId = '';
    }

    public function loadAssignments(): void
    {
        $query = KpiTaskAssignment::query()
            ->with([
                'template.group',
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
            ->orderByDesc('is_active')
            ->where('is_active', $this->is_active);

        if ($this->selectedUserId > 0) {
            $query->where('user_id', $this->selectedUserId);
        }

        $this->assignments = $query->get();
    }

    public function loadInstances(): void
    {
        if (!$this->canManageInstances) {
            $this->instances = collect();

            return;
        }

        $query = KpiTaskInstance::query()
            ->with([
                'template.group',
                'user.position',
                'assignment',
            ])
            ->orderByDesc('period_start')
            ->orderByDesc('id');

        if ($this->instanceUserId > 0) {
            $query->where('user_id', $this->instanceUserId);
        }

        if ($this->instanceStatusFilter !== 'all' && $this->instanceStatusFilter !== '') {
            $query->where('status', $this->instanceStatusFilter);
        }

        if ($this->instanceDateFilter !== '') {
            $query->whereDate('task_date', $this->instanceDateFilter);
        }

        $search = trim($this->instanceSearch);
        if ($search !== '') {
            $query->where(function ($nestedQuery) use ($search): void {
                $like = '%' . $search . '%';

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

        $this->instances = $query->limit(300)->get();
    }

    public function createAssignment(): void
    {
        Gate::authorize('kpiManageAssignments');

        $validated = $this->validateAssignment();
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
            app(KpiTaskInstanceGenerator::class)->generateForAssignment($assignment->fresh(['template.group']));
        } catch (\Throwable $exception) {
            report($exception);

            $templateName = (string) ($assignment->template?->title ?? 'Unknown template');

            throw ValidationException::withMessages([
                'assignmentGenerator' => "Failed to create task instance for template: {$templateName}.",
            ]);
        }

        $this->resetAssignmentForm();
        $this->loadAssignments();
        $this->loadInstances();

        session()->flash('message', 'Employee task assignment created.');
    }

    public function editAssignment(int $assignmentId): void
    {
        // dd(Gate::allows('kpiManageAssignments'));
        Gate::authorize('kpiManageAssignments');

        $assignment = KpiTaskAssignment::query()->with('calendarControl')->findOrFail($assignmentId);

        $this->editingAssignmentId = $assignment->id;
        $this->assignmentTemplateId = (string) $assignment->task_template_id;
        $this->assignmentUserId = (string) $assignment->user_id;
        $this->assignmentFirstApproverId = $assignment->first_approver_user_id ? (string) $assignment->first_approver_user_id : '';
        $this->assignmentFinalApproverId = $assignment->final_approver_user_id ? (string) $assignment->final_approver_user_id : '';
        $this->assignmentStartsOn = $assignment->starts_on?->format('Y-m-d') ?? '';
        $this->assignmentEndsOn = $assignment->ends_on?->format('Y-m-d') ?? '';
        $this->assignmentCalendarPushEnabled = (bool) $assignment->calendar_push_enabled;
        $this->assignmentDailyReminderEnabled = (bool) ($assignment->calendarControl?->daily_reminder_enabled ?? true);
        $this->assignmentReminderStartTime = substr((string) ($assignment->calendarControl?->reminder_start_time ?? '08:45:00'), 0, 5);
        $this->assignmentReminderIntervalMinutes = (int) ($assignment->calendarControl?->reminder_interval_minutes ?? 60);
        $this->assignmentWeeklyMonthlyRefreshEnabled = (bool) ($assignment->calendarControl?->weekly_monthly_refresh_enabled ?? true);
        $this->assignmentWeeklyMonthlyRefreshTime = substr((string) ($assignment->calendarControl?->weekly_monthly_refresh_time ?? '09:15:00'), 0, 5);
        $this->assignmentPushUntilFinalized = (bool) ($assignment->calendarControl?->push_until_finalized ?? true);
        $this->assignmentIsActive = (bool) $assignment->is_active;

        $this->dispatch('openModal', 'assignmentModal');
    }

    public function updateAssignment(): void
    {
        Gate::authorize('kpiManageAssignments');

        if (!$this->editingAssignmentId) {
            return;
        }

        $assignment = KpiTaskAssignment::query()->with('calendarControl')->findOrFail($this->editingAssignmentId);
        $validated = $this->validateAssignment();

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
            app(KpiTaskInstanceGenerator::class)->generateForAssignment($assignment->fresh(['template.group']));
        } catch (\Throwable $exception) {
            report($exception);

            $templateName = (string) ($assignment->template?->title ?? 'Unknown template');

            throw ValidationException::withMessages([
                'assignmentGenerator' => "Failed to create task instance for template: {$templateName}.",
            ]);
        }

        $this->resetAssignmentForm();
        $this->loadAssignments();
        $this->loadInstances();

        session()->flash('message', 'Employee task assignment updated.');
    }

    public function deleteAssignment(int $assignmentId): void
    {
        Gate::authorize('kpiManageAssignments');

        $assignment = KpiTaskAssignment::query()->withCount('instances')->findOrFail($assignmentId);

        if ($assignment->instances_count > 0) {
            throw ValidationException::withMessages([
                'assignmentDelete' => 'This assignment already has task instances. Set it inactive instead of deleting it.',
            ]);
        }

        $assignment->delete();

        $this->resetAssignmentForm();
        $this->loadAssignments();
        $this->loadInstances();

        session()->flash('message', 'Employee task assignment deleted.');
    }

    public function editInstance(int $instanceId): void
    {
        Gate::authorize('isSuperAdmin');

        $instance = KpiTaskInstance::query()
            ->with([
                'submissions' => fn($query) => $query->with('images')->latest('sequence')->latest('id'),
                'template',
            ])
            ->findOrFail($instanceId);

        $this->editingInstanceId = $instance->id;
        $this->instanceStatus = (string) $instance->status;
        $this->instanceTaskDate = $instance->task_date?->format('Y-m-d') ?? '';
        $this->instancePeriodStart = $instance->period_start?->format('Y-m-d') ?? '';
        $this->instancePeriodEnd = $instance->period_end?->format('Y-m-d') ?? '';
        $this->instanceDueAt = $instance->due_at ? Carbon::parse($instance->due_at)->format('Y-m-d\TH:i') : '';
        $this->removeSubmissionImageIds = [];
        $this->newSubmissionPhotos = [];
        $this->newSubmissionPhotoTitles = [];
        $this->newSubmissionPhotoRemarks = [];
        $this->instanceRequiresImages = (bool) ($instance->template?->requires_images ?? false);
        $this->instanceMinImages = (int) ($instance->template?->min_images ?? 0);
        $this->instanceMaxImages = $instance->template?->max_images !== null ? (int) $instance->template->max_images : null;
        $this->instanceEvidenceSummary = $this->buildImageEvidenceSummary(
            $instance->template?->requires_images ?? false,
            $instance->template?->min_images ?? 0,
            $instance->template?->max_images,
            $instance->required_image_count
        );

        $latestSubmission = $instance->submissions->first();
        $this->editingSubmissionId = $latestSubmission?->id;
        $this->instanceIsLate = (bool) ($latestSubmission?->is_late ?? false);
        $this->existingSubmissionImages = $latestSubmission
            ? $latestSubmission->images->map(fn(KpiTaskSubmissionImage $image): array => [
                'id' => (int) $image->id,
                'title' => (string) ($image->title ?? ''),
                'remark' => (string) ($image->remark ?? ''),
                'url' => asset('storage/' . ltrim((string) $image->image_path, '/')),
            ])->values()->all()
            : [];

        $this->dispatch('openModal', 'instanceModal');
    }

    public function updateInstance(KpiSubmissionImageResizer $resizer): void
    {
        Gate::authorize('isSuperAdmin');

        if (!$this->editingInstanceId) {
            return;
        }

        $validated = $this->validate([
            'instanceStatus' => ['required', 'in:pending,rejected,waiting_first_approval,waiting_final_approval,passed,failed_late,failed_missed,excluded'],
            'instanceTaskDate' => ['nullable', 'date'],
            'instancePeriodStart' => ['required', 'date'],
            'instancePeriodEnd' => ['required', 'date', 'after_or_equal:instancePeriodStart'],
            'instanceDueAt' => ['nullable', 'date'],
            'instanceIsLate' => ['boolean'],
            'newSubmissionPhotos' => ['array', 'max:20'],
            'newSubmissionPhotos.*' => ['nullable', 'image', 'max:10240'],
            'newSubmissionPhotoTitles' => ['array'],
            'newSubmissionPhotoRemarks' => ['array'],
        ], [], [
            'instanceStatus' => 'status',
            'instanceTaskDate' => 'task date',
            'instancePeriodStart' => 'period start',
            'instancePeriodEnd' => 'period end',
            'instanceDueAt' => 'due at',
            'newSubmissionPhotos.*' => 'submission image',
        ]);

        $instance = KpiTaskInstance::query()->findOrFail($this->editingInstanceId);
        $storedPaths = [];

        try {
            foreach ($this->newSubmissionPhotos as $index => $photo) {
                if ($photo instanceof TemporaryUploadedFile) {
                    $path = $resizer->store($photo, 900);
                    $storedPaths[$index] = $path;
                }
            }

            DB::transaction(function () use ($instance, $validated, $storedPaths): void {
                $status = $validated['instanceStatus'];
                $shouldCreateSubmission = $this->editingSubmissionId || $storedPaths !== [];
                $submission = null;

                if ($this->editingSubmissionId) {
                    $submission = KpiTaskSubmission::query()
                        ->with('images')
                        ->where('id', $this->editingSubmissionId)
                        ->where('task_instance_id', $instance->id)
                        ->firstOrFail();
                } elseif ($shouldCreateSubmission) {
                    $submission = KpiTaskSubmission::create([
                        'task_instance_id' => $instance->id,
                        'submitted_by_user_id' => Auth::id(),
                        'submitted_at' => now(),
                        'is_late' => (bool) $validated['instanceIsLate'],
                        'sequence' => (int) $instance->submissions()->max('sequence') + 1,
                        'status' => 'submitted',
                        'employee_remark' => null,
                    ]);
                }

                $instance->update([
                    'status' => $status,
                    'task_date' => $validated['instanceTaskDate'] !== '' ? $validated['instanceTaskDate'] : null,
                    'period_start' => $validated['instancePeriodStart'],
                    'period_end' => $validated['instancePeriodEnd'],
                    'due_at' => $validated['instanceDueAt'] !== '' ? Carbon::parse($validated['instanceDueAt']) : null,
                    'submitted_at' => $submission?->submitted_at ?? $instance->submitted_at,
                    'is_on_time' => $validated['instanceIsLate']
                        ? false
                        : (in_array($status, ['passed', 'failed_late', 'failed_missed'], true) ? $status === 'passed' : $instance->is_on_time),
                ]);

                if (!$submission) {
                    return;
                }

                $submission->update([
                    'is_late' => (bool) $validated['instanceIsLate'],
                ]);

                $removeIds = collect($this->removeSubmissionImageIds)
                    ->map(fn($id) => (int) $id)
                    ->filter(fn($id) => $id > 0)
                    ->values();

                if ($removeIds->isNotEmpty()) {
                    $imagesToDelete = $submission->images()->whereIn('id', $removeIds)->get();

                    foreach ($imagesToDelete as $image) {
                        Storage::disk('public')->delete((string) $image->image_path);
                        $image->delete();
                    }
                }

                foreach ($this->newSubmissionPhotos as $index => $photo) {
                    $path = $storedPaths[$index] ?? null;
                    if ($path === null) {
                        continue;
                    }

                    $submission->images()->create([
                        'image_path' => $path,
                        'title' => trim((string) ($this->newSubmissionPhotoTitles[$index] ?? '')) ?: null,
                        'remark' => trim((string) ($this->newSubmissionPhotoRemarks[$index] ?? '')) ?: null,
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

        $this->resetInstanceForm();
        $this->loadInstances();

        session()->flash('message', 'Task instance updated.');
    }

    public function deleteInstance(int $instanceId): void
    {
        Gate::authorize('isSuperAdmin');

        KpiTaskInstance::query()->findOrFail($instanceId)->delete();

        $this->resetInstanceForm();
        $this->loadInstances();

        session()->flash('message', 'Task instance deleted.');
    }

    public function cancelInstance(): void
    {
        $this->resetInstanceForm();
    }

    public function markSubmissionImageRemoval(int $imageId): void
    {
        if (in_array($imageId, $this->removeSubmissionImageIds, true)) {
            $this->removeSubmissionImageIds = array_values(array_filter(
                $this->removeSubmissionImageIds,
                fn($id) => (int) $id !== $imageId
            ));

            return;
        }

        $this->removeSubmissionImageIds[] = $imageId;
    }

    public function cancelAssignment(): void
    {
        $this->resetAssignmentForm();
    }

    public function render()
    {
        return view('livewire.kpi.assignments', [
            'selectedTemplate' => $this->getSelectedTemplateProperty(),
            'employeeAsyncData' => $this->getEmployeeAsyncDataProperty(),
            'approverAsyncData' => $this->getApproverAsyncDataProperty(),
        ]);
    }

    public function getSelectedTemplateProperty(): ?KpiTaskTemplate
    {
        if ($this->assignmentTemplateId === '') {
            return null;
        }

        return $this->templates->firstWhere('id', (int) $this->assignmentTemplateId);
    }

    public function getEmployeeAsyncDataProperty(): array
    {
        $selectedTemplate = $this->getSelectedTemplateProperty();
        $params = [];

        if ($selectedTemplate?->group?->department_id) {
            $params['department_id'] = (int) $selectedTemplate->group->department_id;
        }

        return [
            'api' => route('users.index'),
            'method' => 'GET',
            'params' => $params,
            'alwaysFetch' => false,
        ];
    }

    public function getApproverAsyncDataProperty(): array
    {
        return [
            'api' => route('users.index'),
            'method' => 'GET',
            'params' => [],
            'alwaysFetch' => false,
        ];
    }

    protected function validateAssignment(): array
    {
        $validated = $this->validate([
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
            $validated['assignmentFinalApproverId'] !== '' &&
            (int) $validated['assignmentFinalApproverId'] === (int) $validated['assignmentUserId']
        ) {
            throw ValidationException::withMessages([
                'assignmentFinalApproverId' => 'Final approver cannot be the same user as the assigned employee.',
            ]);
        }

        if (
            $validated['assignmentFinalApproverId'] !== '' &&
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
            'final_approver_user_id' => $validated['assignmentFinalApproverId'] !== '' ? (int) $validated['assignmentFinalApproverId'] : null,
            'starts_on' => $validated['assignmentStartsOn'] !== '' ? $validated['assignmentStartsOn'] : null,
            'ends_on' => $validated['assignmentEndsOn'] !== '' ? $validated['assignmentEndsOn'] : null,
            'calendar_push_enabled' => (bool) $validated['assignmentCalendarPushEnabled'],
            'daily_reminder_enabled' => (bool) $validated['assignmentDailyReminderEnabled'],
            'reminder_start_time' => $validated['assignmentReminderStartTime'],
            'reminder_interval_minutes' => (int) $validated['assignmentReminderIntervalMinutes'],
            'weekly_monthly_refresh_enabled' => (bool) $validated['assignmentWeeklyMonthlyRefreshEnabled'],
            'weekly_monthly_refresh_time' => $validated['assignmentWeeklyMonthlyRefreshTime'],
            'push_until_finalized' => (bool) $validated['assignmentPushUntilFinalized'],
            'is_active' => (bool) $validated['assignmentIsActive'],
        ];
    }

    protected function ensureNoDuplicateActiveAssignment(int $templateId, int $userId, ?int $ignoreId = null): void
    {
        $exists = KpiTaskAssignment::query()
            ->where('task_template_id', $templateId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
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

    protected function resetAssignmentForm(): void
    {
        $this->editingAssignmentId = null;
        $this->assignmentTemplateId = '';
        $this->assignmentUserId = '';
        $this->assignmentFirstApproverId = '';
        $this->assignmentFinalApproverId = '';
        $this->assignmentStartsOn = '';
        $this->assignmentEndsOn = '';
        $this->assignmentCalendarPushEnabled = true;
        $this->assignmentDailyReminderEnabled = true;
        $this->assignmentReminderStartTime = '08:45';
        $this->assignmentReminderIntervalMinutes = 60;
        $this->assignmentWeeklyMonthlyRefreshEnabled = true;
        $this->assignmentWeeklyMonthlyRefreshTime = '09:15';
        $this->assignmentPushUntilFinalized = true;
        $this->assignmentIsActive = true;
        $this->resetErrorBag();
    }

    protected function resetInstanceForm(): void
    {
        $this->editingInstanceId = null;
        $this->editingSubmissionId = null;
        $this->instanceStatus = 'pending';
        $this->instanceIsLate = false;
        $this->instanceTaskDate = '';
        $this->instancePeriodStart = '';
        $this->instancePeriodEnd = '';
        $this->instanceDueAt = '';
        $this->existingSubmissionImages = [];
        $this->removeSubmissionImageIds = [];
        $this->newSubmissionPhotos = [];
        $this->newSubmissionPhotoTitles = [];
        $this->newSubmissionPhotoRemarks = [];
        $this->instanceRequiresImages = false;
        $this->instanceMinImages = 0;
        $this->instanceMaxImages = null;
        $this->instanceEvidenceSummary = '';
        $this->resetErrorBag();
    }

    protected function buildImageEvidenceSummary(bool $requiresImages, int $minImages, ?int $maxImages, ?int $overrideCount = null): string
    {
        if (!$requiresImages) {
            return 'No image evidence required for this task.';
        }

        if ($overrideCount !== null) {
            return "Image evidence required: {$overrideCount} photo(s).";
        }

        if ($maxImages !== null && $maxImages > 0) {
            if ($minImages > 0 && $minImages === $maxImages) {
                return "Image evidence required: {$minImages} photo(s).";
            }

            return "Image evidence required: {$minImages} to {$maxImages} photo(s).";
        }

        if ($minImages > 0) {
            return "Image evidence required: at least {$minImages} photo(s).";
        }

        return 'Image evidence is required.';
    }

    public function getInstanceStatusOptionsProperty(): array
    {
        return [
            'all' => 'All statuses',
            'pending' => 'Pending',
            'rejected' => 'Rejected',
            'waiting_first_approval' => 'Waiting first approval',
            'waiting_final_approval' => 'Waiting final approval',
            'passed' => 'Passed',
            'failed_late' => 'Failed late',
            'failed_missed' => 'Failed missed',
            'excluded' => 'Excluded',
        ];
    }
}
