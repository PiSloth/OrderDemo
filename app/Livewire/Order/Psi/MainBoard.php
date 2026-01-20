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
    public $xSelectInput; //wireui selection input
    public $hashtag_id;
    public $filter_hashtag_id;

    // Persisted filter state (must be public for Livewire hydration)
    public $duration_filter;

    // Monthly sales report filter (null/empty = all branches)
    public $monthly_branch_id = '';

    // Monthly sales report: how many months to show
    public $monthly_months = 6;

    // Metric selector for reports: qty | grams | index
    public $report_metric = 'qty';

    // Date range compare (Flatpickr range mode)
    public $sale_range_from = '';
    public $sale_range_to = '';
    public $sale_compare_mode = 'last_month'; // prev | last_month | custom
    public $sale_compare_from = '';
    public $sale_compare_to = '';

    // Focus vs Actual line chart (custom range; default current month)
    public $focus_chart_from = '';
    public $focus_chart_to = '';
    public $focus_chart_branch_id = '';
    public $focus_chart_metric = 'qty';

    // Stock-out monthly report
    public $stockout_month = ''; // YYYY-MM
    public $stockout_branch_id = '';

    // WireUI modal states used in the Blade (avoid dynamic properties)
    public $productSummaryModal = false;
    public $psiProduct = false;
    public $defaultModal = false;
    public $orderModal = false;
    public $rangeManualModal = false;

    protected $psiProductService;

    public function boot(PsiProductService $psiProductService)
    {
        $this->psiProductService = $psiProductService;
    }

    public function mount(): void
    {
        if (!$this->monthly_months) {
            $this->monthly_months = 6;
        }

        // Default focus chart range = current month to date
        if (!$this->focus_chart_from || !$this->focus_chart_to) {
            $this->focus_chart_from = Carbon::now()->startOfMonth()->format('Y-m-d');
            $this->focus_chart_to = Carbon::now()->format('Y-m-d');
        }

        if (!$this->focus_chart_metric) {
            $this->focus_chart_metric = 'qty';
        }

        if (!$this->stockout_month) {
            $this->stockout_month = Carbon::now()->format('Y-m');
        }

        // Default Range A = previous 7 days (including today)
        if (!$this->sale_range_from || !$this->sale_range_to) {
            $this->sale_range_to = Carbon::now()->format('Y-m-d');
            $this->sale_range_from = Carbon::now()->subDays(6)->format('Y-m-d');
        }

        // Default compare = last month same days
        if (!$this->sale_compare_mode) {
            $this->sale_compare_mode = 'last_month';
        }
    }

    public function focusActualChartData(): array
    {
        if (!$this->focus_chart_from || !$this->focus_chart_to) {
            return ['labels' => [], 'actual' => [], 'focus' => []];
        }

        $branchId = $this->focus_chart_branch_id !== '' ? (int) $this->focus_chart_branch_id : null;
        $metric = (string) ($this->focus_chart_metric ?: 'qty');

        return $this->psiProductService->getDailyFocusActualSeries(
            (string) $this->focus_chart_from,
            (string) $this->focus_chart_to,
            $branchId,
            $metric
        );
    }

    public function setBranchPsiProduct($productId, $branchId)
    {
        $this->productId = $productId;
        $this->branchId = $branchId;
    }

    public function updatingShapeDetail()
    {
        $this->resetPage();
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
                'title' => 'Already added!',
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

        $branchPsiProductId = BranchPsiProduct::where('branch_id', $bId)
            ->where('psi_product_id', $pId)
            ->value('id');

        if (!$branchPsiProductId) {
            $this->notification([
                'title' => 'Not found',
                'description' => 'This product is not registered for the selected branch yet.',
                'icon' => 'error'
            ]);
            return;
        }

        $this->branchPsiProductId = (int) $branchPsiProductId;

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

            $productCreate = BranchPsiProduct::create([
                'remark' => $this->remark,
                'psi_product_id' => $this->productId,
                'branch_id' => $this->branchId,
                'user_id' => auth()->user()->id,
            ]);

            PsiStock::create([
                'branch_psi_product_id' => $productCreate->id,
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
        $this->duration_filter = Carbon::now()->subDays((int) $time)->format('Y-m-d');
    }

    //? product remark add
    public function updateRemark()
    {
        $this->validate([
            'remark' => 'required',
        ]);
        $product = PsiProduct::find($this->productIdFilter);

        if (!$product) {
            $this->notification([
                'title' => 'Not found',
                'description' => 'Product not found.',
                'icon' => 'error'
            ]);
            return;
        }

        $product->update([
            'remark' => $this->remark
        ]);

        $this->reset('remark');
    }

    // #[Layout('components.layouts.simple')]
    public function render()
    {
        $branches = Branch::orderBy('name')->get();
        $productWithEachBranch = $this->psiProductService->getProductsForMainBoard($this->shape_detail);

        $orders = [];
        if ($this->props_to_link === true && $this->branchPsiProductId) {
            $orders = PsiOrder::where('branch_psi_product_id', $this->branchPsiProductId)->get();
            $this->props_to_link = false;
        }

        $jobs = PhotoShooting::where('photo_shooting_status_id', '<', 6)->count();
        $jobsBr = PsiOrder::where('psi_status_id', '=', 8)->count();

        $defaultProductSummary = [
            'weight' => 0,
            'image' => 0,
            'detail' => 'loading...',
            'branches' => [],
        ];

        $productSummary = $defaultProductSummary;
        if ($this->productIdFilter) {
            $structuredData = $this->psiProductService->getStructuredDataForPsiProducts();
            $productSummary = $structuredData->get($this->productIdFilter, $defaultProductSummary);
        }

        $branch_sales = $this->psiProductService->getBranchSales($this->duration_filter);
        $data = json_encode($branch_sales->pluck('total', 'name')->all());

        $monthsToShow = max(1, min(24, (int) $this->monthly_months));

        $monthlyReport = $this->psiProductService->getMonthlyProductSalesReport(
            $this->monthly_branch_id !== '' ? (int) $this->monthly_branch_id : null,
            $monthsToShow
        );

        $rangeCompare = null;
        if ($this->sale_range_from && $this->sale_range_to) {
            $branchId = $this->monthly_branch_id !== '' ? (int) $this->monthly_branch_id : null;
            $useCustom = $this->sale_compare_mode === 'custom' && $this->sale_compare_from && $this->sale_compare_to;

            $rangeCompare = $this->psiProductService->getProductSalesDateRangeCompare(
                $this->sale_range_from,
                $this->sale_range_to,
                $branchId,
                $useCustom ? $this->sale_compare_from : null,
                $useCustom ? $this->sale_compare_to : null,
                (string) $this->sale_compare_mode
            );
        }

        $stockoutReport = $this->psiProductService->getMonthlyStockoutReport(
            (string) ($this->stockout_month ?: Carbon::now()->format('Y-m')),
            $this->stockout_branch_id !== '' ? (int) $this->stockout_branch_id : null
        );

        return view('livewire.order.psi.main-board', [
            'products' => $productWithEachBranch,
            'productSummary' => $productSummary,
            'branches' => $branches,
            'psiOrders' => $orders,
            'jobs4Dm' => $jobs,
            'jobs4Br' => $jobsBr,
            'sales' => $data,
            'branch_sales' => $branch_sales,
            'monthlyReport' => $monthlyReport,
            'rangeCompare' => $rangeCompare,
            'stockoutReport' => $stockoutReport,
        ]);
    }
}
