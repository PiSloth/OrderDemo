<?php

namespace App\Livewire\Order\Psi;

use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProductEdit extends Component
{
    #[Url(as: 'selected')]
    public $id;

    public $category_id;
    public $quality_id;
    public $design_id;
    public $weight;
    public $shape_id;
    public $uom_id;
    public $manufacture_technique_id;
    public $length;
    public $show_more_toggle = false;

    public function mount() {}


    #[Title('edit product')]
    public function render()
    {
        return view('livewire.order.psi.product-edit');
    }
}
