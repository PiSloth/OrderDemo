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

    protected function allComments($users){
        $commentsForAllUsers = [];

        foreach($users as $userId){

            $comments = Comment::leftJoin('notifications',  function ($join) use ($userId) {
                $join->on('notifications.comment_id', '=', 'comments.id')
                     ->where('notifications.user_id', '=', $userId);
            })
                ->leftJoin('orders', 'comments.order_id', 'orders.id')
                ->leftJoin('users', 'users.id', 'comments.user_id')
                ->select(

                    'comments.id AS id',
                    'users.name AS commentBy',
                    'comments.order_id AS orderId',
                    DB::raw('CASE WHEN comments.id = notifications.comment_id THEN TRUE ELSE FALSE END AS isRead
            ')
                )
                ->where('orders.user_id', '=', $userId)
                ->where('comments.user_id', '!=', $userId)
                ->orderBy('comments.id', 'desc')
                ->get();

            $commentCount = $comments->where('is_read', 'false')->count();
            $userName = User::find($userId)->name;

            $result = [$userName, $commentCount];

            $commentsForAllUsers[$userId] = $result;
        }
        return $commentsForAllUsers;
    }

    public function render()
    {
        $branchesData = Branch::select('branches.name', 'branches.id', DB::raw('count(orders.status_id) as total'))
            ->leftJoin('orders', 'branches.id', '=', 'orders.branch_id')
            ->where(function ($query) {
                $query->where('orders.status_id', $this->status_id)
                    ->orWhereNull('orders.status_id'); // Include null status_id as well
            })
            //    ->whereBetween('orders.created_at', [$this->startDate, $this->endDate])
            ->groupBy('branches.id', 'branches.name');

        //Check strt date and end date
        if ($this->startDate && $this->endDate) {
            $branchesData = $branchesData
                ->whereBetween('orders.created_at', [$this->startDate, $this->endDate])
                ->get();
        } else {
            $branchesData = $branchesData->get();
        }


        $priorityData = Branch::select('branches.name', 'branches.id', DB::raw('count(orders.priority_id) as total'))
            ->leftJoin('orders', 'branches.id', '=', 'orders.branch_id')
            ->where(function ($query) {
                $query->where('orders.priority_id', $this->priority_id)
                    ->orWhereNull('orders.priority_id'); // Include null priority_id as well
            })
            ->groupBy('branches.id', 'branches.name');

            if($this->startDate && $this->endDate){
                $priorityData = $priorityData
                ->whereBetween('orders.created_at', [$this->startDate, $this->endDate])
                ->get();
            }else{
                $priorityData = $priorityData->get();
            }


        // query data with dynamically branch id
        $branches = Branch::all();
        $select = ['design_id', 'branch_id', 'weight'];

        foreach ($branches as $branch) {
            $select[] = DB::raw("SUM(CASE WHEN branch_id = $branch->id THEN qty ELSE 0 END) As index$branch->id");
        }

        $products = Order::select($select)
            ->groupBy('design_id', 'weight', 'branch_id');

        if ($this->startDate && $this->endDate) {
            $products = $products
                ->whereBetween('created_at', [$this->startDate, $this->endDate])->get();
        } else {
            $products = $products->get();
        }


        //Process leadtime average
        $averages = DB::table('orders')
            ->leftJoin('order_histories', 'order_histories.order_id', '=', 'orders.id')
            ->selectRaw("
                COUNT(DISTINCT(orders.id)) AS TotalCount,
               CEIL(AVG(TIMESTAMPDIFF(DAY, orders.created_at, (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 3 LIMIT 1)))) AS AvgAddedToAcked,
               CEIL(AVG(TIMESTAMPDIFF(DAY,
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 2 LIMIT 1),
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 3 LIMIT 1)))) AS AvgAckedToRequest,
               CEIL(AVG(TIMESTAMPDIFF(DAY,
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 3 LIMIT 1),
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 4 LIMIT 1)))) AS AvgRequestToApprove,
               CEIL(AVG(TIMESTAMPDIFF(DAY,
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 4 LIMIT 1),
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 5 LIMIT 1)))) AS AvgApproveToOrdered,
               CEIL(AVG(TIMESTAMPDIFF(DAY,
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 5 LIMIT 1),
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 6 LIMIT 1)))) AS AvgOrderedToArrived,
               CEIL(AVG(TIMESTAMPDIFF(DAY,
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 6 LIMIT 1),
                   (SELECT created_at FROM order_histories WHERE order_id = orders.id AND status_id = 7 LIMIT 1)))) AS AvgDeliveredToSuccess
           ")
            ->whereNot('orders.status_id', 8);

        if ($this->startDate && $this->endDate) {
            $averages = $averages
                ->whereBetween('orders.created_at', [$this->startDate, $this->endDate])
                ->get();
        } else {
            $averages = $averages->get();
        }

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
