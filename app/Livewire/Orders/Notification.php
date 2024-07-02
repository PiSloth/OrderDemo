<?php

namespace App\Livewire\Orders;

use App\Models\Notification as ModelsNotification;
use Livewire\Component;

class Notification extends Component
{
    public function render()
    {
        return view('livewire.orders.notification',[
            'notis' => ModelsNotification::latest()->get(),
        ]);
    }
}
