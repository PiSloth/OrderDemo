<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskCalendarControl;
use App\Models\Kpi\KpiTaskTemplate;
use App\Models\User;
use App\Services\Kpi\KpiTaskInstanceGenerator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Assignments extends Component
{
    public $templates;
    public $users;
    public $assignments;

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

    public function mount(): void
    {
        $this->loadOptions();
        $this->loadAssignments();
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
        $this->assignments = KpiTaskAssignment::query()
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
            ->orderByDesc('is_active')
            ->orderBy('user_id')
            ->orderBy('task_template_id')
            ->get();
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
        app(KpiTaskInstanceGenerator::class)->generateForAssignment($assignment->fresh(['template.group']));

        $this->resetAssignmentForm();
        $this->loadAssignments();

        session()->flash('message', 'Employee task assignment created.');
    }

    public function editAssignment(int $assignmentId): void
    {
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
        app(KpiTaskInstanceGenerator::class)->generateForAssignment($assignment->fresh(['template.group']));

        $this->resetAssignmentForm();
        $this->loadAssignments();

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

        session()->flash('message', 'Employee task assignment deleted.');
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
}
