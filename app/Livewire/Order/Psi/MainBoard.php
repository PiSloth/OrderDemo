<?php

namespace App\Livewire\Order\Psi;

use App\Models\Branch;
use App\Models\BranchPsiProduct;
use App\Models\PhotoShooting;
use App\Models\PsiOrder;
use App\Models\PsiProduct;
use App\Models\PsiStock;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\Actions;

class MainBoard extends Component
{
    use Actions;
    use WithPagination;

    public $branchId;
    public $productId;
    public $remark;
    public $orderCount;
    public $props_to_link = false;
    public $branchPsiProductId;
    public $shape_detail;

    public function setBranchPsiProduct($productId, $branchId)
    {
        $this->productId = $productId;
        $this->branchId = $branchId;
    }

    public function propsToLink($pId, $bId)
    {
        $this->productId = $pId;
        $this->branchId = $bId;

        $this->branchPsiProductId = BranchPsiProduct::where('branch_id', '=', $bId)
            ->where('psi_product_id', '=', $pId)
            ->first()->id;
        // $this->branchPsiProductId =

        $this->orderCount = PsiOrder::where('psi_orders.psi_status_id', '<', 10)
            ->where('psi_orders.branch_psi_product_id', '=', $this->branchPsiProductId)
            ->count();

        $this->props_to_link = true;



        // $this->orderCount = $psi
        // dd($psiOrders);

        $this->dispatch('defaultModalFromJs');
        //trigger to js from live wire event
    }

    public function cancle()
    {
        $this->reset('productId', 'branchId', 'remark');
    }

    public function createBranchPsiProduct()
    {
        $this->validate([
            'remark' => 'required',
        ]);
        DB::transaction(function () {

            $porductCreate = BranchPsiProduct::create([
                'remark' => $this->remark,
                'psi_product_id' => $this->productId,
                'branch_id' => $this->branchId,
                'user_id' => auth()->user()->id,
            ]);

            PsiStock::create([
                'branch_psi_product_id' => $porductCreate->id,
                'inventory_balance' => 0,
            ]);
        });


        $this->cancle(); //reset input

        $this->dispatch('close-modal');

        $this->notification([
            'title'       => 'Success!',
            'description' => "Product Successful created!",
            'icon'        => 'success'
        ]);
    }


    public function render()
    {
        // $select = ['psi_products.id', 'shapes.name AS shape', 'psi_products.length', 'uoms.name AS uom', 'psi_products.weight', 'product_photos.image'];
        $branches = Branch::all();



        // dd($select);

        // $products  = PsiProduct::select($select, DB::raw('psi_stock_statuses.name AS status, psi_stock_statuses.color AS color'))
        //     ->leftJoin('product_photos', 'product_photos.psi_product_id', 'psi_products.id')
        //     ->leftJoin('shapes', 'psi_products.shape_id', 'shapes.id')
        //     ->leftJoin('branch_psi_products', 'psi_products.id', 'branch_psi_products.psi_product_id')
        //     ->leftJoin('branches', 'branches.id', 'branch_psi_products.branch_id')
        //     ->leftJoin('uoms', 'psi_products.uom_id', 'uoms.id')
        //     ->leftJoin('psi_stocks', 'psi_stocks.branch_psi_product_id', 'branch_psi_products.id')
        //     ->leftJoin('reorder_points', 'reorder_points.psi_stock_id', 'psi_stocks.id')
        //     ->leftJoin('psi_stock_statuses', 'psi_stock_statuses.id', 'reorder_points.psi_stock_status_id')
        //     ->groupBy('psi_products.id', 'shapes.name', 'psi_products.weight', 'psi_products.length', 'uoms.name', 'product_photos.image')
        //     ->get();

        $select2 = [];
        foreach ($branches as $branch) {
            // Sum for branch-specific index
            $select2[] = DB::raw("SUM(CASE WHEN branch_psi_products.branch_id = $branch->id THEN 1 ELSE 0 END) AS index$branch->id");

            // Subquery for branch-specific status
            $select2[] = DB::raw("(SELECT (psi_stock_statuses.name)
                                       FROM psi_stock_statuses
                                       JOIN reorder_points ON reorder_points.psi_stock_status_id = psi_stock_statuses.id
                                       JOIN psi_stocks ON psi_stocks.id = reorder_points.psi_stock_id
                                       JOIN branch_psi_products ON branch_psi_products.id = psi_stocks.branch_psi_product_id
                                       WHERE branch_psi_products.psi_product_id = psi_products.id
                                         AND branch_psi_products.branch_id = $branch->id) AS status$branch->id");

            // Subquery for branch-specific color
            $select2[] = DB::raw("(SELECT (psi_stock_statuses.color)
                                       FROM psi_stock_statuses
                                       JOIN reorder_points ON reorder_points.psi_stock_status_id = psi_stock_statuses.id
                                       JOIN psi_stocks ON psi_stocks.id = reorder_points.psi_stock_id
                                       JOIN branch_psi_products ON branch_psi_products.id = psi_stocks.branch_psi_product_id
                                       WHERE branch_psi_products.psi_product_id = psi_products.id
                                         AND branch_psi_products.branch_id = $branch->id) AS color$branch->id");
        }

        $products2 = PsiProduct::select(
            array_merge(
                [
                    'psi_products.id',
                    'psi_products.length',
                    'psi_products.weight',
                    'shapes.name as shape',
                    'uoms.name as uom',
                    'product_photos.image as image'
                ],
                $select2
            )

        )
            ->leftJoin('product_photos', 'product_photos.psi_product_id', 'psi_products.id')
            ->leftJoin('shapes', 'psi_products.shape_id', 'shapes.id')
            ->leftJoin('branch_psi_products', 'psi_products.id', 'branch_psi_products.psi_product_id')
            ->leftJoin('branches', 'branches.id', 'branch_psi_products.branch_id')
            ->leftJoin('uoms', 'psi_products.uom_id', 'uoms.id')
            ->where('shapes.name', 'like', '%' . $this->shape_detail . '%')
            ->groupBy(
                'psi_products.id',
                'shapes.name',
                'psi_products.weight',
                'psi_products.length',
                'uoms.name',
                'product_photos.image'
            )
            ->paginate(5);
        // dd($products2);


        if ($this->props_to_link == true) {
            $orders = PsiOrder::where('branch_psi_product_id', '=', $this->branchPsiProductId)
                ->get();
            $this->props_to_link = false;
            // dd($orders);
        } else {
            $orders = [];
        }

        $jobs = PhotoShooting::where('photo_shooting_status_id', '<', 6)->get()->count();

        $jobsBr = PsiOrder::where('psi_status_id', '=', 8)->count();
        // dd($jobsBr);


        return view('livewire.order.psi.main-board', [
            'products' => $products2,
            'branches' => $branches,
            'psiOrders' => $orders,
            'jobs4Dm' => $jobs,
            'jobs4Br' => $jobsBr,
        ]);
    }
}
