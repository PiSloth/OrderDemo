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
use Livewire\Component;
use WireUi\Traits\Actions;

class DailySale extends Component
{
    use Actions;
    public $sale_qty;
    public $stock_id;
    public $sale_date;
    public $detail;
    public $branchPsiProductId;
    public $sale_history_date;

    public function mount()
    {
        $this->sale_history_date = Carbon::now()->toDateString();
        // dd($this->sale_history_date);
    }

    public function updatedSaleHistoryDate($value)
    {
        if (!$value) {
            $this->sale_history_date = Carbon::now()->toDateString();
        }
    }

    //initialize before update sale quantity
    public function initializeUpdate($id)
    {
        $this->stock_id = $id;

        // dd($id);
    }

    // data initialize before viewing daily sale history
    public function initializeSaleHsitory($stock_id)
    {

        $this->branchPsiProductId = PsiStock::findOrFail($stock_id)->branch_psi_product_id;
    }

    public function initializeDailySale($record_id)
    {
        $saleRecord = RealSale::findOrFail($record_id);
        $this->sale_date = $saleRecord->sale_date;
        $this->sale_qty = $saleRecord->qty;

        // dd($this->sale_date);
    }

    //update sale quantity
    public function updateSale()
    {
        $this->validate([
            'sale_qty' => 'required|numeric',
            'sale_date' => 'required',
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
            ->where('psi_stocks.id', $this->stock_id)
            ->groupBy('reorder_points.safty_day', 'branch_psi_products.id', 'psi_stocks.id')
            ->first();

        // dd($productQuery);

        //! todo find last focus qty
        $lastFocus = FocusSale::whereBranchPsiProductId($productQuery->id)
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

        // dd($reorderData);

        if (!$reorderData) {
            $this->dispatch('close-modal');
            $this->dialog([
                'title' => 'Not found Order Data',
                'description' => 'Make sure inventory data is added to order page.',
                'icon' => 'error'
            ]);

            return;
        }
        // dd($reorderData);

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
                return;
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
            } else {
                // dd($findInitialSaleData);
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
                // $addDay = (int) $totalDayToSale;
                $orderDueDate = Carbon::now()->addDays((int) $totalDayToSale); //? due date got
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


    public function render()
    {
        // $saftyDay = BranchPsiProduct::select(DB::raw('AVG(psi_prices.lead_day) AS leadDay,(reorder_points.safty_day) AS saft_day'))
        //     ->leftJoin('psi_stocks', 'psi_stocks.branch_psi_product_id', 'branch_psi_product_id')
        //     ->leftJoin('psi_products', 'psi_products.id', 'branch_psi_products.psi_product_id')
        //     ->leftJoin('psi_suppliers', 'psi_suppliers.psi_product_id', 'psi_products.id')
        //     ->leftJoin('psi_prices', 'psi_prices.id', 'psi_suppliers.psi_price_id')
        //     ->leftJoin('reorder_points', 'psi_stocks.id', 'reorder_points.psi_stock_id')
        //     ->where('psi_stocks.id', 1)
        //     ->groupBy('reorder_points.safty_day')
        //     ->first();

        // dd($saftyDay);

        $products = BranchPsiProduct::select(
            'branch_psi_products.id',
            'shapes.name AS detail',
            'psi_stocks.id AS stock_id',
            'product_photos.image',
            'psi_products.weight AS weight',
            'psi_products.length AS length',
            'designs.name AS design',
            'uoms.name AS uom',
            'psi_stocks.inventory_balance AS balance',
            DB::raw('MAX(real_sales.qty) AS sale_qty')
        )
            ->leftJoin('product_photos', 'product_photos.psi_product_id', 'branch_psi_products.psi_product_id')
            ->leftJoin('psi_products', 'psi_products.id', 'branch_psi_products.psi_product_id')
            ->leftJoin('designs', 'designs.id', 'psi_products.design_id')
            ->leftJoin('uoms', 'uoms.id', 'psi_products.uom_id')
            ->leftJoin('shapes', 'shapes.id',  'psi_products.shape_id')
            ->leftJoin('psi_stocks', 'branch_psi_products.id', 'psi_stocks.branch_psi_product_id')
            ->leftJoin('real_sales', function ($join) {
                $join->on('branch_psi_products.id', '=', 'real_sales.branch_psi_product_id')
                    ->whereRaw('DATE(real_sales.sale_date) = ?',  [$this->sale_history_date]);
                // ->whereRaw('DATE(real_sales.sale_date) = ?',  [now()->toDateString()]);
            })
            ->whereBranchId(auth()->user()->branch->id)
            ->where('shapes.name', 'like', '%' . $this->detail . '%')
            // ->orderBy('real_sales.qty', 'desc')
            ->groupBy(
                'branch_psi_products.id',
                'real_sales.qty',
                'shapes.name',
                'psi_stocks.id',
                'product_photos.image',
                'psi_products.weight',
                'psi_products.length',
                'designs.name',
                'uoms.name',
                'psi_stocks.inventory_balance',
            )
            ->paginate(5);


        $saleHistories = RealSale::whereBranchPsiProductId($this->branchPsiProductId)
            // ->where('sale_date', ) // add only this month
            ->orderBy('sale_date', 'desc')
            ->get();

        // dd($this->branchPsiProductId);

        return view('livewire.order.psi.daily-sale', [
            'products' => $products,
            'saleHistories' => $saleHistories,
        ]);
    }
}
