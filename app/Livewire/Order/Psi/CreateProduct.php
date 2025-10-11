<?php

namespace App\Livewire\Order\Psi;

use App\Models\Category;
use App\Models\Design;
use App\Models\ManufactureTechnique;
use App\Models\ProductPhoto;
use App\Models\PsiProduct;
use App\Models\Quality;
use App\Models\Shape;
use App\Models\Uom;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\Actions;

class CreateProduct extends Component
{
    use WithFileUploads;
    use Actions;

    #[Title('Create Signature Product')]
    public $xSelectInput; //wireui input
    public $productImg;
    public $category_id;
    public $quality_id;
    public $design_id;
    public $weight;
    public $shape_id;
    public $uom_id;
    public $manufacture_technique_id;
    public $length;
    public $remark; // Added remark property

    public function createShape()
    {
        Shape::create([
            'name' => $this->xSelectInput,
        ]);

        $this->notification()->success(
            $title = 'Created',
            $description = 'Successfully created!'
        );
    }

    public function createProduct()
    {
        $validated = $this->validate([
            'category_id' => 'required|exists:categories,id',
            'design_id' => 'required|exists:designs,id',
            'weight' => 'required|numeric',
            'length' => 'nullable|numeric',
            'uom_id' => 'required|exists:uoms,id',
            'shape_id' =>  'required|exists:shapes,id',
            'quality_id' => 'required|exists:qualities,id',
            'manufacture_technique_id' => 'required|exists:manufacture_techniques,id',
            'remark' => 'nullable|string|max:255',

        ]);

        DB::transaction(function () use ($validated) {
            $product = PsiProduct::create(array_merge(
                ['user_id' => auth()->user()->id],
                $validated
            ));

            $path = $this->productImg->store('images', 'public');
            ProductPhoto::create([
                'image' => $path,
                'psi_product_id' => $product->id,
            ]);
        });

        $this->notification([
            'title'       => 'Success!',
            'description' => "Product Creation Successful!",
            'icon'        => 'success'
        ]);

        $this->reset();
    }

    public function render()
    {
        return view('livewire.order.psi.create-product', [
            'uoms' => Uom::all(),
            'categories' => Category::all(),
            'qualities' => Quality::all(),
            'designs' => Design::all(),
            'shapes' => Shape::all(),
            'techniques' => ManufactureTechnique::all(),
        ]);
    }
}
