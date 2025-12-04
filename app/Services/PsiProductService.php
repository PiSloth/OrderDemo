<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PsiProduct;
use App\Models\RealSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PsiProductService
{
    public function getProductsForMainBoard($shape_detail)
    {
        $branches = Branch::orderBy('name')->get();
        $selection = [];
        foreach ($branches as $branch) {
            $bid = (int) $branch->id;
            $selection[] = DB::raw("SUM(CASE WHEN bpp.branch_id = $bid THEN 1 ELSE 0 END) AS index$bid");
            $selection[] = DB::raw("MAX(CASE WHEN bpp.branch_id = $bid THEN pss.name END) AS status$bid");
            $selection[] = DB::raw("MAX(CASE WHEN bpp.branch_id = $bid THEN pss.color END) AS color$bid");
        }

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

        return PsiProduct::from('psi_products as p')
            ->select(array_merge([
                'p.id',
                'p.length',
                'p.weight',
                DB::raw('shapes.name as shape'),
                DB::raw('uoms.name as uom'),
                DB::raw('pp.image AS image'),
                DB::raw('SUM(COALESCE(rs_sum.total_sale, 0)) AS total_sale'),
            ], $selection))
            ->leftJoin('branch_psi_products as bpp', 'p.id', '=', 'bpp.psi_product_id')
            ->leftJoinSub($latestReorderSub, 'lr', function ($join) {
                $join->on('lr.branch_psi_product_id', '=', 'bpp.id');
            })
            ->leftJoin('reorder_points as rp_latest', 'rp_latest.id', '=', 'lr.latest_reorder_id')
            ->leftJoin('psi_stock_statuses as pss', 'pss.id', '=', 'rp_latest.psi_stock_status_id')
            ->leftJoinSub($realSalesSumSub, 'rs_sum', function ($join) {
                $join->on('rs_sum.branch_psi_product_id', '=', 'bpp.id');
            })
            ->leftJoinSub($latestPhotoSub, 'lp', function ($join) {
                $join->on('lp.psi_product_id', '=', 'p.id');
            })
            ->leftJoin('product_photos as pp', 'pp.id', '=', 'lp.latest_photo_id')
            ->leftJoin('shapes', 'p.shape_id', '=', 'shapes.id')
            ->leftJoin('uoms', 'p.uom_id', '=', 'uoms.id')
            ->where('shapes.name', 'like', '%' . $shape_detail . '%')
            ->where('p.is_suspended', '=', 'false')
            ->groupBy('p.id', 'shapes.name', 'p.weight', 'p.length', 'uoms.name', 'pp.image')
            ->orderByDesc('total_sale')
            ->get();
    }

    public function getStructuredDataForPsiProducts()
    {
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
            ->leftJoin('real_sales as rs', 'bp.id', '=', 'rs.branch_psi_product_id')
            ->leftJoin('psi_stocks', 'bp.id', 'psi_stocks.branch_psi_product_id')
            ->leftJoin('reorder_points', 'reorder_points.psi_stock_id', 'psi_stocks.id')
            ->leftJoin('product_photos as pp', 'pp.psi_product_id', '=', 'p.id')
            ->leftJoin('shapes', 'shapes.id', 'p.shape_id')
            ->select(
                'p.id as psi_product_id',
                'p.remark',
                'bp.id as branch_psi_product_id',
                'bp.branch_id',
                'branches.name',
                'psi_stocks.inventory_balance',
                'reorder_points.reorder_due_date',
                'fs_latest.qty as latest_focus_qty',
                'pp.image',
                'p.weight',
                'shapes.name AS detail',
                DB::raw('AVG(rs.qty) as avg_sale_qty'),
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
            ->get();

        return $psiProducts->groupBy('psi_product_id')->map(function ($group) {
            return [
                'image' => $group->max('image'),
                'weight' => $group->max('weight'),
                'detail' => $group->max('detail'),
                'remark' => $group->max('remark'),
                'total_focus_quantity' => $group->sum('latest_focus_qty'),
                'branches' => $group->map(function ($item) {
                    $avg_sale = (int) $item->avg_sale_qty;
                    $remaining_days = $avg_sale > 0 ? (int) ($item->inventory_balance / $avg_sale) : null;
                    return [
                        'branch_psi_product_id' => $item->branch_psi_product_id,
                        'branch_id' => $item->branch_id,
                        'branch_name' => $item->name,
                        'latest_focus_qty' => $item->latest_focus_qty,
                        'avg_sales' => $avg_sale,
                        'balance' => $item->inventory_balance,
                        'due_date' => $item->reorder_due_date,
                        'remaining_days' => $remaining_days,
                    ];
                })->values(),
                'overall_avg_sale_qty' => $group->avg('avg_sale_qty')
            ];
        });
    }

    public function getBranchSales($duration_filter)
    {
        return RealSale::select('branches.name', DB::raw('SUM(real_sales.qty) AS total'))
            ->leftJoin('branch_psi_products as bpp', 'bpp.id', 'real_sales.branch_psi_product_id')
            ->leftJoin('branches', 'branches.id', 'bpp.branch_id')
            ->when($duration_filter, function ($query) use ($duration_filter) {
                return $query->where('real_sales.created_at', '>=', $duration_filter);
            })
            ->when(!$duration_filter, function ($query) {
                return $query->where('real_sales.created_at', '>=', Carbon::now()->subDay(7)->format('Y-m-d'));
            })
            ->groupBy('branches.name')
            ->get();
    }
}
