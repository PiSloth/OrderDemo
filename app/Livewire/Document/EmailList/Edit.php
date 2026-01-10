<?php

namespace App\Livewire\Document\EmailList;

use App\Models\EmailList;
use App\Models\Department;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Edit extends Component
{
    public EmailList $emailList;

    public string $user_name = '';
    public string $email = '';
    public string $department_id = '';

    public $departments;

    public function mount(EmailList $emailList): void
    {
        $this->emailList = $emailList;
        $this->user_name = $emailList->user_name;
        $this->email = $emailList->email;
        $this->department_id = (string) $emailList->department_id;

        $this->departments = Department::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('email_lists', 'email')->ignore($this->emailList->id),
            ],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        $this->emailList->update($validated);

        session()->flash('success', 'Email entry updated.');
        $this->redirectRoute('document.email-list.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.document.email-list.edit');
    }
}
