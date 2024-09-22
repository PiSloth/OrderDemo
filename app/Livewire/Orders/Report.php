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
        $branches = Branch::select('branches.name', 'branches.id', DB::raw('count(orders.status_id) as total'))
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

        $products = Order::select('design_id','weight', DB::raw('SUM(CASE WHEN branch_id = 1 THEN qty ELSE 0 END) As b1')
            ,DB::raw('SUM(CASE WHEN branch_id=2 THEN qty ELSE 0 END) AS b2')
            ,DB::raw('SUM(CASE WHEN branch_id=3 THEN qty ELSE 0 END) AS b3')
            ,DB::raw('SUM(CASE WHEN branch_id=4 THEN qty ELSE 0 END) AS b4')
            ,DB::raw('SUM(CASE WHEN branch_id=5 THEN qty ELSE 0 END) AS b5')
            ,DB::raw('SUM(CASE WHEN branch_id=6 THEN qty ELSE 0 END) AS ho')
            ,DB::raw('SUM(CASE WHEN branch_id=7 THEN qty ELSE 0 END) AS b6')
            )
            ->groupBy('design_id','weight')
            ->get();
            // dd($products);

        return view('livewire.orders.report', [
            'statuses' => Status::all(),
            'branches' => $branches,
            'priorities' => Priority::all(),
            'prioritiesData' => $priorityData,
            'products' => $products,
        ]);
    }
}
