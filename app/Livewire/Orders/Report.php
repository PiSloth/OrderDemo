<?php

namespace App\Livewire\Orders;

use App\Models\Branch;
use App\Models\Comment;
use App\Models\Design;
use App\Models\Grade;
use App\Models\Order;
use App\Models\Priority;
use Carbon\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use WireUi\Traits\Actions;
use App\Models\CommentPool;
use App\Models\Reply;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;

class Report extends Component
{
    use Actions;
    #[Title('Report')]
    public $gradeFilter = 0;
    public $priorityFilter = 0;
    public $designFilter = 0;
    public $durationFilter = 0;
    public $statusFilter = 0;
    public $branchFilter = 100;
    public $priority = '';
    public $date = '';
    public $orderId = '';
    public  $reply_toggle;
    public $content;
    public $comment;
    public $reply;

    public function mount() {
        $this->branchFilter = auth()->user()->branch_id;
    }
    public function order($id){
        // dd("Hello");
        $this->orderId = $id;
    }

    public function replyComment($id){
        $this->reply_toggle = $id;
    }

    public function createReply($comId){

        $this->validate([
            'reply' => 'required'
        ]);
        Reply::create([
            'content' => $this->reply,
            'user_id' => auth()->user()->id,
            'comment_id' => $comId,
        ]);

        $this->reset('reply','reply_toggle');
    }

    public function createComment(){
        $this->validate([
            'comment' => 'required'
        ]);

        Comment::create([
            'content' => $this->comment,
            'user_id' => auth()->user()->id,
            'order_id' => $this->orderId,
        ]);
        $this->reset('orderId','comment');
        $this->dispatch('close-modal');
    }

    public function render()
    {
        $orderQuery = Order::get();

        if($this->statusFilter) {
            $orderQuery = $orderQuery->where('status_id', $this->statusFilter);
        }

        if($this->branchFilter) {
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
        }

        if ($this->durationFilter) {
            $currentTimeLine = Carbon::now();
            $monthDuration = $currentTimeLine->copy()->subMonth($this->durationFilter);

            $orderQuery = $orderQuery->whereBetween('created_at', [$monthDuration->startOfDay(), $currentTimeLine->endOfDay()]);
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

        return view('livewire.orders.report', [
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
