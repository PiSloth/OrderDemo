<?php

namespace App\Livewire\Operation\IT\Issue;

use App\IssueTracking\Models\Issue;
use App\IssueTracking\Models\IssueCategory;
use App\IssueTracking\Models\IssueImportanceLevel;
use App\IssueTracking\Models\IssuePriority;
use App\IssueTracking\Models\IssueStatus;
use App\Models\Department;
use App\Models\User;
use App\Services\Operation\IssueImageResizer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.operation')]
#[Title('Create Issue')]
class Create extends Component
{
    use WithFileUploads;

    public string $activeTab = 'erp';
    public bool $showForm = true;

    public string $title = '';
    public string $description = '';
    public ?int $issue_category_id = null;
    public ?int $issue_by_user_id = null;
    public $cameraPhoto = null;
    public array $galleryPhotos = [];
    public array $submissionPhotos = [];
    public array $submissionPhotoSources = [];

    public function mount(): void
    {
        $this->issue_by_user_id = auth()->id();
    }

    public function updatedCameraPhoto(): void
    {
        $this->validate([
            'cameraPhoto' => ['nullable', 'image', 'max:10240'],
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

        unset($this->submissionPhotos[$index], $this->submissionPhotoSources[$index]);
        $this->submissionPhotos = array_values($this->submissionPhotos);
        $this->submissionPhotoSources = array_values($this->submissionPhotoSources);
    }

    public function save(IssueImageResizer $resizer): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'issue_category_id' => ['required', 'exists:issue_categories,id'],
            'issue_by_user_id' => ['required', 'exists:users,id'],
            'submissionPhotos' => ['array', 'max:4'],
            'submissionPhotos.*' => ['image', 'max:10240'],
        ]);

        $openStatus = IssueStatus::query()->where('code', 'OPEN')->firstOrFail();
        $defaultPriority = IssuePriority::query()->orderBy('level')->firstOrFail();
        $defaultImportance = IssueImportanceLevel::query()->orderBy('level')->firstOrFail();
        $issueByUser = User::query()->findOrFail($this->issue_by_user_id);
        $itDepartment = Department::query()
            ->where('name', 'like', '%IT%')
            ->first();

        if (!$itDepartment) {
            $this->addError('issue_category_id', 'IT department is required. Please create IT department first.');
            return;
        }

        $issue = Issue::query()->create([
            'title' => $this->title,
            'description' => $this->description,
            'issue_category_id' => $this->issue_category_id,
            'issue_priority_id' => $defaultPriority->id,
            'issue_importance_id' => $defaultImportance->id,
            'issue_by' => $issueByUser->name,
            'issue_at' => now(),
            'created_by' => auth()->id(),
            'resolution_department_id' => $itDepartment->id,
            'issue_status_id' => $openStatus->id,
            'follow_up_interval' => 1,
        ]);

        $issue->statusHistories()->create(['issue_status_id' => $openStatus->id, 'changed_by' => auth()->id()]);
        $issue->activityLogs()->create(['action' => 'created', 'description' => 'Issue created', 'performed_by' => auth()->id()]);

        foreach ($this->submissionPhotos as $image) {
            if ($image instanceof TemporaryUploadedFile) {
                $issue->images()->create(['image_path' => $resizer->store($image)]);
            }
        }

        session()->flash('message', 'Issue created successfully.');
        $this->redirectRoute('operation.it.issues.index', navigate: true);
    }

    protected function appendPhotos(array $photos, string $source): void
    {
        foreach ($photos as $photo) {
            if (!$photo instanceof TemporaryUploadedFile) {
                continue;
            }

            if (count($this->submissionPhotos) >= 4) {
                $this->addError('submissionPhotos', 'Maximum 4 images.');
                break;
            }

            $this->submissionPhotos[] = $photo;
            $this->submissionPhotoSources[] = $source;
        }
    }

    public function render()
    {
        return view('livewire.operation.it.issue.create', [
            'erpCategories' => IssueCategory::where('is_erp', true)->orderBy('name')->get(),
            'itCategories' => IssueCategory::where('is_erp', false)->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
