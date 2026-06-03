<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskSubmission;
use App\Models\User;
use App\Services\Kpi\KpiSubmissionImageResizer;
use App\Services\Kpi\KpiTaskInstanceGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.kpi')]
class MyTasks extends Component
{
    use WithFileUploads;

    public $todayTasks;
    public $weeklyTasks;
    public $monthlyTasks;
    public $overdueTasks;

    public array $summaryCards = [];
    public ?int $selectedTaskInstanceId = null;
    public $cameraPhoto = null;
    public array $galleryPhotos = [];
    public array $submissionPhotos = [];
    public array $submissionPhotoSources = [];
    public array $submissionPhotoPreviews = [];
    public array $submissionPhotoTitles = [];
    public array $submissionPhotoRemarks = [];
    public string $submissionEmployeeRemark = '';
    public string $selectedMonth = '';
    public ?int $selectedUserId = null;
    public bool $isSuperAdmin = false;
    public bool $isResubmissionMode = false;
    public array $employeeOptions = [];

    public function mount(KpiTaskInstanceGenerator $generator): void
    {
        $user = Auth::user();

        if ($user) {
            $generator->generateForUser($user);
        }

        $this->isSuperAdmin = (bool) ($user?->can('isSuperAdmin') ?? false);
        $this->selectedUserId = $user?->id;

        if ($this->isSuperAdmin) {
            $this->employeeOptions = User::query()
                ->where('suspended', false)
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn(User $employee): array => [
                    'value' => $employee->id,
                    'label' => "{$employee->name} ({$employee->email})",
                ])
                ->values()
                ->all();
        }

        $this->todayTasks = collect();
        $this->weeklyTasks = collect();
        $this->monthlyTasks = collect();
        $this->overdueTasks = collect();
        $this->selectedMonth = now()->format('Y-m');

        $this->loadTasks();
    }

    public function updatedSelectedUserId($value): void
    {
        if (!$this->isSuperAdmin) {
            $this->selectedUserId = Auth::id();
            return;
        }

        $selectedId = (int) $value;

        $exists = User::query()
            ->where('id', $selectedId)
            ->where('suspended', false)
            ->exists();

        $this->selectedUserId = $exists ? $selectedId : Auth::id();
        $this->cancelSubmission();
        $this->loadTasks();
    }

    public function updatedSelectedMonth(string $value): void
    {
        if (!in_array($value, $this->allowedMonthValues(), true)) {
            $this->selectedMonth = now()->format('Y-m');
        }

        $this->cancelSubmission();
        $this->loadTasks();
    }

    public function loadTasks(): void
    {
        if (!in_array($this->selectedMonth, $this->allowedMonthValues(), true)) {
            $this->selectedMonth = now()->format('Y-m');
        }

        $selectedMonth = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();

        $instances = KpiTaskInstance::query()
            ->with([
                'template.group',
                'assignment.firstApprover',
                'assignment.finalApprover',
                'submissions' => fn($query) => $query->latest('sequence')->latest('id'),
            ])
            ->withCount('submissions')
            ->where('user_id', $this->targetUserId())
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query
                    ->where(function ($daily) use ($monthStart, $monthEnd) {
                        $daily
                            ->where('period_type', 'daily')
                            ->whereBetween('task_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
                    })
                    ->orWhere(function ($weekly) use ($monthStart, $monthEnd) {
                        $weekly
                            ->where('period_type', 'weekly')
                            ->whereDate('period_start', '<=', $monthEnd->toDateString())
                            ->whereDate('period_end', '>=', $monthStart->toDateString());
                    })
                    ->orWhere(function ($monthly) use ($monthStart, $monthEnd) {
                        $monthly
                            ->where('period_type', 'monthly')
                            ->whereDate('period_start', $monthStart->toDateString())
                            ->whereDate('period_end', $monthEnd->toDateString());
                    });
            })
            ->orderBy('due_at')
            ->orderBy('period_index')
            ->get();

        $isOpen = fn(KpiTaskInstance $instance) => !$this->isFinalized($instance);

        $this->todayTasks = $instances
            ->filter(fn(KpiTaskInstance $instance) => $instance->period_type === 'daily' && $isOpen($instance))
            ->values();

        $this->weeklyTasks = $instances
            ->filter(fn(KpiTaskInstance $instance) => $instance->period_type === 'weekly' && $isOpen($instance))
            ->values();

        $this->monthlyTasks = $instances
            ->filter(fn(KpiTaskInstance $instance) => $instance->period_type === 'monthly' && $isOpen($instance))
            ->values();

        $this->overdueTasks = $instances
            ->filter(fn(KpiTaskInstance $instance) => $isOpen($instance) && $instance->due_at && Carbon::parse($instance->due_at)->lt(now()))
            ->values();

        $this->summaryCards = [
            [
                'label' => 'Today Tasks',
                'value' => $this->todayTasks->count(),
                'modalTarget' => 'dailyTaskModal',
            ],
            [
                'label' => 'Open Weekly',
                'value' => $this->weeklyTasks->count(),
                'modalTarget' => 'weeklyTaskModal',
            ],
            [
                'label' => 'Open Monthly',
                'value' => $this->monthlyTasks->count(),
                'modalTarget' => 'monthlyTaskModal',
            ],
            [
                'label' => 'Overdue',
                'value' => $this->overdueTasks->count(),
                'modalTarget' => '',

            ],
        ];

        if ($this->selectedTaskInstanceId) {
            $selectedExists = $instances->contains(fn(KpiTaskInstance $instance) => $instance->id === $this->selectedTaskInstanceId);

            if (!$selectedExists) {
                $this->cancelSubmission();
            }
        }
    }

    public function openSubmission(int $taskInstanceId): void
    {
        if (!$this->canModifyViewedTasks()) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'Viewing another employee is read-only for submissions.',
            ]);
        }

        $instance = $this->findOwnedInstance($taskInstanceId);
        $this->ensureSubmissionAllowed($instance);

        $this->selectedTaskInstanceId = $instance->id;
        $this->cameraPhoto = null;
        $this->galleryPhotos = [];
        $this->submissionPhotos = [];
        $this->submissionPhotoSources = [];
        $this->submissionPhotoPreviews = [];
        $this->submissionPhotoTitles = [];
        $this->submissionPhotoRemarks = [];
        $this->submissionEmployeeRemark = '';
        $this->isResubmissionMode = false;
        $this->resetErrorBag();
    }

    public function openResubmitSubmission(int $taskInstanceId): void
    {
        if (!$this->isSuperAdmin) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'Only Super Admin can resubmit overdue tasks for employees.',
            ]);
        }

        $instance = $this->findOwnedInstance($taskInstanceId);
        $this->ensureOverdueResubmissionAllowed($instance);

        $this->selectedTaskInstanceId = $instance->id;
        $this->cameraPhoto = null;
        $this->galleryPhotos = [];
        $this->submissionPhotos = [];
        $this->submissionPhotoSources = [];
        $this->submissionPhotoPreviews = [];
        $this->submissionPhotoTitles = [];
        $this->submissionPhotoRemarks = [];
        $this->submissionEmployeeRemark = '';
        $this->isResubmissionMode = true;
        $this->resetErrorBag();
    }

    public function cancelSubmission(): void
    {
        $this->selectedTaskInstanceId = null;
        $this->cameraPhoto = null;
        $this->galleryPhotos = [];
        $this->submissionPhotos = [];
        $this->submissionPhotoSources = [];
        $this->submissionPhotoPreviews = [];
        $this->submissionPhotoTitles = [];
        $this->submissionPhotoRemarks = [];
        $this->submissionEmployeeRemark = '';
        $this->isResubmissionMode = false;
        $this->resetErrorBag();
    }

    public function updatedCameraPhoto(): void
    {
        $this->validate([
            'cameraPhoto' => ['nullable', 'image', 'max:10240'],
        ], [], [
            'cameraPhoto' => 'camera photo',
        ]);

        if ($this->cameraPhoto instanceof TemporaryUploadedFile) {
            $errors = [];

            if (!$this->isSupportedForResizing($this->cameraPhoto)) {
                $errors['cameraPhoto'] = $this->unsupportedImageMessage($this->cameraPhoto);
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $this->appendPhotos([$this->cameraPhoto], 'camera');
        }

        $this->cameraPhoto = null;
    }

    public function updatedGalleryPhotos(): void
    {
        $this->validate([
            'galleryPhotos' => ['array', 'max:20'],
            'galleryPhotos.*' => ['image', 'max:10240'],
        ], [], [
            'galleryPhotos' => 'gallery photos',
            'galleryPhotos.*' => 'photo',
        ]);

        $photos = array_values($this->galleryPhotos);

        if ($photos !== []) {
            $errors = [];

            foreach ($photos as $index => $photo) {
                if (!$photo instanceof TemporaryUploadedFile) {
                    continue;
                }

                if (!$this->isSupportedForResizing($photo)) {
                    $errors["galleryPhotos.{$index}"] = $this->unsupportedImageMessage($photo);
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $this->appendPhotos($photos, 'gallery');
        }

        $this->galleryPhotos = [];
    }

    public function removeSubmissionPhoto(int $index): void
    {
        if (!array_key_exists($index, $this->submissionPhotos)) {
            return;
        }

        unset(
            $this->submissionPhotos[$index],
            $this->submissionPhotoSources[$index],
            $this->submissionPhotoPreviews[$index],
            $this->submissionPhotoTitles[$index],
            $this->submissionPhotoRemarks[$index]
        );

        $this->submissionPhotos = array_values($this->submissionPhotos);
        $this->submissionPhotoSources = array_values($this->submissionPhotoSources);
        $this->submissionPhotoPreviews = array_values($this->submissionPhotoPreviews);
        $this->submissionPhotoTitles = array_values($this->submissionPhotoTitles);
        $this->submissionPhotoRemarks = array_values($this->submissionPhotoRemarks);
    }

    public function submitTask(KpiSubmissionImageResizer $resizer): void
    {
        $this->submitTaskInternal($resizer, false);
    }

    public function canSubmit(KpiTaskInstance $instance): bool
    {
        if (!$this->canModifyViewedTasks()) {
            return false;
        }

        if ($instance->template?->requires_table) {
            return false;
        }

        if ($this->isFinalized($instance)) {
            return false;
        }

        if ($this->submissionWindowClosed($instance)) {
            return false;
        }

        return in_array($instance->status, ['pending', 'rejected'], true);
    }

    public function canDirectSubmitNoEvidence(KpiTaskInstance $instance): bool
    {
        if (!$this->canSubmit($instance)) {
            return false;
        }

        $template = $instance->template;

        if (!$template) {
            return false;
        }

        return !$template->requires_images && !$template->requires_table;
    }

    public function canResubmitOverdueTask(KpiTaskInstance $instance): bool
    {
        if (!$this->isSuperAdmin) {
            return false;
        }

        if ($this->isFinalized($instance)) {
            return false;
        }

        if (!$this->isOverdue($instance)) {
            return false;
        }

        if (!in_array($instance->status, ['pending', 'rejected'], true)) {
            return false;
        }

        return (bool) $instance->template && !$instance->template->requires_table;
    }

    public function canSuperAdminResubmitWithoutEvidence(KpiTaskInstance $instance): bool
    {
        return $this->canResubmitOverdueTask($instance)
            && !$instance->template?->requires_images
            && !$instance->template?->requires_table;
    }

    public function canSuperAdminResubmitWithEvidence(KpiTaskInstance $instance): bool
    {
        return $this->canResubmitOverdueTask($instance)
            && (bool) $instance->template?->requires_images;
    }

    public function overdueResubmitUnavailableReason(KpiTaskInstance $instance): ?string
    {
        if (!$this->isSuperAdmin) {
            return null;
        }

        if (!$this->isOverdue($instance)) {
            return 'This task is not overdue yet.';
        }

        if (!$instance->template) {
            return 'Task template is missing.';
        }

        if ($instance->template->requires_table) {
            return 'This task requires table evidence, so resubmission is not available here.';
        }

        if (!in_array($instance->status, ['pending', 'rejected'], true)) {
            return 'This task is already waiting for approval.';
        }

        return null;
    }

    public function submitNoEvidence(int $taskInstanceId): void
    {
        $this->submitNoEvidenceInternal($taskInstanceId, false);
    }

    public function resubmitTask(KpiSubmissionImageResizer $resizer): void
    {
        $this->submitTaskInternal($resizer, true);
    }

    public function resubmitNoEvidence(int $taskInstanceId): void
    {
        $this->submitNoEvidenceInternal($taskInstanceId, true);
    }

    public function submissionWindowLabel(KpiTaskInstance $instance): string
    {
        $windowEnd = $this->submissionWindowEndsAt($instance);

        return $windowEnd ? $windowEnd->format('Y-m-d H:i') : 'N/A';
    }

    public function getSelectedTaskInstanceProperty(): ?KpiTaskInstance
    {
        if (!$this->selectedTaskInstanceId) {
            return null;
        }

        return KpiTaskInstance::query()
            ->with([
                'template.group',
                'assignment.firstApprover',
                'assignment.finalApprover',
                'submissions' => fn($query) => $query->latest('sequence')->latest('id'),
            ])
            ->where('id', $this->selectedTaskInstanceId)
            ->where('user_id', $this->targetUserId())
            ->first();
    }

    public function render()
    {
        return view('livewire.kpi.my-tasks', [
            'selectedTaskInstance' => $this->getSelectedTaskInstanceProperty(),
        ]);
    }

    protected function findOwnedInstance(int $taskInstanceId): KpiTaskInstance
    {
        return KpiTaskInstance::query()
            ->with([
                'template.group',
                'assignment.firstApprover',
                'assignment.finalApprover',
                'submissions' => fn($query) => $query->latest('sequence')->latest('id'),
            ])
            ->where('id', $taskInstanceId)
            ->where('user_id', $this->targetUserId())
            ->firstOrFail();
    }

    protected function ensureSubmissionAllowed(KpiTaskInstance $instance): void
    {
        $this->ensureSubmissionAllowedWithMode($instance, false);
    }

    protected function ensureSubmissionAllowedWithMode(KpiTaskInstance $instance, bool $allowClosedWindow): void
    {
        if (!$instance->template) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'Task template is missing.',
            ]);
        }

        if ($instance->template->requires_table) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'This task requires table evidence and cannot be submitted yet.',
            ]);
        }

        if ($this->isFinalized($instance)) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'This task is already finalized.',
            ]);
        }

        if (!$allowClosedWindow && $this->submissionWindowClosed($instance)) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'The submission window for this task is already closed.',
            ]);
        }

        if (!in_array($instance->status, ['pending', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'This task is already waiting for approval.',
            ]);
        }
    }

    protected function ensureOverdueResubmissionAllowed(KpiTaskInstance $instance): void
    {
        if (!$this->isSuperAdmin) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'Only Super Admin can resubmit overdue tasks for employees.',
            ]);
        }

        if (!$this->isOverdue($instance)) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'This task is not overdue yet.',
            ]);
        }

        $this->ensureSubmissionAllowedWithMode($instance, true);
    }

    protected function submitTaskInternal(KpiSubmissionImageResizer $resizer, bool $allowClosedWindow): void
    {
        $instance = $this->getSelectedTaskInstanceProperty();

        if (!$instance) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'Select a task before submitting.',
            ]);
        }

        $instance = $this->findOwnedInstance($instance->id);

        if ($allowClosedWindow) {
            $this->ensureOverdueResubmissionAllowed($instance);
        } else {
            $this->ensureSubmissionAllowed($instance);
        }

        $template = $instance->template;

        if (!$template) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'Task template is missing.',
            ]);
        }

        if ($template->requires_table) {
            throw ValidationException::withMessages([
                'submissionPhotos' => 'This task requires custom table evidence. Table submission is not built yet.',
            ]);
        }

        $this->validate([
            'submissionEmployeeRemark' => ['nullable', 'string'],
        ], [], [
            'submissionEmployeeRemark' => 'remark',
        ]);

        if ($template->requires_images) {
            $this->validate([
                'submissionPhotos' => ['array', 'max:20'],
                'submissionPhotos.*' => ['image', 'max:10240'],
            ], [], [
                'submissionPhotos' => 'photos',
                'submissionPhotos.*' => 'photo',
            ]);

            $photoCount = count($this->submissionPhotos);
            $requiredImageCount = $instance->required_image_count !== null
                ? (int) $instance->required_image_count
                : null;
            $minImages = $requiredImageCount ?? (int) ($template->min_images ?? 0);
            $maxImages = $requiredImageCount ?? ($template->max_images !== null ? (int) $template->max_images : null);

            if ($photoCount < $minImages) {
                throw ValidationException::withMessages([
                    'submissionPhotos' => "Upload at least {$minImages} photo(s) for this task.",
                ]);
            }

            if ($maxImages !== null && $photoCount > $maxImages) {
                throw ValidationException::withMessages([
                    'submissionPhotos' => "This task allows a maximum of {$maxImages} photo(s).",
                ]);
            }

            foreach ($this->submissionPhotos as $index => $photo) {
                if (!$photo instanceof TemporaryUploadedFile) {
                    continue;
                }

                if (!$this->isSupportedForResizing($photo)) {
                    throw ValidationException::withMessages([
                        "submissionPhotos.{$index}" => $this->unsupportedImageMessage($photo),
                    ]);
                }

                $title = trim((string) ($this->submissionPhotoTitles[$index] ?? ''));
                $remark = trim((string) ($this->submissionPhotoRemarks[$index] ?? ''));

                if ($title === '') {
                    throw ValidationException::withMessages([
                        "submissionPhotoTitles.{$index}" => 'Each photo needs an action title.',
                    ]);
                }

                if ($template->image_remark_required && $remark === '') {
                    throw ValidationException::withMessages([
                        "submissionPhotoRemarks.{$index}" => 'Remark is required for each photo on this task.',
                    ]);
                }
            }
        }

        $storedPaths = [];

        try {
            foreach ($this->submissionPhotos as $index => $photo) {
                if ($photo instanceof TemporaryUploadedFile) {
                    $path = $resizer->store($photo, 900);
                    $storedPaths[$index] = $path;
                }
            }

            DB::transaction(function () use ($instance, $template, $storedPaths): void {
                $submittedAt = now();
                $sequence = (int) $instance->submissions()->max('sequence') + 1;

                $submission = KpiTaskSubmission::create([
                    'task_instance_id' => $instance->id,
                    'submitted_by_user_id' => Auth::id(),
                    'submitted_at' => $submittedAt,
                    'is_late' => $instance->due_at ? $submittedAt->gt($instance->due_at) : false,
                    'sequence' => $sequence,
                    'status' => 'submitted',
                    'employee_remark' => $this->submissionEmployeeRemark !== '' ? $this->submissionEmployeeRemark : null,
                ]);

                foreach ($this->submissionPhotos as $index => $photo) {
                    $path = $storedPaths[$index] ?? null;
                    if ($path === null) {
                        continue;
                    }

                    $submission->images()->create([
                        'image_path' => $path,
                        'title' => trim((string) ($this->submissionPhotoTitles[$index] ?? '')) ?: null,
                        'remark' => trim((string) ($this->submissionPhotoRemarks[$index] ?? '')) ?: null,
                        'sort_order' => $index + 1,
                    ]);
                }

                $submission->approvalSteps()->create([
                    'step_order' => 1,
                    'approver_user_id' => $instance->assignment?->first_approver_user_id,
                    'role_label' => 'First Approver',
                    'status' => 'pending',
                ]);

                if ($instance->assignment?->final_approver_user_id) {
                    $submission->approvalSteps()->create([
                        'step_order' => 2,
                        'approver_user_id' => $instance->assignment->final_approver_user_id,
                        'role_label' => 'Final Approver',
                        'status' => 'pending',
                    ]);
                }

                $instance->update([
                    'submitted_at' => $submittedAt,
                    'status' => 'waiting_first_approval',
                    'is_on_time' => $instance->due_at ? $submittedAt->lte($instance->due_at) : true,
                    'failure_reason' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        $this->cancelSubmission();
        $this->loadTasks();

        session()->flash('message', $allowClosedWindow
            ? $template->title . ' resubmitted for approval by Super Admin.'
            : $template->title . ' submitted for approval.');
    }

    protected function submitNoEvidenceInternal(int $taskInstanceId, bool $allowClosedWindow): void
    {
        $instance = $this->findOwnedInstance($taskInstanceId);

        if ($allowClosedWindow) {
            $this->ensureOverdueResubmissionAllowed($instance);
        } else {
            $this->ensureSubmissionAllowed($instance);
        }

        $template = $instance->template;

        if (!$template) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'Task template is missing.',
            ]);
        }

        if ($template->requires_images || $template->requires_table) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'This task requires evidence. Open submission form instead.',
            ]);
        }

        DB::transaction(function () use ($instance): void {
            $submittedAt = now();
            $sequence = (int) $instance->submissions()->max('sequence') + 1;

            $submission = KpiTaskSubmission::create([
                'task_instance_id' => $instance->id,
                'submitted_by_user_id' => Auth::id(),
                'submitted_at' => $submittedAt,
                'is_late' => $instance->due_at ? $submittedAt->gt($instance->due_at) : false,
                'sequence' => $sequence,
                'status' => 'submitted',
                'employee_remark' => null,
            ]);

            $submission->approvalSteps()->create([
                'step_order' => 1,
                'approver_user_id' => $instance->assignment?->first_approver_user_id,
                'role_label' => 'First Approver',
                'status' => 'pending',
            ]);

            if ($instance->assignment?->final_approver_user_id) {
                $submission->approvalSteps()->create([
                    'step_order' => 2,
                    'approver_user_id' => $instance->assignment->final_approver_user_id,
                    'role_label' => 'Final Approver',
                    'status' => 'pending',
                ]);
            }

            $instance->update([
                'submitted_at' => $submittedAt,
                'status' => 'waiting_first_approval',
                'is_on_time' => $instance->due_at ? $submittedAt->lte($instance->due_at) : true,
                'failure_reason' => null,
            ]);
        });

        $this->cancelSubmission();
        $this->loadTasks();

        session()->flash('message', $allowClosedWindow
            ? ($template->title ?? 'Task') . ' resubmitted without evidence by Super Admin.'
            : ($template->title ?? 'Task') . ' submitted without evidence.');
    }

    protected function isOverdue(KpiTaskInstance $instance): bool
    {
        return $instance->due_at ? Carbon::parse($instance->due_at)->lt(now()) : false;
    }

    protected function submissionWindowClosed(KpiTaskInstance $instance): bool
    {
        $windowEnd = $this->submissionWindowEndsAt($instance);

        return $windowEnd ? now()->gt($windowEnd) : false;
    }

    protected function submissionWindowEndsAt(KpiTaskInstance $instance): ?Carbon
    {
        if ($this->isPreviousMonthInstance($instance)) {
            return now()->copy()->endOfMonth();
        }

        return match ($instance->period_type) {
            'daily' => $instance->task_date ? Carbon::parse($instance->task_date)->endOfDay() : null,
            default => $instance->period_end ? Carbon::parse($instance->period_end)->endOfDay() : null,
        };
    }

    protected function allowedMonthValues(): array
    {
        return [
            now()->format('Y-m'),
            now()->copy()->subMonth()->format('Y-m'),
        ];
    }

    public function monthOptions(): array
    {
        $current = now()->startOfMonth();
        $previous = now()->copy()->subMonth()->startOfMonth();

        return [
            ['value' => $current->format('Y-m'), 'label' => $current->format('F Y')],
            ['value' => $previous->format('Y-m'), 'label' => $previous->format('F Y')],
        ];
    }

    protected function isPreviousMonthInstance(KpiTaskInstance $instance): bool
    {
        $anchor = match ($instance->period_type) {
            'daily' => $instance->task_date,
            default => $instance->period_end ?? $instance->period_start,
        };

        if (!$anchor) {
            return false;
        }

        $previousMonth = now()->copy()->subMonth()->startOfMonth();

        return Carbon::parse($anchor)->format('Y-m') === $previousMonth->format('Y-m');
    }

    public function isFinalized(KpiTaskInstance $instance): bool
    {
        return in_array($instance->status, ['passed', 'failed_late', 'failed_missed', 'excluded'], true);
    }

    public function canModifyViewedTasks(): bool
    {
        return Auth::id() === $this->targetUserId();
    }

    protected function appendPhotos(array $photos, string $source): void
    {
        foreach ($photos as $photo) {
            if (!$photo instanceof TemporaryUploadedFile) {
                continue;
            }

            $this->submissionPhotos[] = $photo;
            $this->submissionPhotoSources[] = $source;
            $this->submissionPhotoPreviews[] = method_exists($photo, 'temporaryUrl')
                ? $photo->temporaryUrl()
                : null;
            $this->submissionPhotoTitles[] = '';
            $this->submissionPhotoRemarks[] = '';
        }
    }

    protected function isSupportedForResizing(TemporaryUploadedFile $photo): bool
    {
        $realPath = $photo->getRealPath();
        $binary = $realPath ? @file_get_contents($realPath) : false;

        if ($binary === false) {
            return false;
        }

        $info = @getimagesizefromstring($binary);

        if (!$info || !isset($info[2])) {
            return false;
        }

        $type = (int) $info[2];

        return in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true);
    }

    protected function unsupportedImageMessage(TemporaryUploadedFile $photo): string
    {
        $extension = Str::lower((string) $photo->getClientOriginalExtension());

        if (in_array($extension, ['heic', 'heif'], true)) {
            return 'HEIC/HEIF photos are not supported yet on this server. Please convert to JPG or PNG first.';
        }

        return 'This image format is not supported. Please use JPG, PNG, or WEBP.';
    }

    protected function targetUserId(): int
    {
        if ($this->isSuperAdmin && $this->selectedUserId) {
            return (int) $this->selectedUserId;
        }

        return (int) Auth::id();
    }
}
