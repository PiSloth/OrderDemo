<?php

namespace App\Livewire\Order\Psi;

use App\Models\BranchPsiProduct;
use App\Models\FocusSale;
use App\Models\PsiProduct;
use App\Models\PsiStock;
use App\Models\PsiSupplier;
use App\Models\RealSale;
use App\Models\ReorderPoint;
use App\Models\StockTransaction;
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
    public $sale_qty;
    public $sale_date;
    public $stock_id;

    public function mount()
    {
        $branchProduct = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first();

        // dd($branchProduct->id);

        // dd($branchProduct->id);
        $this->branchProductId = $branchProduct->id;
        $this->branchName = $branchProduct->branch->name;

        $this->stock_id = BranchPsiProduct::select('psi_stocks.id')
            ->leftJoin('psi_stocks', 'psi_stocks.branch_psi_product_id', 'branch_psi_products.id')
            ->where('branch_psi_products.id', '=', $this->branchProductId)
            ->first()->id;

        // dd($stock);
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
            'sale_qty' => 'required|numeric',
            'sale_date' => 'required|date',
        ]);

        //! find safty day and avgerage lead day
        $productQuery = BranchPsiProduct::select(
            'branch_psi_products.id',
            'psi_stocks.id AS stock_id',
            DB::raw('AVG(psi_prices.lead_day) + reorder_points.safty_day AS total_lead_days,reorder_points.safty_day AS safty_day')
        )
            ->leftJoin('psi_stocks', 'psi_stocks.branch_psi_product_id', 'branch_psi_products.id')
            ->leftJoin('psi_products', 'psi_products.id', 'branch_psi_products.psi_product_id')
            ->leftJoin('psi_suppliers', 'psi_suppliers.psi_product_id', 'psi_products.id')
            ->leftJoin('psi_prices', 'psi_prices.id', 'psi_suppliers.psi_price_id')
            ->leftJoin('reorder_points', 'psi_stocks.id', 'reorder_points.psi_stock_id')
            ->where('branch_psi_products.id', $this->branchProductId)
            ->groupBy('reorder_points.safty_day', 'branch_psi_products.id', 'psi_stocks.id')
            ->first();

        // dd($productQuery);

        //! todo find last focus qty
        $lastFocus = FocusSale::whereBranchPsiProductId($this->branchProductId)
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

        //End return if not found foucs qty


        //todo safty Point => (saft day + avg lead day) * last focus
        $saftyPoint = $productQuery->total_lead_days * $focusQty;

        //find reorder data history
        $reorderData = ReorderPoint::wherePsiStockId($this->stock_id)->first();

        $psiProduct = PsiStock::findOrFail($this->stock_id);
        // dd($psiProduct);

        $findInitialSaleData = RealSale::where('sale_date', $this->sale_date)
            ->where('branch_psi_product_id', $productQuery->id)
            ->first();

        // dd($findInitialSaleData);
        // dd($findInitialSaleData->qty + $psiProduct->inventory_balance < $this->sale_qty);

        //! Check daily sale record and check sale amount and actual stock balance

        if ($findInitialSaleData) {
            if ($findInitialSaleData->qty + $psiProduct->inventory_balance < $this->sale_qty) {
                $this->dispatch('close-modal');

                $this->dialog([
                    'title' => 'Failed to Update',
                    'description' => '\'Sale qty\' greater than \'Stock\'',
                    'icon' => 'error',
                ]);
                return; // return if old record + invstock less than input
            }
        } else {
            if ($psiProduct->inventory_balance < $this->sale_qty) {

                $this->dispatch('close-modal');

                $this->dialog([
                    'title' => 'Failed to Update',
                    'description' => 'There\'s no enough quantity',
                    'icon' => 'error',
                ]);
                return;
            }
        }

        DB::transaction(function () use ($psiProduct, $productQuery, $focusQty, $reorderData, $saftyPoint, $findInitialSaleData) {


            // dd($findInitialSaleData);

            //when found same date and try to input next time
            if ($findInitialSaleData !== null) {
                //! Stock transaction create
                StockTransaction::create([
                    'psi_stock_id' =>  $this->stock_id,
                    'stock_transaction_type_id' => 6, //wrong input return
                    'qty' => $findInitialSaleData->qty,
                    'remark' => "Branch sale transactions.",
                    'user_id' => auth()->user()->id,

                ]);

                // Addition worng sale qty to main stock and subtraction updated sale qty
                $psiProduct->update([
                    'inventory_balance' => $psiProduct->inventory_balance  + $findInitialSaleData->qty
                ]);

                //update real sale qty
                $findInitialSaleData->update([
                    'qty' => $this->sale_qty,
                    'user_id' => auth()->user()->id,
                ]);


                // Subtraction inventory balance
                $psiProduct->update([
                    'inventory_balance' => $psiProduct->inventory_balance - $this->sale_qty
                ]);

                // dd("Helo");
            } else {

                //real sale record created
                RealSale::create([
                    'branch_psi_product_id' => $productQuery->id,
                    'qty' => $this->sale_qty,
                    'sale_date' => $this->sale_date,
                    'user_id' => auth()->user()->id,
                ]);

                //inventory balance update
                $psiProduct->update([
                    'inventory_balance' => $psiProduct->inventory_balance - $this->sale_qty
                ]);

                //! Stock transaction create
                StockTransaction::create([
                    'psi_stock_id' => $this->stock_id,
                    'stock_transaction_type_id' => 3, //sale
                    'qty' => $this->sale_qty,
                    'remark' => "Branch sale transactions.",
                    'user_id' => auth()->user()->id,

                ]);
            }

            //! update inventory stock data
            // $psiProduct->update([
            //     'inventory_balance' => $psiProduct->inventory_balance - $this->sale_qty,
            // ]);

            //! todo reorder_due_date => dayDiff with now/ sub or add days?

            // dd($);

            $inventoryBalance  = PsiStock::findOrFail($this->stock_id)->inventory_balance;

            $netBalance = $inventoryBalance - $saftyPoint;

            $totalDayToSale = $netBalance / $focusQty;

            if ($netBalance < 0) {
                $subDay = ceil($netBalance / $focusQty * -1);
                $orderDueDate = Carbon::now()->subDays($subDay); //? due date got
            } else {
                $addDay = (int) $totalDayToSale;
                $orderDueDate = Carbon::now()->addDays($addDay); //? due date got
            }
            //end due date

            //! todo 4-  stock status change
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

            $reorderData->update([
                'psi_stock_id' => $this->stock_id,
                'safty_day' => $productQuery->safty_day,
                'reorder_point' => $saftyPoint,
                'reorder_due_date' => $orderDueDate,
                'psi_stock_status_id' => $stockStatus
            ]);
        });

        $this->dispatch('close-modal');
        $this->reset('sale_qty', 'sale_date');

        $this->dialog([
            'title' => 'Updated',
            'description' => 'Sale Quantity updaed.',
            'icon' => 'success'
        ]);
    }

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

        $productLeadDay = PsiSupplier::select(DB::raw('AVG(psi_prices.lead_day) AS leadDay'))
            ->leftJoin('psi_prices', 'psi_prices.id', 'psi_suppliers.psi_price_id')
            ->where('psi_suppliers.psi_product_id', $this->product_id)
            ->first();

        $focusHistories = FocusSale::where('branch_psi_product_id', '=', $this->branchProductId)->get();

        // dd($productLeadDay);

        // dd($saleData);
        $productDetail = PsiProduct::findOrFail($this->product_id);
        // dd($productDetail);

        return view('livewire.order.psi.focus', [
            'product' => $productDetail,
            'pastResult' => $mergeData,
            'productLeadDay' => $productLeadDay,
            'foucsHistories' => $focusHistories,
        ]);
    }
}
