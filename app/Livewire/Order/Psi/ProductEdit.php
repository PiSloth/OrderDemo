<?php

namespace App\Livewire\Order\Psi;

use App\Models\Category;
use App\Models\Design;
use App\Models\ManufactureTechnique;
use App\Models\PsiProduct;
use App\Models\Quality;
use App\Models\Shape;
use App\Models\Uom;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\Actions;

class ProductEdit extends Component
{
    use WithFileUploads;
    use Actions;

    #[Url(as: 'selected')]
    public $id;

    public $product;
    public $photo;

    public $category_id;
    public $quality_id;
    public $design_id;
    public $weight;
    public $shape_id;
    public $uom_id;
    public $manufacture_technique_id;
    public $length;
    public $remark;
    public $is_suspended;

    public $show_more_toggle = false;

    public function mount()
    {
        $this->product = PsiProduct::with('productPhoto')->findOrFail($this->id);
        if ($this->product) {
            $this->category_id = $this->product->category_id;
            $this->quality_id = $this->product->quality_id;
            $this->design_id = $this->product->design_id;
            $this->weight = $this->product->weight;
            $this->shape_id = $this->product->shape_id;
            $this->uom_id = $this->product->uom_id;
            $this->manufacture_technique_id = $this->product->manufacture_technique_id;
            $this->length = $this->product->length;
            $this->remark = $this->product->remark;
            $this->is_suspended = $this->product->is_suspended;
        }
    }

    public function save()
    {
        $this->validate([
            'category_id' => 'required|exists:categories,id',
            'quality_id' => 'required|exists:qualities,id',
            'design_id' => 'required|exists:designs,id',
            'weight' => 'required|numeric',
            'shape_id' => 'required|exists:shapes,id',
            'uom_id' => 'required|exists:uoms,id',
            'manufacture_technique_id' => 'required|exists:manufacture_techniques,id',
            'length' => 'nullable|numeric',
            'remark' => 'nullable|string|max:255',
            'is_suspended' => 'boolean',
            'photo' => 'nullable|image|max:1024', // 1MB Max
        ]);

        DB::transaction(function () {
            if ($this->product) {
                $this->product->update([
                    'category_id' => $this->category_id,
                    'quality_id' => $this->quality_id,
                    'design_id' => $this->design_id,
                    'weight' => $this->weight,
                    'shape_id' => $this->shape_id,
                    'uom_id' => $this->uom_id,
                    'manufacture_technique_id' => $this->manufacture_technique_id,
                    'length' => $this->length,
                    'remark' => $this->remark,
                    'is_suspended' => $this->is_suspended,
                ]);
                if ($this->is_suspended) {
                    // if product is suspended, then set all branch products to suspended
                    $this->product->branchProducts()->update(['is_suspended' => true]);
                }
                if ($this->photo) {
                    $path = $this->photo->store('images', 'public');
                    $this->product->productPhoto()->updateOrCreate(
                        ['psi_product_id' => $this->product->id],
                        ['image' => $path]
                    );
                }
            }
        });

        // session()->flash('message', 'Product successfully updated.');
        $this->notification([
            'title'       => 'Success!',
            'description' => "Product updating Successed!",
            'icon'        => 'success'
        ]);

        return redirect()->to('/psi/mainboard');
    }


    #[Title('edit product')]
    public function render()
    {
        $categories = Category::all();
        $qualities = Quality::all();
        $designs = Design::all();
        $shapes = Shape::all();
        $uoms = Uom::all();
        $manufactureTechniques = ManufactureTechnique::all();

        return view('livewire.order.psi.product-edit', [
            'categories' => $categories,
            'qualities' => $qualities,
            'designs' => $designs,
            'shapes' => $shapes,
            'uoms' => $uoms,
            'manufactureTechniques' => $manufactureTechniques,
        ]);
    }
}
