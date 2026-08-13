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
}
