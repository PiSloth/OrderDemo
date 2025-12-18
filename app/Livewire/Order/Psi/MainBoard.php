<?php

namespace App\Livewire\Order\Psi;

use App\Models\Branch;
use App\Models\BranchPsiProduct;
use App\Models\Hashtag;
use App\Models\PhotoShooting;
use App\Models\ProductHashtag;
use App\Models\PsiOrder;
use App\Models\PsiProduct;
use App\Models\PsiStock;
use App\Services\PsiProductService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
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
    public $productIdFilter;
    public $selectedTag = []; // Holds selected Tag IDs
    public $selectedTagNames = []; // Holds selected category names
    public $xSelectInput; //wireui selection input
    public $hashtag_id;
    public $filter_hashtag_id;
    private $duration_filter;
    protected $psiProductService;

    public function boot(PsiProductService $psiProductService)
    {
        $this->psiProductService = $psiProductService;
    }

    public function setBranchPsiProduct($productId, $branchId)
    {
        $this->productId = $productId;
        $this->branchId = $branchId;
    }

    //filter with tags
    public function selectTag()
    {
        $this->validate([
            'filter_hashtag_id' => 'required'
        ]);

        $query = Hashtag::findOrFail($this->filter_hashtag_id);

        if (!in_array($this->filter_hashtag_id, array_column($this->selectedTag, 'key'))) {
            $this->selectedTag[] = [
                'key' => $this->filter_hashtag_id,
                'name' => $query->name
            ];
        } else {
            $this->notification([
                'title' => 'Alerady added!',
                'description' => 'filter added to this.',
                'icon' => 'success'
            ]);
        }

        $this->reset('filter_hashtag_id');
    }

    //remove filter hashtag form array
    public function removeTag($key)
    {
        // Filter out the tag with the specified key
        $this->selectedTag = array_filter($this->selectedTag, function ($tag) use ($key) {
            return $tag['key'] !== $key;
        });

        // Reindex the array to maintain numeric indexes
        $this->selectedTag = array_values($this->selectedTag);
    }

    //hashtag create
    public function createTag()
    {
        $this->validate([
            'xSelectInput' => 'required',
        ]);

        Hashtag::create([
            'name' => $this->xSelectInput,
        ]);


        $this->notification([
            'title' => $this->xSelectInput . ' created!',
            'description' => 'A new tagname have successfully created.',
            'icon' => 'success'
        ]);
        $this->reset('xSelectInput');
    }

    //add tag to product
    public function addTagToProduct()
    {
        $this->validate([
            'hashtag_id' => 'required',
            'productIdFilter' => 'required'
        ]);

        ProductHashtag::create([
            'hashtag_id' => $this->hashtag_id,
            'psi_product_id' => $this->productIdFilter,
            'user_id' => auth()->user()->id,
        ]);

        $this->reset('hashtag_id');
        $this->notification([
            'title' => 'Added',
            'description' => 'Newly added to product ',
            'icon' => 'success'
        ]);
    }


    //initialize product id to show in modal
    public function initializeProductId($id)
    {
        $this->productIdFilter = $id;
    }

    public function propsToLink($pId, $bId)
    {
        $this->productId = $pId;
        $this->branchId = $bId;

        $this->branchPsiProductId = BranchPsiProduct::where('branch_id', '=', $bId)
            ->where('psi_product_id', '=', $pId)
            ->first()->id;

        $this->orderCount = PsiOrder::where('psi_orders.psi_status_id', '<', 10)
            ->where('psi_orders.branch_psi_product_id', '=', $this->branchPsiProductId)
            ->count();

        $this->props_to_link = true;

        $this->dispatch('defaultModalFromJs');
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

    public function durationFilter($time)
    {
        $this->duration_filter = Carbon::now()->subDay($time)->format('Y-m-d');
    }

    //? product remark add
    public function updateRemark()
    {
        $this->validate([
            'remark' => 'required',
        ]);
        $product = PsiProduct::find($this->productIdFilter);

        $product->update([
            'remark' => $this->remark
        ]);

        $this->reset('remark');
    }

    // #[Layout('components.layouts.simple')]
    public function render()
    {
        $branches = Branch::orderBy('name')->get();
        $producutWithEachBranch = $this->psiProductService->getProductsForMainBoard($this->shape_detail);

        if ($this->props_to_link == true) {
            $orders = PsiOrder::where('branch_psi_product_id', '=', $this->branchPsiProductId)
                ->get();
            $this->props_to_link = false;
        } else {
            $orders = [];
        }

        $jobs = PhotoShooting::where('photo_shooting_status_id', '<', 6)->get()->count();
        $jobsBr = PsiOrder::where('psi_status_id', '=', 8)->count();

        $structuredData = $this->psiProductService->getStructuredDataForPsiProducts();

        if ($this->productIdFilter) {
            $productSummary = $structuredData->get($this->productIdFilter, [
                'weight' => 0,
                'image' => 0,
                'detail' => 'loading...',
                'branches' => []
            ]);
        } else {
            $productSummary = [
                'weight' => 0,
                'image' => 0,
                'detail' => 'loading...',
                'branches' => []
            ];
        }

        $branch_sales = $this->psiProductService->getBranchSales($this->duration_filter);
        $data = json_encode($branch_sales->pluck('total', 'name')->all());

        return view('livewire.order.psi.main-board', [
            'products' => $producutWithEachBranch,
            'productSummary' => $productSummary,
            'branches' => $branches,
            'psiOrders' => $orders,
            'jobs4Dm' => $jobs,
            'jobs4Br' => $jobsBr,
            'sales' => $data,
            'branch_sales' => $branch_sales,
        ]);
    }
}
