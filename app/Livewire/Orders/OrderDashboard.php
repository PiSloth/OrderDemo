<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OrderDashboard extends Component
{
    public function render()
    {
        $query = Order::join('statuses','orders.status_id','=','statuses.id')
        ->select('statuses.name as status_name', DB::raw('status_id,count(status_id) As totalStatusCount'))
        ->groupBy('status_id')
        ->get();

        $results  = $query->pluck('totalStatusCount', 'status_name')->all();


        $byStatus = $query->pluck('status_name');
        $byStatusCount = $query->pluck('totalStatusCount');

        return view('livewire.orders.order-dashboard',[
            'results' => $results,
            'byStatusCount' => $byStatusCount,
        ]);
    }
}
