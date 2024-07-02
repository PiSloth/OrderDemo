<?php

namespace App\Livewire\Orders;

// use Illuminate\Support\Carbon;
use App\Models\Comment;
use App\Models\CommentPool;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Reply;
use App\Models\Category;
use App\Models\Grade;
use App\Models\Priority;
use App\Models\Quality;
use App\Models\Design;
use App\Models\Branch;
use App\Models\Status;
use Carbon\Carbon;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Orderlists extends Component
{
    use WithPagination;

    #[Title('Add an Order')]

    #[Url(as: 'order')]
    public $order_toggle;

    #[Url(as: 'reply')]
    public $reply_toggle;

    public $content;
    public $reply_content;
    public $update_qty;
    public $instock_qty;
    public $estimate_time;
    public $status;
    public $branch;
    public $category;
    public $design;
    public $quality;
    public $weight;
    public $priority;
    public $start_date;
    public $end_date;


    public function boot()
    {
        if (!$this->start_date) {
            $this->start_date = Carbon::today()->subDays(30);
        }
        if (!$this->end_date) {
            $this->end_date = Carbon::today();
        }
    }

    public function toggle($ordId)
    {
        if ($this->order_toggle == $ordId) {
            $this->reset('order_toggle');
        } else {
            $this->order_toggle = $ordId;
        }
    }

    public function create_comment($ordId)
    {
        Comment::create([
            'content' => $this->content,
            'user_id' => auth()->user()->id,
            'order_id' => $ordId,
        ]);
        $this->reset('content');
    }

    public function create_pool($ordId)
    {
        $pool = CommentPool::where('order_id', $ordId)
            ->where('completed', false)
            ->first();

        if ($pool) {
            session()->flash('msgByPool', 'Already created this chat');
        } else {
            CommentPool::create([
                'title' => "that's a chat pool",
                'order_id' => $ordId,
                'completed' => false,
                'user_id' => auth()->user()->id,
            ]);
            session()->flash('msgByPool', 'Success Creation');
        }
    }

    public function comment_toggle($cmtId)
    {
        if ($this->reply_toggle == $cmtId) {
            $this->reset('reply_toggle');
        } else {
            $this->reply_toggle = $cmtId;
        }
    }

    public function create_reply($comId)
    {
        Reply::create([
            'content' => $this->reply_content,
            'user_id' => auth()->user()->id,
            'comment_id' => $comId,
        ]);
        Notification::create([
            'user_id' => auth()->user()->id,
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

    public function update_invData($ordId)
    {
        $validated = $this->validate([
            'instock_qty' => 'required|integer',
            'estimate_time' => 'required|integer',
        ]);

        $query = Order::find($ordId);
        $query->instockqty = $this->instock_qty;
        $query->estimatetime = $this->estimate_time;
        $query->status_id = 3;
        $query->save();
        // dd('success');
        $this->reset('instock_qty', 'estimate_time');
        session()->flash('updatedInv', 'Updated Successfully');
    }

    public function render()
    {

        // $query = Order::with('priority', 'status', 'quality', 'design', 'category')
        //     ->latest()
        //     ->whereBetween('created_at', [$this->start_date, $this->end_date])
        //     ->where('priority_id', 'like', "%{$this->priority}%")
        //     ->where('status_id', 'like', "%{$this->status}%")
        //     ->where('branch_id', 'like', "%{$this->branch}%")
        //     ->where('category_id', 'like', "%{$this->category}%")
        //     ->where('quality_id', 'like', "%{$this->quality}%")
        //     ->where('design_id', 'like', "%{$this->design}%")
        //     ->where('weight', 'like', "%{$this->weight}%")
        //     ->paginate(7);
        $query = Order::get();

        return view('livewire.orders.orderlists', [
            'orders' => $query,
            'priorities' => Priority::all(),
            'grades' => Grade::all(),
            'categories' => Category::all(),
            'qualities' => Quality::all(),
            'branches' => Branch::all(),
            'designs' => Design::all(),
            'statuses' => Status::all(),

        ]);
    }
}
