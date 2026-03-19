<?php

namespace App\Livewire\Calendar;

use App\Models\User;
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
        $this->connected = $user && (!empty($user->google_refresh_token) || !empty($user->google_token));
    }

    public function render()
    {
        $connectedUsers = User::query()
            ->where(function ($query) {
                $query->whereNotNull('google_refresh_token')
                    ->orWhereNotNull('google_token');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'google_token_expires_at', 'updated_at']);

        return view('livewire.calendar.auto-sync', [
            'connectedUsers' => $connectedUsers,
        ]);
    }
}
