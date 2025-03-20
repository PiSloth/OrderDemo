<?php

namespace App\Livewire\Orders;

use App\Models\Branch;
use App\Models\Comment;
use App\Models\CommentPool;
use App\Models\Design;
use App\Models\Grade;
use App\Models\Order;
use App\Models\Priority;
use App\Models\Quality;
use App\Models\Reply;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\Actions;

class BranchReport extends Component
{
    use Actions;
    use WithPagination;

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

    public $qualityFilter;

    public $designName;

    public $priority = '';

    public $date = '';

    public $orderId = '';

    public $reply_toggle;

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
            'reply' => 'required',
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
            'comment' => 'required',
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
    public function ack($id)
    {
        Order::whereId($id)->update(['status_id' => 2]);
    }

    #[Title('Report')]
    public function render()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $orderQuery = Order::where(function ($query) use ($sevenDaysAgo) {
            $query->where('status_id', '!=', 7)
                ->where('status_id', '!=', 8)  // Exclude status_id 7 and 8
                ->orWhere(function ($query) use ($sevenDaysAgo) {
                    $query->where('status_id', 7)
                        ->where('updated_at', '>=', $sevenDaysAgo);  // Only include status_id 7 updated within last 7 days
                })
                ->orWhere(function ($query) use ($sevenDaysAgo) {
                    $query->where('status_id', 8)
                        ->where('updated_at', '>=', $sevenDaysAgo);  // Only include status_id 8 updated within last 7 days
                });
        })
            ->where('detail', 'like', '%'.$this->detailFilter.'%')
            ->orderBy('created_at', 'desc')
            ->get();

        // dd($orderQuery);

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

        if ($this->qualityFilter) {
            $orderQuery = $orderQuery->where('quality_id', $this->qualityFilter);
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

        if ($this->startDate && $this->endDate) {
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
            'qualities' => Quality::all(),

        ]);
    }
}
