<?php

namespace App\Http\Controllers\JewelrySales;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JewelrySalesProductController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $goldType = $request->input('gold_type');
        $itemName = $request->input('item_name');
        $productCategoryId = $request->input('product_category_id');
        $goldsmith = $request->input('goldsmith');
        $branchRaw = $request->input('branch');
        $search = trim((string) $request->input('search', ''));

        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id');

        if ($from) {
            $query->whereDate('sales.sale_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('sales.sale_at', '<=', $to);
        }
        if ($branchRaw) {
            $query->where('sales.branch_name_raw', $branchRaw);
        }
        if ($goldType) {
            $query->where('products.gold_type', $goldType);
        }
        if ($itemName) {
            $query->where('products.item_name', $itemName);
        }
        if ($productCategoryId) {
            $query->where('products.product_category_id', $productCategoryId);
        }
        if ($goldsmith) {
            $query->where('products.goldsmith', $goldsmith);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('products.product_name', 'like', "%{$search}%")
                    ->orWhere('products.item_name', 'like', "%{$search}%")
                    ->orWhere('products.length', 'like', "%{$search}%");
            });
        }

        $products = (clone $query)
            ->selectRaw("
                products.id as product_id,
                COALESCE(products.product_name, 'Unknown') as product_name,
                products.gold_type,
                products.item_name,
                COALESCE(product_categories.name, products.category) as category_name,
                products.goldsmith,
                products.length,
                COUNT(DISTINCT sales.id) as invoices_count,
                SUM(sale_items.quantity) as total_qty,
                SUM(sale_items.item_weight_g) as total_item_weight_g,
                SUM(sale_items.stone_weight_g) as total_stone_weight_g,
                SUM(sale_items.gold_weight_g) as total_gold_weight_g,
                SUM(sale_items.item_total_weight_g) as total_weight_g,
                SUM(sale_items.gold_amount) as total_gold_amount,
                SUM(sale_items.stone_amount) as total_stone_amount,
                SUM(sale_items.labor_fee) as total_labor_fee,
                SUM(sale_items.profit_loss_amount) as total_profit_loss,
                SUM(sale_items.item_total_amount) as total_amount
            ")
            ->groupBy([
                'products.id',
                'products.product_name',
                'products.gold_type',
                'products.item_name',
                'product_categories.name',
                'products.category',
                'products.goldsmith',
                'products.length',
            ])
            ->orderByDesc('total_amount')
            ->paginate(25)
            ->withQueryString();

        $totals = (clone $query)
            ->selectRaw('
                SUM(sale_items.quantity) as total_qty,
                SUM(sale_items.item_total_weight_g) as total_weight_g,
                SUM(sale_items.gold_amount) as total_gold_amount,
                SUM(sale_items.labor_fee) as total_labor_fee,
                SUM(sale_items.item_total_amount) as total_amount
            ')
            ->first();

        return Inertia::render('JewelrySales/Products', [
            'products' => $products,
            'totals' => $totals,
            'filterOptions' => [
                'gold_types' => Product::whereNotNull('gold_type')->distinct()->orderBy('gold_type')->pluck('gold_type'),
                'item_names' => Product::whereNotNull('item_name')->distinct()->orderBy('item_name')->pluck('item_name'),
                'product_categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
                'goldsmiths' => Product::whereNotNull('goldsmith')->distinct()->orderBy('goldsmith')->pluck('goldsmith'),
                'branches' => Sale::whereNotNull('branch_name_raw')->distinct()->orderBy('branch_name_raw')->pluck('branch_name_raw'),
            ],
            'filters' => [
                'from' => $from,
                'to' => $to,
                'gold_type' => $goldType,
                'item_name' => $itemName,
                'product_category_id' => $productCategoryId,
                'goldsmith' => $goldsmith,
                'branch' => $branchRaw,
                'search' => $search,
            ],
        ]);
    }
}
