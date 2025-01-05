<?php

namespace App\Livewire\Order\Psi\Branch;

use App\Models\Branch;
use App\Models\BranchPsiProduct;
use App\Models\ProductPhoto;
use App\Models\PsiStock;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class StockUpdate extends Component
{
    public $branch_id;
    protected $transactions = [];
    public $image = '';

    public function mount()
    {
        $this->branch_id = auth()->user()->branch_id;

        // dd($this->branch_id);
    }

    public function selectedImage($id)
    {
        $this->image = ProductPhoto::findOrFail($id)->image;
        $this->dispatch('openModal', 'imageModal');
    }

    public function transactionHistory($id)
    {
        $this->transactions = StockTransaction::wherePsiStockId($id)->orderBy('created_at', 'desc')->get();

        $this->dispatch('openModal', 'historyModal');
    }

    public function updateValue($id, $amount)
    {
        $stock = PsiStock::findOrFail($id);

        DB::transaction(function () use ($stock, $amount) {
            StockTransaction::create([
                'user_id' => auth()->user()->id,
                'remark' => "Stock balance is changed from " . $stock->inventory_balance . ' to ' . $amount,
                'psi_stock_id' => $stock->id,
                'qty' => $amount
            ]);

            $stock->update([
                'inventory_balance' => $amount
            ]);
        });
    }

    public function render()
    {
        $products = BranchPsiProduct::whereBranchId($this->branch_id)
            ->get();
        // dd($products);

        return view('livewire.order.psi.branch.stock-update', [
            'products' => $products,
            'transactions' => $this->transactions,
            'branches' => Branch::all(),
        ]);
    }
}
