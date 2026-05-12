<?php

namespace App\Livewire\Operation\Branch;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.operation')]
#[Title('Branch Config')]
class BranchConfig extends Component
{
    public function render()
    {
        return view('livewire.operation.branch.branch-config');
    }
}

