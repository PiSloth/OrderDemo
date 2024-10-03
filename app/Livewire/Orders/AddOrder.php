<?php

namespace App\Livewire\Orders;

use Livewire\Attribute\Rule;
use Livewire\WithPagination;
use WireUi\Traits\Actions;
use App\Models\Branch;
use App\Models\Category;
use App\Models\CommentPool;
use App\Models\Design;
use App\Models\Grade;
use App\Models\Images;
use App\Models\Order;
use App\Models\Priority;
use App\Models\Quality;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule as AttributesRule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddOrder extends Component
{
    use Actions;
    use WithFileUploads;

    #[Title('Add an Order')]
    #[Url(as: 'cate')]
    public $category_id;
    public $priority_id;
    public $grade_id;
    public $quality_id;
    public $design_id;
    public $branch_id;
    public $counterstock;
    public $sell_rate;
    public $qty;
    public $weight;
    public $size;
    public $detail;
    public $note;
    public $show_more_toggle = false;
    public $toggle_id;
    public $yawe;
    public $estimateTime;
    //for checking select div
    public $emptyPriority;
    public $emptyGrade;
    public $emptyQuality;
    public $emptyCategory;
    public $emptyDesign;
    public $emptyBranch;
    #[AttributesRule('nullable|sometimes|image|max:1024')]
    public $productImg;

    //check order modal
    public $checkOrder;

    public function create_order()
    {

        $validatedData = $this->validate([
            'priority_id' => 'required',
            'category_id' => 'required',
            'grade_id' => 'required',
            'quality_id' => 'required',
            'design_id' => 'required',
            'counterstock' => 'required',
            'sell_rate' => 'required',
            'qty' => 'required',
            'weight' => 'required',
            'size' => 'required',
            'detail' => 'required',
            'note' => 'required',
        ]);

        Order::create(array_merge($validatedData, [
            'user_id' => auth()->user()->id,
            'branch_id' => auth()->user()->branch->id,
            'status_id' => 1,
        ]));
        //Photo Save after create

        // dd($this->productImg);

        if ($this->productImg) {
            $last_id = Order::latest()->first();
            $id = $last_id->id;
            $image = $this->validate([
                'productImg' => 'required|max:1024'
            ]);
            $path = $this->productImg->store('images', 'public');
            // dd($path);
            Images::create([
                'orderimg' => $path,
                'order_id' => $id,
            ]);
        }

        $this->reset(['priority_id', 'category_id', 'grade_id', 'quality_id', 'design_id', 'branch_id', 'counterstock', 'sell_rate', 'qty', 'size', 'weight', 'detail', 'note', 'productImg', 'estimateTime']);
        $this->dialog([
            'title'       => 'Success Creation!',
            'description' => 'Your New Order was successfully created',
            'icon'        => 'success'
        ]);
    }

    public function show_more($id)
    {
        $this->toggle_id = $id;
    }

    public function create_pool($id)
    {
        $pool = CommentPool::where('order_id', $id)
            ->where('completed', false)
            ->first();

        if ($pool) {
            session()->flash('msgByPool', 'Already created this chat');
        } else {
            CommentPool::create([
                'title' => "that's a chat pool",
                'order_id' => $id,
                'completed' => false,
                'user_id' => $this->user_id,
            ]);
            session()->flash('msgByPool', 'Success Creation');
        }
    }

    public function render()
    {

        if($this->category_id){
            $orderWithFilter = Order::latest()
                ->where('branch_id','=',auth()->user()->branch_id)
                ->where('category_id', 'like', "%{$this->category_id}%")
                ->where('quality_id', 'like', "%{$this->quality_id}%")
                ->where('design_id', 'like', "%{$this->design_id}%")
                ->where('weight', 'like', "%{$this->weight}%")
                ->get();
        }else{
            $orderWithFilter = [];
        }

        $priority = Priority::where('id', $this->priority_id)->get();
        if (!$this->priority_id) {
            $this->emptyPriority = "ဦးစားပေးအဆင့် သတ်မှတ်ပါ";
        } else {
            $this->emptyPriority = '';
        }

        $grade = Grade::where('id', $this->grade_id)->get();
        if (!$this->priority_id) {
            $this->emptyGrade = "ရောင်းအားအဆင့် သတ်မှတ်ပါ";
        } else {
            $this->emptyGrade = '';
        }
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

        $branch = Branch::where('id', $this->branch_id)->get();
        if (!$this->branch_id) {
            $this->emptyBranch = "ဆိုင်ခွဲရွေးချယ် သတ်မှတ်ပါ";
        } else {
            $this->emptyBranch = '';
        }

        return view('livewire.orders.add-order', [
            'priorities' => Priority::all(),
            'grades' => Grade::all(),
            'categories' => Category::all(),
            'qualities' => Quality::all(),
            'branches' => Branch::all(),
            'designs' => Design::all(),
            'orders' => Order::latest()->take(1)->get(),
            'pools' => CommentPool::all(),
            'liveOrders' => $orderWithFilter,
            'priority_item' => $priority,
            'quality_item' => $quality,
            'design_item' => $design,
            'grade_item' => $grade,
            'category_item' => $category,
            'branch_item' => $branch,

        ]);
    }
}
