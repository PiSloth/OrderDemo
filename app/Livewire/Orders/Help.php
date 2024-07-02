<?php

namespace App\Livewire\Orders;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\CommentPool;

class Help extends Component
{
    public function render()
    {
        return view('livewire.orders.help', [
            'relevantMeetingCount' => CommentPool::where('completed', 'false')->where('user_id', '=', Auth::user()->id)->count(),
            'agmMeetingCount' => CommentPool::where('completed', 'false')->count(),
        ]);
    }
}
