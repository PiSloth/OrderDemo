<?php

namespace App\Livewire\Document;

use App\Models\Department;
use App\Models\EmailTag;
use App\Models\EmailList as EmailListModel;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Spatie\SimpleExcel\SimpleExcelReader;

#[Layout('components.layouts.app')]
class EmailList extends Component
{
    use WithPagination;
    use WithFileUploads;

    #[Url]
    public string $search = '';

    #[Url]
    public string $department_id = '';

    #[Url]
    public string $tag_id = '';

    #[Url]
    public bool $archived = false;

    /**
     * Recipient builders for Outlook copy/paste.
     * These are not URL-bound on purpose.
     */
    public array $toEmails = [];
    public array $ccEmails = [];

    // Tag CRUD + assignment
    public $tags;
    public string $new_tag_name = '';
    public ?int $editTagId = null;
    public string $edit_tag_name = '';

    // Create form fields
    public string $new_user_name = '';
    public string $new_email = '';
    public string $new_department_id = '';

    // Edit modal fields
    public ?int $editId = null;
    public string $edit_user_name = '';
    public string $edit_email = '';
    public string $edit_department_id = '';

    public $departments;

    // Import
    public $importFile;
    public array $importErrors = [];
    public int $importedCount = 0;
    public int $updatedCount = 0;

    public function mount(): void
    {
        $this->departments = Department::query()->orderBy('name')->get();
        $this->refreshTags();
    }

    public function import(): void
    {
        $this->resetValidation();
        $this->importErrors = [];
        $this->importedCount = 0;
        $this->updatedCount = 0;

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $path = method_exists($this->importFile, 'getRealPath') ? $this->importFile->getRealPath() : null;
        $path = $path ?: (method_exists($this->importFile, 'getPathname') ? $this->importFile->getPathname() : null);

        if (!$path) {
            $this->addError('importFile', 'Could not read uploaded file.');
            return;
        }

        $reader = SimpleExcelReader::create($path);

        $rowNumber = 1; // header is not included in rows, but keep human-friendly count
        foreach ($reader->getRows() as $rawRow) {
            $rowNumber++;

            $row = [];
            foreach (($rawRow ?? []) as $key => $value) {
                $normalizedKey = strtolower(trim((string) $key));
                $row[$normalizedKey] = is_string($value) ? trim($value) : $value;
            }

            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->importErrors[] = "Row {$rowNumber}: invalid or missing email.";
                continue;
            }

            $userName = trim((string) ($row['user_name'] ?? $row['name'] ?? ''));
            if ($userName === '') {
                $userName = strstr($email, '@', true) ?: $email;
            }

            $departmentId = null;
            $departmentIdRaw = $row['department_id'] ?? null;
            if ($departmentIdRaw !== null && $departmentIdRaw !== '') {
                $departmentId = (int) $departmentIdRaw;
            } else {
                $departmentName = trim((string) ($row['department'] ?? ''));
                if ($departmentName !== '') {
                    $departmentId = Department::query()
                        ->whereRaw('LOWER(name) = ?', [strtolower($departmentName)])
                        ->value('id');
                }
            }

            if (!$departmentId) {
                $this->importErrors[] = "Row {$rowNumber}: missing department (use department_id or department name).";
                continue;
            }

            $existing = EmailListModel::withTrashed()->where('email', $email)->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update([
                    'user_name' => $userName,
                    'department_id' => $departmentId,
                ]);
                $model = $existing;
                $this->updatedCount++;
            } else {
                $model = EmailListModel::create([
                    'user_name' => $userName,
                    'email' => $email,
                    'department_id' => $departmentId,
                ]);
                $this->importedCount++;
            }

            $tagsRaw = (string) ($row['tags'] ?? $row['tag'] ?? '');
            if (trim($tagsRaw) !== '') {
                $tagNames = preg_split('/[;,|]/', $tagsRaw) ?: [];
                $tagIds = [];
                foreach ($tagNames as $tagName) {
                    $tagName = trim((string) $tagName);
                    if ($tagName === '') {
                        continue;
                    }

                    $tag = EmailTag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }

                if (!empty($tagIds)) {
                    $model->tags()->syncWithoutDetaching($tagIds);
                    $this->refreshTags();
                }
            }
        }

        $this->importFile = null;
        $this->resetPage();
        session()->flash('success', "Import completed. Imported {$this->importedCount}, updated {$this->updatedCount}.");
    }

    private function refreshTags(): void
    {
        $this->tags = EmailTag::query()->withCount('emailLists')->orderBy('name')->get();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentId(): void
    {
        $this->resetPage();
        $this->closeEdit();
    }

    public function updatedTagId(): void
    {
        $this->resetPage();
        $this->closeEdit();
    }

    public function updatedArchived(): void
    {
        $this->resetPage();
        $this->closeEdit();
    }

    public function startCreate(): void
    {
        $this->resetValidation();
        $this->new_user_name = '';
        $this->new_email = '';
        $this->new_department_id = '';
    }

    public function create(): void
    {
        $validated = $this->validate([
            'new_user_name' => ['required', 'string', 'max:255'],
            'new_email' => ['required', 'string', 'email', 'max:255', 'unique:email_lists,email'],
            'new_department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        EmailListModel::create([
            'user_name' => $validated['new_user_name'],
            'email' => $validated['new_email'],
            'department_id' => $validated['new_department_id'],
        ]);

        session()->flash('success', 'Email entry created.');
        $this->startCreate();
    }

    public function openEdit(int $id): void
    {
        $this->resetValidation();
        $row = EmailListModel::query()->findOrFail($id);

        $this->editId = $row->id;
        $this->edit_user_name = $row->user_name;
        $this->edit_email = $row->email;
        $this->edit_department_id = (string) $row->department_id;
    }

    public function update(): void
    {
        $validated = $this->validate([
            'edit_user_name' => ['required', 'string', 'max:255'],
            'edit_email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('email_lists', 'email')->ignore($this->editId),
            ],
            'edit_department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        EmailListModel::query()->findOrFail($this->editId)->update([
            'user_name' => $validated['edit_user_name'],
            'email' => $validated['edit_email'],
            'department_id' => $validated['edit_department_id'],
        ]);

        session()->flash('success', 'Email entry updated.');
        $this->dispatch('close-edit-modal');
        $this->closeEdit();
    }

    public function closeEdit(): void
    {
        $this->editId = null;
        $this->edit_user_name = '';
        $this->edit_email = '';
        $this->edit_department_id = '';
    }

    public function archive(int $id): void
    {
        EmailListModel::query()->findOrFail($id)->delete();
        session()->flash('success', 'Email entry archived.');
        $this->closeEdit();
    }

    public function restore(int $id): void
    {
        EmailListModel::withTrashed()->findOrFail($id)->restore();
        session()->flash('success', 'Email entry restored.');
        $this->closeEdit();
    }

    public function deletePermanently(int $id): void
    {
        EmailListModel::withTrashed()->findOrFail($id)->forceDelete();
        session()->flash('success', 'Email entry deleted permanently.');
        $this->closeEdit();
    }

    public function createTag(): void
    {
        $validated = $this->validate([
            'new_tag_name' => ['required', 'string', 'max:50', 'unique:email_tags,name'],
        ]);

        EmailTag::create([
            'name' => trim($validated['new_tag_name']),
        ]);

        $this->new_tag_name = '';
        $this->refreshTags();
        session()->flash('success', 'Tag created.');
    }

    public function openEditTag(int $id): void
    {
        $this->resetValidation();
        $tag = EmailTag::query()->findOrFail($id);
        $this->editTagId = $tag->id;
        $this->edit_tag_name = $tag->name;
    }

    public function cancelEditTag(): void
    {
        $this->editTagId = null;
        $this->edit_tag_name = '';
    }

    public function updateTag(): void
    {
        $validated = $this->validate([
            'edit_tag_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('email_tags', 'name')->ignore($this->editTagId),
            ],
        ]);

        EmailTag::query()->findOrFail($this->editTagId)->update([
            'name' => trim($validated['edit_tag_name']),
        ]);

        $this->cancelEditTag();
        $this->refreshTags();
        session()->flash('success', 'Tag updated.');
    }

    public function deleteTag(int $id): void
    {
        EmailTag::query()->findOrFail($id)->delete();
        $this->refreshTags();
        session()->flash('success', 'Tag deleted.');
    }

    public function attachTag(int $emailListId, $tagId): void
    {
        $tagId = (int) $tagId;
        if ($tagId <= 0) {
            return;
        }

        $emailList = EmailListModel::withTrashed()->findOrFail($emailListId);
        $tag = EmailTag::query()->findOrFail($tagId);
        $emailList->tags()->syncWithoutDetaching([$tag->id]);
        $this->refreshTags();
    }

    public function detachTag(int $emailListId, int $tagId): void
    {
        $emailList = EmailListModel::withTrashed()->findOrFail($emailListId);
        $emailList->tags()->detach($tagId);
        $this->refreshTags();
    }

    public function addToByTag(int $tagId): void
    {
        $emails = EmailListModel::query()
            ->whereHas('tags', fn($q) => $q->where('email_tags.id', $tagId))
            ->pluck('email')
            ->all();

        foreach ($emails as $email) {
            $this->addRecipient('toEmails', $email);
        }
    }

    public function addCcByTag(int $tagId): void
    {
        $emails = EmailListModel::query()
            ->whereHas('tags', fn($q) => $q->where('email_tags.id', $tagId))
            ->pluck('email')
            ->all();

        foreach ($emails as $email) {
            $this->addRecipient('ccEmails', $email);
        }
    }

    public function addToById(int $id): void
    {
        $email = EmailListModel::withTrashed()->findOrFail($id)->email;
        $this->addRecipient('toEmails', $email);
    }

    public function addCcById(int $id): void
    {
        $email = EmailListModel::withTrashed()->findOrFail($id)->email;
        $this->addRecipient('ccEmails', $email);
    }

    public function removeTo(string $email): void
    {
        $this->toEmails = array_values(array_filter($this->toEmails, fn($e) => $e !== $email));
    }

    public function removeCc(string $email): void
    {
        $this->ccEmails = array_values(array_filter($this->ccEmails, fn($e) => $e !== $email));
    }

    public function clearTo(): void
    {
        $this->toEmails = [];
    }

    public function clearCc(): void
    {
        $this->ccEmails = [];
    }

    private function addRecipient(string $listProperty, string $email): void
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if (!property_exists($this, $listProperty) || !is_array($this->{$listProperty})) {
            return;
        }

        if (!in_array($email, $this->{$listProperty}, true)) {
            $this->{$listProperty}[] = $email;
        }
    }

    public function render()
    {
        $query = EmailListModel::query()->with(['department', 'tags']);

        if ($this->archived) {
            $query->onlyTrashed();
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->department_id !== '') {
            $query->where('department_id', $this->department_id);
        }

        if ($this->tag_id !== '') {
            $query->whereHas('tags', fn($q) => $q->where('email_tags.id', $this->tag_id));
        }

        $emailLists = $query->orderBy('user_name')->paginate(20);

        return view('livewire.document.email-list', [
            'emailLists' => $emailLists,
            'tags' => $this->tags,
        ]);
    }
}
