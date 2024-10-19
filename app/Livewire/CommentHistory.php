<?php

namespace App\Livewire;

use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

class CommentHistory extends Component
{

    public function readComment($id, $orderId)
    {

        $query = Notification::whereCommentId($id)
        ->whereUserId(auth()->user()->id)
        ->exists();

        if (!$query) {
            // dd('stop');
            Notification::create([
                'comment_id' => $id,
                'user_id' => auth()->user()->id,
                'is_read' => true,
            ]);
        }
        return redirect()->route('per_order', ['order_id' => $orderId]);
    }

    #[Title('Comments')]
    public function render()
    {

        if (Gate::allows('isAllCommentReader')) {
            $comment = Comment::leftJoin('notifications',  function ($join) {
                $join->on('notifications.comment_id', '=', 'comments.id')
                     ->where('notifications.user_id', '=', auth()->user()->id);
            })
                ->leftJoin('orders', 'comments.order_id', 'orders.id')
                ->leftJoin('users', 'users.id', 'comments.user_id')
                ->select(
                    'comments.content',
                    'comments.id AS id',
                    'users.name AS commentBy',
                    'comments.order_id AS orderId',
                    DB::raw('CASE WHEN comments.id = notifications.comment_id THEN TRUE ELSE FALSE END AS isRead
            ')
                )
                ->where('comments.user_id', '!=', auth()->user()->id)
                ->orderBy('comments.id', 'desc')
                ->get();

        } else {
            $comment = Comment::leftJoin('notifications',  function ($join) {
                $join->on('notifications.comment_id', '=', 'comments.id')
                     ->where('notifications.user_id', '=', auth()->user()->id);
            })
                ->leftJoin('orders', 'comments.order_id', 'orders.id')
                ->leftJoin('users', 'users.id', 'comments.user_id')
                ->select(
                    'comments.content',
                    'comments.id AS id',
                    'users.name AS commentBy',
                    'comments.order_id AS orderId',
                    DB::raw('CASE WHEN comments.id = notifications.comment_id THEN TRUE ELSE FALSE END AS isRead
            ')
                )
                ->where('orders.user_id', '=', auth()->user()->id)
                ->where('comments.user_id', '!=', auth()->user()->id)
                ->orderBy('comments.id', 'desc')
                ->get();
        }

        $newComment = $comment->where('isRead', false)->count();

        //မိမိ တင်ထားတဲ့ Order ဖြစ်ရမယ်/ မိမိရေးထားတဲ့ Comment မဟုတ်ရဘူး/ အဲတာဆိုရင် Comment ပေါ်ပါ့မယ်။

        return view('livewire.comment-history', [
            'comments' => $comment,
            'newComment' => $newComment,
        ]);
    }
}
