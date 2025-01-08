<?php

namespace App\Livewire\Order\Psi;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class OutOfStockAnalysis extends Component
{
    use WithPagination;
    public $filter = [];
    public $checkbox = '';
    public $branch_1 = '';
    public $branches = [
        'br1' => 'branch 1',
        'br2' => 'branch 2',
        'br3' => 'branch 3',
        'br4' => 'branch 4',
        'br5' => 'branch 5',
        'br6' => 'branch 6',
        'ho' => 'ho',
        'online_sale' => 'online sale'
    ];
    public $selectedBranch = [];
    public $br1;
    public $br2;
    public $br3;
    public $br4;
    public $br5;
    public $br6;
    public $ho;
    public $online_sale;

    public function mount() {}

    public function updated($property, $value)
    {
        if ($value == true) {
            $this->selectedBranch[] = $this->branches[$property];
        } else {
            unset($this->selectedBranch[$property]);
        }
        // dd(in_array('branch 1', $this->selectedBranch));
    }

    public function removeFilter($key)
    {


        // Filter out the tag with the specified key
        $this->selectedBranch = array_filter($this->selectedBranch, function ($branch) use ($key) {
            // dd($branch);

            return $branch !== $key;
        });

        // Reindex the array to maintain numeric indexes
        $this->selectedBranch = array_values($this->selectedBranch);
    }



    #[Title('\'Out of Stock\' analysis of your business')]
    public function render()
    {

        $rawAnalysis = DB::table('branch_psi_products as bpsi')
            ->select(DB::raw('shp.name AS product,
             b.name AS branch,
              pst.inventory_balance AS balance,
              p.weight,
              p.length,
              uoms.name AS uom,
              photo.image,
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
            ->leftJoin('uoms', 'uoms.id', 'p.uom_id')
            ->leftJoin('product_photos AS photo', 'photo.psi_product_id', 'p.id')
            ->where('bpsi.is_suspended', '=', 'false')
            ->when(!empty($this->selectedBranch), function ($query) {
                return $query->whereIn('b.name', $this->selectedBranch);
            })
            ->orderBy('shp.name')
            ->orderBy('b.name')
            ->groupBy('b.name', 'pst.inventory_balance', 'p.weight', 'shp.name', 'fs_latest.qty', 'p.length', 'photo.image', 'uoms.name')
            ->get();


        $analysis = [];
        $allBranchRealSale = [];


        foreach ($rawAnalysis as $data) {
            $key = $data->product . " / " . $data->weight . " g/ size-" . $data->length . " " . $data->uom;
            $branch = ucfirst($data->branch);

            if (!isset($analysis[$key][$branch])) {
                $analysis[$key][$branch] = [];
            }


            $analysis[$key][$branch] = [
                'balance' => $data->balance ?? 0,
                'focus' => $data->focus,
                'avg_sale' => $data->avg_sale
            ];

            //all branch summary for ho focus and real sale
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
            $porductPhoto[$key] = $data->image ?? '';
        }

        // dd($analysis);

        foreach ($analysis as $key => $products) {
            if (isset($analysis[$key]['HO'])) {
                $analysis[$key]['HO']['avg_sale'] = $allBranchRealSale[$key]['total_sale'];
                $analysis[$key]['HO']['focus'] = $allBranchRealSale[$key]['total_focus'];
                // dd($analysis[$key]['HO']);
            }
        }

        if (!empty($this->selectedBranch)) {
            // dd($analysis);
        }
        // dd($allBranchRealSale);

        // dd($analysis);

        return view('livewire.order.psi.out-of-stock-analysis', [
            'analysis' => $analysis,
            'images' => $porductPhoto,
            'branches' => Branch::all(),
            // dd(Branch::all())
        ]);
    }
}
