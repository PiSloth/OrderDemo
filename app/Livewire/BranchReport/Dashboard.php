<?php

namespace App\Livewire\BranchReport;

use App\Livewire\Order\Psi\DailySale;
use App\Models\Branch;
use App\Models\DailyReportRecord;
use App\Models\PsiProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $duration_filter;
    public $self_date_filter;
    public $month_filter;
    public $year_filter;
    public $report_types_date_filter;
    public $ac = 'desc';
    public $limit = 5;

    public $branch_id;
    public $popular_date_filter;

    public $popular_month_filter;
    public $popular_year_filter;


    public function mount()
    {
        $this->month_filter = Carbon::now()->month;
        $this->year_filter = Carbon::now()->year;

        $this->popular_month_filter = Carbon::now()->month;
        $this->popular_year_filter =  Carbon::now()->year;


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

    public function render()
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

        $selfComparation = DailyReportRecord::select('branches.name AS branch', 'daily_reports.name AS type')
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->orderBy('daily_reports.id')
            ->get();

        // dd($monthlyAllReportTypes);



        //Branch index count Monthly

        //PSI Most popular sale
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

        // dd($most_popular);

        //Index by psi

        //Branch Entrace counts

        //All branch specific dayily

        //All branch monthly comparation

        //self branch cpomparation [monthly, daily]


        return view('livewire.branch-report.dashboard', [
            'monthlyAllReportTypes' => $monthlyAllReportTypes,
            'branches' => Branch::orderBy('name')->get(),
            'sales' => $most_popular,
        ]);
    }
}
