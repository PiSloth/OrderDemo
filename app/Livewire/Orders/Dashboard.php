<?php

namespace App\Livewire\Orders;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

// #[Layout('layout.app')]
#[Title('dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        // dd(auth()->user()->id);
        return view('livewire.orders.dashboard')->layout('layouts.app');
    }
}
