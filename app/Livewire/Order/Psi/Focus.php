<?php

namespace App\Livewire\Order\Psi;

use App\Models\BranchPsiProduct;
use App\Models\FocusSale;
use App\Models\PsiProduct;
use App\Models\PsiStock;
use App\Models\PsiSupplier;
use App\Models\RealSale;
use App\Models\ReorderPoint;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use WireUi\Traits\Actions;

class Focus extends Component
{
    use Actions;

    #[Url(as: 'brch')]
    public $branch_id;

    #[Url(as: 'prod')]
    public $product_id;

    public $branchName;
    public $branchProductId;
    public $focus_quantity;
    public $sale_quantity;
    public $sale_date;

    public function mount()
    {
        $branchProduct = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first();

        // dd($branchProduct->id);
        $this->branchProductId = $branchProduct->id;
        $this->branchName = $branchProduct->branch->name;
    }

    //todo focus save
    public function focusSave()
    {
        if ($this->focus_quantity == 0) {
            $this->dialog([
                'title' => 'Really ?',
                'description' => 'Can\'t add 0, it must be > 0 ',
                'icon' => 'error'
            ]);
            return;
        }

        //! break if not found Lead day
        $porductSupplierCount = PsiSupplier::wherePsiProductId($this->product_id)->count();

        if ($porductSupplierCount < 1) {
            $this->dispatch('close-modal');

            $this->dialog([
                'title'       => 'Not enough data',
                'description' => 'Supplier price  is not found!',
                'icon'        => 'error'
            ]);
            return;
        }

        $this->validate([
            'focus_quantity' => 'required|numeric'
        ]);

        FocusSale::create([
            'branch_psi_product_id' => $this->branchProductId,
            'qty' => $this->focus_quantity,
            'user_id' => auth()->user()->id,
        ]);

        $this->generateReorderPoint();


        $this->reset('focus_quantity');

        $this->notification([
            'title'       => 'Success',
            'description' => "Focus Add Successed!",
            'icon'        => 'success'
        ]);
    }

    //todo sale qty update
    public function saleUpdate()
    {
        $this->validate([
            'sale_quantity' => 'required|numeric',
            'sale_date' => 'required|date',
        ]);



        //! break if not found Lead day
        $porductSupplierCount = PsiSupplier::wherePsiProductId($this->product_id)->count();

        if ($porductSupplierCount < 1) {
            $this->dispatch('close-modal');

            $this->dialog([
                'title'       => 'Not enough data',
                'description' => 'Supplier price  is not found!',
                'icon'        => 'error'
            ]);
            return;
        }

        $queryStock = PsiStock::whereBranchPsiProductId($this->branchProductId)->first();

        if (!$queryStock) {
            $queryStock = PsiStock::create([
                'branch_psi_product_id' => $this->branchProductId,
                'inventory_balance' => 0,
            ]);
        }

        //! When real sale greater than real stock balance
        if ($queryStock->inventory_balance < $this->sale_quantity) {
            $this->dialog([
                'title'       => 'Not enough stock',
                'description' => 'Inventory balance is less than real sale!',
                'icon'        => 'error'
            ]);
            return;
        }

        //! Check initial data exists or not
        $findInitialSaleData = RealSale::where('sale_date', $this->sale_date)->first();
        // dd($findInitialSaleData);

        //? trying to commit sale data



        DB::transaction(function () use ($queryStock, $findInitialSaleData) {

            if ($findInitialSaleData !== null) {

                // readd worng sale qty
                $queryStock->update([
                    'inventory_balance' => $queryStock->inventory_balance  + $findInitialSaleData->qty
                ]);

                //update sale qty
                $findInitialSaleData->update([
                    'qty' => $this->sale_quantity
                ]);

                //Sub inventory balance
                $queryStock->update([
                    'inventory_balance' => $queryStock->inventory_balance - $this->sale_quantity
                ]);
            } else {
                RealSale::create([
                    'branch_psi_product_id' => $this->branchProductId,
                    'qty' => $this->sale_quantity,
                    'sale_date' => $this->sale_date,
                ]);

                $queryStock->update([
                    'inventory_balance' => $queryStock->inventory_balance - $this->sale_quantity
                ]);
            }



            $this->generateReorderPoint();
        });


        $this->reset('sale_date', 'sale_quantity');

        if ($findInitialSaleData !== null) {
            $this->notification([
                'title' => 'Updated',
                'description' => 'Sale quantity successfully updated',
                'icon' => 'success'
            ]);
        } else {
            $this->notification([
                'title' => 'Created',
                'description' => 'Sale quantity created successfully',
                'icon' => 'success'
            ]);
        }
    }


    //! reorder point generate
    public function generateReorderPoint()
    {
        //todo find branch pis product
        $branchPsiProductId = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first();
        $branchPsiProductId = $branchPsiProductId->id;

        //todo find stock info
        $stockInfo = PsiStock::whereBranchPsiProductId($branchPsiProductId)->first();
        $stockId = $stockInfo->id;


        //todo find last focus qty
        $lastFocus = FocusSale::whereBranchPsiProductId($branchPsiProductId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastFocus) {
            $focusQty = 1;

            $this->notification([
                'title' => 'Warning',
                'description' => 'Focus qty is set to default 0!',
                'icon' => 'warning',
            ]);
        } else if ($lastFocus->qty >= 0) {
            $focusQty = $lastFocus->qty;
        } else {
            $this->notification([
                'title' => 'Error',
                'description' => 'Focus qty is not found!',
                'icon' => 'Error',
            ]);
            return;
        }

        //todo find inv balance
        $invBalance = PsiStock::whereBranchPsiProductId($branchPsiProductId)->first();
        $invBalance = $invBalance->inventory_balance;

        //todo 1 - Calculate totoal delivered days + Safty Day => $saftyPoint
        //todo find lead day
        $productLeadDay = PsiSupplier::select(DB::raw('SUM(psi_prices.lead_day) AS leadDay'))
            ->leftJoin('psi_prices', 'psi_prices.id', 'psi_suppliers.psi_price_id')
            ->where('psi_suppliers.psi_product_id', $this->product_id)
            ->first();

        //todo get safty day
        $saftyDay = BranchPsiProduct::select('reorder_points.safty_day')
            ->leftJoin('psi_stocks', 'psi_stocks.branch_psi_product_id', 'branch_psi_product_id')
            ->leftJoin('reorder_points', 'psi_stocks.id', 'reorder_points.psi_stock_id')
            ->where('psi_stocks.id', $stockId)
            ->first();
        // dd($saftyDay->safty_day == null);

        if ($saftyDay->safty_day == null) {
            $safty_day = 3; //default value 3

            $this->notification([
                'title' => 'Default 3 Days',
                'description' => 'Didn\'t find safty day',
                'error' => 'warning',
            ]);
        } else {
            $safty_day = $saftyDay->safty_day;
        }
        // dd($safty_day);

        $productLeadDay = $productLeadDay->leadDay;
        $saftyPoint = ($productLeadDay + $safty_day) * $focusQty;

        //todo check stock level and SET due date
        $balance = $invBalance - $saftyPoint;
        $totalDayToSale = $balance / $focusQty;
        if ($balance < 0) {
            $subDay = ceil($balance / $focusQty * -1);
            // dd($subDay);

            $orderDueDate = Carbon::now()->subDays($subDay);
            // dd($orderDueDate);
        } else {
            $addDay = (int) $totalDayToSale;
            // dd($totalDayToSale);
            // $totalDayToSale = (int) $totalDayToSale;
            //
            //todo 2 - Reorder Due Date => $orderDueDate
            $orderDueDate = Carbon::now()->addDays($addDay);
        }


        //todo 3 - Stock Status => $stockStatus
        switch (true) {
            case $totalDayToSale >= 10:
                $stockStatus = 1; // balanced
                break;
            case $totalDayToSale >= 6:
                $stockStatus = 2; //warning
                break;
            case $totalDayToSale > 0 && $totalDayToSale < 6:
                $stockStatus = 3; //Emergency
                break;
            case $totalDayToSale <= 0:
                $stockStatus = 4; //
                break;
            default:
                $this->notification([
                    'title' => "Warning",
                    'description' => 'No status code, Code Logical error!',
                    'icon' => 'warning',
                ]);
                break;
        }

        DB::transaction(function () use ($stockId, $saftyPoint, $orderDueDate, $stockStatus, $safty_day) {
            // dd($orderDueDate);

            $reorderData = ReorderPoint::wherePsiStockId($stockId)->exists();


            if ($reorderData) {
                $reorderData = ReorderPoint::wherePsiStockId($stockId)->first();

                $reorderData->update([
                    'psi_stock_id' => $stockId,
                    'safty_day' => $safty_day,
                    'reorder_point' => $saftyPoint,
                    'reorder_due_date' => $orderDueDate,
                    'psi_stock_status_id' => $stockStatus
                ]);

                $this->notification([
                    'title' => 'Safty Day',
                    'description' => 'Updated Successful',
                    'icon' => 'success'
                ]);
            } else {

                ReorderPoint::create([
                    'psi_stock_id' => $stockId,
                    'safty_day' => $safty_day,
                    'reorder_point' => $saftyPoint,
                    'reorder_due_date' => $orderDueDate,
                    'psi_stock_status_id' => $stockStatus
                ]);

                $this->notification([
                    'title' => 'Safty Day',
                    'description' => 'Created sccessful',
                    'icon' => 'success'
                ]);
            }
        });

        $this->dispatch('close-modal');
        // $this->reset('safty_day');

        $this->dialog([
            'title' => 'Reorder Point Regenerated',
            'description' => 'Reorder point is successfully generated',
            'icon' => 'success'
        ]);
    }

    public function render()
    {



        $querySale = BranchPsiProduct::select(
            DB::raw("DATE_FORMAT(real_sales.sale_date, '%b') as monthByReal"), // Use COALESCE for month name
            DB::raw("YEAR(real_sales.sale_date) as yearByReal"), // Use COALESCE for year
            // DB::raw('AVG(focus_sales.qty) AS avgFocus'), // Calculate average of focus sales quantity
            DB::raw('ROUND(AVG(real_sales.qty)) AS avgRealSale') // Calculate average of real sales quantity
        )
            ->leftJoin('focus_sales', 'focus_sales.branch_psi_product_id', '=', 'branch_psi_products.id')
            ->leftJoin('real_sales', 'real_sales.branch_psi_product_id', '=', 'branch_psi_products.id')
            ->where('real_sales.branch_psi_product_id', '=', $this->branchProductId)
            ->groupBy(DB::raw("DATE_FORMAT(real_sales.sale_date, '%b')"))
            ->groupBy(DB::raw("YEAR(real_sales.sale_date)"))
            ->get();


        $queryFocus = BranchPsiProduct::select(
            DB::raw("DATE_FORMAT(focus_sales.created_at, '%b') as monthByFocus"), // Use COALESCE for month name
            DB::raw("YEAR(focus_sales.created_at) as yearByFocus"), // Use COALESCE for year
            DB::raw('ROUND(AVG(focus_sales.qty)) AS avgFocus'), // Calculate average of focus sales quantity
        )
            ->leftJoin('focus_sales', 'focus_sales.branch_psi_product_id', '=', 'branch_psi_products.id')
            ->leftJoin('real_sales', 'real_sales.branch_psi_product_id', '=', 'branch_psi_products.id')
            ->where('focus_sales.branch_psi_product_id', '=', $this->branchProductId)
            ->groupBy(DB::raw("DATE_FORMAT(focus_sales.created_at, '%b')"))
            ->groupBy(DB::raw("YEAR(focus_sales.created_at)"))
            ->groupBy(DB::raw("DATE_FORMAT(real_sales.sale_date, '%b')"))
            ->groupBy(DB::raw("YEAR(real_sales.sale_date)"))
            ->get();

        // dd($queryFocus);


        //todo restructure to keyed arrays for easier mapping
        $saleData = [];

        foreach ($querySale as $sale) {
            $key = $sale->monthByReal . ' ' . $sale->yearByReal;
            $saleData[$key] = [
                'month' => $sale->monthByReal,
                'year' => $sale->yearByReal,
                'avgRealSale' => $sale->avgRealSale,
            ];
        }


        $focusData = [];



        foreach ($queryFocus as $focus) {
            $key = $focus->monthByFocus . ' ' . $focus->yearByFocus;
            $focusData[$key] = [
                'month' => $focus->monthByFocus,
                'year' => $focus->yearByFocus,
                'avgFocus' => $focus->avgFocus
            ];
        }
        //? end restructure


        //todo merge data if key is equal
        $mergeData = [];
        foreach ($focusData as $key => $focus) {
            $mergeData[$key] = [
                'month' => $focus['month'],
                'year' => $focus['year'],
                'avgFocus' => $focus['avgFocus']
            ];

            if (isset($saleData[$key])) {
                $mergeData[$key]['avgRealSale'] = $saleData[$key]['avgRealSale'];
            } else {
                $mergeData[$key]['avgRealSale'] = 0;
            }
        }

        //todo if not found key id in merge array, insert to merge array directly

        foreach ($saleData as $key => $sale) {
            if (!isset($mergeData[$key])) {
                $mergeData[$key] = [
                    'month' => $sale['month'],
                    'year' => $sale['year'],
                    'avgFocus' => 0,
                    'avgRealSale' => $sale['avgRealSale']
                ];
            }
        }
        // Define month order for sorting
        $monthOrder = [
            'Jan' => 1,
            'Feb' => 2,
            'Mar' => 3,
            'Apr' => 4,
            'May' => 5,
            'Jun' => 6,
            'Jul' => 7,
            'Aug' => 8,
            'Sep' => 9,
            'Oct' => 10,
            'Nov' => 11,
            'Dec' => 12,
        ];

        // Sort the array by year, then by month
        usort($mergeData, function ($a, $b) use ($monthOrder) {
            if ($a['year'] === $b['year']) {
                return $monthOrder[$a['month']] <=> $monthOrder[$b['month']];
            }
            return $a['year'] <=> $b['year'];
        });

        $productLeadDay = PsiSupplier::select(DB::raw('SUM(psi_prices.lead_day) AS leadDay'))
            ->leftJoin('psi_prices', 'psi_prices.id', 'psi_suppliers.psi_price_id')
            ->where('psi_suppliers.psi_product_id', $this->product_id)
            ->first();

        $focusHistories = FocusSale::where('branch_psi_product_id', '=', $this->branchProductId)->get();

        // dd($productLeadDay);

        // dd($saleData);

        return view('livewire.order.psi.focus', [
            'product' => PsiProduct::find($this->product_id)->first(),
            'pastResult' => $mergeData,
            'productLeadDay' => $productLeadDay,
            'foucsHistories' => $focusHistories,
        ]);
    }
}
