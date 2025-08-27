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
use App\Models\RealSale;
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
    public $selectedTagNames = []; // Holds selected category names
    public $xSelectInput; //wireui selection input
    public $hashtag_id;
    public $filter_hashtag_id;
    private $duration_filter;


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

        if (! in_array($this->filter_hashtag_id, array_column($this->selectedTag, 'key'))) {
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

    public function durationFilter($time)
    {
        $this->duration_filter = Carbon::now()->subDay($time)->format('Y-m-d');

        // dd($this->duration_filter);
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

    public function render()
    {
        // dd(Carbon::now()->format('Y-m-d'));
        // $select = ['psi_products.id', 'shapes.name AS shape', 'psi_products.length', 'uoms.name AS uom', 'psi_products.weight', 'product_photos.image'];
        $branches = Branch::orderBy('name')->get();

        $selection = [];
        foreach ($branches as $branch) {
            $bid = (int) $branch->id;
            // Per-branch presence index
            $selection[] = DB::raw("SUM(CASE WHEN bpp.branch_id = $bid THEN 1 ELSE 0 END) AS index$bid");
            // Per-branch latest status and color (from latest reorder status per BPP)
            $selection[] = DB::raw("MAX(CASE WHEN bpp.branch_id = $bid THEN pss.name END) AS status$bid");
            $selection[] = DB::raw("MAX(CASE WHEN bpp.branch_id = $bid THEN pss.color END) AS color$bid");
        }

        // dd($selection);

        // Pre-aggregations to reduce row explosion and improve readability
        $latestReorderSub = DB::table('reorder_points as rp')
            ->select('ps.branch_psi_product_id', DB::raw('MAX(rp.id) as latest_reorder_id'))
            ->join('psi_stocks as ps', 'ps.id', '=', 'rp.psi_stock_id')
            ->groupBy('ps.branch_psi_product_id');

        $realSalesSumSub = DB::table('real_sales')
            ->select('branch_psi_product_id', DB::raw('SUM(qty) as total_sale'))
            ->groupBy('branch_psi_product_id');

        $latestPhotoSub = DB::table('product_photos')
            ->select('psi_product_id', DB::raw('MAX(id) as latest_photo_id'))
            ->groupBy('psi_product_id');

        $producutWithEachBranch = PsiProduct::from('psi_products as p')
            ->select(array_merge([
                'p.id',
                'p.length',
                'p.weight',
                DB::raw('shapes.name as shape'),
                DB::raw('uoms.name as uom'),
                DB::raw('pp.image AS image'),
                DB::raw('SUM(COALESCE(rs_sum.total_sale, 0)) AS total_sale'),
            ], $selection))
            // Joins
            ->leftJoin('branch_psi_products as bpp', 'p.id', '=', 'bpp.psi_product_id')
            // Latest reorder status per BPP
            ->leftJoinSub($latestReorderSub, 'lr', function ($join) {
                $join->on('lr.branch_psi_product_id', '=', 'bpp.id');
            })
            ->leftJoin('reorder_points as rp_latest', 'rp_latest.id', '=', 'lr.latest_reorder_id')
            ->leftJoin('psi_stock_statuses as pss', 'pss.id', '=', 'rp_latest.psi_stock_status_id')
            // Real sales aggregated per BPP
            ->leftJoinSub($realSalesSumSub, 'rs_sum', function ($join) {
                $join->on('rs_sum.branch_psi_product_id', '=', 'bpp.id');
            })
            // Latest photo per product
            ->leftJoinSub($latestPhotoSub, 'lp', function ($join) {
                $join->on('lp.psi_product_id', '=', 'p.id');
            })
            ->leftJoin('product_photos as pp', 'pp.id', '=', 'lp.latest_photo_id')
            // Other dimension tables
            ->leftJoin('shapes', 'p.shape_id', '=', 'shapes.id')
            ->leftJoin('uoms', 'p.uom_id', '=', 'uoms.id')
            ->where('shapes.name', 'like', '%' . $this->shape_detail . '%')
            ->where('bpp.is_suspended', '=', 'false')
            ->groupBy('p.id', 'shapes.name', 'p.weight', 'p.length', 'uoms.name', 'pp.image')
            ->orderByDesc('total_sale')
            ->paginate(5);

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


        $psiProducts = DB::table('psi_products as p')
            ->leftJoin('branch_psi_products as bp', 'p.id', '=', 'bp.psi_product_id')
            ->leftJoin('branches', 'bp.branch_id', '=', 'branches.id')
            ->leftJoin(
                DB::raw('(
                SELECT branch_psi_product_id, MAX(id) as latest_focus_id
                FROM focus_sales
                GROUP BY branch_psi_product_id
            ) as latest_focus'),
                'bp.id',
                '=',
                'latest_focus.branch_psi_product_id'
            )
            ->leftJoin('focus_sales as fs_latest', 'latest_focus.latest_focus_id', '=', 'fs_latest.id')
            ->leftJoin('real_sales as rs', 'bp.id', '=', 'rs.branch_psi_product_id') // Join with real_sales
            ->leftJoin('psi_stocks', 'bp.id', 'psi_stocks.branch_psi_product_id')
            ->leftJoin('reorder_points', 'reorder_points.psi_stock_id', 'psi_stocks.id')
            ->leftJoin('product_photos as pp', 'pp.psi_product_id', '=', 'p.id')
            ->leftJoin('shapes', 'shapes.id', 'p.shape_id')
            // ->leftJoin('product_hashtags', 'product_hashtags.psi_product_id', 'psi_products.id')
            // ->when($this->selectedTag, function ($query) {
            //     $keys = array_column($this->selectedTag, 'key');
            //     $query->whereIn('product_hashtags.id', $keys);
            // })
            ->select(
                'p.id as psi_product_id',
                'p.remark',
                // 'p.name as psi_product_name',
                'bp.id as branch_psi_product_id',
                'bp.branch_id',
                'branches.name',
                'psi_stocks.inventory_balance',
                'reorder_points.reorder_due_date',
                'fs_latest.qty as latest_focus_qty',
                'pp.image',
                'p.weight',
                'shapes.name AS detail',
                DB::raw('AVG(rs.qty) as avg_sale_qty'), // Average sale quantity for each branch_psi_product
                // DB::raw('MONTH(rs.sale_date) as sale_month'), // Month of the sale for grouping
                // DB::raw('YEAR(rs.sale_date) as sale_year') // Year of the sale for grouping
            )
            ->groupBy(
                'p.remark',
                'p.id',
                'bp.id',
                'branches.name',
                'bp.branch_id',
                'fs_latest.qty',
                'psi_stocks.inventory_balance',
                'reorder_points.reorder_due_date',
                'pp.image',
                'p.weight',
                'shapes.name',
            )
            // ->groupBy('p.id', 'bp.id', 'rs.qty')
            ->get();

        $structuredData = $psiProducts->groupBy('psi_product_id')->map(function ($group) {
            return [
                'image' => $group->max('image'),
                'weight' => $group->max('weight'),
                'detail' => $group->max('detail'),
                'remark' => $group->max('remark'),
                'total_focus_quantity' => $group->sum('latest_focus_qty'), // Sum of latest focus quantities
                'branches' => $group->map(function ($item) {
                    return [

                        'branch_psi_product_id' => $item->branch_psi_product_id,
                        'branch_id' => $item->branch_id,
                        'branch_name' => $item->name,
                        'latest_focus_qty' => $item->latest_focus_qty,
                        'avg_sales' => $item->avg_sale_qty,
                        'balance' => $item->inventory_balance,
                        'due_date' => $item->reorder_due_date,

                        // 'sale_month' => $item->sale_month,
                        // 'sale_year' => $item->sale_year,
                    ];
                })->values(),
                'overall_avg_sale_qty' => $group->avg('avg_sale_qty') // Overall average sale for each psi product
            ];
        });

        // dd($structuredData);
        // dd($structuredData[1]['branches']);

        if ($this->productIdFilter) {
            $productSummary = $structuredData[$this->productIdFilter];
            // $hashtags = ProductHashtag::wherePsiProductId($this->productIdFilter)
            //     ->get();
            // dd($hashtags);
        } else {
            $productSummary = [
                'weight' => 0,
                'image' => 0,
                'detail' => 'loading...',
                'branches' => []
            ];
            // $hashtags = [];
        }

        $branch_sales = RealSale::select('branches.name', DB::raw('SUM(real_sales.qty) AS total'))
            ->leftJoin('branch_psi_products as bpp', 'bpp.id', 'real_sales.branch_psi_product_id')
            ->leftJoin('branches', 'branches.id', 'bpp.branch_id')
            ->when($this->duration_filter, function ($query) {
                return $query->where('real_sales.created_at', '>=', $this->duration_filter);
            })
            ->when(! $this->duration_filter, function ($query) {
                return $query->where('real_sales.created_at', '>=', Carbon::now()->subDay(7)->format('Y-m-d'));
            })
            ->groupBy('branches.name')
            ->get();

        $data = $branch_sales->pluck('total', 'name')->all();

        $data = json_encode($data);



        // dd($data);

        return view('livewire.order.psi.main-board', [
            'products' => $producutWithEachBranch,
            'productSummary' => $productSummary,
            'branches' => $branches,
            'psiOrders' => $orders,
            'jobs4Dm' => $jobs,
            'jobs4Br' => $jobsBr,
            'sales' => $data,
            'branch_sales' => $branch_sales,
            // 'tags' => $hashtags,
        ]);
    }
}
