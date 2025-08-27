<?php

namespace App\Livewire\Orders;

use App\Models\Branch;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Report extends Component
{
    public $status_id = 1;
    public $priority_id = 1;
    public $startDate;
    public $endDate;

    protected function allComments($users)
    {
        $commentsForAllUsers = [];

        // Avoid N+1 for user names
        $userNames = User::whereIn('id', $users instanceof \Illuminate\Support\Collection ? $users->all() : (array) $users)
            ->pluck('name', 'id');

        foreach ($users as $userId) {
            // Count unread comments for each user: comments on user's orders by others, without a matching notification for this user
            $unreadCount = Comment::leftJoin('orders', 'comments.order_id', '=', 'orders.id')
                ->leftJoin('notifications', function ($join) use ($userId) {
                    $join->on('notifications.comment_id', '=', 'comments.id')
                        ->where('notifications.user_id', '=', $userId);
                })
                ->where('orders.user_id', '=', $userId)
                ->where('comments.user_id', '!=', $userId)
                ->whereNull('notifications.comment_id')
                ->count();

            $commentsForAllUsers[$userId] = [
                $userNames[$userId] ?? 'Unknown',
                $unreadCount,
            ];
        }

        return $commentsForAllUsers;
    }

    public function render()
    {
        $branchesData = Branch::select('branches.name', 'branches.id', DB::raw('COUNT(orders.id) AS total'))
            ->leftJoin('orders', 'branches.id', '=', 'orders.branch_id')
            ->where(function ($query) {
                $query->where('orders.status_id', $this->status_id)
                    ->orWhereNull('orders.status_id');
            })
            ->when($this->startDate && $this->endDate, function ($q) {
                $q->whereBetween('orders.created_at', [$this->startDate, $this->endDate]);
            })
            ->groupBy('branches.id', 'branches.name')
            ->get();


        $priorityData = Branch::select('branches.name', 'branches.id', DB::raw('COUNT(orders.id) AS total'))
            ->leftJoin('orders', 'branches.id', '=', 'orders.branch_id')
            ->where(function ($query) {
                $query->where('orders.priority_id', $this->priority_id)
                    ->orWhereNull('orders.priority_id');
            })
            ->when($this->startDate && $this->endDate, function ($q) {
                $q->whereBetween('orders.created_at', [$this->startDate, $this->endDate]);
            })
            ->groupBy('branches.id', 'branches.name')
            ->get();


        // query data with dynamically branch id
        $branches = Branch::all();
        $select = ['design_id', 'branch_id', 'weight'];

        foreach ($branches as $branch) {
            $select[] = DB::raw("SUM(CASE WHEN branch_id = $branch->id THEN qty ELSE 0 END) As index$branch->id");
        }

        $products = Order::select($select)
            ->when($this->startDate && $this->endDate, function ($q) {
                $q->whereBetween('created_at', [$this->startDate, $this->endDate]);
            })
            ->groupBy('design_id', 'weight', 'branch_id')
            ->get();


        //Process leadtime average
        // Lead-time calculation: pre-aggregate first time each status was reached per order
        $base = DB::table('orders')->where('orders.status_id', '!=', 8);
        if ($this->startDate && $this->endDate) {
            $base->whereBetween('orders.created_at', [$this->startDate, $this->endDate]);
        }

        $s2 = DB::table('order_histories')->select('order_id', DB::raw('MIN(created_at) AS reached_at'))
            ->where('status_id', 2)->groupBy('order_id');
        $s3 = DB::table('order_histories')->select('order_id', DB::raw('MIN(created_at) AS reached_at'))
            ->where('status_id', 3)->groupBy('order_id');
        $s4 = DB::table('order_histories')->select('order_id', DB::raw('MIN(created_at) AS reached_at'))
            ->where('status_id', 4)->groupBy('order_id');
        $s5 = DB::table('order_histories')->select('order_id', DB::raw('MIN(created_at) AS reached_at'))
            ->where('status_id', 5)->groupBy('order_id');
        $s6 = DB::table('order_histories')->select('order_id', DB::raw('MIN(created_at) AS reached_at'))
            ->where('status_id', 6)->groupBy('order_id');
        $s7 = DB::table('order_histories')->select('order_id', DB::raw('MIN(created_at) AS reached_at'))
            ->where('status_id', 7)->groupBy('order_id');

        $averages = DB::query()
            ->fromSub($base, 'o')
            ->leftJoinSub($s2, 's2', 's2.order_id', '=', 'o.id')
            ->leftJoinSub($s3, 's3', 's3.order_id', '=', 'o.id')
            ->leftJoinSub($s4, 's4', 's4.order_id', '=', 'o.id')
            ->leftJoinSub($s5, 's5', 's5.order_id', '=', 'o.id')
            ->leftJoinSub($s6, 's6', 's6.order_id', '=', 'o.id')
            ->leftJoinSub($s7, 's7', 's7.order_id', '=', 'o.id')
            ->selectRaw('
                COUNT(DISTINCT o.id) AS TotalCount,
                CEIL(AVG(TIMESTAMPDIFF(DAY, o.created_at, s2.reached_at))) AS AvgAddedToAcked,
                CEIL(AVG(TIMESTAMPDIFF(DAY, s2.reached_at, s3.reached_at))) AS AvgAckedToRequest,
                CEIL(AVG(TIMESTAMPDIFF(DAY, s3.reached_at, s4.reached_at))) AS AvgRequestToApprove,
                CEIL(AVG(TIMESTAMPDIFF(DAY, s4.reached_at, s5.reached_at))) AS AvgApproveToOrdered,
                CEIL(AVG(TIMESTAMPDIFF(DAY, s5.reached_at, s6.reached_at))) AS AvgOrderedToArrived,
                    CEIL(AVG(TIMESTAMPDIFF(DAY, s6.reached_at, s7.reached_at))) AS AvgDeliveredToSuccess
            ')
            ->get();

        // End process leadtime average

        //user comments count
        $users = User::all()->pluck('id');
        // get all comments of each user
        $commentsForAll = $this->allComments($users);
        //  dd($commentsForAll);

        return view('livewire.orders.report', [
            'statuses' => Status::all(),
            'branches' => $branchesData,
            'thBranches' => $branches,
            'priorities' => Priority::all(),
            'prioritiesData' => $priorityData,
            'products' => $products,
            'average' => $averages,
            'allUserComments' => $commentsForAll,
        ]);
    }
}
