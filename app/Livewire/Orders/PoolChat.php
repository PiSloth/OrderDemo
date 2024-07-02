<?php

namespace App\Livewire\Orders;

use App\Models\Comment;
use App\Models\CommentPool;
use App\Models\Notification;
use App\Models\Reply;
use League\CommonMark\Extension\Mention\Mention;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use WireUi\Traits\Actions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PoolChat extends Component
{
    use Actions;
    #[Title('i Meeting')]
    public $toggle_id;
    public $order_id;
    public $pool_id;
    public $content;
    public $meetingnote;
    public $assign_id;
    public $reason;

    #[Url()]
    public $comment_toggle;

    public $reply_content;
    public $estimatetime;
    public $instockqty;

    public $editqty;
    public $arqty;
    public $closeqty;
    public $reply_toggle;
    public $commentId;
    public $cancel_reason;
    #[Rule('required')]
    public $i_title;

    protected $invUser;
    protected $approver;
    protected $creator;

    public function reply_to_comment($cmtId)
    {
        if ($this->reply_toggle == $cmtId) {
            $this->reset('reply_toggle');
        } else {
            $this->reply_toggle = $cmtId;
        }
    }

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



    public function toggle($id)
    {
        if ($this->toggle_id == $id) {
            $this->reset('toggle_id');
        } else {
            $this->toggle_id = $id;
        }
    }


    public function complete_pool($poolId, $ordStatus, $poolStatus)
    {
        $table = CommentPool::find($poolId);
        if ($ordStatus == $poolStatus) {
            $table->completed = true;
            $table->save();
            $this->notification([
                'title'       => 'Success!',
                'description' => 'Your i-Meeting was successfully Closed',
                'icon'        => 'success'
            ]);
        } else {
            $this->validate(['reason' => 'required',]);
            $table->reason = $this->reason;
            $table->completed = true;
            $table->save();
            $this->reset('reason');
            $this->dispatch('close-modal');
            $this->notification([
                'title'       => 'Cancled!',
                'description' => 'Your i-Meeting was Cancled',
                'icon'        => 'warning'
            ]);
        }
    }

    public function create_comment($ordId, $poolId)
    {
        $this->validate([
            'content' => 'required',
        ]);
        Comment::create([
            'user_id' => auth()->user()->id,
            'order_id' => $ordId,
            'pool_id' => $poolId,
            'content' => $this->content,
        ]);
        $this->reset('content');
    }

    public function create_meetingnote($poolId)
    {
        $this->validate([
            'assign_id' => 'required',
            'meetingnote' => 'required',
        ]);
        $table = CommentPool::find($poolId);
        $table->meeting_note = $this->meetingnote;
        $table->status_id = $this->assign_id;
        $table->save();
        // session()->flash('notedSuccess', 'Successfully assigned and noted');
        $this->reset('meetingnote', 'assign_id');
        $this->dispatch('close-modal');
        $this->dialog()->success(
            $title = 'Meeting Note Saved',
            $description = 'Your i-Meeting note was successfully saved'
        );
    }


    public function render()
    {
        $currentTime = Carbon::now();

        return view('livewire.orders.pool-chat', [
            'relevantMeetingCount' => CommentPool::where('completed', 'false')->where('user_id', '=', Auth::user()->id)->count(),
            'agmMeetingCount' => CommentPool::where('completed', 'false')->count(),
            'agmMeetings' => CommentPool::where('completed', 'false')->get(),
            'chatspool' => CommentPool::where('completed', false)->get(),
            'currentTime' => $currentTime
        ]);
    }
}
