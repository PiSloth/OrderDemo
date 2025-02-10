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

    public $popular_month_filter;
    public $popular_year_filter;
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

        // dd($this->year_filter);
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

        //! monthly report of report types
        {
            $allBranchMonthlyData = DailyReportRecord::select('branches.name AS branch', 'daily_reports.name AS type', DB::raw('SUM(daily_report_records.number) AS result'))
                ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
                ->whereMonth('daily_report_records.report_date',  $this->month_filter) //
                ->whereYear('daily_report_records.report_date',  $this->year_filter) //
                ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
                ->groupBy('branches.name', 'daily_reports.name', 'daily_reports.id')
                ->orderBy('branches.name')
                ->orderBy('daily_reports.id')
                ->get();


            $monthlyAllReportTypes = [];

            foreach ($allBranchMonthlyData as $data) {
                $key = $data->type;
                $branch = ucfirst($data->branch);

                if (!isset($monthlyAllReportTypes[$key][$branch])) {
                    $monthlyAllReportTypes[$key][$branch] = [];
                }
                $monthlyAllReportTypes[$key][$branch] = [
                    $data->result,
                ];
            }
        }

        $selfComparation = DailyReportRecord::select('branches.name AS branch', 'daily_reports.name AS type')
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->orderBy('daily_reports.id')
            ->get();

        // dd($monthlyAllReportTypes);



        //Branch index count Monthly



        //! PSI Most popular sale
        $most_popular = DB::table('real_sales as rs')
            ->select('s.name as shape', 'p.length', 'p.weight', 'uoms.name as uom', DB::raw('SUM(rs.qty) AS sale'))
            ->leftJoin('branch_psi_products as bpsi', 'rs.branch_psi_product_id', 'bpsi.id')
            ->leftJoin('psi_products as p', 'p.id', 'bpsi.psi_product_id')
            ->leftJoin('shapes as shp', 'shp.id', 'p.shape_id')
            ->leftJoin('uoms', 'uoms.id', 'p.uom_id')
            ->leftJoin('shapes as s', 's.id', 'p.shape_id')
            ->leftJoin('branches as b', 'b.id', 'bpsi.branch_id')
            ->where(function ($query) {
                $query->whereMonth('rs.sale_date', $this->popular_month_filter)
                    ->whereYear('rs.sale_date', $this->popular_year_filter);
            })
            ->when($this->branch_id, function ($query) {
                return $query->where('b.id', $this->branch_id);
            })
            ->groupBy('s.name', 'p.length', 'uoms.name', 'p.weight')
            ->orderByRaw('SUM(rs.qty)' . $this->ac)
            ->limit($this->limit)
            ->get();


        //! index by daily records
        $totalIndexByMonth =  DailyReportRecord::select(
            'branches.name AS branch',
            DB::raw(
                'SUM(CASE WHEN daily_reports.is_sale_gram = true THEN daily_report_records.number  ELSE 0 END) AS total_gram,
                SUM(CASE WHEN daily_reports.is_sale_quantity = true THEN daily_report_records.number  ELSE 0 END) AS total_quantity'
            )
        )
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where(function ($query) {
                $query->whereMonth('daily_report_records.report_date', $this->index_month_filter)
                    ->whereYear('daily_report_records.report_date', $this->index_year_filter);
            })
            ->groupBy('branches.name')
            ->get();



        //Index by psi


        //Branch Entrace counts

        //All branch specific dayily

        //All branch monthly comparation

        //self branch cpomparation [monthly, daily]


        return view('livewire.branch-report.dashboard', [
            'monthlyAllReportTypes' => $monthlyAllReportTypes,
            'branches' => Branch::orderBy('name')->get(),
            'sales' => $most_popular,
            'indexs' => $totalIndexByMonth,
        ]);
    }
}
