<?php

namespace App\Livewire\Calendar;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Calendar Auto Sync')]
class AutoSync extends Component
{
    public bool $connected = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->connected = $user && !empty($user->google_refresh_token);
    }

    public function render()
    {
        return view('livewire.calendar.auto-sync');
    }
}
