<?php

namespace App\Livewire\Order\Psi;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Design;
use App\Models\Grade;
use App\Models\Images;
use App\Models\ManufactureTechnique;
use App\Models\Priority;
use App\Models\ProductPhoto;
use App\Models\PsiProduct;
use App\Models\Quality;
use App\Models\Shape;
use App\Models\Uom;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use WireUi\Traits\Actions;

class CreateProduct extends Component
{
    use WithFileUploads;
    use Actions;
    #[Title('Create Signature Product')]

    public $xSelectInput; //wireui input
    #[Rule('nullable|sometimes|image|max:1024')]
    public $productImg;

    public $category_id;
    public $quality_id;
    public $design_id;
    public $weight;
    public $shape_id;
    public $uom_id;
    public $manufacture_technique_id;
    public $length;
    public $show_more_toggle = false;


    //for checking select div
    public $emptyPriority;
    public $emptyGrade;
    public $emptyQuality;
    public $emptyCategory;
    public $emptyDesign;
    public $emptyBranch;


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
            'category_id' => 'required',
            'design_id' => 'required',
            'weight' => 'required',
            'length' => 'required',
            'uom_id' => 'required',
            'shape_id' =>  'required',
            'quality_id' => 'required',
            'manufacture_technique_id' => 'required',

        ]);
        DB::transaction(function () use ($validated) {

            $product = PsiProduct::create(array_merge(
                ['user_id' => auth()->user()->id],
                $validated
            ));

            // todo Product Photo binding
            $this->validate([
                'productImg' => 'required|max:1024'
            ]);

            $path = $this->productImg->store('images', 'public');
            ProductPhoto::create([
                'image' => $path,
                'psi_product_id' => $product->id,
            ]);

            $this->reset('productImg');
        });

        $this->notification([
            'title'       => 'Success!',
            'description' => "Product Creation Successed!",
            'icon'        => 'success'
        ]);
    }


    public function render()
    {

        $quality = Quality::where('id', $this->quality_id)->get();
        if (!$this->quality_id) {
            $this->emptyQuality = "ရွှေရည် သတ်မှတ်ပါ";
        } else {
            $this->emptyQuality = '';
        }

        $category = Category::where('id', $this->category_id)->get();
        if (!$this->category_id) {
            $this->emptyCategory = "Prodcut အမျိုးအစား ခွဲခြားပါ";
        } else {
            $this->emptyCategory = '';
        }

        $design = Design::where('id', $this->design_id)->get();
        if (!$this->design_id) {
            $this->emptyDesign = "ဒီဇိုင်းကာလာ သတ်မှတ်ပါ";
        } else {
            $this->emptyDesign = '';
        }



        return view('livewire.order.psi.create-product', [
            'priorities' => Priority::all(),
            'uoms' => Uom::all(),
            'grades' => Grade::all(),
            'categories' => Category::all(),
            'qualities' => Quality::all(),
            'designs' => Design::all(),
            // 'priority_item' => $priority,
            'quality_item' => $quality,
            'design_item' => $design,
            'shapes' => Shape::all(),
            'category_item' => $category,
            'techniques' => ManufactureTechnique::all(),
        ]);
    }
}
