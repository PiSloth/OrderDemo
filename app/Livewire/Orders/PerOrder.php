<?php

namespace App\Livewire\Orders;

use App\Models\ApprovedOrder;
use App\Models\Order;
use App\Models\CommentPool;
use App\Models\Notification;
use App\Models\SupplierProduct;
use App\Models\Comment;
use App\Models\Images;
use App\Models\OrderHistory;
use App\Models\Reply;
use App\Models\RequestedOrder;
use App\Models\SupplierProductPriceHistory;
use Exception;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use WireUi\Traits\Actions;
use Illuminate\Support\Facades\Auth;

class PerOrder extends Component
{
    use Actions;


    #[Url()]
    public $order_id;
    #[Url()]
    public $comment_toggle;

    public $supplierProductToggle = 0;
    public $previousSupplierToggle;
    public $content;
    public $reply_content;
    public $estimatetime;
    public $instockqty;
    public $assign_id;
    public $editqty;
    public $arqty;
    public $closeqty;
    public $reply_toggle;
    public $commentId;
    public $cancel_reason;
    #[Rule('required')]
    public $i_title;
    //permission
    protected $invUser;
    protected $approver;
    protected $creator;
    protected $purchaser;
    //approve data
    public $selected_approved_supplier;
    public $approved_note;
    public $to_order_date;


    //supplier  product model
    public $supplier_id;
    public $quality_id;
    public $design_id;
    public $detail;
    public $color;
    public $weight;
    public $weight_in_kpy;
    public $product_remark;
    public $min_ar_date;
    public $max_ar_date;
    public $remark;
    //supplier product price model
    public $youktwat;
    public $youktwat_in_kpy;
    public $laukkha;

    //supplier name
    public $supplier;
    //reject supplier_product_data
    public $rejectSupplierProduct_id;
    public $reject_note;
    public $supplier_data_serarch;

    //edit supplier product data
    public $editSupplierProductMode;
    public $supplier_product_id;

    public function boot()
    {
        $user_position = auth()->user()->position->name;

        $this->invUser = false;
        if (in_array($user_position, ["Super Admin", "Purchaser", "Inventory"])) {
            $this->invUser = true;
        }

        $this->approver = false;
        if (in_array($user_position, ["AGM", "Super Admin", "Purchaser","Inventory"])) {
            $this->approver = true;
        }

        $this->creator = false;
        if (in_array($user_position, ["AGM", "Supervisor", "Super Admin", "Purchaser"])) {
            $this->creator = true;
        }

        $this->purchaser = false;
        if (in_array($user_position, [ "Super Admin", "Purchaser"])) {
            $this->purchaser = true;
        }
    }

    //add supplier product toggle
    public function supplierToggle() {
        if($this->supplierProductToggle == 0) {
            $this->supplierProductToggle = 1;
        } else {
            $this->supplierProductToggle = 0;
        }
    }

    //show previous supplier product toggle
    public function previousToggle(){
        if($this->previousSupplierToggle){
            $this->previousSupplierToggle = null;
        }else {
        $this->previousSupplierToggle = true;
        }
    }

    //add requested order table for approver
    public function addRequestedOrder($supId){
        RequestedOrder::create([
            'supplier_product_id' => $supId,
            'order_id' => $this->order_id,
        ]);
    }

    //remove order from requested order table
    public function removeRequestedOrder($id){
        RequestedOrder::find($id)->delete();
    }

    // Reject Supplier Product Data in relevent
    public function rejectSupplierProduct($id){
        $this->rejectSupplierProduct_id = $id;
    }

    //reject supplier data with modal
    public function rejectSupplierData(){
        $this->validate([
            'reject_note' => 'required'
        ]);
        try{
            $query = SupplierProduct::find($this->rejectSupplierProduct_id);
            $query ->is_reject = true;
            $query ->reject_note = $this->reject_note;
            $query -> save();
            $this->dispatch('close-modal');
            $this->reset('reject_note','rejectSupplierProduct_id');
            $this->notification([
                'title'       => 'Success!',
                'description' => 'Rejected this data',
                'icon'        => 'success'
            ]);
        }catch(Exception $e){
            $this->notification([
                'title'       => 'Success!',
                'description' => $e,
                'icon'        => 'success'
            ]);
            $this->dispatch('close-modal');

        }
    }

//create supplier product by purchaser or requester
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
            'min_ar_date' => 'required',
            'max_ar_date' => 'required'
        ]);
try{
    SupplierProduct::create(array_merge($validate, [
        'youktwat' => $this->youktwat,
        'youktwat_in_kpy' => $this->youktwat_in_kpy,
        'laukkha' => $this->laukkha,
        'remark' => $this->remark,
    ]));
}catch (Exception $e){

}

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

        RequestedOrder::create([
            'order_id' => $this->order_id,
            'supplier_product_id' => SupplierProduct::latest()->first()->id
        ]);

        $this->reset('supplier_id', 'quality_id', 'design_id', 'detail', 'weight',  'weight_in_kpy', 'product_remark','remark', 'min_ar_date', 'max_ar_date');


        $this->notification([
            'title'       => 'Success!',
            'description' => 'Created successfully',
            'icon'        => 'success'
        ]);
    }

    //cancel editSupplier product
    public function cancelEditSupplierProduct(){
        $this->supplierProductToggle = 0;
        $this->editSupplierProductMode = null;

        $this->reset('supplier_id', 'quality_id', 'design_id', 'detail', 'weight',  'weight_in_kpy', 'product_remark','remark','min_ar_date','max_ar_date');
        $this->reset('youktwat', 'youktwat_in_kpy', 'laukkha');

    }
    //edit supplier product by purchaser or requester
    public function editSupplierProduct($id){
        //toggle enable supplier add
        $this->supplierProductToggle = 1;
        $this->editSupplierProductMode = true;
        $this->supplier_product_id = $id;
        //find and set related supplier product item

        $query = SupplierProduct::find($id);
        $this->supplier_id = $query->supplier_id;
        $this-> quality_id = $query->quality_id;
        $this->design_id = $query->design_id;
        $this->detail = $query->detail;
        $this->color = $query->color;
        $this->weight= $query->weight;
        $this->weight_in_kpy = $query->weight_in_kpy;
        $this->product_remark = $query->product_remark;
        $this->youktwat = $query->youktwat;
        $this->youktwat_in_kpy = $query->youktwat_in_kpy;
        $this->laukkha = $query->laukkha;
        $this->remark = $query->remark;
        $this->min_ar_date = $query->min_ar_date;
        $this->max_ar_date = $query->max_ar_date;
    }

    public function updateSupplierProduct(){
        $this->validate([
            'supplier_id' => 'required',
            'quality_id' =>  'required',
            'design_id' => 'required',
            'detail' => 'required',
            'color' => 'string',
            'weight' => 'required',
            'weight_in_kpy' => 'required',
            'product_remark' => 'required',
        ]);

        //update query
        try{
            $query = SupplierProduct::find($this->supplier_product_id);
            $query->supplier_id = $this->supplier_id;
            $query-> quality_id = $this->quality_id;
            $query->design_id = $this->design_id;
            $query->detail = $this->detail;
            $query->color = $this->color;
            $query->weight= $this->weight;
            $query->weight_in_kpy = $this->weight_in_kpy;
            $query->product_remark = $this->product_remark;
            $query->youktwat = $this->youktwat;
            $query->youktwat_in_kpy = $this->youktwat_in_kpy;
            $query->laukkha = $this->laukkha;
            $query->remark = $this->remark;
            $query->min_ar_date = $this->min_ar_date;
            $query->max_ar_date = $this->max_ar_date;
            $query -> save();

            if ($this->youktwat > 0 || $this->laukkha > 0) {
                $data = SupplierProduct::latest()->first();

                SupplierProductPriceHistory::create([
                    'supplier_product_id' => $this->supplier_product_id,
                    'youktwat' => $this->youktwat,
                    'youktwat_in_kpy' => $this->youktwat_in_kpy,
                    'laukkha' => $this->laukkha,
                ]);
            }
            $this->cancelEditSupplierProduct();
            $this->notification([
                'title'       => 'Successfully!',
                'description' => 'Successfully updated to this order',
                'icon'        => 'success'
            ]);


        } catch(Exception $e){
            $this->notification([
                'title'       => 'Error!',
                'description' => 'Error while updated to this order',
                'icon'        => 'error'
            ]);
        }


    }


    //reply toggle for selected comment
    public function reply_to_comment($cmtId)
    {
        if ($this->reply_toggle == $cmtId) {
            $this->reset('reply_toggle');
        } else {
            $this->reply_toggle = $cmtId;
        }
    }

    //create comment
    public function create_comment($ordId)
    {
        $validated = $this->validate([
            'content' => 'required',
        ]);
        Comment::create([
            'content' => $this->content,
            'user_id' => auth()->user()->id,
            'order_id' => $ordId,
        ]);
        $this->reset('content');
    }

    //create i meeting
    public function create_pool($ordId)
    {
        $pool = CommentPool::where('order_id', $ordId)
            ->first();
        $validated = $this->validate();

        if ($pool) {
            $pool->completed = 0;
            $pool->title = $validated['i_title'];
            $pool->save();
            $this->dispatch('close-modal');
            $this->reset('i_title');
            $this->notification([
                'title'       => 'ReCreated!',
                'description' => 'Your i-Meeting was re-created',
                'icon'        => 'warning'
            ]);
        }
        if (!$pool) {

            CommentPool::create([
                'title' => $validated['i_title'],
                'order_id' => $ordId,
                'status_id' => 0,
                'user_id' => auth()->user()->id,
            ]);
            $this->dispatch('close-modal');
            $this->reset('i_title');
            $this->notification([
                'title'       => 'Successfully Created!',
                'description' => 'Your i-Meeting was successfully created',
                'icon'        => 'success'
            ]);
        }
    }

    //reply button in comment
    public function create_reply($comId)
    {
        $validated = $this->validate([
            'reply_content' => 'required'
        ]);
        Reply::create([
            'content' => $this->reply_content,
            'user_id' => auth()->user()->id,
            'comment_id' => $comId,
        ]);
        Notification::create([
            'user_id' => auth()->user()->id,
            'is_read' => true,
            'comment_id' => $comId,
        ]);
        $this->reset('reply_content');
    }

    public function update_ordQty($ordId)
    {
        $validated = $this->validate([
            'update_qty' => 'required|integer',
        ]);

        $query = Order::find($ordId);
        $query->qty = $this->update_qty;
        $query->save();
        // dd('success');
        $this->reset('update_qty');
        session()->flash('updatedQty', 'Updated Successfully');
    }

    // ack by purchaser that added by branch
    public function acked($ordId)
    {
        OrderHistory::create([
            'order_id' => $this->order_id,
            'user_id' => auth()->user()->id,
            'status_id' => 2,
        ]);
        $query =  Order::find($ordId);
        $query->status_id = 2;
        $query->save();
        $this->notification([
            'title'       => 'Successfully Acknowleged!',
            'description' => 'Your was successfully acknowledged to this Order',
            'icon'        => 'success'
        ]);
        session()->flash('ackedSuccess', 'Acknowledged Successful');
    }

    //update quantity by inventory user or stock checker
    public function updateInstockqty($ordId)
    {
        $this->validate([
            'instockqty' => 'required|numeric',
        ]);
        OrderHistory::create([
            'order_id' => $this->order_id,
            'user_id' => auth()->user()->id,
            'status_id' => 2,
            'content' => "Updated inventory stock",
        ]);
        $query = Order::find($ordId);
        $query->instockqty = $this->instockqty;
        $query->save();
        $this->reset('instockqty');
        $this->notification([
            'title'       => 'Successful!',
            'description' => 'Your update Stock quantity was successfully',
            'icon'        => 'success'
        ]);
    }

    // request this order to approver
    public function requested($ordId)
    {

       $query = RequestedOrder::where('order_id',$ordId)->count();
       if($query > 0){
        OrderHistory::create([
            'order_id' => $this->order_id,
            'user_id' => auth()->user()->id,
            'status_id' => 3,
        ]);

        $query =  Order::find($ordId);
        $query->status_id = 3;
        $query->save();
        $this->reset('estimatetime');
        $this->notification([
            'title'       => 'Successfully Requested!',
            'description' => 'Your was successfully requested to AGM',
            'icon'        => 'success'
        ]);
       }else {
           $this->dialog([
            'title'       => 'Error',
            'description' => 'Nothing found Supplier Data!',
            'icon'        => 'error'
        ]);
       }
    }

     //Select supplier porduct for approver
     public function selectedSupplier($id)
     {
         $this->selected_approved_supplier = $id;
     }

    //approve order updated with approved order list
    public function approved($ordId, $init_qty)
    {

        $this->validate([
            'selected_approved_supplier' => 'required',
            'approved_note' => 'required',
            'to_order_date' => 'required',
        ]);

        $query =  Order::find($ordId);

        if ($this->editqty) {
            $this->validate([
                'editqty' => 'numeric',
            ]);
            $query->qty = $this->editqty;
        }

        $query->status_id = 4;
        $query->save();
        $content = '';
        if ($this->editqty) {
            $content = "Changed Order Quantity $init_qty to $this->editqty";
        }
        OrderHistory::create([
            'order_id' => $this->order_id,
            'user_id' => auth()->user()->id,
            'status_id' => 4,
            'content' => $content,
        ]);
        //saving approved order table
        $supplierProduct = SupplierProduct::find($this->selected_approved_supplier);
        ApprovedOrder::create([
            'order_id' => $ordId,
            'supplier_product_id' => $this->selected_approved_supplier,
            'approve_note' => $this->approved_note,
            'to_order_date' => $this->to_order_date,
            'youktwat' => $supplierProduct->youktwat,
            'youktwat_in_kpy' => $supplierProduct->youktwat_in_kpy,
            'laukkha' => $supplierProduct->laukkha,
        ]);

        $this->notification([
            'title'       => 'Successfully Approved!',
            'description' => 'Your was successfully approved to this order',
            'icon'        => 'success'
        ]);

        return redirect()->route('per_order', 'order_id=' . $ordId);
    }

    //order this selected order id
    public function ordered($ordId)
    {
        $query =  Order::find($ordId);
        $query->status_id = 5;
        $query->save();

        OrderHistory::create([
            'order_id' => $this->order_id,
            'user_id' => auth()->user()->id,
            'status_id' => 5,
        ]);

        $this->notification([
            'title'       => 'Successfully Ordered!',
            'description' => 'Your was successfully ordered this order',
            'icon'        => 'success'
        ]);

        return redirect()->route('per_order', 'order_id=' . $ordId);
    }

    //arrivals of this order id
    public function arrived($ordId)
    {
        $this->validate([
            'arqty' => 'required|numeric',
        ]);
        $query =  Order::find($ordId);
        $query->arqty = $this->arqty;
        $query->status_id = 6;
        $query->save();
        OrderHistory::create([
            'order_id' => $this->order_id,
            'user_id' => auth()->user()->id,
            'status_id' => 6,
        ]);

        $this->reset('arqty');
        $this->notification([
            'title'       => 'Successfully!',
            'description' => 'Your was successfully add this order to arrivals ',
            'icon'        => 'success'
        ]);

        return redirect()->route('per_order', 'order_id=' . $ordId);
    }

    //successfully closed order id
    public function closed($ordId)
    {
        $this->validate([
            'closeqty' => 'required|numeric',
        ]);
        $query =  Order::find($ordId);
        $query->closeqty = $this->closeqty;
        $query->status_id = 7;
        $query->save();

        OrderHistory::create([
            'order_id' => $this->order_id,
            'user_id' => auth()->user()->id,
            'status_id' => 7,
        ]);

        $this->reset('closeqty');
        $this->notification([
            'title'       => 'Successfully!',
            'description' => 'This Order ticket was successfully closed',
            'icon'        => 'success'
        ]);
    }

    //calceled order id
    public function cancel_order($ordId)
    {
        $this->validate([
            'cancel_reason' => 'required',
        ]);
        $query =  Order::find($ordId);
        $query->status_id = 8;
        $query->save();

        OrderHistory::create([
            'order_id' => $this->order_id,
            'user_id' => auth()->user()->id,
            'status_id' => 8,
            'content' => "Canceled this order coz of - $this->cancel_reason"
        ]);

        $this->notification([
            'title'       => 'Cancel!',
            'description' => 'Cancled your order',
            'icon'        => 'warning'
        ]);

        $this->reset('cancel_reason');
        $this->dispatch('close_modal');
    }


    public function render()
    {
        //Count to Noti
        $relevantMeetingCount = CommentPool::where('completed', 'false')->where('user_id', '=', Auth::user()->id)->count();
        $agmMeetingCount = CommentPool::where('completed', 'false')->count();
        $comments = Comment::where('order_id', $this->order_id)->count();

        //target order
        $order = Order::query()
            ->find($this->order_id);
        // dd($order->approvedOrder->supplierProduct->supplier->name);
        $approvedSupplier = ApprovedOrder::where('order_id', '=', $this->order_id)->get();

        //Suppler info
        $supplierDatas = SupplierProduct::where('quality_id', $order->quality_id)
        ->whereBetween('weight',[$order->weight - 0.05, $order->weight + 0.05])
        ->where('is_reject', 'false')
        ->where('detail','like','%'. $this->supplier_data_serarch .'%')
        ->get();


        return view('livewire.orders.per-order', [
            'order' => $order,
            'approvedSupplier' => $approvedSupplier,
            'chatPool' => CommentPool::where('order_id', $this->order_id)->first(),

            //Supplier info
            'supplierdatas' => $supplierDatas,

            //Permission to blade
            'approver' => $this->approver,
            'invUser' => $this->invUser,
            'creator' => $this->creator,

            //Count to Noti
            'comments' => $comments,

            //Selected supplier product
            'selectedApprovedSupplier' => SupplierProduct::find($this->selected_approved_supplier),
        ]);
    }
}
