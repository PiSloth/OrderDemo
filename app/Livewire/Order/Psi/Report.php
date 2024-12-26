<?php

namespace App\Livewire\Order\Psi;

use App\Models\RealSale;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Report extends Component
{
    public function render()
    {
        $products = RealSale::select('shapes.name as product', 'branches.name', 'psi_products.weight', DB::raw('SUM(real_sales.qty) AS total_sale'))
            ->leftJoin('branch_psi_products', 'branch_psi_products.id', 'real_sales.branch_psi_product_id')
            ->leftJoin('psi_products', 'psi_products.id', 'branch_psi_products.psi_product_id')
            ->leftJoin('branches', 'branches.id', 'branch_psi_products.branch_id')
            ->leftJoin('shapes', 'shapes.id', 'psi_products.shape_id')
            ->groupBy('shapes.name', 'branches.name', 'psi_products.weight')
            ->orderBy('shapes.id')
            ->get();

        //     $mergeData = [];

        //     foreach($products as $product){

        //     }
        // dd($products);

        return view('livewire.order.psi.report', [
            'products' => $products,
        ]);
    }
}
