<?php

namespace App\Livewire\Document\EmailList;

use App\Models\EmailList;
use App\Models\Department;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Create extends Component
{
    public string $user_name = '';
    public string $email = '';
    public string $department_id = '';

    public $departments;

    public function mount(): void
    {
        $this->departments = Department::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:email_lists,email'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        EmailList::create($validated);

        session()->flash('success', 'Email entry created.');
        $this->redirectRoute('document.email-list.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.document.email-list.create');
    }
}
