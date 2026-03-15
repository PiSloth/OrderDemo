<?php

namespace App\Livewire\Whiteboard;

use App\Models\Department;
use App\Models\EmailList;
use App\Models\WhiteboardContentType;
use App\Models\WhiteboardFlag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.whiteboard')]
#[Title('Whiteboard Configuration')]
class Config extends Component
{
    public array $newContentType = [
        'name' => '',
        'color' => '#2563EB',
        'description' => '',
        'requires_decision' => false,
    ];

    public ?int $editContentTypeId = null;
    public array $editContentType = [
        'name' => '',
        'color' => '',
        'description' => '',
        'requires_decision' => false,
    ];

    public array $newFlag = [
        'name' => '',
        'color' => '#F59E0B',
        'description' => '',
    ];

    public ?int $editFlagId = null;
    public array $editFlag = [
        'name' => '',
        'color' => '',
        'description' => '',
    ];

    public array $newEmailList = [
        'user_name' => '',
        'email' => '',
        'department_id' => '',
    ];

    public ?int $editEmailListId = null;
    public array $editEmailList = [
        'user_name' => '',
        'email' => '',
        'department_id' => '',
    ];

    public function createContentType(): void
    {
        $validated = Validator::make($this->newContentType, [
            'name' => ['required', 'string', 'max:255', 'unique:whiteboard_content_types,name'],
            'color' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'requires_decision' => ['boolean'],
        ])->validate();

        WhiteboardContentType::query()->create($validated);
        $this->newContentType = ['name' => '', 'color' => '#2563EB', 'description' => '', 'requires_decision' => false];

        session()->flash('success', 'Content type created.');
    }

    public function editContentType(int $id): void
    {
        $row = WhiteboardContentType::query()->findOrFail($id);

        $this->editContentTypeId = $row->id;
        $this->editContentType = [
            'name' => $row->name,
            'color' => $row->color,
            'description' => (string) $row->description,
            'requires_decision' => (bool) $row->requires_decision,
        ];
    }

    public function updateContentType(): void
    {
        $validated = Validator::make($this->editContentType, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('whiteboard_content_types', 'name')->ignore($this->editContentTypeId),
            ],
            'color' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'requires_decision' => ['boolean'],
        ])->validate();

        WhiteboardContentType::query()->findOrFail($this->editContentTypeId)->update($validated);
        $this->cancelContentTypeEdit();

        session()->flash('success', 'Content type updated.');
    }

    public function deleteContentType(int $id): void
    {
        WhiteboardContentType::query()->findOrFail($id)->delete();
        $this->cancelContentTypeEdit();

        session()->flash('success', 'Content type deleted.');
    }

    public function cancelContentTypeEdit(): void
    {
        $this->editContentTypeId = null;
        $this->editContentType = ['name' => '', 'color' => '', 'description' => '', 'requires_decision' => false];
    }

    public function createFlag(): void
    {
        $validated = Validator::make($this->newFlag, [
            'name' => ['required', 'string', 'max:255', 'unique:whiteboard_flags,name'],
            'color' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
        ])->validate();

        WhiteboardFlag::query()->create($validated);
        $this->newFlag = ['name' => '', 'color' => '#F59E0B', 'description' => ''];

        session()->flash('success', 'Flag created.');
    }

    public function editFlag(int $id): void
    {
        $row = WhiteboardFlag::query()->findOrFail($id);

        $this->editFlagId = $row->id;
        $this->editFlag = [
            'name' => $row->name,
            'color' => $row->color,
            'description' => (string) $row->description,
        ];
    }

    public function updateFlag(): void
    {
        $validated = Validator::make($this->editFlag, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('whiteboard_flags', 'name')->ignore($this->editFlagId),
            ],
            'color' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
        ])->validate();

        WhiteboardFlag::query()->findOrFail($this->editFlagId)->update($validated);
        $this->cancelFlagEdit();

        session()->flash('success', 'Flag updated.');
    }

    public function deleteFlag(int $id): void
    {
        WhiteboardFlag::query()->findOrFail($id)->delete();
        $this->cancelFlagEdit();

        session()->flash('success', 'Flag deleted.');
    }

    public function cancelFlagEdit(): void
    {
        $this->editFlagId = null;
        $this->editFlag = ['name' => '', 'color' => '', 'description' => ''];
    }

    public function createEmailList(): void
    {
        $validated = Validator::make($this->newEmailList, [
            'user_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:email_lists,email'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ])->validate();

        EmailList::query()->create($validated);
        $this->newEmailList = ['user_name' => '', 'email' => '', 'department_id' => ''];

        session()->flash('success', 'Email list entry created.');
    }

    public function editEmailList(int $id): void
    {
        $row = EmailList::query()->findOrFail($id);

        $this->editEmailListId = $row->id;
        $this->editEmailList = [
            'user_name' => $row->user_name,
            'email' => $row->email,
            'department_id' => (string) $row->department_id,
        ];
    }

    public function updateEmailList(): void
    {
        $validated = Validator::make($this->editEmailList, [
            'user_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('email_lists', 'email')->ignore($this->editEmailListId),
            ],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ])->validate();

        EmailList::query()->findOrFail($this->editEmailListId)->update($validated);
        $this->cancelEmailListEdit();

        session()->flash('success', 'Email list entry updated.');
    }

    public function deleteEmailList(int $id): void
    {
        EmailList::query()->findOrFail($id)->delete();
        $this->cancelEmailListEdit();

        session()->flash('success', 'Email list entry archived.');
    }

    public function cancelEmailListEdit(): void
    {
        $this->editEmailListId = null;
        $this->editEmailList = ['user_name' => '', 'email' => '', 'department_id' => ''];
    }

    public function render()
    {
        return view('livewire.whiteboard.config', [
            'contentTypes' => WhiteboardContentType::query()->orderBy('name')->get(),
            'flags' => WhiteboardFlag::query()->orderBy('name')->get(),
            'emailLists' => EmailList::query()->with('department')->orderBy('user_name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }
}
