<?php

namespace App\Http\Controllers\JewelrySales;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JewelrySalesDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $branchRaw = $request->input('branch');

        $salesQuery = Sale::query();
        if ($from) {
            $salesQuery->whereDate('sale_at', '>=', $from);
        }
        if ($to) {
            $salesQuery->whereDate('sale_at', '<=', $to);
        }
        if ($branchRaw) {
            $salesQuery->where(function ($q) use ($branchRaw) {
                $q->where('branch_name_raw', $branchRaw)
                    ->orWhereHas('branch', fn ($b) => $b->where('name', $branchRaw));
            });
        }

        $saleIds = (clone $salesQuery)->select('id');

        $summary = [
            'total_revenue' => (float) (clone $salesQuery)->sum('invoice_total'),
            'total_invoices' => (int) (clone $salesQuery)->count(),
            'total_weight_g' => (float) (clone $salesQuery)->sum('invoice_total_weight_g'),
            'unique_customers' => (int) (clone $salesQuery)->whereNotNull('customer_id')->distinct('customer_id')->count('customer_id'),
            'total_items' => (int) SaleItem::whereIn('sale_id', $saleIds)->sum('quantity'),
            'avg_invoice_value' => (float) ((clone $salesQuery)->avg('invoice_total') ?? 0),
        ];

        $repeatCustomersCount = (int) Sale::query()
            ->whereIn('id', $saleIds)
            ->whereNotNull('customer_id')
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $summary['repeat_customers'] = $repeatCustomersCount;
        $summary['repeat_rate'] = $summary['unique_customers'] > 0
            ? round(($repeatCustomersCount / $summary['unique_customers']) * 100, 1)
            : 0;

        $dailyTrend = (clone $salesQuery)
            ->selectRaw('DATE(sale_at) as sale_date, COUNT(*) as invoices_count, SUM(invoice_total) as total_amount, SUM(invoice_total_weight_g) as total_weight_g')
            ->groupByRaw('DATE(sale_at)')
            ->orderBy('sale_date')
            ->get();

        $byBranch = (clone $salesQuery)
            ->selectRaw("COALESCE(branch_name_raw, 'Unknown') as branch_name, COUNT(*) as invoices_count, SUM(invoice_total) as total_amount, SUM(invoice_total_weight_g) as total_weight_g")
            ->groupByRaw("COALESCE(branch_name_raw, 'Unknown')")
            ->orderByDesc('total_amount')
            ->get();

        $byGoldType = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->whereIn('sales.id', $saleIds)
            ->selectRaw("COALESCE(products.gold_type, 'Unspecified') as gold_type, SUM(sale_items.quantity) as total_qty, SUM(sale_items.item_total_weight_g) as total_weight_g, SUM(sale_items.item_total_amount) as total_amount")
            ->groupByRaw("COALESCE(products.gold_type, 'Unspecified')")
            ->orderByDesc('total_amount')
            ->get();

        $byGoldsmith = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->whereIn('sales.id', $saleIds)
            ->selectRaw("COALESCE(products.goldsmith, 'Unspecified') as goldsmith, SUM(sale_items.quantity) as total_qty, SUM(sale_items.item_total_weight_g) as total_weight_g, SUM(sale_items.item_total_amount) as total_amount, SUM(sale_items.labor_fee) as total_labor_fee")
            ->groupByRaw("COALESCE(products.goldsmith, 'Unspecified')")
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $byCategory = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->whereIn('sales.id', $saleIds)
            ->selectRaw("COALESCE(product_categories.name, products.category, 'Unspecified') as category_name, SUM(sale_items.quantity) as total_qty, SUM(sale_items.item_total_weight_g) as total_weight_g, SUM(sale_items.item_total_amount) as total_amount")
            ->groupByRaw("COALESCE(product_categories.name, products.category, 'Unspecified')")
            ->orderByDesc('total_amount')
            ->get();

        $branches = Sale::query()
            ->whereNotNull('branch_name_raw')
            ->distinct()
            ->orderBy('branch_name_raw')
            ->pluck('branch_name_raw');

        $latestBatch = ImportBatch::latest('id')->first();

        return Inertia::render('JewelrySales/Dashboard', [
            'summary' => $summary,
            'dailyTrend' => $dailyTrend,
            'byBranch' => $byBranch,
            'byGoldType' => $byGoldType,
            'byGoldsmith' => $byGoldsmith,
            'byCategory' => $byCategory,
            'branches' => $branches,
            'latestBatch' => $latestBatch,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'branch' => $branchRaw,
            ],
        ]);
    }
}
