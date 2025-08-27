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
        'online_sale' => 'online sale',
    ];

    public $selectedBranch = [];

    // For x-select binding (branch IDs)
    public $selectedBranchIds = [];

    public $br1;

    public $br2;

    public $br3;

    public $br4;

    public $br5;

    public $br6;

    public $ho;

    public $online_sale;

    // public function mount() {}

    public function updated($property, $value)
    {
        // Only handle checkbox-driven branch filters (ignore x-select array updates)
        if (! array_key_exists($property, $this->branches)) {
            return;
        }

        $label = $this->branches[$property] ?? null;
        if ($label === null) {
            return;
        }

        if ($value === true) {
            // add if not already present
            if (! in_array($label, $this->selectedBranch, true)) {
                $this->selectedBranch[] = $label;
            }
        } else {
            // remove by value and reindex
            $this->selectedBranch = array_values(array_filter(
                $this->selectedBranch,
                fn($b) => $b !== $label
            ));
        }
    }

    public function updatedSelectedBranchIds($value): void
    {
        // Normalize to integer IDs and reset pagination for fresh results
        $this->selectedBranchIds = array_values(
            array_filter(array_map('intval', (array) $value), fn($v) => $v > 0)
        );
        $this->resetPage();
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
        // Pre-aggregate subqueries to avoid large GROUP BY on the main query
        $latestFocusSub = DB::table('focus_sales')
            ->select('branch_psi_product_id', DB::raw('MAX(id) as latest_focus_id'))
            ->groupBy('branch_psi_product_id');

        $avgSaleSub = DB::table('real_sales')
            ->select('branch_psi_product_id', DB::raw('AVG(qty) as avg_sale'))
            ->groupBy('branch_psi_product_id');

        $latestStockSub = DB::table('psi_stocks')
            ->select('branch_psi_product_id', DB::raw('MAX(id) as latest_stock_id'))
            ->groupBy('branch_psi_product_id');

        $latestPhotoSub = DB::table('product_photos')
            ->select('psi_product_id', DB::raw('MAX(id) as latest_photo_id'))
            ->groupBy('psi_product_id');

        $rawAnalysis = DB::table('branch_psi_products as bpsi')
            ->join('psi_products as p', 'p.id', '=', 'bpsi.psi_product_id')
            ->leftJoin('shapes as shp', 'shp.id', '=', 'p.shape_id')
            ->leftJoin('branches as b', 'b.id', '=', 'bpsi.branch_id')
            ->leftJoin('uoms', 'uoms.id', '=', 'p.uom_id')
            // Latest focus per branch_psi_product
            ->leftJoinSub($latestFocusSub, 'latest_focus', function ($join) {
                $join->on('latest_focus.branch_psi_product_id', '=', 'bpsi.id');
            })
            ->leftJoin('focus_sales as fs_latest', 'fs_latest.id', '=', 'latest_focus.latest_focus_id')
            // Average sale per branch_psi_product
            ->leftJoinSub($avgSaleSub, 'rs_avg', function ($join) {
                $join->on('rs_avg.branch_psi_product_id', '=', 'bpsi.id');
            })
            // Latest stock per branch_psi_product
            ->leftJoinSub($latestStockSub, 'ls', function ($join) {
                $join->on('ls.branch_psi_product_id', '=', 'bpsi.id');
            })
            ->leftJoin('psi_stocks as pst', 'pst.id', '=', 'ls.latest_stock_id')
            // Latest product photo per psi_product
            ->leftJoinSub($latestPhotoSub, 'lp', function ($join) {
                $join->on('lp.psi_product_id', '=', 'p.id');
            })
            ->leftJoin('product_photos as photo', 'photo.id', '=', 'lp.latest_photo_id')
            ->where('bpsi.is_suspended', '=', 'false')
            // Prefer filtering by selected IDs from x-select if provided
            ->when(! empty($this->selectedBranchIds), function ($query) {
                return $query->whereIn('b.id', $this->selectedBranchIds);
            })
            // Backward-compat: fallback to name-based selections (checkbox tags)
            ->when(empty($this->selectedBranchIds) && ! empty($this->selectedBranch), function ($query) {
                return $query->whereIn('b.name', $this->selectedBranch);
            })
            ->orderBy('shp.name')
            ->orderBy('b.name')
            ->select([
                'shp.name AS product',
                'b.name AS branch',
                'pst.inventory_balance AS balance',
                'p.weight',
                'p.length',
                'uoms.name AS uom',
                'photo.image',
                DB::raw('COALESCE(rs_avg.avg_sale, 0) AS avg_sale'),
                'fs_latest.qty AS focus',
            ])
            ->get();

        $analysis = [];
        $allBranchRealSale = [];
        $porductPhoto = [];

        foreach ($rawAnalysis as $data) {
            $key = $data->product . ' / ' . $data->weight . ' g/ size-' . $data->length . ' ' . $data->uom;
            $branch = ucfirst($data->branch);

            if (! isset($analysis[$key][$branch])) {
                $analysis[$key][$branch] = [];
            }

            $analysis[$key][$branch] = [
                'balance' => $data->balance ?? 0,
                'focus' => $data->focus,
                'avg_sale' => $data->avg_sale,
            ];

            //all branch summary for ho focus and real sale
            if (! isset($allBranchRealSale[$key])) {
                $totalSale = 0;
                $totalFocus = 0;
                $totalStock = 0;
            }

            $totalSale += ceil($data->avg_sale);
            $totalFocus += $data->focus;
            $totalStock += $data->balance;

            $allBranchRealSale[$key] = [
                'total_sale' => $totalSale,
                'total_focus' => $totalFocus,
                'total_stock' => $totalStock,
            ];
            $porductPhoto[$key] = $data->image ?? '';
        }



        if (! empty($this->selectedBranch)) {
            // dd($analysis);
        }
        // dd($allBranchRealSale);

        // dd($analysis);
        $productPhoto = [];

        return view('livewire.order.psi.out-of-stock-analysis', [
            'analysis' => $analysis,
            'images' => $porductPhoto,
            'branchOptions' => Branch::select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(function ($b) {
                    $b->name = ucfirst($b->name);
                    return $b;
                }),
            'totals' => $allBranchRealSale,
            // dd(Branch::all())
        ]);
    }
}
