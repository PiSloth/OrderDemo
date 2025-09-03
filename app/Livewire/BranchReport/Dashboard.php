<?php

namespace App\Livewire\BranchReport;

use App\Livewire\Order\Psi\DailySale;
use App\Models\Branch;
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

    //daily specific report type
    public $dailyAllReportTypes = [
        'ရွှေ (weight / g)' => []
    ];
    public $specific_date_filter;
    public $specific_branch_id;

    //monthly target
    public $monthly_target = [
        'branch 1' => 3848,
        'branch 2' => 1780,
        'branch 3' => 1506,
        'branch 4' => 1589,
        'branch 5' => 3011,
        'branch 6' => 800,
        'online sale' => 2258,
        'ho' => 0,
    ];


    public function mount()
    {
        // dd($this->target['branch 1']);

        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

        $this->month_filter = $this->popular_month_filter = $this->index_month_filter = $month;
        $this->year_filter = $this->popular_year_filter = $this->index_year_filter = $year;

        $this->specific_date_filter = now();

        $this->specific_branch_id = auth()->user()->branch_id;

        $this->popular_start_date_filter = Carbon::now()->subMonth(5)->startOfMonth();
        $this->popular_end_date_filter = Carbon::now();

        // dd($this->popular_start_date_filter);
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

    public function render()
    {
        $monthlyAllReportTypes = $this->getMonthlyAllReportTypes();
        $totalIndexByMonth = $this->getTotalIndexByMonth();
        $most_popular_summary = $this->getMostPopularSummary();
        $most_popular_details = $this->getMostPopularDetails();

        // Sale gram data for last 1 month
        $startDate = Carbon::now()->subMonth()->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $records = DailyReportRecord::select('daily_report_records.report_date', DB::raw('SUM(daily_report_records.number) as sale_gram'))
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where('daily_reports.is_sale_gram', true)
            ->whereBetween('daily_report_records.report_date', [$startDate, $endDate])
            ->groupBy('daily_report_records.report_date')
            ->orderBy('daily_report_records.report_date')
            ->get();

        $dates = $records->pluck('report_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();
        $saleGramData = $records->pluck('sale_gram')->toArray();

        return view('livewire.branch-report.dashboard', [
            'monthlyAllReportTypes' => $monthlyAllReportTypes,
            'branches' => Branch::orderBy('name')->get(),
            'indexs' => $totalIndexByMonth,
            'most_popular_details' => $most_popular_details,
            'most_popular_summary' => $most_popular_summary,
            'dates' => $dates,
            'saleGramData' => $saleGramData,
        ]);
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
}
