<?php

namespace App\Livewire\Orders;

use App\Models\CommentPool;
use App\Models\Supplier as SupplierModel;
use App\Models\SupplierData;
use App\Models\SupplierProduct;
use App\Models\SupplierProductPriceHistory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use PhpParser\Node\Expr\FuncCall;
use WireUi\Traits\Actions;

class Supplier extends Component
{
    use Actions;
    use WithPagination;
    //supplier model
    public $name = '';
    public $address = '';
    public $phone = '';
    public $error_rate = '';
    public $remark = '';

    //supplier  product model
    public $supplier_id;
    public $quality_id;
    public $design_id;
    public $detail;
    public $weight;
    public $weight_in_kpy;
    public $product_remark;
    //supplier product price model
    public $youktwat;
    public $youktwat_in_kpy;
    public $laukkha;
    //edit toggle
    public $edit_toggle = false;
    public $edit_id;
    //supplier name
    public $supplier;
    public $color = " ";

    public function update($id)
    {
        $this->edit_toggle = true;
        $this->edit_id = $id;

        $updateProductData = SupplierProduct::find($id);
        $this->supplier = $updateProductData->supplier->name;
        $this->quality_id = $updateProductData->quality_id;
        $this->design_id = $updateProductData->design_id;
        $this->weight = $updateProductData->weight;
        $this->detail =  $updateProductData->detail;
        $this->youktwat = $updateProductData->youktwat;
        $this->youktwat_in_kpy = $updateProductData->yuktwat_in_kpy;
        $this->laukkha =  $updateProductData->laukkha;
        $this->product_remark = $updateProductData->product_remark;
    }
    public function updateProduct($id)
    {
        $updateProductData = SupplierProduct::find($id);
        $updateProductData->youktwat = $this->youktwat;
        $updateProductData->yuktwat_in_kpy = $this->youktwat_in_kpy;
        $updateProductData->laukkha = $this->laukkha;
        $updateProductData->product_remark = $this->product_remark;
        $updateProductData->save();

        SupplierProductPriceHistory::create([
            'supplier_product_id' => $id,
            'youktwat' => $this->youktwat,
            'youktwat_in_kpy' => $this->youktwat_in_kpy,
            'laukkha' => $this->laukkha,
        ]);
        //toggle false
        $this->edit_toggle = false;
        $this->dispatch('close-modal');
        $this->reset('edit_id', 'supplier_id', 'quality_id', 'design_id', 'detail', 'weight',  'weight_in_kpy', 'product_remark', 'youktwat', 'youktwat_in_kpy', 'laukkha');

        $this->notification([
            'title'       => 'Success!',
            'description' => 'Updated successful',
            'icon'        => 'success'
        ]);
    }
    public function createSupplier()
    {
        $validate = $this->validate([
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required',
            'error_rate' => 'numeric',
            'remark' => '',
        ]);

        SupplierModel::create($validate);

        $this->dispatch('close-modal');
        $this->reset('name', 'address', 'phone', 'error_rate', 'remark');
        $this->notification([
            'title'       => 'Success!',
            'description' => 'Created successfully',
            'icon'        => 'success'
        ]);
    }
    public function createSupplierProduct()
    {
        $validate = $this->validate([
            'supplier_id' => 'required',
            'quality_id' =>  'required',
            'design_id' => 'required',
            'detail' => 'required',
            'color' => 'string',
            'weight' => 'required',
            'weight_in_kpy' => 'required',
            'product_remark' => 'required',
        ]);

        SupplierProduct::create(array_merge($validate, [
            'youktwat' => $this->youktwat,
            'youktwat_in_kpy' => $this->youktwat_in_kpy,
            'laukkha' => $this->laukkha,
        ]));

        if ($this->youktwat > 0 || $this->laukkha > 0) {
            $data = SupplierProduct::latest()->first();

            SupplierProductPriceHistory::create([
                'supplier_product_id' => $data->id,
                'youktwat' => $this->youktwat,
                'youktwat_in_kpy' => $this->youktwat_in_kpy,
                'laukkha' => $this->laukkha,
            ]);

            $this->reset('youktwat', 'youktwat_in_kpy', 'laukkha');
        }
        $this->reset('supplier_id', 'quality_id', 'design_id', 'detail', 'weight',  'weight_in_kpy', 'product_remark');
        $this->notification([
            'title'       => 'Success!',
            'description' => 'Created successfully',
            'icon'        => 'success'
        ]);
    }
    public function render()
    {

        if ($this->edit_toggle) {
            $updateProductData = SupplierProduct::find($this->edit_id);
        }
        return view('livewire.orders.supplier', [
            'relevantMeetingCount' => CommentPool::where('completed', 'false')->where('user_id', '=', Auth::user()->id)->count(),
            'agmMeetingCount' => CommentPool::where('completed', 'false')->count(),
            'suppliers' => SupplierModel::paginate(5),
            'supplierproducts' => SupplierProduct::get(),
            // 'updateProductData'  => $this->edit_toggle ? SupplierProduct::find($this->edit_id) : null,
        ]);
    }
}
