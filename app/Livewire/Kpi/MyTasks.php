<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskSubmission;
use App\Services\Kpi\KpiSubmissionImageResizer;
use App\Services\Kpi\KpiTaskInstanceGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
    public array $submissionPhotoTitles = [];
    public array $submissionPhotoRemarks = [];
    public string $submissionEmployeeRemark = '';

    public function mount(KpiTaskInstanceGenerator $generator): void
    {
        $user = Auth::user();

        if ($user) {
            $generator->generateForUser($user);
        }

        $this->todayTasks = collect();
        $this->weeklyTasks = collect();
        $this->monthlyTasks = collect();
        $this->overdueTasks = collect();

        $this->loadTasks();
    }

    public function loadTasks(): void
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $instances = KpiTaskInstance::query()
            ->with([
                'template.group',
                'assignment.firstApprover',
                'assignment.finalApprover',
                'submissions' => fn ($query) => $query->latest('sequence')->latest('id'),
            ])
            ->withCount('submissions')
            ->where('user_id', Auth::id())
            ->where(function ($query) use ($today, $weekStart, $weekEnd, $monthStart, $monthEnd) {
                $query
                    ->where(function ($daily) use ($today) {
                        $daily
                            ->where('period_type', 'daily')
                            ->whereDate('task_date', $today->toDateString());
                    })
                    ->orWhere(function ($weekly) use ($weekStart, $weekEnd) {
                        $weekly
                            ->where('period_type', 'weekly')
                            ->whereDate('period_start', $weekStart->toDateString())
                            ->whereDate('period_end', $weekEnd->toDateString());
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

        $isOpen = fn (KpiTaskInstance $instance) => !$this->isFinalized($instance);

        $this->todayTasks = $instances
            ->filter(fn (KpiTaskInstance $instance) => $instance->period_type === 'daily' && $isOpen($instance))
            ->values();

        $this->weeklyTasks = $instances
            ->filter(fn (KpiTaskInstance $instance) => $instance->period_type === 'weekly' && $isOpen($instance))
            ->values();

        $this->monthlyTasks = $instances
            ->filter(fn (KpiTaskInstance $instance) => $instance->period_type === 'monthly' && $isOpen($instance))
            ->values();

        $this->overdueTasks = $instances
            ->filter(fn (KpiTaskInstance $instance) => $isOpen($instance) && $instance->due_at && Carbon::parse($instance->due_at)->lt(now()))
            ->values();

        $this->summaryCards = [
            [
                'label' => 'Today Tasks',
                'value' => $this->todayTasks->count(),
            ],
            [
                'label' => 'Open Weekly',
                'value' => $this->weeklyTasks->count(),
            ],
            [
                'label' => 'Open Monthly',
                'value' => $this->monthlyTasks->count(),
            ],
            [
                'label' => 'Overdue',
                'value' => $this->overdueTasks->count(),
            ],
        ];

        if ($this->selectedTaskInstanceId) {
            $selectedExists = $instances->contains(fn (KpiTaskInstance $instance) => $instance->id === $this->selectedTaskInstanceId);

            if (!$selectedExists) {
                $this->cancelSubmission();
            }
        }
    }

    public function openSubmission(int $taskInstanceId): void
    {
        $instance = $this->findOwnedInstance($taskInstanceId);
        $this->ensureSubmissionAllowed($instance);

        $this->selectedTaskInstanceId = $instance->id;
        $this->cameraPhoto = null;
        $this->galleryPhotos = [];
        $this->submissionPhotos = [];
        $this->submissionPhotoSources = [];
        $this->submissionPhotoTitles = [];
        $this->submissionPhotoRemarks = [];
        $this->submissionEmployeeRemark = '';
        $this->resetErrorBag();
    }

    public function cancelSubmission(): void
    {
        $this->selectedTaskInstanceId = null;
        $this->cameraPhoto = null;
        $this->galleryPhotos = [];
        $this->submissionPhotos = [];
        $this->submissionPhotoSources = [];
        $this->submissionPhotoTitles = [];
        $this->submissionPhotoRemarks = [];
        $this->submissionEmployeeRemark = '';
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
            $this->submissionPhotoTitles[$index],
            $this->submissionPhotoRemarks[$index]
        );

        $this->submissionPhotos = array_values($this->submissionPhotos);
        $this->submissionPhotoSources = array_values($this->submissionPhotoSources);
        $this->submissionPhotoTitles = array_values($this->submissionPhotoTitles);
        $this->submissionPhotoRemarks = array_values($this->submissionPhotoRemarks);
    }

    public function submitTask(KpiSubmissionImageResizer $resizer): void
    {
        $instance = $this->getSelectedTaskInstanceProperty();

        if (!$instance) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'Select a task before submitting.',
            ]);
        }

        $instance = $this->findOwnedInstance($instance->id);
        $this->ensureSubmissionAllowed($instance);

        $template = $instance->template;

        if (!$template || !$template->requires_images) {
            throw ValidationException::withMessages([
                'submissionPhotos' => 'This task is not configured for photo submission.',
            ]);
        }

        if ($template->requires_table) {
            throw ValidationException::withMessages([
                'submissionPhotos' => 'This task also requires custom table evidence. Table submission is not built yet.',
            ]);
        }

        $this->validate([
            'submissionPhotos' => ['array', 'max:20'],
            'submissionPhotos.*' => ['image', 'max:10240'],
            'submissionEmployeeRemark' => ['nullable', 'string'],
        ], [], [
            'submissionPhotos' => 'photos',
            'submissionPhotos.*' => 'photo',
            'submissionEmployeeRemark' => 'remark',
        ]);

        $photoCount = count($this->submissionPhotos);
        $minImages = (int) ($template->min_images ?? 0);
        $maxImages = $template->max_images !== null ? (int) $template->max_images : null;

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

        $storedPaths = [];

        try {
            DB::transaction(function () use ($instance, $template, $resizer, &$storedPaths): void {
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
                    $path = $resizer->store($photo, 300);
                    $storedPaths[] = $path;

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

        session()->flash('message', $template->title . ' submitted for approval.');
    }

    public function canSubmit(KpiTaskInstance $instance): bool
    {
        if (!$instance->template?->requires_images || $instance->template?->requires_table) {
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
                'submissions' => fn ($query) => $query->latest('sequence')->latest('id'),
            ])
            ->where('id', $this->selectedTaskInstanceId)
            ->where('user_id', Auth::id())
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
                'submissions' => fn ($query) => $query->latest('sequence')->latest('id'),
            ])
            ->where('id', $taskInstanceId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    protected function ensureSubmissionAllowed(KpiTaskInstance $instance): void
    {
        if (!$instance->template?->requires_images || $instance->template?->requires_table) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'This task does not support photo-only submission.',
            ]);
        }

        if ($this->isFinalized($instance)) {
            throw ValidationException::withMessages([
                'selectedTaskInstanceId' => 'This task is already finalized.',
            ]);
        }

        if ($this->submissionWindowClosed($instance)) {
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

    protected function submissionWindowClosed(KpiTaskInstance $instance): bool
    {
        $windowEnd = $this->submissionWindowEndsAt($instance);

        return $windowEnd ? now()->gt($windowEnd) : false;
    }

    protected function submissionWindowEndsAt(KpiTaskInstance $instance): ?Carbon
    {
        return match ($instance->period_type) {
            'daily' => $instance->task_date ? Carbon::parse($instance->task_date)->endOfDay() : null,
            default => $instance->period_end ? Carbon::parse($instance->period_end)->endOfDay() : null,
        };
    }

    public function isFinalized(KpiTaskInstance $instance): bool
    {
        return in_array($instance->status, ['passed', 'failed_late', 'failed_missed', 'excluded'], true);
    }

    protected function appendPhotos(array $photos, string $source): void
    {
        foreach ($photos as $photo) {
            if (!$photo instanceof TemporaryUploadedFile) {
                continue;
            }

            $this->submissionPhotos[] = $photo;
            $this->submissionPhotoSources[] = $source;
            $this->submissionPhotoTitles[] = '';
            $this->submissionPhotoRemarks[] = '';
        }
    }
}
