<?php

namespace App\Livewire\BranchReport;

use App\Models\Branch;
use App\Models\DailyReport;
use App\Models\DailyReportRecord;
use App\Models\RealSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use SebastianBergmann\CodeCoverage\Report\Xml\Totals;
use WireUi\Traits\Actions;

use function PHPSTORM_META\map;

class SaleAndRepurchase extends Component
{
    use Actions;
    public $name;
    public $description;
    public $report_date;
    public $entry_modal;
    public $update_number;
    public $edit_id;
    public $branch_id = '';
    private $duration_filter = 30;
    public $branchOverIndex = 0;
    public $index_score;

    public function mount()
    {
        $this->branch_id = auth()->user()->branch_id;

        $this->report_date = Carbon::now()->format('Y-m-d');

        $daily_entries =  DailyReportRecord::select('daily_report_records.*')
            ->where('report_date', '=', $this->report_date)
            ->where('branch_id', '=', $this->branch_id)
            ->exists();

        // dd($daily_entries);
        if ($daily_entries) {
            $this->entry_modal = true;
        } else {
            $this->reset('entry_modal');
        }
    }

    // public function durationFilter($time)
    // {
    //     $this->duration_filter = Carbon::now()->subDay($time)->format('Y-m-d');

    //     // dd($this->duration_filter);
    // }

    public function edit($id)
    {
        $this->edit_id = $id;
    }

    public function updatedReportDate()
    {
        $daily_entries =  DailyReportRecord::select('daily_report_records.*')
            ->where('report_date', '=', $this->report_date)
            ->where('branch_id', '=', $this->branch_id)
            ->exists();

        // dd($daily_entries);
        if ($daily_entries) {
            $this->entry_modal = true;
        } else {
            $this->entry_modal = null;
        }
    }

    public function updatedBranchId()
    {
        $daily_entries =  DailyReportRecord::select('daily_report_records.*')
            ->where('report_date', '=', $this->report_date)
            ->where('branch_id', '=', $this->branch_id)
            ->exists();

        // dd($daily_entries);
        if ($daily_entries) {
            $this->entry_modal = true;
        } else {
            $this->entry_modal = null;
        }
    }

    public function createReportType()
    {

        $validated =  $this->validate([
            'name' => 'required',
            'description' => 'required'
        ]);

        DailyReport::create($validated);

        $this->dispatch('closeModal', 'addReportTypeModal');
        $this->reset('name', 'description');

        $this->notification([
            'icon' => 'success',
            'title' => 'Successed',
            'description' => 'New report type added successfully.'
        ]);
    }

    public function crateNewRecord()
    {
        $report_types = DailyReport::all();
        if ($report_types->count() < 1) {
            $this->dispatch('openModal', 'addReportTypeModal');

            $this->notification([
                'icon' => 'error',
                'title' => 'Failed',
                'description' => 'First new report type add before you create a report.'
            ]);
            return;
        }
        DB::transaction(function () use ($report_types) {
            foreach ($report_types as $type) {
                DailyReportRecord::create([
                    'daily_report_id' => $type->id,
                    'user_id' => auth()->user()->id,
                    'branch_id' => $this->branch_id,
                    'number' => 0,
                    'report_date' => $this->report_date,
                ]);
            }
        });
        $this->notification([
            'icon' => 'success',
            'title' => 'Successed',
            'description' => 'New report generated successfully.'
        ]);

        $this->entry_modal = true;
    }

    public function update($id)
    {
        $this->validate([
            'update_number' => 'required',
        ]);
        DailyReportRecord::findOrFail($id)->update([
            'number' => $this->update_number,
        ]);

        $this->reset('update_number', 'edit_id');
    }

    public function render()
    {
        if ($this->entry_modal) {
            $daily_entries =  DailyReportRecord::select('daily_report_records.*')
                ->where('report_date', '=', $this->report_date)
                ->where('branch_id', '=', $this->branch_id)
                ->get();

            $this->dispatch('open-modal', 'dataEntryModal');
        } else {
            $daily_entries = [];
        }

        $reportTypeSummary = DailyReportRecord::select('branches.name AS branch', 'daily_reports.name AS type', DB::raw('SUM(daily_report_records.number) AS total'))
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->when($this->duration_filter, function ($query) {
                return $query->where('daily_report_records.report_date', '>=', Carbon::now()->subDay($this->duration_filter)->format('Y-m-d'));
            })
            ->when(! $this->duration_filter, function ($query) {
                return $query->where('daily_report_records.report_date', '=', Carbon::now()->format('Y-m-d'));
            })
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->groupBy('branches.name', 'daily_reports.name')
            ->get();

        $formattedReports = [];

        foreach ($reportTypeSummary as $item) {
            $key = $item->branch;
            $type = $item->type;

            if (!isset($formattedReports[$key])) {
                $formattedReports[$key] = [];
            }

            $formattedReports[$key][] = [
                'x' => $type,
                'y' => round($item->total, 2),
            ];
        }
        $daily_reports = json_encode($formattedReports); // report type summary

        $all_data =  DailyReportRecord::select('daily_reports.name AS type', 'daily_report_records.report_date', DB::raw('SUM(daily_report_records.number) AS total'))
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->groupBy('daily_reports.name', 'daily_report_records.report_date')
            ->get();

        $categories = $all_data->pluck('report_date')->toArray();
        $mergeCategory = [];

        foreach ($categories as $item) {
            $key = Carbon::parse($item)->format('M j, Y');


            if (!in_array($item, $mergeCategory)) {
                $mergeCategory[] = $key;
            }
        }
        // dd($mergeCategory);


        // Restructure the data
        $chartData = [];
        $colors = [
            "#ba891e", //g pcs
            "#d8f211", //gold weight - yellow
            "#1eba7c", //p pcs
            "#46e810", //pandora weight - green
            "#ba1eb2", //k pcs
            "#8e10e8", // 18 k weight - brown
            "#4789bf",
            "#0c33cc",
            "#cf1717", //repurchase weight - red
            "#00000A",
            // Add more types and their colors if needed
        ];

        $index = 0;
        foreach ($all_data as $record) {
            $type = $record->type;
            $total = $record->total;

            if (!isset($chartData[$type])) {
                $chartData[$type] = [
                    "name" => $type,
                    "data" => [],
                    "color" => $colors[$index], // Default color if not in $colors
                ];
                $index++;
            }

            $chartData[$type]["data"][] = round($total, 2); // Ensure numeric values
        }

        $indexYesterday =  DailyReportRecord::select(
            'branches.name AS branch',
            DB::raw(
                'SUM(CASE WHEN daily_reports.is_sale_gram = true THEN daily_report_records.number  ELSE 0 END) AS total_gram,
                SUM(CASE WHEN daily_reports.is_sale_quantity = true THEN daily_report_records.number  ELSE 0 END) AS total_quantity'
            )
        )
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where('daily_report_records.report_date', '=', Carbon::now()->subDay(1)->format('Y-m-d'))
            ->groupBy('branches.name', 'daily_report_records.report_date')
            ->get();

        $indexToday =  DailyReportRecord::select(
            'branches.name AS branch',
            DB::raw(
                'SUM(CASE WHEN daily_reports.is_sale_gram = true THEN daily_report_records.number  ELSE 0 END) AS total_gram,
                    SUM(CASE WHEN daily_reports.is_sale_quantity = true THEN daily_report_records.number  ELSE 0 END) AS total_quantity'
            )
        )
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->where('daily_report_records.report_date', '=', Carbon::now()->format('Y-m-d'))
            ->groupBy('branches.name', 'daily_report_records.report_date')
            ->get();

        $todayIndex = 0;
        $yseterdayIndex = 0;

        foreach ($indexToday as $index) {
            $totalToday = round(($index->total_gram * 0.6) + ($index->total_quantity * 0.4), 2);
            $todayIndex += $totalToday;
        }

        foreach ($indexYesterday as $index) {
            $totalYesterday = round(($index->total_gram * 0.6) + ($index->total_quantity * 0.4), 2);
            $yseterdayIndex += $totalYesterday;
        }

        if ($todayIndex == 0) {
            $this->index_score = 0;
        } else {
            $this->index_score = $todayIndex - $yseterdayIndex;
        }
        // dump($todayIndex);
        // dump($yseterdayIndex);


        //index data to chart series line
        $indexQuery =  DailyReportRecord::select(
            'branches.name AS branch',
            'daily_report_records.report_date',
            DB::raw(
                'SUM(CASE WHEN daily_reports.is_sale_gram = true THEN daily_report_records.number  ELSE 0 END) AS total_gram,
                SUM(CASE WHEN daily_reports.is_sale_quantity = true THEN daily_report_records.number  ELSE 0 END) AS total_quantity'
            )
        )
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->groupBy('branches.name', 'daily_report_records.report_date')
            ->get();

        $indexReformed = [];

        $indexCount = 0;

        foreach ($indexQuery as $index) {
            $key = $index->branch;
            $toIndex = ($index->total_gram * 0.6) + ($index->total_quantity * 0.4);

            if (!isset($indexReformed[$key])) {
                $indexReformed[$key] = [];
                if (!isset($indexReformed[$key][$colors[$indexCount]])) {
                    $indexReformed[$key]['name'] = $key;
                    $indexReformed[$key]['color'] = $colors[$indexCount];
                    $indexReformed[$key]['data'] = [];
                    $indexCount++;
                }
                // $indexReformed[$key] = $colors[$indexCount];
            }
            $indexReformed[$key]['data'][] = round($toIndex, 2);
            $this->branchOverIndex += round($toIndex, 2);
        }

        $indexDate = $indexQuery->pluck('report_date')->toArray();

        // dd($indexDate);

        $all_reports = json_encode($chartData);
        $mergeCategory = json_encode($mergeCategory);
        $indexData = json_encode($indexReformed);
        $indexDate = json_encode($indexDate);

        return view('livewire.branch-report.sale-and-repurchase', [
            'daily_entries' => $daily_entries,
            'daily_reports' => $daily_reports,
            'all_reports' => $all_reports,
            'branches' => Branch::all(),
            'types' => DailyReport::all(),
            'categories' => $mergeCategory,
            'index_data' => $indexData,
            'index_date' => $indexDate,
        ]);
    }
}
