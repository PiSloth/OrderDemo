<?php

namespace App\Livewire\Order\Psi;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Report extends Component
{
    public int $periodDays = 30;
    public string $search = '';
    public string $status = 'all';
    public bool $manualModal = false;

    public function render()
    {
        $periodDays = max(1, (int) $this->periodDays);
        $since = Carbon::today()->subDays($periodDays - 1)->toDateString();
        $today = Carbon::today();

        $latestFocusSub = DB::table('focus_sales')
            ->select('branch_psi_product_id', DB::raw('MAX(id) as latest_focus_id'))
            ->groupBy('branch_psi_product_id');

        $latestArrivalSub = DB::table('branch_lead_days')
            ->select('branch_psi_product_id', DB::raw('MAX(id) as latest_arrival_id'))
            ->groupBy('branch_psi_product_id');

        $realSalesPeriodSub = DB::table('real_sales')
            ->select('branch_psi_product_id', DB::raw('SUM(qty) as real_qty_period'))
            ->where('sale_date', '>=', $since)
            ->groupBy('branch_psi_product_id');

        $latestPhotoSub = DB::table('product_photos')
            ->select('psi_product_id', DB::raw('MAX(id) as latest_photo_id'))
            ->groupBy('psi_product_id');

        $rows = DB::table('branch_psi_products as bpp')
            ->leftJoin('branches as b', 'b.id', '=', 'bpp.branch_id')
            ->leftJoin('psi_products as p', 'p.id', '=', 'bpp.psi_product_id')
            ->leftJoin('shapes as s', 's.id', '=', 'p.shape_id')
            // latest photo per product
            ->leftJoinSub($latestPhotoSub, 'lp', function ($join) {
                $join->on('lp.psi_product_id', '=', 'p.id');
            })
            ->leftJoin('product_photos as pp', 'pp.id', '=', 'lp.latest_photo_id')
            ->leftJoin('psi_stocks as st', 'st.branch_psi_product_id', '=', 'bpp.id')
            ->leftJoin('reorder_points as rp', 'rp.psi_stock_id', '=', 'st.id')
            // Latest focus per branch_psi_product
            ->leftJoinSub($latestFocusSub, 'lf', function ($join) {
                $join->on('lf.branch_psi_product_id', '=', 'bpp.id');
            })
            ->leftJoin('focus_sales as fs_latest', 'fs_latest.id', '=', 'lf.latest_focus_id')
            // Latest arrival day per branch_psi_product
            ->leftJoinSub($latestArrivalSub, 'la', function ($join) {
                $join->on('la.branch_psi_product_id', '=', 'bpp.id');
            })
            ->leftJoin('branch_lead_days as bld_latest', 'bld_latest.id', '=', 'la.latest_arrival_id')
            // Real sales in period
            ->leftJoinSub($realSalesPeriodSub, 'rs', function ($join) {
                $join->on('rs.branch_psi_product_id', '=', 'bpp.id');
            })
            ->select([
                'bpp.id as branch_psi_product_id',
                'b.id as branch_id',
                'b.name as branch_name',
                'p.id as psi_product_id',
                's.name as product_name',
                'p.weight as weight',
                'pp.image as photo',
                DB::raw('COALESCE(st.inventory_balance, 0) as inventory_balance'),
                DB::raw('COALESCE(rp.safty_day, 0) as safety_day'),
                DB::raw('COALESCE(rp.display_qty, 0) as display_qty'),
                DB::raw('COALESCE(fs_latest.qty, 0) as focus_qty'),
                DB::raw('COALESCE(bld_latest.quantity, 0) as arrival_day'),
                DB::raw('COALESCE(rs.real_qty_period, 0) as real_qty_period'),
            ])
            ->orderBy('b.name')
            ->orderBy('s.name')
            ->get();

        $analytics = $rows->map(function ($r) use ($periodDays, $today) {
            $inventory = (float) ($r->inventory_balance ?? 0);
            $focusQty = (float) ($r->focus_qty ?? 0);
            $arrivalDay = (int) ($r->arrival_day ?? 0);
            $safetyDay = (float) ($r->safety_day ?? 0);
            $displayQty = (float) ($r->display_qty ?? 0);
            $realPeriodQty = (float) ($r->real_qty_period ?? 0);

            $realAvgPerDay = $realPeriodQty / $periodDays;

            // Minimum stock limits for branch only (ignore supplier lead day)
            // Min with focus = (delivery day + safety day) * focus + display_qty
            // Min with real  = (delivery day + safety day) * real_avg + display_qty
            $bufferDays = max(0, (float) $arrivalDay + (float) $safetyDay);
            $minWithFocus = ceil(($bufferDays * $focusQty) + $displayQty);
            $minWithReal = ceil(($bufferDays * $realAvgPerDay) + $displayQty);
            $minDiffQty = $minWithFocus - $minWithReal;

            $baseMinFocus = ceil((max(0, $safetyDay) * $focusQty) + $displayQty);
            $baseMinReal = ceil((max(0, $safetyDay) * $realAvgPerDay) + $displayQty);

            $minDiffPercent = $minWithReal > 0
                ? ($minDiffQty / $minWithReal) * 100
                : null;

            $outOfStockDays = null;
            $outOfStockDate = null;
            if ($realAvgPerDay > 0) {
                $outOfStockDays = (int) floor($inventory / $realAvgPerDay);
                $outOfStockDate = $today->copy()->addDays($outOfStockDays)->toDateString();
            }

            $arrivalDate = $today->copy()->addDays(max(0, $arrivalDay))->toDateString();
            $needArrivalBy = $outOfStockDate;

            $minGapQty = $inventory - $minWithFocus;

            $condition = 'OK';
            if ($realAvgPerDay <= 0) {
                $condition = 'NO SALES';
            } elseif ($inventory <= 0) {
                $condition = 'OUT';
            } elseif ($inventory < $minWithFocus) {
                $condition = 'SHORT';
            } elseif ($minGapQty > 0) {
                $condition = 'OVER';
            }

            return [
                'branch_id' => (int) ($r->branch_id ?? 0),
                'branch_name' => (string) ($r->branch_name ?? ''),
                'psi_product_id' => (int) ($r->psi_product_id ?? 0),
                'product_name' => (string) ($r->product_name ?? ''),
                'weight' => (string) ($r->weight ?? ''),
                'photo' => $r->photo ?? null,
                'inventory_balance' => $inventory,
                'focus_qty' => $focusQty,
                'arrival_day' => $arrivalDay,
                'arrival_date' => $arrivalDate,
                'safety_day' => $safetyDay,
                'display_qty' => $displayQty,
                'base_min_focus' => $baseMinFocus,
                'base_min_real' => $baseMinReal,
                'min_with_focus' => $minWithFocus,
                'min_with_real' => $minWithReal,
                'min_diff_qty' => $minDiffQty,
                'min_diff_percent' => $minDiffPercent,
                'real_qty_period' => $realPeriodQty,
                'real_avg_per_day' => $realAvgPerDay,
                'out_of_stock_days' => $outOfStockDays,
                'out_of_stock_date' => $outOfStockDate,
                'need_arrival_by' => $needArrivalBy,
                'min_gap_qty' => $minGapQty,
                'condition' => $condition,
            ];
        });

        $trendProducts = $analytics
            ->groupBy('psi_product_id')
            ->map(function ($items) use ($periodDays) {
                $first = $items->first();

                $totalInventory = (float) $items->sum('inventory_balance');
                $totalBaseMinFocus = (float) $items->sum('base_min_focus');
                $totalMinWithFocus = (float) $items->sum('min_with_focus');
                $totalMinWithReal = (float) $items->sum('min_with_real');
                $totalFocusPerDay = (float) $items->sum('focus_qty');
                $totalRealPeriodQty = (float) $items->sum('real_qty_period');

                $totalRealAvgPerDay = $totalRealPeriodQty / $periodDays;
                $minDiffQty = $totalMinWithFocus - $totalMinWithReal;
                $overUnderVsMinFocus = $totalInventory - $totalMinWithFocus;

                $minDiffPercent = $totalMinWithReal > 0
                    ? ($minDiffQty / $totalMinWithReal) * 100
                    : null;

                $branchConditions = $items->pluck('condition')->filter()->values();
                $status = 'OK';
                if ($branchConditions->contains('OUT')) {
                    $status = 'OUT';
                } elseif ($branchConditions->contains('SHORT')) {
                    $status = 'SHORT';
                } elseif ($branchConditions->every(fn($c) => $c === 'NO SALES')) {
                    $status = 'NO SALES';
                } elseif ($overUnderVsMinFocus > 0) {
                    $status = 'OVER';
                }

                $branches = $items
                    ->sortBy('branch_name')
                    ->values()
                    ->all();

                return [
                    'psi_product_id' => (int) ($first['psi_product_id'] ?? 0),
                    'product_name' => (string) ($first['product_name'] ?? ''),
                    'weight' => (string) ($first['weight'] ?? ''),
                    'photo' => $first['photo'] ?? null,
                    'status' => $status,
                    'total_inventory' => $totalInventory,
                    'total_base_min_focus' => $totalBaseMinFocus,
                    'total_min_with_focus' => $totalMinWithFocus,
                    'total_min_with_real' => $totalMinWithReal,
                    'total_focus_per_day' => $totalFocusPerDay,
                    'total_real_avg_per_day' => $totalRealAvgPerDay,
                    'min_diff_qty' => $minDiffQty,
                    'min_diff_percent' => $minDiffPercent,
                    'over_under_vs_min_focus' => $overUnderVsMinFocus,
                    'branches' => $branches,
                ];
            })
            ->sortBy('product_name')
            ->values();

        $search = trim(mb_strtolower($this->search));
        if ($search !== '') {
            $trendProducts = $trendProducts->filter(function ($p) use ($search) {
                return str_contains(mb_strtolower((string) ($p['product_name'] ?? '')), $search);
            })->values();
        }

        $status = $this->status;
        if ($status !== 'all') {
            $trendProducts = $trendProducts->filter(function ($p) use ($status) {
                return (string) ($p['status'] ?? 'OK') === $status;
            })->values();
        }

        $grandTotal = [
            'label' => 'Grand Total',
            'product_count' => (int) $trendProducts->count(),
            'total_inventory' => (float) $trendProducts->sum('total_inventory'),
            'total_base_min_focus' => (float) $trendProducts->sum('total_base_min_focus'),
            'total_min_with_focus' => (float) $trendProducts->sum('total_min_with_focus'),
            'total_min_with_real' => (float) $trendProducts->sum('total_min_with_real'),
            'total_real_avg_per_day' => (float) $trendProducts->sum('total_real_avg_per_day'),
            'min_diff_qty' => (float) $trendProducts->sum('min_diff_qty'),
            'over_under_vs_min_focus' => (float) $trendProducts->sum('over_under_vs_min_focus'),
        ];

        $branchLines = $trendProducts->flatMap(function ($p) {
            return collect($p['branches'] ?? []);
        })->values();

        $branchLineTotal = (int) $branchLines->count();
        $branchLineRed = (int) $branchLines->filter(function ($b) {
            return ((float) ($b['inventory_balance'] ?? 0)) <= ((float) ($b['base_min_focus'] ?? 0));
        })->count();
        $branchLineGreen = max(0, $branchLineTotal - $branchLineRed);

        $grandTotal['branch_line_total'] = $branchLineTotal;
        $grandTotal['branch_line_green'] = $branchLineGreen;
        $grandTotal['branch_line_red'] = $branchLineRed;
        $grandTotal['fulfill_percent'] = $branchLineTotal > 0
            ? ($branchLineGreen / $branchLineTotal) * 100
            : null;
        $grandTotal['fail_percent'] = $branchLineTotal > 0
            ? ($branchLineRed / $branchLineTotal) * 100
            : null;

        $grandTotal['min_diff_percent'] = $grandTotal['total_min_with_real'] > 0
            ? ($grandTotal['min_diff_qty'] / $grandTotal['total_min_with_real']) * 100
            : null;

        return view('livewire.order.psi.report', [
            'periodDays' => $periodDays,
            'since' => $since,
            'trendProducts' => $trendProducts,
            'grandTotal' => $grandTotal,
        ]);
    }
}
