<?php

namespace App\Livewire\Kpi;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;


#[Layout('components.layouts.kpi')]
#[Title('KPI Manual')]
class Manual extends Component
{
    public function render()
    {
        return view('livewire.kpi.manual');
    }
}
