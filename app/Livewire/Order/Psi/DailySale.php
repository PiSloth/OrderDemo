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

    //initialize before update sale quantity
    public function initializeUpdate($id)
    {
        $this->stock_id = $id;

        // dd($id);
    }

    //update sale quantity
    public function updateSale()
    {
        $this->validate([
            'sale_qty' => 'required|numeric',
            'sale_date' => 'required|date',
        ]);

        //! find safty day and avgerage lead day
        $productQuery = BranchPsiProduct::select(
            'branch_psi_products.id',
            DB::raw('AVG(psi_prices.lead_day) AS lead_day,reorder_points.safty_day AS safty_day')
        )
            ->leftJoin('psi_stocks', 'psi_stocks.branch_psi_product_id', 'branch_psi_product_id')
            ->leftJoin('psi_products', 'psi_products.id', 'branch_psi_products.psi_product_id')
            ->leftJoin('psi_suppliers', 'psi_suppliers.psi_product_id', 'psi_products.id')
            ->leftJoin('psi_prices', 'psi_prices.id', 'psi_suppliers.psi_price_id')
            ->leftJoin('reorder_points', 'psi_stocks.id', 'reorder_points.psi_stock_id')
            ->where('psi_stocks.id', $this->stock_id)
            ->groupBy('reorder_points.safty_day', 'branch_psi_products.id')
            ->first();

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
        $saftyPoint = ($productQuery->safty_day + $productQuery->lead_day) * $focusQty;

        //find reorder data history
        $reorderData = ReorderPoint::wherePsiStockId($this->stock_id)->first();

        $psiProduct = PsiStock::findOrFail($this->stock_id);

        if ($psiProduct->inventory_balance < $this->sale_qty) {

            $this->dispatch('close-modal');

            $this->dialog([
                'title' => 'Failed to Update',
                'description' => 'There\'s no enough quantity',
                'icon' => 'error',
            ]);
            return;
        }

        DB::transaction(function () use ($psiProduct, $productQuery, $focusQty, $reorderData, $saftyPoint) {

            //! update inventory stock data
            $psiProduct->update([
                'inventory_balance' => $psiProduct->inventory_balance - $this->sale_qty,
            ]);

            //! todo reorder_due_date => dayDiff with now/ sub or add days?

            // dd($);

            $netBalance = $psiProduct->inventory_balance - $this->sale_qty - $saftyPoint;

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


            //! Stock transaction create
            StockTransaction::create([
                'psi_stock_id' => $this->stock_id,
                'stock_transaction_type_id' => 3, //sale
                'qty' => $this->sale_qty,
                'remark' => "Branch sale transactions.",
                'user_id' => auth()->user()->id,

            ]);

            //! Real sale history update
            RealSale::create([
                'branch_psi_product_id' => $productQuery->id,
                'qty' => $this->sale_qty,
                'sale_date' => $this->sale_date,
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

        $products = BranchPsiProduct::select('branch_psi_products.*', 'shapes.name AS detail', 'psi_stocks.id AS stock_id', 'psi_stocks.inventory_balance AS balance', 'product_photos.image')
            ->whereBranchId(auth()->user()->branch->id)
            ->leftJoin('product_photos', 'product_photos.psi_product_id', 'branch_psi_products.psi_product_id')
            ->leftJoin('psi_products', 'psi_products.id', 'branch_psi_products.psi_product_id')
            ->leftJoin('shapes', 'shapes.id',  'psi_products.shape_id')
            ->leftJoin('psi_stocks', 'branch_psi_products.id', 'psi_stocks.branch_psi_product_id')
            ->paginate(5);
        // ->get();

        // dd($products);
        return view('livewire.order.psi.daily-sale', [
            'products' => $products,
        ]);
    }
}
