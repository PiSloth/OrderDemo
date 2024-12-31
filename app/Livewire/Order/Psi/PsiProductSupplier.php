<?php

namespace App\Livewire\Order\Psi;

use App\Models\Branch;
use App\Models\BranchLeadDay;
use App\Models\BranchPsiProduct;
use App\Models\FocusSale;
use App\Models\OverDueDateOrder;
use App\Models\ProductPhoto;
use App\Models\PsiOrder;
use App\Models\PsiPrice;
use App\Models\PsiProduct;
use App\Models\PsiStock;
use App\Models\PsiSupplier;
use App\Models\ReorderPoint;
use App\Models\StockTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use PhpParser\Node\Expr\FuncCall;
use WireUi\Traits\Actions;

use function Laravel\Prompts\error;
use function Laravel\Prompts\warning;
use function Livewire\Volt\title;

class PsiProductSupplier extends Component
{
    use Actions;
    #[Title('Stock Balance & Order')]

    #[Url(as: 'prod')]
    public $product_id;

    #[Url(as: 'bch')]
    public $branch_id;

    public $supplier_id;
    public $youktwat;
    public $youktwat_in_kpy;
    public $laukkha;
    public $lead_day;
    public $product_remark;
    public $remark;
    public $photo;
    public $safty_day;
    public $branch_lead_day;
    public $stock_balance;
    public $adjust_qty;
    public $adjust_remark;
    public $psi_price_id;
    public $order_qty;


    private $stockId;
    private $porductSupplierCount;
    private $stockStatus;
    private $focusQty;
    public $branchProductId;
    public $branchName;


    // public $is_edit = false;

    public function mount()
    {
        $photo = ProductPhoto::wherePsiProductId($this->product_id)->first();
        $this->photo = $photo->image;

        $branchProduct = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first();

        // dd($branchProduct->id);

        // dd($branchProduct->id);
        $this->branchProductId = $branchProduct->id;
        $this->branchName = $branchProduct->branch->name;
    }

    //!Branch Product set Lead day except HO

    public function createLeadDay()
    {
        $this->validate([
            'branch_lead_day' => 'required|numeric',
        ]);



        $this->generateReorderPoint();

        $branchProductId = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first()->id;

        $query = BranchLeadDay::find($branchProductId);

        if ($query) {
            $query->update([
                'quantity' => $this->branch_lead_day
            ]);
        } else {
            BranchLeadDay::create([
                'quantity' => $this->branch_lead_day,
                'branch_psi_product_id' => $branchProductId,
            ]);
        }
        // dd($branchProductId);


        $this->notification([
            'icon' => 'success',
            'title' => 'Created',
            'description' => 'Successfully created.'
        ]);
    }

    public function updatedYouktwat($value)
    {
        if ($value == 0) {
            $this->youktwat_in_kpy = 0;
            return;
        }
        $value = (float) $value;

        $totalKyat = $value / 16.606;
        $kyat = (int) $totalKyat; //cut only integer
        $resultKyat = $kyat > 0 ? $kyat . ' ကျပ် ' : ''; // result to show

        $totalPae = ($totalKyat - $kyat) * 16;
        $pae = (int) $totalPae;

        $resultPae = $pae > 0 ? $pae . ' ပဲ ' : '';

        $totalYawe = ($totalPae - $pae) * 8;
        $yawe = round($totalYawe, 2);
        $resultYawe = $yawe > 0 ? $yawe .  ' ရွေး' : '';

        $this->youktwat_in_kpy = $resultKyat . $resultPae . $resultYawe;
    }

    public function editInitialize($id)
    {
        $query = PsiPrice::findOrFail($id);

        // dd($query);
        $this->supplier_id = $query->supplier_id;
        $this->youktwat = $query->youktwat;
        $this->youktwat_in_kpy = $query->youktwat_in_kpy;
        $this->laukkha = $query->laukkha;
        $this->lead_day = $query->lead_day;
        $this->product_remark = $query->product_remark;
        $this->remark = $query->remark;
    }

    public function createProductPrice()
    {
        $validated = $this->validate([
            'supplier_id' => 'required',
            'youktwat' => 'required',
            'youktwat_in_kpy' => 'required',
            'laukkha' => 'required',
            'lead_day' => 'numeric|required',
            'product_remark' => 'required',
        ]);

        DB::transaction(function () use ($validated) {
            $psiPrice = PsiPrice::create(array_merge(
                [
                    'user_id' => auth()->user()->id,
                    'psi_product_id' => $this->product_id
                ],
                $validated
            ));

            //todo check psi supplier add or not
            $productSupplier = PsiSupplier::wherePsiProductId($this->product_id)
                ->whereSupplierId($this->supplier_id)
                ->first();

            if ($productSupplier) {
                $productSupplier->update([
                    'psi_price_id' => $psiPrice->id,
                ]);
            } else {
                PsiSupplier::create([
                    'psi_price_id' => $psiPrice->id,
                    'supplier_id' => $this->supplier_id,
                    'psi_product_id' => $this->product_id,
                ]);
            }
        });

        $this->dispatch('close-modal');

        $this->notification([
            'title' => 'Success',
            'description' => 'Update successfully saved.',
            'icon' => 'success',
        ]);
        $this->reset('supplier_id', 'youktwat', 'youktwat_in_kpy', 'laukkha', 'lead_day', 'product_remark', 'remark');
    }

    //! generate new product reorder point
    public function createReorderPoint()
    {
        $validated = $this->validate([
            'safty_day' => 'required|numeric',
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

        $this->generateReorderPoint();

        $this->reset('safty_day');
    }


    //! update inventory balance and regenerate reorder point
    public function adjustmentIn()
    {

        $validated = $this->validate([
            'adjust_qty' => 'required|numeric',
            'adjust_remark' => 'required',
        ]);

        //todo find current stock level
        $branchPsiProductId = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first()->id;


        $invBalance = PsiStock::whereBranchPsiProductId($branchPsiProductId)->first();

        DB::transaction(function () use ($invBalance) {

            $invBalance->update([
                'inventory_balance' => $this->adjust_qty + $invBalance->inventory_balance,
            ]);

            StockTransaction::create([
                'psi_stock_id' => $invBalance->id,
                'stock_transaction_type_id' => 1,
                'qty' => $this->adjust_qty,
                'remark' => $this->adjust_remark,
                'user_id' => auth()->user()->id,
            ]);
            $this->generateReorderPoint();
        });
        $this->reset('adjust_qty', 'adjust_remark');
    }

    //! stock balance out
    public function adjustmentOut()
    {

        $validated = $this->validate([
            'adjust_qty' => 'required|numeric',
            'adjust_remark' => 'required',
        ]);

        //todo find current stock level
        $branchPsiProductId = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first();
        $branchPsiProductId = $branchPsiProductId->id;

        $invBalance = PsiStock::whereBranchPsiProductId($branchPsiProductId)->first();

        if ($invBalance->inventory_balance < $this->adjust_qty) {
            $this->dispatch('close-modal');
            $this->dialog([
                'title' => 'Error',
                'description' => 'Not enough stock balance',
                'icon' => 'error',
            ]);
            return;
        }

        DB::transaction(function () use ($invBalance) {

            $invBalance->update([
                'inventory_balance' =>  $invBalance->inventory_balance - $this->adjust_qty,
            ]);

            StockTransaction::create([
                'psi_stock_id' => $invBalance->id,
                'stock_transaction_type_id' => 2, // inventory adjust out
                'qty' => $this->adjust_qty,
                'remark' => $this->adjust_remark,
                'user_id' => auth()->user()->id,
            ]);
            $this->generateReorderPoint();
        });
        $this->reset('adjust_qty', 'adjust_remark');
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

        $saftyDay = BranchPsiProduct::select('reorder_points.safty_day')
            ->leftJoin('psi_stocks', 'psi_stocks.branch_psi_product_id', 'branch_psi_product_id')
            ->leftJoin('reorder_points', 'psi_stocks.id', 'reorder_points.psi_stock_id')
            ->where('psi_stocks.id', $stockInfo->id)
            ->first();


        //todo find last focus qty
        $lastFocus = FocusSale::whereBranchPsiProductId($branchPsiProductId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastFocus) {
            $this->focusQty = 1;

            $this->notification([
                'title' => 'Warning',
                'description' => 'Focus qty is set to default 0!',
                'icon' => 'warning',
            ]);
        } else if ($lastFocus->qty >= 0) {
            $this->focusQty = $lastFocus->qty;
        } else {
            $this->notification([
                'title' => 'Error',
                'description' => 'Focus qty is not found!',
                'icon' => 'Error',
            ]);
            return;
        }

        //todo find inv balance
        $invBalance = PsiStock::whereBranchPsiProductId($branchPsiProductId)->first()->inventory_balance;
        // $invBalance = $invBalance->inventory_balance;

        //todo 1 - Calculate totoal delivered days + Safty Day => $saftyPoint

        //todo find lead day
        if ($this->branch_id == 7) {
            $productLeadDay = PsiSupplier::select(DB::raw('AVG(psi_prices.lead_day) AS leadDay'))
                ->leftJoin('psi_prices', 'psi_prices.id', 'psi_suppliers.psi_price_id')
                ->where('psi_suppliers.psi_product_id', $this->product_id)
                ->first();
        } else {
            $productLeadDay = BranchLeadDay::select('quantity AS leadDay')
                ->whereBranchPsiProductId($branchPsiProductId)
                ->first();
        }



        $productLeadDay = $productLeadDay->leadDay;
        if ($this->safty_day > 0) {
            $saftyPoint = ($productLeadDay +  $this->safty_day) * $this->focusQty;
        } else {
            $saftyPoint = ($productLeadDay +  $saftyDay->safty_day) * $this->focusQty;
        }
        // dd($saftyPoint);
        //todo check stock level and SET due date
        $balance = $invBalance - $saftyPoint;
        $totalDayToSale = $balance / $this->focusQty;

        if ($balance < 0) {
            $subDay = ceil($balance / $this->focusQty * -1);

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
                $this->stockStatus = 1; // balanced
                break;
            case $totalDayToSale >= 6:
                $this->stockStatus = 2; //warning
                break;
            case $totalDayToSale > 0 && $totalDayToSale < 6:
                $this->stockStatus = 3; //Emergency
                break;
            case $totalDayToSale <= 0:
                $this->stockStatus = 4; //
                break;
            default:
                $this->notification([
                    'title' => "Warning",
                    'description' => 'No status code, Code Logical error!',
                    'icon' => 'warning',
                ]);
                break;
        }

        DB::transaction(function () use ($stockId, $saftyPoint, $orderDueDate) {

            $reorderData = ReorderPoint::wherePsiStockId($stockId)->exists();


            if ($reorderData) {
                $reorderData = ReorderPoint::wherePsiStockId($stockId)->first();


                $reorderData->update([
                    'psi_stock_id' => $stockId,
                    'safty_day' => $this->safty_day ? $this->safty_day : $reorderData->safty_day,
                    'reorder_point' => $saftyPoint,
                    'reorder_due_date' => $orderDueDate,
                    'psi_stock_status_id' => $this->stockStatus
                ]);

                $this->notification([
                    'title' => 'Success',
                    'description' => 'Safty Day Update Successful',
                    'icon' => 'success'
                ]);
            } else {

                ReorderPoint::create([
                    'psi_stock_id' => $stockId,
                    'safty_day' => $this->safty_day,
                    'reorder_point' => $saftyPoint,
                    'reorder_due_date' => $orderDueDate,
                    'psi_stock_status_id' => $this->stockStatus
                ]);

                $this->notification([
                    'title' => 'Success',
                    'description' => 'Safty Day created sccessful',
                    'icon' => 'success'
                ]);
            }
        });

        $this->dispatch('close-modal');
        $this->reset('safty_day');

        $this->dialog([
            'title' => 'Success',
            'description' => 'Reorder point is successfully created',
            'icon' => 'success'
        ]);
    }

    //! order intialize
    public function orderInitialize($id)
    {
        $this->psi_price_id = $id;
    }

    //!create Order
    public function createOrder()
    {
        $this->validate([
            'order_qty' => 'required'
        ]);




        //todo find branch pis product
        $branchPsiProductId = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first()->id;
        // $branchPsiProductId = $branchPsiProductId->id;

        //todo find stock info
        $stockId = PsiStock::whereBranchPsiProductId($branchPsiProductId)->first();

        //todo find due date
        $due_date = ReorderPoint::wherePsiStockId($stockId->id)->get('reorder_due_date')->first()->reorder_due_date;
        // dd($due_date);

        //todo find last focus qty

        // dd($lastFocus);

        $lastFocus = FocusSale::whereBranchPsiProductId($branchPsiProductId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastFocus) {
            $this->dispatch('close-modal');

            $this->dialog([
                'title' => 'Not have Enough Data',
                'description' => 'Focus Quantity dose\'nt exists.',
                'icon' => 'error',
            ]);
            return;
        } else if ($lastFocus->qty > 0) {
            $focus_sale_id = $lastFocus->id;
        } else {
            $this->notification([
                'title' => 'Error',
                'description' => 'Focus qty is not found!',
                'icon' => 'Error',
            ]);
            return;
        }

        $invBalance = $stockId->inventory_balance;
        $remainBalance = fmod($invBalance, $lastFocus->qty);

        //todo find date diff
        $dateDiff = Carbon::now()->diffInHours($due_date) / 24;
        $sale_loss = round($dateDiff * $lastFocus->qty) - $remainBalance;

        // dd($sale_loss);

        DB::transaction(function () use ($sale_loss, $due_date, $focus_sale_id, $branchPsiProductId) {
            PsiOrder::create([
                'branch_psi_product_id' => $branchPsiProductId,
                'user_id' => auth()->user()->id,
                'psi_price_id' => $this->psi_price_id,
                'order_qty' => $this->order_qty,
                'psi_status_id' =>  3 // order,
            ]);

            if ($due_date < Carbon::now()) {
                OverDueDateOrder::create([
                    'branch_psi_product_id' => $this->product_id,
                    'focus_sale_id' => $focus_sale_id,
                    'due_date' => $due_date,
                    'ordered_date' => Carbon::now(),
                    'sale_loss' => $sale_loss
                ]);

                $this->notification([
                    'title' => 'Due Date Over',
                    'description' => 'Odering over due date!',
                    'icon' => 'info'
                ]);
            }
        });
        $this->dispatch('close-modal');

        $this->dialog([
            'title' => 'Success',
            'description' => 'Order is created successfully',
            'icon' => 'success'
        ]);
        $this->reset('psi_price_id', 'order_qty');
    }

    //Stock Transfer




    public function render()
    {
        // Example of specifications array


        $productSuppliers = PsiSupplier::wherePsiProductId($this->product_id)->get();

        $branchPsiProductId = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first();

        $stockInfo = PsiStock::whereBranchPsiProductId($branchPsiProductId->id)->first();

        // dd($stockInfo->reorderPoint);

        $this->stockId = $stockInfo->id;

        // dd($branchPsiProductId);

        $lastFocus = FocusSale::whereBranchPsiProductId($branchPsiProductId->id)
            ->orderBy('id', 'desc')
            ->first();
        if ($lastFocus) {
            $lastFocus = $lastFocus->qty;
        } else {
            $lastFocus = 1;
        }

        $branchProductId = BranchPsiProduct::whereBranchId($this->branch_id)
            ->wherePsiProductId($this->product_id)
            ->first()->id;

        if ($this->branch_id == 7) {
            $productLeadDay = PsiSupplier::select(DB::raw('AVG(psi_prices.lead_day) AS leadDay'))
                ->leftJoin('psi_prices', 'psi_prices.id', 'psi_suppliers.psi_price_id')
                ->where('psi_suppliers.psi_product_id', $this->product_id)
                ->first();
        } else {
            $productLeadDay = BranchLeadDay::select('quantity AS leadDay')
                ->whereBranchPsiProductId($branchProductId)
                ->first();
        }

        $productLeadDay = ceil($productLeadDay->leadDay);

        // show currently orders, not include transfered
        $psiOrders = PsiOrder::where('psi_status_id', '<', 10)
            ->where('branch_psi_product_id', '=', $branchPsiProductId->id)
            ->get();


        //show product detail facts
        $productDetail = PsiProduct::findOrFail($this->product_id);

        //show stock that available branch
        $branchStock = PsiStock::select('psi_stocks.inventory_balance', 'branches.name', 'psi_stocks.id AS stock_id')
            ->leftJoin('branch_psi_products', 'branch_psi_products.id', 'psi_stocks.branch_psi_product_id')
            ->leftJoin('branches', 'branches.id', 'branch_psi_products.branch_id')
            ->where('branch_psi_products.psi_product_id', '=', $this->product_id)
            ->get();

        // dd($branchStock);

        return view('livewire.order.psi.psi-product-supplier', [
            'product' => $productDetail,
            'productSuppliers' => $productSuppliers,
            'stockInfo' => $stockInfo,
            'lastFocus' => $lastFocus,
            'productLeadDay' => $productLeadDay,
            'psiOrders' => $psiOrders,
            'branchStock' => $branchStock,
        ]);
    }
}
