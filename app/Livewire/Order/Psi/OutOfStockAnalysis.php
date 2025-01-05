<?php

namespace App\Livewire\Order\Psi;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class OutOfStockAnalysis extends Component
{
    use WithPagination;


    #[Title('\'Out of Stock\' analysis of your business')]
    public function render()
    {

        $rawAnalysis = DB::table('branch_psi_products as bpsi')
            ->select(DB::raw('shp.name AS product,
             b.name AS branch,
              pst.inventory_balance AS balance,
              p.weight,
              (AVG(rs.qty)) AS avg_sale,
              fs_latest.qty AS focus
              '))
            ->leftJoin(
                DB::raw('(
                SELECT branch_psi_product_id, MAX(id) as latest_focus_id
                FROM focus_sales
                GROUP BY branch_psi_product_id
            ) as latest_focus'),
                'bpsi.id',
                '=',
                'latest_focus.branch_psi_product_id'
            )
            ->leftJoin('focus_sales as fs_latest', 'latest_focus.latest_focus_id', '=', 'fs_latest.id')
            ->leftJoin('psi_products as p', 'p.id', 'bpsi.psi_product_id')
            ->leftJoin('shapes as shp', 'shp.id', 'p.shape_id')
            ->leftJoin('branches as b', 'b.id', 'bpsi.branch_id')
            ->leftJoin('psi_stocks as pst', 'pst.branch_psi_product_id', 'bpsi.id')
            ->leftJoin('real_sales as rs', 'rs.branch_psi_product_id', 'bpsi.id')
            ->orderBy('shp.name')
            ->orderBy('b.name')
            ->groupBy('b.name', 'pst.inventory_balance', 'p.weight', 'shp.name', 'fs_latest.qty')
            ->get();

        $analysis = [];
        $allBranchRealSale = [];


        foreach ($rawAnalysis as $data) {
            $key = $data->product . " / " . $data->weight . " g";
            $branch = ucfirst($data->branch);

            if (!isset($analysis[$key][$branch])) {
                $analysis[$key][$branch] = [];
            }


            $analysis[$key][$branch] = [
                'balance' => $data->balance,
                'focus' => $data->focus,
                'avg_sale' => $data->avg_sale
            ];

            if (!isset($allBranchRealSale[$key])) {
                $totalSale = 0;
                $totalFocus = 0;
            }

            $totalSale += ceil($data->avg_sale);
            $totalFocus += $data->focus;

            $allBranchRealSale[$key] = [
                'total_sale' => $totalSale,
                'total_focus' => $totalFocus
            ];
        }

        foreach ($analysis as $key => $products) {
            $analysis[$key]['HO']['avg_sale'] = $allBranchRealSale[$key]['total_sale'];
            $analysis[$key]['HO']['focus'] = $allBranchRealSale[$key]['total_focus'];
            // dd($analysis[$key]['HO']);
        }

        // dd($allBranchRealSale);

        // dd($analysis);

        return view('livewire.order.psi.out-of-stock-analysis', [
            'analysis' => $analysis,
        ]);
    }
}
