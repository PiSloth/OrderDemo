<?php

namespace App\Http\Controllers\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchTarget;
use App\Models\DailyReport;
use App\Models\DailyReportRecord;
use App\Models\Department;
use App\Models\PromoteAction;
use App\Models\TodoList;
use App\Models\Kpi\KpiTaskTemplate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KpiSaleKpiController extends Controller
{
    /**
     * Render the Sale KPI dashboard.
     */
    public function index(Request $request): Response
    {
        $branches = Branch::where('is_jewelry_shop', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($b) {
                $b->name = ucwords(strtolower($b->name));
                return $b;
            });
        $departments = Department::orderBy('name')->get(['id', 'name']);

        // Determine default date range based on active promote actions
        $today = Carbon::today();
        $activePAs = PromoteAction::where('start_at', '<=', $today)
            ->where('end_at', '>=', $today)
            ->get();

        if ($activePAs->isNotEmpty()) {
            $minStart = Carbon::parse($activePAs->min('start_at'));
            $maxEnd = Carbon::parse($activePAs->max('end_at'));
            $defaultFrom = $minStart->copy()->subDays(10)->format('Y-m-d');
            $defaultTo = $maxEnd->copy()->addDays(10)->format('Y-m-d');
        } else {
            $defaultFrom = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $defaultTo = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
        }

        $taxonomies = \App\Models\MasterTaxonomy::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group_key');

        return Inertia::render('Kpi/SaleKpi', [
            'branches' => $branches,
            'departments' => $departments,
            'defaultFrom' => $defaultFrom,
            'defaultTo' => $defaultTo,
            'taxonomies' => $taxonomies,
        ]);
    }

    /**
     * Get statistics and charts data for the dashboard.
     */
    public function getData(Request $request)
    {
        $today = Carbon::today();

        // 1. Resolve date range filters
        $fromInput = $request->input('start_date');
        $toInput = $request->input('end_date');

        if ($fromInput && $toInput) {
            $from = Carbon::parse($fromInput)->startOfDay();
            $to = Carbon::parse($toInput)->endOfDay();
        } else {
            // Find active promote actions to base date range
            $activePAs = PromoteAction::where('start_at', '<=', $today)
                ->where('end_at', '>=', $today)
                ->get();

            if ($activePAs->isNotEmpty()) {
                $minStart = Carbon::parse($activePAs->min('start_at'));
                $maxEnd = Carbon::parse($activePAs->max('end_at'));
                $from = $minStart->copy()->subDays(10)->startOfDay();
                $to = $maxEnd->copy()->addDays(10)->endOfDay();
            } else {
                $from = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $to = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            }
        }

        // 2. Resolve branch filters (only jewellery shops)
        $branchIdsRaw = $request->input('branch_ids');
        $branchIds = [];
        if (!empty($branchIdsRaw)) {
            if (is_string($branchIdsRaw)) {
                $branchIds = array_filter(explode(',', $branchIdsRaw), fn($v) => strlen(trim($v)) > 0);
            } else if (is_array($branchIdsRaw)) {
                $branchIds = $branchIdsRaw;
            }
            $branchIds = array_values(array_filter(array_map('intval', $branchIds)));
        }

        // Fetch selected or all jewellery shop branches
        $branchesQuery = Branch::where('is_jewelry_shop', true);
        if (!empty($branchIds)) {
            $branchesQuery->whereIn('id', $branchIds);
        }
        $branches = $branchesQuery->orderBy('name')->get()->map(function ($b) {
            $b->name = ucwords(strtolower($b->name));
            return $b;
        });

        $jewelBranchIds = $branches->pluck('id')->toArray();

        // 3. Fetch Daily Report Records & Targets in date range
        $records = DailyReportRecord::query()
            ->join('daily_reports', 'daily_reports.id', '=', 'daily_report_records.daily_report_id')
            ->whereBetween('daily_report_records.report_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->whereIn('daily_report_records.branch_id', !empty($branchIds) ? $branchIds : (empty($jewelBranchIds) ? [0] : $jewelBranchIds))
            ->select('daily_report_records.*', 'daily_reports.properties')
            ->get()
            ->map(function ($record) {
                $record->decoded_properties = is_string($record->properties) 
                    ? json_decode($record->properties, true) 
                    : $record->properties;
                return $record;
            });

        $targets = BranchTarget::whereBetween('year', [$from->year, $to->year])
            ->whereIn('branch_id', !empty($branchIds) ? $branchIds : (empty($jewelBranchIds) ? [0] : $jewelBranchIds))
            ->get()
            ->filter(function ($t) use ($from, $to) {
                $date = Carbon::create($t->year, $t->month, $t->day);
                return $date->between($from, $to);
            });

        // 4. Fetch promote actions that overlap this date range
        $promoteActions = PromoteAction::with(['branch', 'department'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('start_at', [$from->format('Y-m-d'), $to->format('Y-m-d')])
                  ->orWhereBetween('end_at', [$from->format('Y-m-d'), $to->format('Y-m-d')])
                  ->orWhere(function ($sub) use ($from, $to) {
                      $sub->where('start_at', '<=', $from->format('Y-m-d'))
                          ->where('end_at', '>=', $to->format('Y-m-d'));
                  });
            })
            ->when(!empty($branchIds), function ($q) use ($branchIds) {
                return $q->where(function ($sub) use ($branchIds) {
                    $sub->whereIn('target_branch_id', $branchIds)
                        ->orWhereNull('target_branch_id');
                });
            })
            ->get();

        // Helper to check if a record matches JSON criteria
        $matches = function ($record, $scope, $matric, $types) {
            $props = $record->decoded_properties;
            if (!$props) return false;
            
            $propScope = $props['scope'] ?? null;
            $propMatric = $props['matric_type'] ?? null;
            $propType = $props['product_type'] ?? null;

            if ($scope && strtolower($propScope) !== strtolower($scope)) return false;
            if ($matric && strtolower($propMatric) !== strtolower($matric)) return false;
            if ($types && !in_array(strtolower($propType), array_map('strtolower', $types))) return false;

            return true;
        };

        // --- SECTION 1: COLUMN CHARTS ---
        $gramChartData = [];
        $pcsChartData = [];

        foreach ($branches as $branch) {
            $bId = $branch->id;

            // Actual Gram: scope: sale, matrix: weight, type: [gold, pandora, 18k]
            $actualGram = $records->filter(function ($r) use ($bId, $matches) {
                return $r->branch_id === $bId && $matches($r, 'sale', 'weight', ['gold', 'pandora', '18K']);
            })->sum('number');

            // Target Gram
            $targetGram = $targets->where('branch_id', $bId)->sum('target_gram');

            // Actual Pcs: scope: sale, matrix: quantity, type: [gold, pandora, 18k]
            $actualPcs = $records->filter(function ($r) use ($bId, $matches) {
                return $r->branch_id === $bId && $matches($r, 'sale', 'quantity', ['gold', 'pandora', '18K']);
            })->sum('number');

            // Target Pcs
            $targetPcs = $targets->where('branch_id', $bId)->sum('target_pcs');

            $gramRatio = $targetGram > 0 ? ($actualGram / $targetGram) : 0;
            $pcsRatio = $targetPcs > 0 ? ($actualPcs / $targetPcs) : 0;

            $gramChartData[] = [
                'branch_id' => $bId,
                'branch_name' => $branch->name,
                'actual' => round($actualGram, 2),
                'target' => round($targetGram, 2),
                'ratio' => $gramRatio,
            ];

            $pcsChartData[] = [
                'branch_id' => $bId,
                'branch_name' => $branch->name,
                'actual' => (int) $actualPcs,
                'target' => (int) $targetPcs,
                'ratio' => $pcsRatio,
            ];
        }

        // Sort by highest ratio
        usort($gramChartData, fn($a, $b) => $b['ratio'] <=> $a['ratio']);
        usort($pcsChartData, fn($a, $b) => $b['ratio'] <=> $a['ratio']);


        // --- SECTION 2: LINE CHART WITH MARKERS ---
        $viewType = $request->input('view_type', 'daily'); // daily or monthly
        $lineLabels = [];
        $lineDataWeight = [];
        $lineDataQuantity = [];
        $lineDataCustomer = [];
        $overlapCounts = []; // overlap counts for coloring line segments
        $overlapDetails = []; // active promote actions info per label

        if ($viewType === 'monthly') {
            // Group by Month
            $period = CarbonPeriod::create($from, '1 month', $to);
            foreach ($period as $dt) {
                $year = $dt->year;
                $month = $dt->month;
                $label = $dt->format('Y-M');
                $lineLabels[] = $label;

                // Sum actuals for month
                $weight = $records->filter(function ($r) use ($year, $month, $matches) {
                    $rDate = Carbon::parse($r->report_date);
                    return $rDate->year === $year && $rDate->month === $month && $matches($r, 'sale', 'weight', ['gold', 'pandora', '18K']);
                })->sum('number');

                $qty = $records->filter(function ($r) use ($year, $month, $matches) {
                    $rDate = Carbon::parse($r->report_date);
                    return $rDate->year === $year && $rDate->month === $month && $matches($r, 'sale', 'quantity', ['gold', 'pandora', '18K']);
                })->sum('number');

                $customer = $records->filter(function ($r) use ($year, $month, $matches) {
                    $rDate = Carbon::parse($r->report_date);
                    return $rDate->year === $year && $rDate->month === $month && $matches($r, 'sale', 'quantity', ['customer']);
                })->sum('number');

                $lineDataWeight[] = round($weight, 2);
                $lineDataQuantity[] = (int) $qty;
                $lineDataCustomer[] = (int) $customer;

                // Active promote actions during this month
                $activePAs = $promoteActions->filter(function ($pa) use ($dt) {
                    $paStart = Carbon::parse($pa->start_at);
                    $paEnd = Carbon::parse($pa->end_at);
                    return $paStart->startOfMonth() <= $dt->endOfMonth() && $paEnd->endOfMonth() >= $dt->startOfMonth();
                });

                $overlapCounts[] = $activePAs->count();
                $overlapDetails[$label] = $activePAs->map(fn($pa) => [
                    'id' => $pa->id,
                    'name' => $pa->name,
                    'start_at' => $pa->start_at->format('Y-m-d'),
                    'end_at' => $pa->end_at->format('Y-m-d'),
                    'department' => $pa->department?->name ?? 'N/A',
                ])->values()->all();
            }
        } else {
            // Group by Day
            $period = CarbonPeriod::create($from, '1 day', $to);
            foreach ($period as $dt) {
                $dateStr = $dt->format('Y-m-d');
                $lineLabels[] = $dateStr;

                $weight = $records->filter(function ($r) use ($dateStr, $matches) {
                    return $r->report_date === $dateStr && $matches($r, 'sale', 'weight', ['gold', 'pandora', '18K']);
                })->sum('number');

                $qty = $records->filter(function ($r) use ($dateStr, $matches) {
                    return $r->report_date === $dateStr && $matches($r, 'sale', 'quantity', ['gold', 'pandora', '18K']);
                })->sum('number');

                $customer = $records->filter(function ($r) use ($dateStr, $matches) {
                    return $r->report_date === $dateStr && $matches($r, 'sale', 'quantity', ['customer']);
                })->sum('number');

                $lineDataWeight[] = round($weight, 2);
                $lineDataQuantity[] = (int) $qty;
                $lineDataCustomer[] = (int) $customer;

                // Active promote actions on this day
                $activePAs = $promoteActions->filter(function ($pa) use ($dateStr) {
                    $start = $pa->start_at->format('Y-m-d');
                    $end = $pa->end_at->format('Y-m-d');
                    return $start <= $dateStr && $end >= $dateStr;
                });

                $overlapCounts[] = $activePAs->count();
                $overlapDetails[$dateStr] = $activePAs->map(fn($pa) => [
                    'id' => $pa->id,
                    'name' => $pa->name,
                    'start_at' => $pa->start_at->format('Y-m-d'),
                    'end_at' => $pa->end_at->format('Y-m-d'),
                    'department' => $pa->department?->name ?? 'N/A',
                ])->values()->all();
            }
        }


        // --- SECTION 3: REWARDS METAL TABLE ---
        $rewardsData = [];
        foreach ($branches as $branch) {
            $bId = $branch->id;

            // Actual Sale Pcs (gold, pandora, 18k, sale)
            $branchPcs = $records->filter(function ($r) use ($bId, $matches) {
                return $r->branch_id === $bId && $matches($r, 'sale', 'quantity', ['gold', 'pandora', '18K']);
            })->sum('number');

            // Actual Sale Weight (gold, pandora, 18k, sale)
            $branchWeight = $records->filter(function ($r) use ($bId, $matches) {
                return $r->branch_id === $bId && $matches($r, 'sale', 'weight', ['gold', 'pandora', '18K']);
            })->sum('number');

            // Customer count (sale, customer)
            $branchCustomer = $records->filter(function ($r) use ($bId, $matches) {
                return $r->branch_id === $bId && $matches($r, 'sale', 'quantity', ['customer']);
            })->sum('number');

            // Staff count (sale, staff)
            $branchStaff = $records->filter(function ($r) use ($bId, $matches) {
                return $r->branch_id === $bId && $matches($r, 'sale', 'quantity', ['staff']);
            })->sum('number');

            // Calculate ratios
            $pcsRatio = $branchCustomer > 0 ? ($branchPcs / $branchCustomer) : 0;
            $gramRatio = $branchCustomer > 0 ? ($branchWeight / $branchCustomer) : 0;
            $pcsPerStaff = $branchStaff > 0 ? ($branchPcs / $branchStaff) : 0;
            $customerPerStaff = $branchStaff > 0 ? ($branchCustomer / $branchStaff) : 0;

            $rewardsData[] = [
                'branch_id' => $bId,
                'branch_name' => $branch->name,
                'pcs_ratio' => round($pcsRatio, 3),
                'gram_ratio' => round($gramRatio, 3),
                'pcs_per_staff' => round($pcsPerStaff, 3),
                'customer_per_staff' => round($customerPerStaff, 3),
            ];
        }


        return response()->json([
            'start_date' => $from->format('Y-m-d'),
            'end_date' => $to->format('Y-m-d'),
            'gram_chart' => $gramChartData,
            'pcs_chart' => $pcsChartData,
            'line_chart' => [
                'labels' => $lineLabels,
                'weight' => $lineDataWeight,
                'quantity' => $lineDataQuantity,
                'customer' => $lineDataCustomer,
                'overlap_counts' => $overlapCounts,
                'overlap_details' => $overlapDetails,
            ],
            'rewards_table' => $rewardsData,
            'promote_actions' => $promoteActions->map(function ($pa) {
                return [
                    'id' => $pa->id,
                    'name' => $pa->name,
                    'target_branch' => $pa->branch?->name ?? 'All Branches',
                    'action_by_dept' => $pa->department?->name ?? 'N/A',
                    'start_at' => $pa->start_at->format('Y-m-d'),
                    'end_at' => $pa->end_at->format('Y-m-d'),
                    'reference' => $pa->reference,
                ];
            }),
        ]);
    }

    /**
     * Create a new promote action.
     */
    public function storePromoteAction(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_branch_id' => 'nullable|exists:branches,id',
            'action_by' => 'required|exists:departments,id',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after_or_equal:start_at',
            'reference' => 'nullable|array',
        ]);

        $promote = PromoteAction::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Promote action created successfully.',
            'data' => $promote,
        ]);
    }

    /**
     * Search Todo Lists for autocomplete.
     */
    public function searchTodos(Request $request)
    {
        $search = $request->input('q', '');
        $todos = TodoList::where('task', 'like', "%{$search}%")
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get(['id', 'task']);

        return response()->json($todos);
    }

    /**
     * Search KPI Task Templates for autocomplete.
     */
    public function searchKpiTasks(Request $request)
    {
        $search = $request->input('q', '');
        $tasks = KpiTaskTemplate::where('title', 'like', "%{$search}%")
            ->orderBy('title')
            ->limit(20)
            ->get(['id', 'title as name']);

        return response()->json($tasks);
    }

    /**
     * Period Comparison (Current vs Prev Period / YoY).
     */
    public function getComparisonData(Request $request)
    {
        $fromInput = $request->input('start_date');
        $toInput = $request->input('end_date');
        $mode = $request->input('compare_mode', 'prev_period'); // prev_period | yoy | none
        $metric = $request->input('metric', 'gram') === 'pcs' ? 'pcs' : 'gram';
        $branchIdsRaw = $request->input('branch_ids');

        $from = $fromInput ? Carbon::parse($fromInput)->startOfDay() : Carbon::now()->subDays(6)->startOfDay();
        $to = $toInput ? Carbon::parse($toInput)->startOfDay() : Carbon::now()->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $branchIds = [];
        if (!empty($branchIdsRaw)) {
            if (is_string($branchIdsRaw)) {
                $branchIds = array_filter(explode(',', $branchIdsRaw), fn($v) => strlen(trim($v)) > 0);
            } else if (is_array($branchIdsRaw)) {
                $branchIds = $branchIdsRaw;
            }
            $branchIds = array_values(array_filter(array_map('intval', $branchIds)));
        }

        $daysCount = $from->diffInDays($to) + 1;
        $metricLabel = $metric === 'pcs' ? 'Pcs' : 'Gram';

        // Base series (actual)
        $baseMap = $this->querySaleMetricMap($from, $to, $branchIds, $metric);
        $categories = [];
        $baseData = [];
        $cursor = $from->copy();
        for ($i = 0; $i < $daysCount; $i++) {
            $key = $cursor->format('Y-m-d');
            $categories[] = $cursor->format('M j');
            $baseData[] = round((float) ($baseMap[$key] ?? 0), 2);
            $cursor->addDay();
        }

        $series = [
            [
                'name' => 'Actual ' . $metricLabel . ' (' . $from->format('M j') . ' - ' . $to->format('M j') . ')',
                'data' => $baseData,
            ],
        ];

        if ($mode !== 'none') {
            if ($mode === 'yoy') {
                $compareFrom = $from->copy()->subYear();
                $compareTo = $to->copy()->subYear();
            } else {
                $compareTo = $from->copy()->subDay();
                $compareFrom = $compareTo->copy()->subDays($daysCount - 1);
            }

            $compareMap = $this->querySaleMetricMap($compareFrom, $compareTo, $branchIds, $metric);
            $compareData = [];
            $cursor = $compareFrom->copy();
            for ($i = 0; $i < $daysCount; $i++) {
                $key = $cursor->format('Y-m-d');
                $compareData[] = round((float) ($compareMap[$key] ?? 0), 2);
                $cursor->addDay();
            }

            $label = ($mode === 'yoy')
                ? 'Compare YoY (' . $compareFrom->format('M j, Y') . ' - ' . $compareTo->format('M j, Y') . ')'
                : 'Compare Prev (' . $compareFrom->format('M j') . ' - ' . $compareTo->format('M j') . ')';

            $series[] = [
                'name' => $label,
                'data' => $compareData,
            ];
        }

        $baseTotal = array_sum($baseData);
        $compareTotal = isset($compareData) ? array_sum($compareData) : 0;
        $growth = $compareTotal > 0 ? round((($baseTotal - $compareTotal) / $compareTotal) * 100, 2) : 0;

        return response()->json([
            'categories' => $categories,
            'series' => $series,
            'summary' => [
                'base_total' => $baseTotal,
                'compare_total' => $compareTotal,
                'growth_pct' => $growth,
                'metric' => $metricLabel,
            ],
        ]);
    }

    /**
     * Branch Achievement Heatmap Matrix.
     */
    public function getHeatmapData(Request $request)
    {
        $days = (int) $request->input('days', 7);
        $days = max(7, min(31, $days));
        $metric = $request->input('metric', 'gram') === 'pcs' ? 'pcs' : 'gram';
        $branchIdsRaw = $request->input('branch_ids');

        $branchIds = [];
        if (!empty($branchIdsRaw)) {
            if (is_string($branchIdsRaw)) {
                $branchIds = array_filter(explode(',', $branchIdsRaw), fn($v) => strlen(trim($v)) > 0);
            } else if (is_array($branchIdsRaw)) {
                $branchIds = $branchIdsRaw;
            }
            $branchIds = array_values(array_filter(array_map('intval', $branchIds)));
        }

        $today = Carbon::now()->startOfDay();
        $from = $today->copy()->subDays($days - 1);
        $to = $today->copy();

        $branchesQuery = Branch::where('is_jewelry_shop', true)->orderBy('name');
        if (!empty($branchIds)) {
            $branchesQuery->whereIn('id', $branchIds);
        }
        $branches = $branchesQuery->get(['id', 'name']);
        $allBranchIds = $branches->pluck('id')->map(fn($id) => (int) $id)->all();

        $dateKeys = [];
        $dateLabels = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $dateKeys[] = $cursor->format('Y-m-d');
            $dateLabels[] = $cursor->format('M j');
            $cursor->addDay();
        }

        // Targets map
        $targetCol = $metric === 'pcs' ? 'target_pcs' : 'target_gram';
        $targets = BranchTarget::select('branch_id', 'year', 'month', 'day', $targetCol)
            ->whereIn('branch_id', $allBranchIds)
            ->whereBetween('year', [$from->year, $to->year])
            ->get();

        $targetsByBranchDate = [];
        foreach ($targets as $t) {
            $d = Carbon::create($t->year, $t->month, $t->day)->format('Y-m-d');
            $targetsByBranchDate[$t->branch_id][$d] = (float) $t->{$targetCol};
        }

        // Actual map
        $flag = $metric === 'pcs' ? 'daily_reports.is_sale_quantity' : 'daily_reports.is_sale_gram';
        $actualRows = DailyReportRecord::select('daily_report_records.branch_id', 'daily_report_records.report_date', DB::raw('SUM(daily_report_records.number) as total'))
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where($flag, true)
            ->whereIn('daily_report_records.branch_id', $allBranchIds)
            ->whereBetween('daily_report_records.report_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->groupBy('daily_report_records.branch_id', 'daily_report_records.report_date')
            ->get();

        $actualByBranchDate = [];
        foreach ($actualRows as $row) {
            $actualByBranchDate[$row->branch_id][$row->report_date] = (float) $row->total;
        }

        $series = [];
        foreach ($branches as $branch) {
            $bId = (int) $branch->id;
            $data = [];
            for ($i = 0; $i < count($dateKeys); $i++) {
                $dk = $dateKeys[$i];
                $targetVal = (float) ($targetsByBranchDate[$bId][$dk] ?? 0);
                $actualVal = (float) ($actualByBranchDate[$bId][$dk] ?? 0);

                $pct = $targetVal > 0 ? round(($actualVal / $targetVal) * 100, 1) : null;
                $data[] = [
                    'x' => $dateLabels[$i],
                    'y' => $pct,
                    'target' => round($targetVal, 2),
                    'actual' => round($actualVal, 2),
                ];
            }

            $series[] = [
                'name' => ucwords(strtolower($branch->name)),
                'data' => $data,
            ];
        }

        return response()->json([
            'dateLabels' => $dateLabels,
            'series' => $series,
            'metric' => $metric,
        ]);
    }

    /**
     * Target vs Actual Month Trajectory (Optimized).
     */
    public function getTrajectoryData(Request $request)
    {
        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);
        $metric = $request->input('metric', 'gram') === 'pcs' ? 'pcs' : 'gram';
        $branchIdsRaw = $request->input('branch_ids');

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $branchIds = [];
        if (!empty($branchIdsRaw)) {
            if (is_string($branchIdsRaw)) {
                $branchIds = array_filter(explode(',', $branchIdsRaw), fn($v) => strlen(trim($v)) > 0);
            } else if (is_array($branchIdsRaw)) {
                $branchIds = $branchIdsRaw;
            }
            $branchIds = array_values(array_filter(array_map('intval', $branchIds)));
        }

        $targetCol = $metric === 'pcs' ? 'target_pcs' : 'target_gram';
        $targetsMap = BranchTarget::where('year', $year)
            ->where('month', $month)
            ->when(!empty($branchIds), fn($q) => $q->whereIn('branch_id', $branchIds))
            ->groupBy('day')
            ->select('day', DB::raw("SUM({$targetCol}) as total"))
            ->pluck('total', 'day')
            ->toArray();

        $actualFlag = $metric === 'pcs' ? 'daily_reports.is_sale_quantity' : 'daily_reports.is_sale_gram';
        $actualsMap = DailyReportRecord::whereBetween('report_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where($actualFlag, true)
            ->when(!empty($branchIds), fn($q) => $q->whereIn('daily_report_records.branch_id', $branchIds))
            ->groupBy('report_date')
            ->select('report_date', DB::raw('SUM(daily_report_records.number) as total'))
            ->pluck('total', 'report_date')
            ->toArray();

        $categories = [];
        $targets = [];
        $actuals = [];

        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            $categories[] = $date->format('M j');
            $targets[] = round((float) ($targetsMap[$date->day] ?? 0), 2);
            $actuals[] = round((float) ($actualsMap[$date->format('Y-m-d')] ?? 0), 2);
        }

        $metricLabel = $metric === 'pcs' ? 'Pcs' : 'Gram';

        return response()->json([
            'categories' => $categories,
            'series' => [
                [
                    'name' => 'Target (' . $metricLabel . ')',
                    'data' => $targets,
                ],
                [
                    'name' => 'Actual (' . $metricLabel . ')',
                    'data' => $actuals,
                ],
            ],
            'total_target' => array_sum($targets),
            'total_actual' => array_sum($actuals),
            'achievement_pct' => array_sum($targets) > 0 ? round((array_sum($actuals) / array_sum($targets)) * 100, 2) : 0,
        ]);
    }

    /**
     * Top 10 Most Popular Products.
     */
    public function getPopularProducts(Request $request)
    {
        $from = $request->input('start_date', Carbon::now()->subMonth()->format('Y-m-d'));
        $to = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');
        $limit = (int) $request->input('limit', 10);

        $query = DB::table('real_sales as rs')
            ->select(
                's.name as shape',
                'p.length',
                'p.weight',
                'uoms.name as uom',
                DB::raw('SUM(rs.qty) AS total_sale')
            )
            ->leftJoin('branch_psi_products as bpsi', 'rs.branch_psi_product_id', 'bpsi.id')
            ->leftJoin('psi_products as p', 'p.id', 'bpsi.psi_product_id')
            ->leftJoin('uoms', 'uoms.id', 'p.uom_id')
            ->leftJoin('shapes as s', 's.id', 'p.shape_id')
            ->whereBetween('rs.sale_date', [$from, $to])
            ->when($branchId, fn($q) => $q->where('bpsi.branch_id', $branchId))
            ->groupBy('s.name', 'p.length', 'uoms.name', 'p.weight')
            ->orderByDesc('total_sale')
            ->limit($limit);

        $items = $query->get();

        return response()->json($items);
    }

    /**
     * Monthly Target vs Actual Summary with Dual-Period Comparison.
     */
    public function getTargetActualSummary(Request $request)
    {
        $primaryFromStr = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $primaryToStr = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $compareFromStr = $request->input('compare_start_date');
        $compareToStr = $request->input('compare_end_date');
        $metric = $request->input('metric', 'gram') === 'pcs' ? 'pcs' : 'gram';
        $branchIdsRaw = $request->input('branch_ids');

        $primaryStart = Carbon::parse($primaryFromStr)->startOfDay();
        $primaryEnd = Carbon::parse($primaryToStr)->endOfDay();

        $compareStart = $compareFromStr ? Carbon::parse($compareFromStr)->startOfDay() : null;
        $compareEnd = $compareToStr ? Carbon::parse($compareToStr)->endOfDay() : null;

        $branchIds = [];
        if (!empty($branchIdsRaw)) {
            if (is_string($branchIdsRaw)) {
                $branchIds = array_filter(explode(',', $branchIdsRaw), fn($v) => strlen(trim($v)) > 0);
            } else if (is_array($branchIdsRaw)) {
                $branchIds = $branchIdsRaw;
            }
            $branchIds = array_values(array_filter(array_map('intval', $branchIds)));
        }

        // Fetch Jewelry branches
        $branchesQuery = Branch::where('is_jewelry_shop', true);
        if (!empty($branchIds)) {
            $branchesQuery->whereIn('id', $branchIds);
        }
        $branches = $branchesQuery->orderBy('name')->get(['id', 'name']);
        $allBranchIds = $branches->pluck('id')->toArray();

        // Query primary period data
        $primaryData = $this->queryTargetActualPeriod($primaryStart, $primaryEnd, $allBranchIds, $metric);

        // Query compare period data if requested
        $compareData = null;
        if ($compareStart && $compareEnd) {
            $compareData = $this->queryTargetActualPeriod($compareStart, $compareEnd, $allBranchIds, $metric);
        }

        $rows = [];
        $totalPrimaryTarget = 0.0;
        $totalPrimaryActual = 0.0;
        $totalCompareTarget = 0.0;
        $totalCompareActual = 0.0;

        foreach ($branches as $branch) {
            $bId = (int) $branch->id;
            $bName = ucwords(strtolower($branch->name));

            $pTarget = (float) ($primaryData['targets'][$bId] ?? 0);
            $pActual = (float) ($primaryData['actuals'][$bId] ?? 0);
            $pGap = $pActual - $pTarget;
            $pPercent = $pTarget > 0 ? round((($pActual - $pTarget) / $pTarget) * 100, 1) : null;

            $totalPrimaryTarget += $pTarget;
            $totalPrimaryActual += $pActual;

            $row = [
                'branch_id' => $bId,
                'branch_name' => $bName,
                'target' => $pTarget,
                'actual' => $pActual,
                'gap' => $pGap,
                'percent' => $pPercent,
            ];

            if ($compareData) {
                $cTarget = (float) ($compareData['targets'][$bId] ?? 0);
                $cActual = (float) ($compareData['actuals'][$bId] ?? 0);
                $cGap = $cActual - $cTarget;
                $cPercent = $cTarget > 0 ? round((($cActual - $cTarget) / $cTarget) * 100, 1) : null;

                $totalCompareTarget += $cTarget;
                $totalCompareActual += $cActual;

                // Compare variances between primary and comparison periods
                $targetChange = $cTarget > 0 ? round((($pTarget - $cTarget) / $cTarget) * 100, 1) : null;
                $actualChange = $cActual > 0 ? round((($pActual - $cActual) / $cActual) * 100, 1) : null;

                $row['compare'] = [
                    'target' => $cTarget,
                    'actual' => $cActual,
                    'gap' => $cGap,
                    'percent' => $cPercent,
                    'target_change_pct' => $targetChange,
                    'actual_change_pct' => $actualChange,
                ];
            }

            $rows[] = $row;
        }

        $totalPrimaryGap = $totalPrimaryActual - $totalPrimaryTarget;
        $totalPrimaryPercent = $totalPrimaryTarget > 0 ? round((($totalPrimaryActual - $totalPrimaryTarget) / $totalPrimaryTarget) * 100, 1) : null;

        $totals = [
            'target' => $totalPrimaryTarget,
            'actual' => $totalPrimaryActual,
            'gap' => $totalPrimaryGap,
            'percent' => $totalPrimaryPercent,
        ];

        if ($compareData) {
            $totalCompareGap = $totalCompareActual - $totalCompareTarget;
            $totalComparePercent = $totalCompareTarget > 0 ? round((($totalCompareActual - $totalCompareTarget) / $totalCompareTarget) * 100, 1) : null;

            $totals['compare'] = [
                'target' => $totalCompareTarget,
                'actual' => $totalCompareActual,
                'gap' => $totalCompareGap,
                'percent' => $totalComparePercent,
                'target_change_pct' => $totalCompareTarget > 0 ? round((($totalPrimaryTarget - $totalCompareTarget) / $totalCompareTarget) * 100, 1) : null,
                'actual_change_pct' => $totalCompareActual > 0 ? round((($totalPrimaryActual - $totalCompareActual) / $totalCompareActual) * 100, 1) : null,
            ];
        }

        return response()->json([
            'metric' => $metric,
            'has_comparison' => !empty($compareData),
            'primary_range' => [
                'from' => $primaryStart->format('Y-m-d'),
                'to' => $primaryEnd->format('Y-m-d'),
            ],
            'compare_range' => $compareData ? [
                'from' => $compareStart->format('Y-m-d'),
                'to' => $compareEnd->format('Y-m-d'),
            ] : null,
            'totals' => $totals,
            'rows' => $rows,
        ]);
    }

    private function queryTargetActualPeriod(Carbon $start, Carbon $end, array $branchIds, string $metric): array
    {
        $targetCol = $metric === 'pcs' ? 'target_pcs' : 'target_gram';
        $actualFlag = $metric === 'pcs' ? 'daily_reports.is_sale_quantity' : 'daily_reports.is_sale_gram';

        // Segments per year/month for targets
        $segments = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $year = (int) $cursor->year;
            $month = (int) $cursor->month;

            $startDay = ($year === (int) $start->year && $month === (int) $start->month) ? (int) $start->day : 1;
            $endDay = ($year === (int) $end->year && $month === (int) $end->month) ? (int) $end->day : (int) $cursor->daysInMonth;

            $segments[] = [
                'year' => $year,
                'month' => $month,
                'startDay' => $startDay,
                'endDay' => $endDay,
            ];

            $cursor->addMonth();
        }

        $targets = BranchTarget::query()
            ->select('branch_id', DB::raw("SUM({$targetCol}) as total"))
            ->when(!empty($branchIds), fn($q) => $q->whereIn('branch_id', $branchIds))
            ->where(function ($q) use ($segments) {
                foreach ($segments as $i => $seg) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->{$method}(function ($sq) use ($seg) {
                        $sq->where('year', $seg['year'])
                            ->where('month', $seg['month'])
                            ->whereBetween('day', [$seg['startDay'], $seg['endDay']]);
                    });
                }
            })
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id')
            ->toArray();

        $actuals = DailyReportRecord::query()
            ->select('daily_report_records.branch_id', DB::raw('SUM(daily_report_records.number) as total'))
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where($actualFlag, true)
            ->when(!empty($branchIds), fn($q) => $q->whereIn('daily_report_records.branch_id', $branchIds))
            ->whereBetween('daily_report_records.report_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->groupBy('daily_report_records.branch_id')
            ->pluck('total', 'daily_report_records.branch_id')
            ->toArray();

        return [
            'targets' => $targets,
            'actuals' => $actuals,
        ];
    }

    private function querySaleMetricMap(Carbon $fromDate, Carbon $toDate, array $branchIds = [], string $metric = 'gram')
    {
        $flag = $metric === 'pcs' ? 'daily_reports.is_sale_quantity' : 'daily_reports.is_sale_gram';

        return DailyReportRecord::select('daily_report_records.report_date', DB::raw('SUM(daily_report_records.number) as total'))
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where($flag, true)
            ->when(!empty($branchIds), fn($q) => $q->whereIn('daily_report_records.branch_id', $branchIds))
            ->whereBetween('daily_report_records.report_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
            ->groupBy('daily_report_records.report_date')
            ->pluck('total', 'daily_report_records.report_date')
            ->toArray();
    }
}

