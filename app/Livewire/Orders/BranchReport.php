<?php

namespace App\Livewire\Orders;

use Carbon\Carbon;
use App\Models\Grade;
use App\Models\Order;
use App\Models\Reply;
use App\Models\Branch;
use App\Models\Design;
use App\Models\Status;
use App\Models\Comment;
use Livewire\Component;
use App\Models\Priority;
use WireUi\Traits\Actions;
use App\Models\CommentPool;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

class BranchReport extends Component
{
    use Actions;
    #[Title('Report')]
    public $gradeFilter = 0;

    #[Url(as: 'priority')]
    public $priorityFilter = 0;

    #[Url(as: 'status', keep: false)]
    public $statusFilter;

    #[Url(as: 'branch', keep: false)]
    public $branchFilter;

    #[Url(as: 'st')]
    public $startDate;

    #[Url(as: 'en')]
    public $endDate;

    public $designFilter = 0;
    public $durationFilter = 0;
    public $detailFilter;

    public $designName;

    public $priority = '';
    public $date = '';
    public $orderId = '';
    public  $reply_toggle;
    public $content;
    public $comment;
    public $reply;

    // comment modal
    public $commentModal;

    // public function mount() {
    //     if(!$this->branchFilter){
    //         $this->branchFilter = auth()->user()->branch_id;
    //     }
    // }

    public function order($id)
    {
        // dd("Hello");
        $this->orderId = $id;
    }

    public function replyComment($id)
    {
        $this->reply_toggle = $id;
    }

    public function createReply($comId)
    {

        $this->validate([
            'reply' => 'required'
        ]);
        Reply::create([
            'content' => $this->reply,
            'user_id' => auth()->user()->id,
            'comment_id' => $comId,
        ]);

        $this->reset('reply', 'reply_toggle');
    }

    public function createComment()
    {
        $this->validate([
            'comment' => 'required'
        ]);

        Comment::create([
            'content' => $this->comment,
            'user_id' => auth()->user()->id,
            'order_id' => $this->orderId,
        ]);
        $this->reset('orderId', 'comment');
        $this->dispatch('close-modal');
    }

    //ack order
    public function ack($id){
        Order::whereId($id)->update(['status_id' => 2]);
    }

    public function render()
    {
        $orderQuery = Order::orderBy('created_at', 'desc')
        ->where('detail','like', '%'. $this->detailFilter . '%')
        ->get();


        if ($this->statusFilter) {
            $orderQuery = $orderQuery->where('status_id', $this->statusFilter);
        }

        // dd($this->statusFilter);

        if ($this->branchFilter) {
            $orderQuery = $orderQuery->where('branch_id', $this->branchFilter);
        }

        //  if($this->branchFilter == 100){
        //     $orderQuery = $orderQuery->where('branch_id', auth()->user()->id);
        // }

        if ($this->gradeFilter) {
            $orderQuery = $orderQuery->where('grade_id', $this->gradeFilter);
        }

        if ($this->priorityFilter) {
            $orderQuery = $orderQuery->where('priority_id', $this->priorityFilter);
        }

        if ($this->designFilter) {
            $orderQuery = $orderQuery->where('design_id', $this->designFilter);

            $this->designName = Design::find($this->designFilter)->first();
        }

        // if ($this->durationFilter) {
        //     $currentTimeLine = Carbon::now();
        //     $monthDuration = $currentTimeLine->copy()->subMonth($this->durationFilter);

        //     $orderQuery = $orderQuery->whereBetween('created_at', [$monthDuration->startOfDay(), $currentTimeLine->endOfDay()]);
        // }

        if($this->startDate && $this->endDate){
            $orderQuery = $orderQuery
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        $orderQuery = $orderQuery->groupBy(function ($order) {
            return $order->branch->name;
        });

        $orders = $orderQuery->map(function ($order) {
            return $order->groupBy(function ($data) {
                return $data->status->name;
            });
        });

        $comments = Comment::whereOrderId($this->orderId)->get();

        // dd(CommentPool::where('completed', 'false')->where('user_id', '=', Auth::user()->id)->count());

        return view('livewire.orders.branch-report', [
            'currentTime' => Carbon::now(),
            'orderGroup' => $orders,
            'grades' => Grade::get(),
            'priorities' => Priority::get(),
            'designs' => Design::get(),
            'currentTime' => Carbon::now(),
            'statuses' => Status::all(),
            'branches' => Branch::all(),
            'comments' => $comments,

        ]);
    }
}
