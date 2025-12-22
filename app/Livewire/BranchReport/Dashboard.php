<?php

namespace App\Livewire\BranchReport;

use App\Livewire\Order\Psi\DailySale;
use App\Models\Branch;
use App\Models\BranchTarget;
use App\Models\DailyReportRecord;
use App\Models\PsiProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use WireUi\Traits\Actions;

class Dashboard extends Component
{
    use Actions;
    public $duration_filter;
    public $self_date_filter;
    public $month_filter;
    public $year_filter;
    public $report_types_date_filter;
    public $ac = 'desc';
    public $limit = 5;

    public $branch_id;
    //top ten sale items
    public $popular_date_filter;

    //popular slae product filters
    public $popular_month_filter, $popular_year_filter;
    public $popular_start_date_filter, $popular_end_date_filter;
    //index of daily records
    public $index_date_filter, $index_month_filter, $index_year_filter;
    public $index_month_year_filter;

    //daily specific report type
    public $dailyAllReportTypes = [
        'ရွှေ (weight / g)' => []
    ];
    public $specific_date_filter;
    public $specific_branch_id;

    // monthly target is derived from branch_targets (daily totals)

    // Daily targets calendar
    public $calendar_month;
    public $calendar_year;
    public $selected_date;
    public $show_target_modal = false;
    public $daily_targets = [];

    // Sale compare (date range + preset compare)
    public $sale_compare_from;
    public $sale_compare_to;
    public $sale_compare_mode = 'prev_period'; // prev_period | yoy | none
    public $sale_compare_branch_ids = [];

    // Target vs Actual summary table (date range)
    public $target_actual_from;
    public $target_actual_to;


    public function mount()
    {
        // dd($this->target['branch 1']);

        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

        $this->month_filter = $this->popular_month_filter = $this->index_month_filter = $month;
        $this->year_filter = $this->popular_year_filter = $this->index_year_filter = $year;

        $this->index_month_year_filter = Carbon::create($year, $month, 1)->format('Y-m');

        $this->specific_date_filter = now();

        $this->specific_branch_id = auth()->user()->branch_id;

        $this->popular_start_date_filter = Carbon::now()->subMonth(5)->startOfMonth();
        $this->popular_end_date_filter = Carbon::now();

        $this->calendar_month = Carbon::now()->month;
        $this->calendar_year = Carbon::now()->year;

        $this->target_actual_from = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $this->target_actual_to = Carbon::now()->format('Y-m-d');

        // Default: this month-to-date vs previous period
        $this->sale_compare_from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->sale_compare_to = Carbon::now()->format('Y-m-d');
        $this->sale_compare_mode = 'prev_period';

        // dd($this->popular_start_date_filter);
    }

    public function updatedSaleCompareFrom()
    {
        $this->dispatch('sale-compare-chart-updated', chart: $this->getSaleCompareChartData());
    }

    public function updatedSaleCompareTo()
    {
        $this->dispatch('sale-compare-chart-updated', chart: $this->getSaleCompareChartData());
    }

    public function updatedSaleCompareBranchIds()
    {
        $this->dispatch('sale-compare-chart-updated', chart: $this->getSaleCompareChartData());
    }

    public function setSaleCompareMode($mode)
    {
        $allowed = ['prev_period', 'yoy', 'none'];
        $this->sale_compare_mode = in_array($mode, $allowed, true) ? $mode : 'prev_period';
        $this->dispatch('sale-compare-chart-updated', chart: $this->getSaleCompareChartData());
    }

    public function presetQuarterlyCompare()
    {
        $start = Carbon::now()->startOfQuarter();
        $end = Carbon::now();
        $this->sale_compare_from = $start->format('Y-m-d');
        $this->sale_compare_to = $end->format('Y-m-d');
        $this->sale_compare_mode = 'prev_period';
        $this->dispatch('sale-compare-chart-updated', chart: $this->getSaleCompareChartData());
    }

    public function presetHalfYearCompare()
    {
        $now = Carbon::now();
        $start = ($now->month <= 6)
            ? Carbon::create($now->year, 1, 1)
            : Carbon::create($now->year, 7, 1);
        $end = $now;
        $this->sale_compare_from = $start->format('Y-m-d');
        $this->sale_compare_to = $end->format('Y-m-d');
        $this->sale_compare_mode = 'prev_period';
        $this->dispatch('sale-compare-chart-updated', chart: $this->getSaleCompareChartData());
    }

    public function presetMonthYoYCompare()
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now();
        $this->sale_compare_from = $start->format('Y-m-d');
        $this->sale_compare_to = $end->format('Y-m-d');
        $this->sale_compare_mode = 'yoy';
        $this->dispatch('sale-compare-chart-updated', chart: $this->getSaleCompareChartData());
    }

    public function updatedReportTypesDateFilter($value)
    {
        $this->month_filter = Carbon::parse($value)->month;
        $this->year_filter = Carbon::parse($value)->year;
    }

    //sale popular data
    public function updatedPopularDateFilter($value)
    {
        $this->popular_month_filter = Carbon::parse($value)->month;
        $this->popular_year_filter = Carbon::parse($value)->year;
    }
    //sale popular data
    public function updatedPopularStartDateFilter($value)
    {
        $this->popular_start_date_filter = $value;
    }

    //sale popular data
    public function updatedPopularEndDateFilter($value)
    {
        $this->popular_end_date_filter = $value;
    }

    public function updatedIndexDateFilter($value)
    {
        $this->index_month_filter = Carbon::parse($value)->month;
        $this->index_year_filter = Carbon::parse($value)->year;

        $this->dispatch('index-chart-updated', chart: $this->getIndexChartData());
    }

    public function updatedIndexMonthYearFilter($value)
    {
        if (!$value) {
            return;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m', $value);
        } catch (\Throwable $e) {
            return;
        }

        $this->index_month_filter = $parsed->month;
        $this->index_year_filter = $parsed->year;

        $this->dispatch('index-chart-updated', chart: $this->getIndexChartData());
    }

    //specific date filter of reports type
    public function specificDateFilterOfReportType()
    {
        $this->validate([
            'specific_date_filter' => 'required',
            // 'specific_branch_id' => 'required'
        ]);

        $branchDailyData = DailyReportRecord::select('branches.name AS branch', 'daily_reports.name AS type', DB::raw('SUM(daily_report_records.number) AS result'))
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->whereDate('daily_report_records.report_date',  $this->specific_date_filter) //
            ->where('branches.id', $this->specific_branch_id ?? auth()->user()->branch_id)
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->groupBy('branches.name', 'daily_reports.name', 'daily_reports.id')
            ->orderBy('branches.name')
            ->orderBy('daily_reports.id')
            ->get();


        if ($branchDailyData->count() == 0) {
            $this->notification([
                'icon' => 'info',
                'title' => 'No data found',
                'description' => 'No data found to show'
            ]);

            return;
        }

        foreach ($branchDailyData as $data) {
            $key = $data->type;
            $branch = ucfirst($data->branch) .  Carbon::parse($this->specific_date_filter)->format(' (j M y)');

            if (!isset($this->dailyAllReportTypes[$key][$branch])) {
                $this->dailyAllReportTypes[$key][$branch] = [];
            }
            $this->dailyAllReportTypes[$key][$branch] = [
                $data->result,
            ];
        }

        // dd($this->dailyAllReportTypes);
    }

    public function removeKeyFromSelectedArray($keyItem)
    {
        $result = array_map(fn($item) => array_filter($item, fn($key) => $key !== $keyItem, ARRAY_FILTER_USE_KEY), $this->dailyAllReportTypes);
        $this->dailyAllReportTypes = $result;
    }

    public function openTargetModal($date)
    {
        $this->selected_date = $date;
        $this->show_target_modal = true;

        // Load existing targets
        $carbonDate = Carbon::parse($date);
        $targets = BranchTarget::where('year', $carbonDate->year)
            ->where('month', $carbonDate->month)
            ->where('day', $carbonDate->day)
            ->get();

        $this->daily_targets = [];
        foreach ($targets as $target) {
            $this->daily_targets[$target->branch_id] = $target->target_gram;
        }
    }

    public function closeTargetModal()
    {
        $this->show_target_modal = false;
        $this->selected_date = null;
        $this->daily_targets = [];
    }

    public function saveTargets()
    {
        $date = Carbon::parse($this->selected_date);
        $jewelryBranches = Branch::where('is_jewelry_shop', true)->get();

        foreach ($jewelryBranches as $branch) {
            $targetGram = $this->daily_targets[$branch->id] ?? 0;

            BranchTarget::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'year' => $date->year,
                    'month' => $date->month,
                    'day' => $date->day,
                ],
                [
                    'target_gram' => $targetGram,
                    'target_pcs' => 0,
                ]
            );
        }

        $this->closeTargetModal();
        session()->flash('message', 'Daily targets saved successfully!');

        $this->dispatch('target-vs-actual-chart-updated', chart: $this->getTargetVsActualData());
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->calendar_year, $this->calendar_month, 1)->subMonth();
        $this->calendar_month = $date->month;
        $this->calendar_year = $date->year;

        $this->dispatch('target-vs-actual-chart-updated', chart: $this->getTargetVsActualData());
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->calendar_year, $this->calendar_month, 1)->addMonth();
        $this->calendar_month = $date->month;
        $this->calendar_year = $date->year;

        $this->dispatch('target-vs-actual-chart-updated', chart: $this->getTargetVsActualData());
    }

    public function render()
    {
        $monthlyAllReportTypes = $this->getMonthlyAllReportTypes();
        $totalIndexByMonth = $this->getTotalIndexByMonth();
        $indexChartData = $this->getIndexChartData();
        $most_popular_summary = $this->getMostPopularSummary();
        $most_popular_details = $this->getMostPopularDetails();

        // Sale gram data for last 1 month
        $startDate = Carbon::now()->subMonth()->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $records = DailyReportRecord::select('daily_report_records.report_date', DB::raw('SUM(daily_report_records.number) as sale_gram'))
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where('daily_reports.is_sale_gram', true)
            // ->whereBetween('daily_report_records.report_date', [$startDate, $endDate])
            ->groupBy('daily_report_records.report_date')
            ->orderBy('daily_report_records.report_date')
            ->get();

        $dates = $records->pluck('report_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();
        $saleGramData = $records->pluck('sale_gram')->toArray();

        $calendarData = $this->getCalendarData();
        $targetVsActualData = $this->getTargetVsActualData();
        $targetVsActualTable = $this->getTargetVsActualTableData();
        $saleCompareChart = $this->getSaleCompareChartData();

        return view('livewire.branch-report.dashboard', [
            'monthlyAllReportTypes' => $monthlyAllReportTypes,
            'branches' => Branch::orderBy('name')->get(),
            'jewelryBranches' => Branch::where('is_jewelry_shop', true)->orderBy('name')->get(),
            'indexs' => $totalIndexByMonth,
            'indexChartData' => $indexChartData,
            'most_popular_details' => $most_popular_details,
            'most_popular_summary' => $most_popular_summary,
            'dates' => $dates,
            'saleGramData' => $saleGramData,
            'calendarData' => $calendarData,
            'targetVsActualData' => $targetVsActualData,
            'targetVsActualTable' => $targetVsActualTable,
            'saleCompareChart' => $saleCompareChart,
        ]);
        dd($saleGramData);
    }

    private function getTargetVsActualTableData()
    {
        $start = $this->target_actual_from ? Carbon::parse($this->target_actual_from)->startOfDay() : Carbon::create((int) $this->calendar_year, (int) $this->calendar_month, 1)->startOfMonth();
        $end = $this->target_actual_to ? Carbon::parse($this->target_actual_to)->endOfDay() : Carbon::create((int) $this->calendar_year, (int) $this->calendar_month, 1)->endOfMonth();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $segments = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $year = (int) $cursor->year;
            $month = (int) $cursor->month;

            $segmentStartDay = ($year === (int) $start->year && $month === (int) $start->month) ? (int) $start->day : 1;
            $segmentEndDay = ($year === (int) $end->year && $month === (int) $end->month) ? (int) $end->day : (int) $cursor->daysInMonth;

            $segments[] = [
                'year' => $year,
                'month' => $month,
                'startDay' => $segmentStartDay,
                'endDay' => $segmentEndDay,
            ];

            $cursor->addMonth();
        }

        $targetRows = BranchTarget::select(
            'branches.id AS branch_id',
            'branches.name AS branch_name',
            DB::raw('SUM(branch_targets.target_gram) AS target_gram')
        )
            ->leftJoin('branches', 'branches.id', 'branch_targets.branch_id')
            ->where(function ($q) use ($segments) {
                foreach ($segments as $i => $seg) {
                    $qMethod = $i === 0 ? 'where' : 'orWhere';
                    $q->{$qMethod}(function ($sq) use ($seg) {
                        $sq->where('branch_targets.year', $seg['year'])
                            ->where('branch_targets.month', $seg['month'])
                            ->whereBetween('branch_targets.day', [$seg['startDay'], $seg['endDay']]);
                    });
                }
            })
            ->groupBy('branches.id', 'branches.name')
            ->get();

        $actualRows = DailyReportRecord::select(
            'branches.id AS branch_id',
            'branches.name AS branch_name',
            DB::raw('SUM(daily_report_records.number) AS actual_gram')
        )
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->where('daily_reports.is_sale_gram', true)
            ->whereBetween('daily_report_records.report_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->groupBy('branches.id', 'branches.name')
            ->get();

        $targetsById = [];
        $namesById = [];
        foreach ($targetRows as $row) {
            if (!$row->branch_id) {
                continue;
            }
            $targetsById[(int) $row->branch_id] = (float) ($row->target_gram ?? 0);
            $namesById[(int) $row->branch_id] = (string) ($row->branch_name ?? '');
        }

        $actualById = [];
        foreach ($actualRows as $row) {
            if (!$row->branch_id) {
                continue;
            }
            $actualById[(int) $row->branch_id] = (float) ($row->actual_gram ?? 0);
            $namesById[(int) $row->branch_id] = (string) ($row->branch_name ?? '');
        }

        $branchIds = array_values(array_unique(array_merge(array_keys($targetsById), array_keys($actualById))));

        // Ensure consistent ordering by branch name
        usort($branchIds, function ($a, $b) use ($namesById) {
            return strnatcasecmp($namesById[$a] ?? '', $namesById[$b] ?? '');
        });

        $rows = [];
        $totalTarget = 0.0;
        $totalActual = 0.0;

        foreach ($branchIds as $branchId) {
            $target = (float) ($targetsById[$branchId] ?? 0);
            $actual = (float) ($actualById[$branchId] ?? 0);
            $gap = $actual - $target;

            $percent = null;
            if ($target > 0) {
                $percent = (($actual - $target) / $target) * 100;
            }

            $rows[] = [
                'branch_name' => ucfirst($namesById[$branchId] ?? ('Branch #' . $branchId)),
                'target_gram' => $target,
                'actual_gram' => $actual,
                'gap_gram' => $gap,
                'percent' => $percent,
            ];

            $totalTarget += $target;
            $totalActual += $actual;
        }

        $totalGap = $totalActual - $totalTarget;
        $totalPercent = null;
        if ($totalTarget > 0) {
            $totalPercent = (($totalActual - $totalTarget) / $totalTarget) * 100;
        }

        return [
            'totals' => [
                'target_gram' => $totalTarget,
                'actual_gram' => $totalActual,
                'gap_gram' => $totalGap,
                'percent' => $totalPercent,
            ],
            'rows' => $rows,
        ];
    }

    private function getIndexChartData()
    {
        $branchesWithActual = $this->getTotalIndexByMonth();

        $targetRows = BranchTarget::select(
            'branches.name AS branch',
            DB::raw('SUM(branch_targets.target_gram) AS target_gram'),
            DB::raw('SUM(branch_targets.target_pcs) AS target_pcs')
        )
            ->leftJoin('branches', 'branches.id', 'branch_targets.branch_id')
            ->where('branch_targets.year', $this->index_year_filter)
            ->where('branch_targets.month', $this->index_month_filter)
            ->groupBy('branches.name')
            ->orderBy('branches.name')
            ->get();

        $achievedByBranch = [];
        foreach ($branchesWithActual as $row) {
            $key = strtolower((string) $row->branch);
            $achievedByBranch[$key] = ((float) $row->total_gram * 0.6) + ((float) $row->total_quantity * 0.4);
        }

        $targetByBranch = [];
        foreach ($targetRows as $row) {
            $key = strtolower((string) $row->branch);
            $targetByBranch[$key] = ((float) $row->target_gram * 0.6) + ((float) $row->target_pcs * 0.4);
        }

        $allBranchKeys = array_unique(array_merge(array_keys($achievedByBranch), array_keys($targetByBranch)));
        sort($allBranchKeys, SORT_NATURAL | SORT_FLAG_CASE);

        $categories = ['All Branches'];
        $targetSeries = [array_sum($targetByBranch)];
        $achievedSeries = [array_sum($achievedByBranch)];

        foreach ($allBranchKeys as $key) {
            $categories[] = ucwords($key);
            $targetSeries[] = (float) ($targetByBranch[$key] ?? 0);
            $achievedSeries[] = (float) ($achievedByBranch[$key] ?? 0);
        }

        return [
            'categories' => $categories,
            'series' => [
                ['name' => 'Target Index', 'data' => $targetSeries],
                ['name' => 'Achieved Index', 'data' => $achievedSeries],
            ],
        ];
    }


    private function getMonthlyAllReportTypes()
    {
        $records = DailyReportRecord::select(
            'branches.name AS branch',
            'daily_reports.name AS type',
            DB::raw('SUM(daily_report_records.number) AS result')
        )
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->whereMonth('daily_report_records.report_date', $this->month_filter)
            ->whereYear('daily_report_records.report_date', $this->year_filter)
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->groupBy('branches.name', 'daily_reports.name', 'daily_reports.id')
            ->orderBy('branches.name')
            ->orderBy('daily_reports.id')
            ->get();

        $result = [];
        foreach ($records as $data) {
            $result[$data->type][ucfirst($data->branch)] = [$data->result];
        }
        return $result;
    }

    private function getTotalIndexByMonth()
    {
        return DailyReportRecord::select(
            'branches.name AS branch',
            DB::raw(
                'SUM(CASE WHEN daily_reports.is_sale_gram = true THEN daily_report_records.number ELSE 0 END) AS total_gram,
                     SUM(CASE WHEN daily_reports.is_sale_quantity = true THEN daily_report_records.number ELSE 0 END) AS total_quantity'
            )
        )
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->whereMonth('daily_report_records.report_date', $this->index_month_filter)
            ->whereYear('daily_report_records.report_date', $this->index_year_filter)
            ->groupBy('branches.name')
            ->get();
    }

    private function getMostPopularSummary()
    {
        return DB::table('real_sales as rs')
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
            ->whereBetween('rs.sale_date', [$this->popular_start_date_filter, $this->popular_end_date_filter])
            ->when($this->branch_id, function ($query) {
                return $query->where('bpsi.branch_id', $this->branch_id);
            })
            ->groupBy('s.name', 'p.length', 'uoms.name', 'p.weight')
            ->orderByDesc('total_sale')
            ->limit($this->limit)
            ->get();
    }

    private function getMostPopularDetails()
    {
        return DB::table('real_sales as rs')
            ->select(
                's.name as shape',
                'p.length',
                'p.weight',
                'uoms.name as uom',
                'b.name as branch',
                DB::raw('SUM(rs.qty) AS branch_sale')
            )
            ->leftJoin('branch_psi_products as bpsi', 'rs.branch_psi_product_id', 'bpsi.id')
            ->leftJoin('psi_products as p', 'p.id', 'bpsi.psi_product_id')
            ->leftJoin('uoms', 'uoms.id', 'p.uom_id')
            ->leftJoin('shapes as s', 's.id', 'p.shape_id')
            ->leftJoin('branches as b', 'b.id', 'bpsi.branch_id')
            ->whereBetween('rs.sale_date', [$this->popular_start_date_filter, $this->popular_end_date_filter])
            ->when($this->branch_id, function ($query) {
                return $query->where('bpsi.branch_id', $this->branch_id);
            })
            ->groupBy('s.name', 'p.length', 'uoms.name', 'p.weight', 'b.name')
            ->orderByDesc('branch_sale')
            ->get();
    }

    private function getCalendarData()
    {
        $start = Carbon::create($this->calendar_year, $this->calendar_month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $days = [];
        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            $targetGram = BranchTarget::where('year', $date->year)
                ->where('month', $date->month)
                ->where('day', $date->day)
                ->sum('target_gram');

            $actualGram = DailyReportRecord::where('report_date', $date->format('Y-m-d'))
                ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
                ->where('daily_reports.is_sale_gram', true)
                ->sum('daily_report_records.number');

            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->day,
                'target_gram' => $targetGram,
                'actual_gram' => $actualGram,
            ];
        }

        return $days;
    }

    private function getTargetVsActualData()
    {
        $start = Carbon::create($this->calendar_year, $this->calendar_month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $categories = [];
        $targets = [];
        $actuals = [];

        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            $target = BranchTarget::where('year', $date->year)
                ->where('month', $date->month)
                ->where('day', $date->day)
                ->sum('target_gram');

            $actual = DailyReportRecord::where('report_date', $date->format('Y-m-d'))
                ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
                ->where('daily_reports.is_sale_gram', true)
                ->sum('daily_report_records.number');

            $categories[] = $date->format('M j');
            $targets[] = $target;
            $actuals[] = $actual;
        }

        return [
            'categories' => $categories,
            'series' => [
                [
                    'name' => 'Target (g)',
                    'data' => $targets,
                ],
                [
                    'name' => 'Actual (g)',
                    'data' => $actuals,
                ],
            ],
        ];
    }

    private function getSaleCompareChartData()
    {
        $from = $this->sale_compare_from ? Carbon::parse($this->sale_compare_from)->startOfDay() : Carbon::now()->startOfMonth();
        $to = $this->sale_compare_to ? Carbon::parse($this->sale_compare_to)->endOfDay() : Carbon::now()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $daysCount = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        // Base series (actual sale grams)
        $baseMap = $this->getSaleGramMapByDate($from->copy()->startOfDay(), $to->copy()->startOfDay(), $this->sale_compare_branch_ids);
        $categories = [];
        $baseData = [];
        $cursor = $from->copy()->startOfDay();
        for ($i = 0; $i < $daysCount; $i++) {
            $key = $cursor->format('Y-m-d');
            $categories[] = $cursor->format('M j, Y');
            $baseData[] = (float) ($baseMap[$key] ?? 0);
            $cursor->addDay();
        }

        $series = [
            [
                'name' => 'Actual (' . $from->format('M j') . ' - ' . $to->format('M j') . ')',
                'data' => $baseData,
            ],
        ];

        if ($this->sale_compare_mode !== 'none') {
            if ($this->sale_compare_mode === 'yoy') {
                $compareFrom = $from->copy()->subYear();
                $compareTo = $to->copy()->subYear();
            } else {
                // prev_period
                $compareTo = $from->copy()->subDay();
                $compareFrom = $compareTo->copy()->subDays($daysCount - 1);
            }

            $compareMap = $this->getSaleGramMapByDate($compareFrom->copy()->startOfDay(), $compareTo->copy()->startOfDay(), $this->sale_compare_branch_ids);
            $compareData = [];
            $cursor = $compareFrom->copy()->startOfDay();
            for ($i = 0; $i < $daysCount; $i++) {
                $key = $cursor->format('Y-m-d');
                $compareData[] = (float) ($compareMap[$key] ?? 0);
                $cursor->addDay();
            }

            $label = ($this->sale_compare_mode === 'yoy')
                ? 'Compare YoY (' . $compareFrom->format('M j, Y') . ' - ' . $compareTo->format('M j, Y') . ')'
                : 'Compare Prev (' . $compareFrom->format('M j') . ' - ' . $compareTo->format('M j') . ')';

            $series[] = [
                'name' => $label,
                'data' => $compareData,
            ];
        }

        return [
            'categories' => $categories,
            'series' => $series,
        ];
    }

    private function getSaleGramMapByDate(Carbon $fromDate, Carbon $toDate, $branchIds = [])
    {
        $branchIds = is_array($branchIds) ? array_values(array_filter($branchIds)) : [];

        return DailyReportRecord::select('daily_report_records.report_date', DB::raw('SUM(daily_report_records.number) as total'))
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where('daily_reports.is_sale_gram', true)
            ->when(!empty($branchIds), function ($query) use ($branchIds) {
                return $query->whereIn('daily_report_records.branch_id', $branchIds);
            })
            ->whereBetween('daily_report_records.report_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
            ->groupBy('daily_report_records.report_date')
            ->pluck('total', 'daily_report_records.report_date')
            ->toArray();
    }
}
