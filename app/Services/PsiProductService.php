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

    /**
     * Monthly actual sales (qty) per product for the last N months.
     *
     * @return array{months: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>}
     */
    public function getMonthlyProductSalesReport(?int $branchId = null, int $months = 6): array
    {
        $months = max(1, min(24, (int) $months));

        $monthsMeta = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = Carbon::now()->startOfMonth()->subMonths($i);
            $monthsMeta[] = [
                'key' => $m->format('Y-m'),
                'label' => $m->format('M Y'),
            ];
        }

        $startDate = Carbon::now()->startOfMonth()->subMonths($months - 1);

        // Build a month key expression that works across common DB drivers.
        $driver = DB::connection()->getDriverName();
        $monthKeyExpr = match ($driver) {
            'pgsql' => "TO_CHAR(DATE_TRUNC('month', rs.created_at), 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', rs.created_at)",
            default => "DATE_FORMAT(rs.created_at, '%Y-%m')", // mysql/mariadb
        };

        // All active products (for rows, even if sales are 0).
        $products = DB::table('psi_products as p')
            ->leftJoin('shapes', 'shapes.id', '=', 'p.shape_id')
            ->leftJoin('uoms', 'uoms.id', '=', 'p.uom_id')
            ->select([
                'p.id',
                'p.weight',
                'p.length',
                'p.is_suspended',
                DB::raw('COALESCE(shapes.name, "-") as shape'),
                DB::raw('COALESCE(uoms.name, "") as uom'),
            ])
            ->where('p.is_suspended', '=', 'false')
            ->orderBy('shapes.name')
            ->orderBy('p.weight')
            ->get();

        // Latest focus qty per branch_psi_product_id, aggregated to product.
        $latestFocusSub = DB::table('focus_sales')
            ->select('branch_psi_product_id', DB::raw('MAX(id) as latest_focus_id'))
            ->groupBy('branch_psi_product_id');

        $focusByProduct = DB::table('psi_products as p')
            ->leftJoin('branch_psi_products as bpp', 'bpp.psi_product_id', '=', 'p.id')
            ->leftJoinSub($latestFocusSub, 'lf', function ($join) {
                $join->on('lf.branch_psi_product_id', '=', 'bpp.id');
            })
            ->leftJoin('focus_sales as fs_latest', 'fs_latest.id', '=', 'lf.latest_focus_id')
            ->where('p.is_suspended', '=', 'false')
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->groupBy('p.id')
            ->select([
                'p.id as psi_product_id',
                DB::raw('SUM(COALESCE(fs_latest.qty, 0)) as focus_qty'),
            ])
            ->pluck('focus_qty', 'psi_product_id')
            ->map(fn($v) => (float) $v)
            ->all();

        $sums = DB::table('real_sales as rs')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'rs.branch_psi_product_id')
            ->join('psi_products as p', 'p.id', '=', 'bpp.psi_product_id')
            ->where('rs.created_at', '>=', $startDate)
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->select([
                'p.id as psi_product_id',
                DB::raw($monthKeyExpr . ' as month_key'),
                DB::raw('SUM(rs.qty) as total_qty'),
            ])
            ->groupBy('p.id', DB::raw($monthKeyExpr))
            ->get();

        $sumIndex = [];
        foreach ($sums as $row) {
            $pid = (int) $row->psi_product_id;
            $mk = (string) $row->month_key;
            $sumIndex[$pid][$mk] = (float) $row->total_qty;
        }

        $monthKeys = array_map(fn($m) => $m['key'], $monthsMeta);
        $currentKey = end($monthKeys) ?: Carbon::now()->format('Y-m');
        $prevKey = $months >= 2 ? $monthKeys[count($monthKeys) - 2] : null;

        $rows = [];
        foreach ($products as $p) {
            $pid = (int) $p->id;
            $weight = (float) ($p->weight ?? 0);

            $labelParts = [trim((string) $p->shape)];
            if (!empty($p->weight)) {
                $labelParts[] = trim((string) $p->weight) . 'g';
            }
            if (!empty($p->length)) {
                $labelParts[] = trim((string) $p->length) . ' ' . trim((string) $p->uom);
            }
            $productLabel = trim(implode(' · ', array_filter($labelParts)));

            $salesByMonth = [];
            $maxAmount = null;
            $maxMonth = null;
            $minAmount = null;
            $minMonth = null;

            foreach ($monthKeys as $mk) {
                $amount = (float) ($sumIndex[$pid][$mk] ?? 0);
                $salesByMonth[$mk] = $amount;

                if ($maxAmount === null || $amount > $maxAmount) {
                    $maxAmount = $amount;
                    $maxMonth = $mk;
                }
                if ($minAmount === null || $amount < $minAmount) {
                    $minAmount = $amount;
                    $minMonth = $mk;
                }
            }

            $currentAmount = (float) ($salesByMonth[$currentKey] ?? 0);
            $prevAmount = $prevKey ? (float) ($salesByMonth[$prevKey] ?? 0) : 0.0;
            $delta = $currentAmount - $prevAmount;
            $deltaPct = $prevAmount > 0 ? ($delta / $prevAmount) * 100.0 : ($currentAmount > 0 ? 100.0 : 0.0);

            $rows[] = [
                'psi_product_id' => $pid,
                'product' => $productLabel !== '' ? $productLabel : ('Product #' . $pid),
                'weight' => $weight,
                'focus_qty' => (float) ($focusByProduct[$pid] ?? 0),
                'sales' => $salesByMonth,
                'max' => ['month' => $maxMonth, 'amount' => (float) ($maxAmount ?? 0)],
                'min' => ['month' => $minMonth, 'amount' => (float) ($minAmount ?? 0)],
                'current' => $currentAmount,
                'prev' => $prevAmount,
                'delta' => $delta,
                'delta_pct' => $deltaPct,
                'trend' => $currentAmount <=> $prevAmount, // -1,0,1
            ];
        }

        // Sort by current month desc (most relevant), then product label.
        usort($rows, function ($a, $b) {
            $c = ($b['current'] ?? 0) <=> ($a['current'] ?? 0);
            if ($c !== 0) return $c;
            return strcmp((string) ($a['product'] ?? ''), (string) ($b['product'] ?? ''));
        });

        return [
            'months' => $monthsMeta,
            'rows' => $rows,
        ];
    }

    /**
     * Sum actual sales (qty) per product in a date range.
     *
     * @return array<int, float> keyed by psi_product_id
     */
    public function getProductSalesTotalsByDateRange(string $from, string $to, ?int $branchId = null): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        return DB::table('real_sales as rs')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'rs.branch_psi_product_id')
            ->join('psi_products as p', 'p.id', '=', 'bpp.psi_product_id')
            ->whereBetween('rs.created_at', [$start, $end])
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->select([
                'p.id as psi_product_id',
                DB::raw('SUM(rs.qty) as total_qty'),
            ])
            ->groupBy('p.id')
            ->pluck('total_qty', 'psi_product_id')
            ->map(fn($v) => (float) $v)
            ->all();
    }

    /**
     * Compare sales between two date ranges per product.
     *
     * @return array{rangeA: array{from:string,to:string}, rangeB: array{from:string,to:string}, rows: array<int, array<string,mixed>>}
     */
    public function getProductSalesDateRangeCompare(
        string $from,
        string $to,
        ?int $branchId = null,
        ?string $compareFrom = null,
        ?string $compareTo = null,
        string $compareMode = 'prev'
    ): array {
        $aFrom = Carbon::parse($from)->startOfDay();
        $aTo = Carbon::parse($to)->endOfDay();

        if ($compareMode === 'custom') {
            if (!$compareFrom || !$compareTo) {
                // If user selected custom but didn't provide dates, fall back to prev.
                $compareMode = 'prev';
            }
        }

        if ($compareMode === 'custom' && $compareFrom && $compareTo) {
            $bFrom = Carbon::parse($compareFrom)->startOfDay();
            $bTo = Carbon::parse($compareTo)->endOfDay();
        } elseif ($compareMode === 'last_month') {
            // Same calendar days, previous month.
            // Use NoOverflow to avoid invalid dates (e.g., Mar 31 -> Feb 28).
            $bFrom = (clone $aFrom)->subMonthNoOverflow()->startOfDay();
            $bTo = (clone $aTo)->subMonthNoOverflow()->endOfDay();
        } else {
            // Previous period with same length.
            $days = max(1, $aFrom->diffInDays($aTo) + 1);
            $bTo = (clone $aFrom)->subDay()->endOfDay();
            $bFrom = (clone $bTo)->subDays($days - 1)->startOfDay();
        }

        $products = DB::table('psi_products as p')
            ->leftJoin('shapes', 'shapes.id', '=', 'p.shape_id')
            ->leftJoin('uoms', 'uoms.id', '=', 'p.uom_id')
            ->select([
                'p.id',
                'p.weight',
                'p.length',
                DB::raw("COALESCE(shapes.name, '-') as shape"),
                DB::raw("COALESCE(uoms.name, '') as uom"),
            ])
            ->where('p.is_suspended', '=', 'false')
            ->orderBy('shapes.name')
            ->orderBy('p.weight')
            ->get();

        // Focus qty aggregated to product (latest per branch_psi_product_id).
        $latestFocusSub = DB::table('focus_sales')
            ->select('branch_psi_product_id', DB::raw('MAX(id) as latest_focus_id'))
            ->groupBy('branch_psi_product_id');

        $focusByProduct = DB::table('psi_products as p')
            ->leftJoin('branch_psi_products as bpp', 'bpp.psi_product_id', '=', 'p.id')
            ->leftJoinSub($latestFocusSub, 'lf', function ($join) {
                $join->on('lf.branch_psi_product_id', '=', 'bpp.id');
            })
            ->leftJoin('focus_sales as fs_latest', 'fs_latest.id', '=', 'lf.latest_focus_id')
            ->where('p.is_suspended', '=', 'false')
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->groupBy('p.id')
            ->select([
                'p.id as psi_product_id',
                DB::raw('SUM(COALESCE(fs_latest.qty, 0)) as focus_qty'),
            ])
            ->pluck('focus_qty', 'psi_product_id')
            ->map(fn($v) => (float) $v)
            ->all();

        $totalsA = $this->getProductSalesTotalsByDateRange($aFrom->format('Y-m-d'), $aTo->format('Y-m-d'), $branchId);
        $totalsB = $this->getProductSalesTotalsByDateRange($bFrom->format('Y-m-d'), $bTo->format('Y-m-d'), $branchId);

        $rows = [];
        foreach ($products as $p) {
            $pid = (int) $p->id;
            $weight = (float) ($p->weight ?? 0);

            $labelParts = [trim((string) $p->shape)];
            if (!empty($p->weight)) {
                $labelParts[] = trim((string) $p->weight) . 'g';
            }
            if (!empty($p->length)) {
                $labelParts[] = trim((string) $p->length) . ' ' . trim((string) $p->uom);
            }

            $productLabel = trim(implode(' · ', array_filter($labelParts)));

            $a = (float) ($totalsA[$pid] ?? 0);
            $b = (float) ($totalsB[$pid] ?? 0);
            $delta = $a - $b;
            $pct = $b > 0 ? ($delta / $b) * 100.0 : ($a > 0 ? 100.0 : 0.0);

            $rows[] = [
                'psi_product_id' => $pid,
                'product' => $productLabel !== '' ? $productLabel : ('Product #' . $pid),
                'weight' => $weight,
                'focus_qty' => (float) ($focusByProduct[$pid] ?? 0),
                'a' => $a,
                'b' => $b,
                'delta' => $delta,
                'delta_pct' => $pct,
                'trend' => $a <=> $b,
            ];
        }

        // Show biggest movers first (absolute delta), then rangeA total.
        usort($rows, function ($x, $y) {
            $dx = abs((float) ($x['delta'] ?? 0));
            $dy = abs((float) ($y['delta'] ?? 0));
            $c = $dy <=> $dx;
            if ($c !== 0) return $c;
            return ((float) ($y['a'] ?? 0)) <=> ((float) ($x['a'] ?? 0));
        });

        return [
            'rangeA' => [
                'from' => $aFrom->format('Y-m-d'),
                'to' => $aTo->format('Y-m-d'),
            ],
            'rangeB' => [
                'from' => $bFrom->format('Y-m-d'),
                'to' => $bTo->format('Y-m-d'),
            ],
            'rows' => $rows,
        ];
    }
}
