<?php

namespace App\Livewire\Orders;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Priority;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Report extends Component
{
    public $status_id = 1;
    public $priority_id = 1;

    public function render()
    {
        $branchesData = Branch::select('branches.name', 'branches.id', DB::raw('count(orders.status_id) as total'))
            ->leftJoin('orders', 'branches.id', '=', 'orders.branch_id')
            ->where(function ($query) {
                $query->where('orders.status_id', $this->status_id)
                    ->orWhereNull('orders.status_id'); // Include null status_id as well
            })
            ->groupBy('branches.id', 'branches.name')
            ->get();
        // dd($branches);

        $priorityData = Branch::select('branches.name', 'branches.id', DB::raw('count(orders.priority_id) as total'))
            ->leftJoin('orders', 'branches.id', '=', 'orders.branch_id')
            ->where(function ($query) {
                $query->where('orders.priority_id', $this->priority_id)
                    ->orWhereNull('orders.priority_id'); // Include null priority_id as well
            })
            ->groupBy('branches.id', 'branches.name')
            ->get();

        // query data with dynamically branch id
        $branches = Branch::all();
        $select = ['design_id','branch_id','weight'];

        foreach ($branches as $branch) {
            $select[] = DB::raw("SUM(CASE WHEN branch_id = $branch->id THEN qty ELSE 0 END) As index$branch->id");
        }
        $products = Order::select($select)
            ->groupBy('design_id', 'weight', 'branch_id')
            ->get();



            $startDate = '2024-01-01';
            $endDate = '2024-12-31';

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
        //    ->whereBetween('orders.created_at', [$startDate, $endDate])
           ->get();


        //  dd($averages);

        return view('livewire.orders.report', [
            'statuses' => Status::all(),
            'branches' => $branchesData,
            'thBranches' => $branches,
            'priorities' => Priority::all(),
            'prioritiesData' => $priorityData,
            'products' => $products,
            'average' => $averages,
        ]);
    }
}
