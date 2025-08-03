<?php

namespace App\Livewire\BranchReport;

use App\Models\Branch;
use App\Models\DailyReport;
use App\Models\DailyReportRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Spatie\SimpleExcel\SimpleExcelWriter;
use WireUi\Traits\Actions;

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

    public $export_branch_id;

    public $duration_filter = 30;

    public $branchOverIndex = 0;

    public $index_score;

    public $start_date;

    public $end_date;

    public $start_date_summary;

    public $end_date_summary;

    public $scope = 'S';

    public function mount()
    {
        $this->branch_id = auth()->user()->branch_id;
        $this->export_branch_id = auth()->user()->branch_id;

        $this->report_date = Carbon::now()->format('Y-m-d');

        $daily_entries = DailyReportRecord::select('daily_report_records.*')
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

    public function edit($id)
    {
        $this->edit_id = $id;
    }

    public function scopeChange($scope)
    {
        // dd($scope);
        $this->scope = $scope;
    }

    //when update report date
    public function updatedReportDate()
    {
        $daily_entries = DailyReportRecord::select('daily_report_records.*')
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

    //when update branch id in filter
    public function updatedBranchId()
    {
        $daily_entries = DailyReportRecord::select('daily_report_records.*')
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

    //create a new report type
    public function createReportType()
    {

        $validated = $this->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        DailyReport::create($validated);

        $this->dispatch('closeModal', 'addReportTypeModal');
        $this->reset('name', 'description');

        $this->notification([
            'icon' => 'success',
            'title' => 'Successed',
            'description' => 'New report type added successfully.',
        ]);
    }

    //create a new report record
    public function crateNewRecord()
    {
        $report_types = DailyReport::all();
        if ($report_types->count() < 1) {
            $this->dispatch('openModal', 'addReportTypeModal');

            $this->notification([
                'icon' => 'error',
                'title' => 'Failed',
                'description' => 'First new report type add before you create a report.',
            ]);

            return;
        }

        // dd($type);

        $daily_entries = DailyReportRecord::select('daily_report_records.*')
            ->where('report_date', '=', $this->report_date)
            ->where('branch_id', '=', $this->branch_id)
            ->get();

        $entries = $daily_entries->pluck('daily_report_id')->toArray();
        $type = $report_types->pluck('id')->toArray();

        $remaining_record = array_diff($type, $entries);

        if (count($remaining_record) > 0) {
            DB::transaction(function () use ($remaining_record) {
                foreach ($remaining_record as $type) {
                    DailyReportRecord::create([
                        'daily_report_id' => $type,
                        'user_id' => auth()->user()->id,
                        'branch_id' => $this->branch_id,
                        'number' => 0,
                        'report_date' => $this->report_date,
                    ]);
                }
            });
        } else {
            $this->notification([
                'icon' => 'info',
                'title' => 'Already created',
                'description' => 'These records were already generated.',
            ]);

            return;
        }

        $this->notification([
            'icon' => 'success',
            'title' => 'Successed',
            'description' => 'New report generated successfully.',
        ]);

        $this->entry_modal = true;
    }

    //update a new record
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

    //delect record
    public function delete($id)
    {
        DailyReportRecord::findOrFail($id)->delete();
    }

    //Export to Excel
    public function export()
    {

        $daily_branch_report = DailyReportRecord::select('daily_report_records.*')
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->when($this->export_branch_id, function ($query) {
                return $query->where('branch_id', $this->export_branch_id);
            })
            ->when($this->start_date && $this->end_date, function ($query) {
                return $query->whereBetween('report_date', [$this->start_date, $this->end_date]);
            })
            ->when($this->start_date && ! $this->end_date, function ($query) {
                return $query->where('report_date', '>=', $this->start_date);
            })
            ->when(! $this->start_date && $this->end_date, function ($query) {
                return $query->where('report_date', '<=', $this->end_date);
            })
            ->orderBy('report_date')
            ->orderBy('daily_report_records.daily_report_id')
            ->get();
        //export data
        // Create a temporary file
        // Create a temporary file with .xlsx extension
        $tempFilePath = tempnam(sys_get_temp_dir(), 'pos') . '.xlsx';

        // dd($tempFilePath);

        // Create the Excel file at the temporary location
        $writer = SimpleExcelWriter::create($tempFilePath)
            ->addHeader([
                'Month',
                'Day',
                'Branch',
                'Title', //Report title
                'Quantity', //Quantity
            ]);

        foreach ($daily_branch_report as $record) {
            // dd($record);

            $writer->addRow([
                date('F', strtotime($record->report_date)),
                date('j', strtotime($record->report_date)),
                ucfirst($record->branch->name),
                $record->dailyReport->name,
                $record->number,
            ]);
        }
        $writer->close();

        // Stream the file to the browser
        return Response::download($tempFilePath, Carbon::now()->format('dmY_His') . '-branch-daily-report.xlsx')->deleteFileAfterSend(true);
    }

    public function render()
    {
        if ($this->entry_modal) {
            $daily_entries = DailyReportRecord::select('daily_report_records.*')
                ->join('daily_reports', 'daily_reports.id', '=', 'daily_report_records.daily_report_id')
                ->where('daily_report_records.report_date', '=', $this->report_date)
                ->where('daily_report_records.branch_id', '=', $this->branch_id)
                ->where('daily_reports.scope', 'like', "%{$this->scope}%")
                ->orderBy('daily_report_records.daily_report_id')
                ->get();

            $this->dispatch('open-modal', 'dataEntryModal');
        } else {
            $daily_entries = [];
        }

        $reportTypeSummary = DailyReportRecord::select('branches.name AS branch', 'daily_reports.name AS type', DB::raw('SUM(daily_report_records.number) AS total'))
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->when($this->start_date_summary && $this->end_date_summary, function ($query) {
                return $query->whereBetween('daily_report_records.report_date', [$this->start_date_summary, $this->end_date_summary]);
            })
            ->when($this->start_date_summary && ! $this->end_date_summary, function ($query) {
                return $query->where('daily_report_records.report_date', '>=', $this->start_date_summary);
            })
            ->when(! $this->start_date_summary && $this->end_date_summary, function ($query) {
                return $query->where('daily_report_records.report_date', '<=', $this->end_date_summary);
            })
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->groupBy('branches.name', 'daily_reports.name', 'daily_reports.id')
            ->orderBy('branches.name')
            ->orderBy('daily_reports.id')
            ->get();

        // dd($reportTypeSummary);

        $formattedReports = [];

        foreach ($reportTypeSummary as $item) {
            $key = ucfirst($item->branch);
            $type = $item->type;

            if (! isset($formattedReports[$key])) {
                $formattedReports[$key] = [];
            }

            $formattedReports[$key][] = [
                'x' => $type,
                'y' => round($item->total, 2),
            ];
        }
        $daily_reports = json_encode($formattedReports); // report type summary

        $all_data = DailyReportRecord::select('daily_reports.name AS type', 'daily_report_records.report_date', DB::raw('SUM(daily_report_records.number) AS total'))
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->groupBy('daily_reports.name', 'daily_report_records.report_date')
            ->get();

        $categories = $all_data->pluck('report_date')->toArray();
        $mergeCategory = [];

        foreach ($categories as $item) {
            $key = Carbon::parse($item)->format('M j, Y');

            if (! in_array($item, $mergeCategory)) {
                $mergeCategory[] = $key;
            }
        }
        // dd($mergeCategory);

        //important summary table
        $impSummaryData = [];

        foreach ($reportTypeSummary as $data) {
            $key = $data->type;
            $branch = ucfirst($data->branch);
            $total = $data->total;

            if (! isset($impSummaryData[$key])) {
                $impSummaryData[$key] = [];
            }

            $impSummaryData[$key][] = [
                'name' => $branch,
                'total' => $total,
            ];
        }

        $impSummaryTotalGram = DailyReportRecord::select(DB::raw(
            'SUM(CASE WHEN daily_reports.is_sale_gram = true THEN daily_report_records.number ELSE 0 END) AS total_sale,
            SUM(CASE WHEN daily_reports.is_repurchase_gram = true THEN daily_report_records.number ELSE 0 END) AS total_repurchase'
        ))
            ->when($this->duration_filter, function ($query) {
                return $query->where('daily_report_records.report_date', '>=', Carbon::now()->subDay($this->duration_filter)->format('Y-m-d'));
            })
            ->when(! $this->duration_filter, function ($query) {
                return $query->where('daily_report_records.report_date', '=', Carbon::now()->format('Y-m-d'));
            })
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->get();

        // dd($impSummaryTotalGram);

        // $reportTypes = DailyReport::select('name')->get()->toArray();

        // dd($reportTypes[0]['name']);

        // $reStureImpData = [];

        // foreach ($reportTypeSummary as $data) {
        //     $key = $data->branch;
        //     $type = $data->type;
        //     $total = $data->total;

        //     if (!isset($reStureImpData[$key][$type])) {
        //         $reStureImpData[$key][$type] = [];
        //     }

        //     // $reStureImpData[$key][$branch];
        // }
        // // dd($reStureImpData);

        // $example = [
        //     'branch 1' => [
        //         "ရွှေ" => 10,
        //         "18K" => 11
        //     ]
        // ];

        // dd($example['branch 1']['ရွှေ']);

        // Restructure the data
        $chartData = [];
        $colors = [
            '#ba891e', //g pcs
            '#d8f211', //gold weight - yellow
            '#1eba7c', //p pcs
            '#46e810', //pandora weight - green
            '#ba1eb2', //k pcs
            '#8e10e8', // 18 k weight - brown
            '#4789bf',
            '#0c33cc',
            '#cf1717', //repurchase weight - red
            '#00000A',
            '#08DAE6',
            '#BA17DA',
            '#67047B',
            '#96038F',
            '#579D0C',
            '#BA17DA',
            '#67047B',
            '#96038F',
            '#579D0C',
            '#579D0C',
            '#BA17DA',
            '#67047B',
            '#96038F',
            '#579D0C',
            // Add more types and their colors if needed
        ];

        $index = 0;
        foreach ($all_data as $record) {
            $type = $record->type;
            $total = $record->total;

            if (! isset($chartData[$type])) {
                $chartData[$type] = [
                    'name' => $type,
                    'data' => [],
                    'color' => $colors[$index], // Default color if not in $colors
                ];
                $index++;
            }

            $chartData[$type]['data'][] = round($total, 2); // Ensure numeric values
        }

        $indexYesterday = DailyReportRecord::select(
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

        $indexToday = DailyReportRecord::select(
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

        //!index data to chart series line
        $indexQuery = DailyReportRecord::select(
            'branches.name AS branch',
            'daily_report_records.report_date AS date',
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
            $key = ucfirst($index->branch);
            $toIndex = ($index->total_gram * 0.6) + ($index->total_quantity * 0.4);

            if (! isset($indexReformed[$key])) {
                $indexReformed[$key] = [];
                if (! isset($indexReformed[$key][$colors[$indexCount]])) {
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

        $indexDate = $indexQuery->pluck('date')->toArray();

        //? convert data to json format
        $indexData = json_encode($indexReformed);
        $indexDate = json_encode($indexDate);

        // dd($indexDate);

        //! Daily Report Card view

        $daily_branch_report = DailyReportRecord::select('daily_report_records.*')
            ->where('report_date', '=', $this->report_date)
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->orderBy('branches.name')
            ->orderBy('daily_report_records.daily_report_id')
            ->get();
        $branch_report = [];
        // dd($daily_branch_report);

        foreach ($daily_branch_report as $data) {

            $key = $data->branch->name . ' (' . Carbon::parse($data->report_date)->format('M j, Y') . ')';

            $key = ucfirst($key);
            if (! isset($branch_report[$key])) {
                $branch_report[$key]['key'] = $key;
            }

            if (! isset($branch_report[$key][$data->dailyReport->name])) {
                $branch_report[$key][$data->dailyReport->name] = $data->number;
            }

            //  $branch_report[$key]['data'][$data->dailyReport->name] = [];
            // $branch_report[$key]['name'] = $data->dailyReport->name;

            // $branch_report[$key]['data'][$data->daily_report_id]['quantity'] = $data->number;
        }

        // dd($branch_report);

        $dailySpirit = DailyReportRecord::select(DB::raw(
            'SUM(CASE WHEN daily_reports.is_sale_gram = true THEN daily_report_records.number ELSE 0 END) AS total_sale,
            SUM(CASE WHEN daily_reports.is_repurchase_gram = true THEN daily_report_records.number ELSE 0 END) AS total_repurchase'
        ))
            ->where('daily_report_records.report_date', '=', $this->report_date)
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->get();

        // dd($dailySpirit);

        $all_reports = json_encode($chartData);
        $mergeCategory = json_encode($mergeCategory);

        return view('livewire.branch-report.sale-and-repurchase', [
            'daily_entries' => $daily_entries,
            'daily_reports' => $daily_reports,
            'all_reports' => $all_reports,
            'branches' => Branch::all(),
            'types' => DailyReport::all(),
            'categories' => $mergeCategory,
            'index_data' => $indexData,
            'index_date' => $indexDate,
            'daily_branch_reports' => $branch_report,
            'daily_spirit' => $dailySpirit,
            'impSummaryData' => $impSummaryData,
            'impSummaryTotalGram' => $impSummaryTotalGram,
        ]);
    }
}
