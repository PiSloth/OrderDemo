<?php

namespace App\Livewire\Order\Psi;

use App\Models\BranchPsiProduct;
use App\Models\FocusSale;
use App\Models\PhotoShooting;
use App\Models\PhotoShootingStatusHistory;
use App\Models\PsiOrder;
use App\Models\PsiOrderStatusHistory;
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

class OrderDetail extends Component
{
    use Actions;

    #[Url(as: 'ord')]
    public $order_id;

    #[Url(as: 'prod')]
    public $product_id;

    #[Url(as: 'brch')]
    public $branch_id;

    public $skip = false;
    public $arrival_qty;
    public $qc_passed_qty;
    public $error_qty;
    public $transfer_qty;
    public $skip_note;
    public $schedule_date;

    // public function mount() {}

    public function skipFun()
    {
        // dd("Skip");
        $this->skip = !$this->skip;
    }
    public function updateArrival()
    {
        $this->validate([
            'arrival_qty' => 'required|numeric'
        ]);


        if ($this->skip) {
            // dd($this->skip);
            $this->validate([
                'skip_note' => 'required'
            ]);
        } else {
            $this->validate([
                'schedule_date' => 'required|date'
            ]);
        }

        $orderQuery = PsiOrder::findOrFail($this->order_id)->first();

        DB::transaction(function () use ($orderQuery) {

            $orderQuery->update([
                'arrival_qty' => $this->arrival_qty,
                'psi_status_id' => 4, //arrival
            ]);

            PsiOrderStatusHistory::create([
                'psi_order_id' => $this->order_id,
                'psi_status_id' => $orderQuery->psi_status_id,
                'user_id' => auth()->user()->id,
            ]);

            if ($this->skip) {
                $photoShoot = PhotoShootingStatusHistory::create([
                    'psi_order_id' => $orderQuery->id,
                    'photo_shooting_status_id' => 1, //skip
                    'remark' => $this->skip,
                    'user_id' => auth()->user()->id,
                ]);
            } else {
                $photoShoot = PhotoShootingStatusHistory::create([
                    'psi_order_id' => $orderQuery->id,
                    'photo_shooting_status_id' => 2, //schedule
                    'user_id' => auth()->user()->id,
                ]);

                PhotoShooting::create([
                    'psi_order_id' => $orderQuery->id,
                    'photo_shooting_status_id' => $photoShoot->photo_shooting_status_id,
                    'schedule_date' => $this->schedule_date,
                ]);
            }
        });


        $this->reset('arrival_qty', 'skip_note', 'schedule_date');

        $this->dispatch('close-modal');

        $this->dialog([
            'title' => 'Nice',
            'description' => 'Arrival qty updated successfully.',
            'icon' => 'success'
        ]);
    }

    //! QC Passed
    public function updateQC()
    {
        $this->validate([
            'qc_passed_qty' => 'required|numeric'
        ]);

        $query = PsiOrder::findOrFail($this->order_id)->first();

        $query->update([
            'qc_passed_qty' => $this->qc_passed_qty,
            'pis_status_id' => 5, //QC Passed
        ]);

        $this->reset('qc_passed_qty');


        $this->dialog([
            'title' => 'Nice',
            'description' => 'QC Passed qty updated successfully.',
            'icon' => 'success'
        ]);
    }

    public function updateError()
    {
        $this->validate([
            'error_qty' => 'required|numeric'
        ]);

        $query = PsiOrder::findOrFail($this->order_id)->first();

        $query->update([
            'error_qty' => $this->error_qty,
        ]);

        $this->reset('error_qty');

        $this->dialog([
            'title' => 'Nice',
            'description' => 'Error qty updated successfully.',
            'icon' => 'success'
        ]);
    }

    public function updateTransfer()
    {
        $this->validate([
            'transfer_qty' => 'required|numeric'
        ]);

        $query = PsiOrder::findOrFail($this->order_id)->first();

        $inventory = PsiOrder::select('psi_stocks.id AS stockId', 'psi_stocks.inventory_balance')
            ->leftJoin('psi_stocks', 'psi_stocks.branch_psi_product_id', '=', 'psi_orders.branch_psi_product_id')
            ->where('psi_orders.id', '=', $this->order_id)
            ->first();

        $stockQuery = PsiStock::findOrFail($inventory->stockId)->first();

        DB::transaction(function () use ($query, $inventory, $stockQuery) {

            $query->update([
                'transfer_qty' => $this->transfer_qty,
                'psi_status_id' => 8 //Transfered to branch
            ]);

            //! todo update inventory balance
            $stockQuery->update([
                'inventory_balance' => $inventory->inventory_balance +  $this->transfer_qty,
            ]);

            StockTransaction::create([
                'psi_stock_id' => $inventory->stockId,
                'stock_transaction_type_id' => 4, // inventory adjust out
                'qty' => $this->transfer_qty,
                'remark' => "Order arrival to Branch",
                'user_id' => auth()->user()->id,
            ]);

            $this->generateReorderPoint();
        });



        $this->reset('transfer_qty');

        $this->dialog([
            'title' => 'Nice',
            'description' => 'Transfer qty updated successfully.',
            'icon' => 'success'
        ]);
    }

    //! reorder point generate
    public function generateReorderPoint()
    {

        $productQuery = BranchPsiProduct::select(
            'branch_psi_products.id',
            'reorder_points.safty_day',
            'psi_stocks.inventory_balance',
            'psi_stocks.id AS stockId',
            DB::raw('(SELECT focus_sales.qty
                      FROM focus_sales
                      WHERE focus_sales.branch_psi_product_id = branch_psi_products.id
                      ORDER BY focus_sales.id DESC LIMIT 1) AS latest_focus'),

            DB::raw('(SELECT AVG(psi_prices.lead_day)
		                FROM psi_prices
		                WHERE psi_prices.psi_product_id = branch_psi_products.psi_product_id
                        ) AS lead_day')
        )
            ->leftJoin('psi_stocks', 'psi_stocks.branch_psi_product_id', '=', 'branch_psi_products.id')
            ->leftJoin('reorder_points', 'reorder_points.psi_stock_id', '=', 'psi_stocks.id')
            ->leftJoin('psi_orders', 'psi_orders.branch_psi_product_id', '=', 'branch_psi_products.id')
            ->leftJoin('psi_prices', 'psi_orders.psi_price_id', '=', 'psi_prices.id')
            ->first();


        // dd($productQuery->leadDay);

        $saftyPoint = ($productQuery->lead_day + $productQuery->safty_day) * $productQuery->latest_focus;
        // dd($saftyPoint);

        $totalDayToSale = ($productQuery->inventory_balance - $saftyPoint) / $productQuery->latest_focus;
        $totalDayToSale = (int) $totalDayToSale;
        //
        //todo 2 - Reorder Due Date => $orderDueDate
        $orderDueDate = Carbon::now()->addDays($totalDayToSale);
        // dd($totalDayToSale);

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
            case $totalDayToSale < 0:
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

        $safty_day = $productQuery->safty_day;
        $stockId = $productQuery->stockId;

        DB::transaction(function () use ($stockId, $saftyPoint, $orderDueDate, $stockStatus, $safty_day) {

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
        // use Illuminate\Support\Facades\DB;



        // dd($productQuery);
        $order = PsiOrder::findOrFail($this->order_id);
        // dd($order);


        return view('livewire.order.psi.order-detail', [
            'order' => $order,
        ]);
    }
}
