<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PsiProduct;
use App\Models\RealSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PsiProductService
{
    /**
     * Daily series for line chart: Actual vs Focus.
     *
     * Notes:
     * - Focus qty is treated as PER-DAY focus.
     * - Focus line is constant across days (latest focus).
     *
     * @return array{labels: array<int,string>, actual: array<int,float>, focus: array<int,float>}
     */
    public function getDailyFocusActualSeries(string $from, string $to, ?int $branchId = null, string $metric = 'qty'): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // Keep charts reasonable (max 93 days).
        $maxDays = 93;
        $days = max(1, $start->diffInDays($end) + 1);
        if ($days > $maxDays) {
            $end = (clone $start)->addDays($maxDays - 1)->endOfDay();
        }

        $metric = in_array($metric, ['qty', 'grams', 'index'], true) ? $metric : 'qty';

        $calcMetric = function (float $qty, float $weight) use ($metric): float {
            $grams = $qty * max(0, $weight);
            return match ($metric) {
                'grams' => $grams,
                'index' => ($qty * 0.4) + ($grams * 0.6),
                default => $qty,
            };
        };

        // Build labels.
        $labels = [];
        $cursor = (clone $start)->startOfDay();
        while ($cursor->lte($end)) {
            $labels[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        $actualByDay = array_fill_keys($labels, 0.0);

        $driver = DB::connection()->getDriverName();
        $dayExpr = match ($driver) {
            'pgsql' => "TO_CHAR(rs.sale_date::date, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', rs.sale_date)",
            default => "DATE_FORMAT(rs.sale_date, '%Y-%m-%d')",
        };

        // Actual sales grouped by day + product.
        $salesRows = DB::table('real_sales as rs')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'rs.branch_psi_product_id')
            ->join('psi_products as p', 'p.id', '=', 'bpp.psi_product_id')
            ->whereBetween('rs.sale_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->select([
                DB::raw($dayExpr . ' as day'),
                'p.id as psi_product_id',
                'p.weight as weight',
                DB::raw('SUM(rs.qty) as qty'),
            ])
            ->groupBy(DB::raw($dayExpr), 'p.id', 'p.weight')
            ->get();

        foreach ($salesRows as $r) {
            $day = (string) ($r->day ?? '');
            if ($day === '' || !array_key_exists($day, $actualByDay)) {
                continue;
            }
            $qty = (float) ($r->qty ?? 0);
            $weight = (float) ($r->weight ?? 0);
            $actualByDay[$day] += $calcMetric($qty, $weight);
        }

        // Focus per-day totals (latest focus).
        $latestFocusSub = DB::table('focus_sales')
            ->select('branch_psi_product_id', DB::raw('MAX(id) as latest_focus_id'))
            ->groupBy('branch_psi_product_id');

        $focusRows = DB::table('psi_products as p')
            ->leftJoin('branch_psi_products as bpp', 'bpp.psi_product_id', '=', 'p.id')
            ->leftJoinSub($latestFocusSub, 'lf', function ($join) {
                $join->on('lf.branch_psi_product_id', '=', 'bpp.id');
            })
            ->leftJoin('focus_sales as fs_latest', 'fs_latest.id', '=', 'lf.latest_focus_id')
            ->where('p.is_suspended', '=', 'false')
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->groupBy('p.id', 'p.weight')
            ->select([
                'p.id as psi_product_id',
                'p.weight as weight',
                DB::raw('SUM(COALESCE(fs_latest.qty, 0)) as focus_qty'),
            ])
            ->get();

        $focusTotalPerDay = 0.0;
        foreach ($focusRows as $r) {
            $qty = (float) ($r->focus_qty ?? 0);
            $weight = (float) ($r->weight ?? 0);
            $focusTotalPerDay += $calcMetric($qty, $weight);
        }

        $actual = [];
        $focus = [];
        foreach ($labels as $d) {
            $actual[] = (float) ($actualByDay[$d] ?? 0);
            $focus[] = (float) $focusTotalPerDay;
        }

        return [
            'labels' => $labels,
            'actual' => $actual,
            'focus' => $focus,
        ];
    }

    /**
     * Monthly stock-out report.
     *
     * Definitions:
     * - Opening balance: balance at start of month before any movements on day 1.
     * - Refill amount: sum of stock-in transactions during the month.
     * - Closing balance: balance at end of month after all movements.
     * - Stock-out days: days where end-of-day balance <= 0.
     *
     * Assumptions:
     * - `psi_stocks.inventory_balance` is the current balance (now).
     * - Historical balances can be reconstructed by reversing `stock_transactions` (signed by type) and `real_sales`.
     *
     * @return array{range: array{from:string,to:string,month:string}, rows: array<int, array<string,mixed>>}
     */
    public function getMonthlyStockoutReport(string $month, ?int $branchId = null): array
    {
        // month can be 'YYYY-MM' or a date string; normalize.
        $m = preg_match('/^\d{4}-\d{2}$/', $month)
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : Carbon::parse($month)->startOfMonth();

        $from = (clone $m)->startOfMonth();
        $to = (clone $m)->endOfMonth();

        // If the user is viewing the current month, don't project into future days.
        // Cap the report range to today so 0-stock intervals end at today.
        $now = Carbon::now();
        if ($m->isSameMonth($now)) {
            $to = $now->copy()->endOfDay();
        }

        $driver = DB::connection()->getDriverName();
        $dayExpr = match ($driver) {
            'pgsql' => "TO_CHAR(st.created_at::date, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', st.created_at)",
            default => "DATE_FORMAT(st.created_at, '%Y-%m-%d')",
        };

        // Current balances (now) per product.
        $currentBalances = DB::table('psi_stocks as ps')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'ps.branch_psi_product_id')
            ->join('psi_products as p', 'p.id', '=', 'bpp.psi_product_id')
            ->leftJoin('shapes', 'shapes.id', '=', 'p.shape_id')
            ->leftJoin('uoms', 'uoms.id', '=', 'p.uom_id')
            ->where('p.is_suspended', '=', 'false')
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->groupBy('p.id', 'p.remark', 'p.weight', 'p.length', DB::raw('COALESCE(shapes.name, "-")'), DB::raw('COALESCE(uoms.name, "")'))
            ->select([
                'p.id as psi_product_id',
                'p.remark as product_remark',
                'p.weight',
                'p.length',
                DB::raw('COALESCE(shapes.name, "-") as shape'),
                DB::raw('COALESCE(uoms.name, "") as uom'),
                DB::raw('SUM(ps.inventory_balance) as current_balance'),
            ])
            ->get()
            ->keyBy('psi_product_id');

        if ($currentBalances->isEmpty()) {
            return [
                'range' => [
                    'from' => $from->format('Y-m-d'),
                    'to' => $to->format('Y-m-d'),
                    'month' => $from->format('Y-m'),
                ],
                'rows' => [],
            ];
        }

        $productIds = $currentBalances->keys()->map(fn($v) => (int) $v)->values()->all();

        // Focus qty aggregated to product (latest per branch_psi_product_id). Focus qty is PER-DAY.
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
            ->whereIn('p.id', $productIds)
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->groupBy('p.id')
            ->select([
                'p.id as psi_product_id',
                DB::raw('SUM(COALESCE(fs_latest.qty, 0)) as focus_qty'),
            ])
            ->pluck('focus_qty', 'psi_product_id')
            ->map(fn($v) => (float) $v)
            ->all();

        // Sales after a given date are used to reconstruct opening/closing.
        $salesAfterStart = DB::table('real_sales as rs')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'rs.branch_psi_product_id')
            ->whereIn('bpp.psi_product_id', $productIds)
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->where('rs.sale_date', '>=', $from->format('Y-m-d'))
            ->select(['bpp.psi_product_id', DB::raw('SUM(rs.qty) as qty')])
            ->groupBy('bpp.psi_product_id')
            ->pluck('qty', 'psi_product_id')
            ->map(fn($v) => (float) $v)
            ->all();

        $salesAfterEnd = DB::table('real_sales as rs')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'rs.branch_psi_product_id')
            ->whereIn('bpp.psi_product_id', $productIds)
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->where('rs.sale_date', '>', $to->format('Y-m-d'))
            ->select(['bpp.psi_product_id', DB::raw('SUM(rs.qty) as qty')])
            ->groupBy('bpp.psi_product_id')
            ->pluck('qty', 'psi_product_id')
            ->map(fn($v) => (float) $v)
            ->all();

        // Signed stock change after start (>= start) and after end (> endOfDay).
        $stockChangeAfterStart = DB::table('stock_transactions as st')
            ->join('stock_transaction_types as stt', 'stt.id', '=', 'st.stock_transaction_type_id')
            ->join('psi_stocks as ps', 'ps.id', '=', 'st.psi_stock_id')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'ps.branch_psi_product_id')
            ->whereIn('bpp.psi_product_id', $productIds)
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->where('st.created_at', '>=', $from)
            ->select([
                'bpp.psi_product_id',
                DB::raw('SUM(CASE WHEN stt.is_stockin = 1 THEN st.qty ELSE -st.qty END) as qty'),
            ])
            ->groupBy('bpp.psi_product_id')
            ->pluck('qty', 'psi_product_id')
            ->map(fn($v) => (float) $v)
            ->all();

        $stockChangeAfterEnd = DB::table('stock_transactions as st')
            ->join('stock_transaction_types as stt', 'stt.id', '=', 'st.stock_transaction_type_id')
            ->join('psi_stocks as ps', 'ps.id', '=', 'st.psi_stock_id')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'ps.branch_psi_product_id')
            ->whereIn('bpp.psi_product_id', $productIds)
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->where('st.created_at', '>', $to)
            ->select([
                'bpp.psi_product_id',
                DB::raw('SUM(CASE WHEN stt.is_stockin = 1 THEN st.qty ELSE -st.qty END) as qty'),
            ])
            ->groupBy('bpp.psi_product_id')
            ->pluck('qty', 'psi_product_id')
            ->map(fn($v) => (float) $v)
            ->all();

        // Refills within month (stock-in only).
        $refills = DB::table('stock_transactions as st')
            ->join('stock_transaction_types as stt', 'stt.id', '=', 'st.stock_transaction_type_id')
            ->join('psi_stocks as ps', 'ps.id', '=', 'st.psi_stock_id')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'ps.branch_psi_product_id')
            ->whereIn('bpp.psi_product_id', $productIds)
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->whereBetween('st.created_at', [$from, $to])
            ->where('stt.is_stockin', '=', 1)
            ->select(['bpp.psi_product_id', DB::raw('SUM(st.qty) as qty')])
            ->groupBy('bpp.psi_product_id')
            ->pluck('qty', 'psi_product_id')
            ->map(fn($v) => (float) $v)
            ->all();

        // Daily sales in month.
        $dailySales = DB::table('real_sales as rs')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'rs.branch_psi_product_id')
            ->whereIn('bpp.psi_product_id', $productIds)
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->whereBetween('rs.sale_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->select(['bpp.psi_product_id', 'rs.sale_date', DB::raw('SUM(rs.qty) as qty')])
            ->groupBy('bpp.psi_product_id', 'rs.sale_date')
            ->get();

        $dailySalesIndex = [];
        foreach ($dailySales as $r) {
            $pid = (int) $r->psi_product_id;
            $day = (string) $r->sale_date;
            $dailySalesIndex[$pid][$day] = (float) $r->qty;
        }

        // Daily signed stock changes in month.
        $dailyTx = DB::table('stock_transactions as st')
            ->join('stock_transaction_types as stt', 'stt.id', '=', 'st.stock_transaction_type_id')
            ->join('psi_stocks as ps', 'ps.id', '=', 'st.psi_stock_id')
            ->join('branch_psi_products as bpp', 'bpp.id', '=', 'ps.branch_psi_product_id')
            ->whereIn('bpp.psi_product_id', $productIds)
            ->when($branchId, fn($q) => $q->where('bpp.branch_id', '=', $branchId))
            ->whereBetween('st.created_at', [$from, $to])
            ->select([
                'bpp.psi_product_id',
                DB::raw($dayExpr . ' as day'),
                DB::raw('SUM(CASE WHEN stt.is_stockin = 1 THEN st.qty ELSE -st.qty END) as qty'),
            ])
            ->groupBy('bpp.psi_product_id', DB::raw($dayExpr))
            ->get();

        $dailyTxIndex = [];
        foreach ($dailyTx as $r) {
            $pid = (int) $r->psi_product_id;
            $day = (string) $r->day;
            $dailyTxIndex[$pid][$day] = (float) $r->qty;
        }

        // Pre-build list of days in month.
        $days = [];
        $cursor = (clone $from);
        while ($cursor->lte($to)) {
            $days[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        $rows = [];
        foreach ($currentBalances as $pid => $meta) {
            $pid = (int) $pid;
            $currentBalance = (float) ($meta->current_balance ?? 0);

            $weight = (float) ($meta->weight ?? 0);
            $focusQtyPerDay = (float) ($focusByProduct[$pid] ?? 0);

            $saleAfterStart = (float) ($salesAfterStart[$pid] ?? 0);
            $saleAfterEnd = (float) ($salesAfterEnd[$pid] ?? 0);
            $stockAfterStart = (float) ($stockChangeAfterStart[$pid] ?? 0);
            $stockAfterEnd = (float) ($stockChangeAfterEnd[$pid] ?? 0);

            // balanceAt(startOfMonth) = now - stockAfterStart + salesAfterStart
            $opening = $currentBalance - $stockAfterStart + $saleAfterStart;
            // balanceAt(endOfMonth) = now - stockAfterEnd + salesAfterEnd
            $closing = $currentBalance - $stockAfterEnd + $saleAfterEnd;

            $balance = $opening;
            $zeroDays = [];
            $saleInRange = 0.0;
            $txInRangeSigned = 0.0;
            foreach ($days as $d) {
                $tx = (float) ($dailyTxIndex[$pid][$d] ?? 0);
                $sale = (float) ($dailySalesIndex[$pid][$d] ?? 0);

                $txInRangeSigned += $tx;
                $saleInRange += $sale;

                $balance += $tx;
                $balance -= $sale;
                if ($balance <= 0) {
                    $zeroDays[] = $d;
                }
            }

            $lossDays = count($zeroDays);
            $lossQty = $focusQtyPerDay * $lossDays;
            $lossGrams = $lossQty * max(0, $weight);
            $lossIndex = ($lossQty * 0.4) + ($lossGrams * 0.6);

            // Build intervals from zero days.
            $intervals = [];
            if (!empty($zeroDays)) {
                $startD = $zeroDays[0];
                $prevD = $zeroDays[0];
                for ($i = 1; $i < count($zeroDays); $i++) {
                    $curr = $zeroDays[$i];
                    $prev = Carbon::parse($prevD);
                    $expected = $prev->addDay()->format('Y-m-d');
                    if ($curr !== $expected) {
                        $len = Carbon::parse($startD)->diffInDays(Carbon::parse($prevD)) + 1;
                        $intervals[] = $startD === $prevD ? "$startD ($len day)" : "$startD ~ $prevD ($len days)";
                        $startD = $curr;
                    }
                    $prevD = $curr;
                }
                $len = Carbon::parse($startD)->diffInDays(Carbon::parse($prevD)) + 1;
                $intervals[] = $startD === $prevD ? "$startD ($len day)" : "$startD ~ $prevD ($len days)";
            }

            $labelParts = [trim((string) ($meta->shape ?? '-'))];
            if (!empty($meta->weight)) {
                $labelParts[] = trim((string) $meta->weight) . 'g';
            }
            if (!empty($meta->length)) {
                $labelParts[] = trim((string) $meta->length) . ' ' . trim((string) ($meta->uom ?? ''));
            }
            $productLabel = trim(implode(' · ', array_filter($labelParts)));

            $rows[] = [
                'psi_product_id' => $pid,
                'product' => $productLabel !== '' ? $productLabel : ('Product #' . $pid),
                'remark' => (string) ($meta->product_remark ?? ''),
                'opening' => (float) $opening,
                'refill' => (float) ($refills[$pid] ?? 0),
                'sale' => (float) $saleInRange,
                'closing' => (float) $closing,
                'zero_days' => count($zeroDays),
                'zero_intervals' => $intervals,
                'focus_qty_per_day' => (float) $focusQtyPerDay,
                'loss_qty' => (float) $lossQty,
                'loss_grams' => (float) $lossGrams,
                'loss_index' => (float) $lossIndex,
            ];
        }

        usort($rows, function ($a, $b) {
            $c = ((int) ($b['zero_days'] ?? 0)) <=> ((int) ($a['zero_days'] ?? 0));
            if ($c !== 0) return $c;
            return ((float) ($b['sale'] ?? 0)) <=> ((float) ($a['sale'] ?? 0));
        });

        return [
            'range' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'month' => $from->format('Y-m'),
            ],
            'rows' => $rows,
        ];
    }

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
