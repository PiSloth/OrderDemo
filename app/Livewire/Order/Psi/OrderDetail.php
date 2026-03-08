<?php

namespace App\Livewire\Order\Psi;

use App\Models\PhotoShooting;
use App\Models\PhotoShootingStatusHistory;
use App\Models\PsiOrder;
use App\Models\PsiOrderStatusHistory;
use App\Models\PsiProduct;
use App\Models\PsiStock;
use App\Models\StockTransaction;
use App\Services\Psi\ReorderPointCalculator;
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

        $order = PsiOrder::findOrFail($this->order_id);
        $branchPsiProductId = (int) $order->branch_psi_product_id;

        /** @var ReorderPointCalculator $calculator */
        $calculator = app(ReorderPointCalculator::class);
        $result = $calculator->recalculate($branchPsiProductId);

        if (!($result['ok'] ?? false)) {
            $this->dispatch('close-modal');
            $this->notification([
                'title' => 'Error',
                'description' => $result['error'] ?? 'Reorder point calculation failed!',
                'icon' => 'error',
            ]);
            return;
        }

        if (($result['updated'] ?? false) === true) {
            $this->notification([
                'title' => 'Safty Day',
                'description' => 'Updated Successful',
                'icon' => 'success'
            ]);
        } elseif (($result['created'] ?? false) === true) {
            $this->notification([
                'title' => 'Safty Day',
                'description' => 'Created sccessful',
                'icon' => 'success'
            ]);
        }

        $this->dispatch('close-modal');

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
