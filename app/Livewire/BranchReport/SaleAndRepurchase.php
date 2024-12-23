<?php

namespace App\Livewire\BranchReport;

use App\Models\Branch;
use App\Models\DailyReport;
use App\Models\DailyReportRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
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
    private $duration_filter;

    public function mount()
    {
        $this->branch_id = auth()->user()->branch_id;

        $this->report_date = Carbon::now()->format('Y-m-d');

        $daily_entries =  DailyReportRecord::select('daily_report_records.*')
            ->where('report_date', '=', $this->report_date)
            ->where('branch_id', '=', $this->branch_id ? $this->branch_id : auth()->user()->branch_id)
            ->exists();

        // dd($daily_entries);
        if ($daily_entries) {
            $this->entry_modal = true;
        } else {
            $this->reset('entry_modal');
        }
    }

    public function durationFilter($time)
    {
        $this->duration_filter = Carbon::now()->subDay($time)->format('Y-m-d');

        // dd($this->duration_filter);
    }

    public function edit($id)
    {
        $this->edit_id = $id;
    }

    public function updatedReportDate()
    {
        $daily_entries =  DailyReportRecord::select('daily_report_records.*')
            ->where('report_date', '=', $this->report_date)
            ->where('branch_id', '=', $this->branch_id ? $this->branch_id : auth()->user()->branch_id)
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
                    'branch_id' => $this->branch_id ? $this->branch_id : auth()->user()->branch_id,
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
                ->where('branch_id', '=', $this->branch_id ? $this->branch_id : auth()->user()->branch_id)
                ->get();

            $this->dispatch('open-modal', 'dataEntryModal');
        } else {
            $daily_entries = [];
        }

        $entrace = DailyReportRecord::select('branches.name AS branch', 'daily_reports.name AS type', DB::raw('SUM(daily_report_records.number) AS total'))
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->when($this->duration_filter, function ($query) {
                return $query->where('daily_report_records.report_date', '>=', $this->duration_filter);
            })
            ->when(! $this->duration_filter, function ($query) {
                return $query->where('daily_report_records.report_date', '>=', Carbon::now()->subDay(7)->format('Y-m-d'));
            })
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->groupBy('branches.name', 'daily_reports.name')
            ->get();



        $formattedReports = [];

        foreach ($entrace as $item) {
            $key = $item->branch;
            $type = $item->type;

            if (!isset($formattedReports[$key])) {
                $formattedReports[$key] = [];
            }

            $formattedReports[$key][] = [
                'x' => $type,
                'y' => (float) $item->total,
            ];
        }

        $overall_data = [];
        $all_data =  DailyReportRecord::select('daily_reports.name AS type', DB::raw('SUM(daily_report_records.number) AS total'))
            ->leftJoin('branches', 'branches.id', 'daily_report_records.branch_id')
            ->leftJoin('daily_reports', 'daily_reports.id', 'daily_report_records.daily_report_id')
            ->groupBy('daily_reports.name', 'daily_report_records.report_date')
            ->get();




        $daily_reports = json_encode($formattedReports);


        // Restructure the data
        $chartData = [];
        $colors = [
            "#03045E",
            "#023E8A",
            "#81B622",
            "#0077B6",
            "#FFAEBC",
            "#A0E7E5",
            "#B4F8C8",
            "#FBE7C6",
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
                    "color" => $colors[$index] ?? "#000000", // Default color if not in $colors
                ];
            }

            $chartData[$type]["data"][] = (float) $total; // Ensure numeric values
            $index++;
        }

        $all_reports = json_encode($chartData);




        return view('livewire.branch-report.sale-and-repurchase', [
            'daily_entries' => $daily_entries,
            'daily_reports' => $daily_reports,
            'all_reports' => $all_reports,
            'branches' => Branch::all(),
            'types' => DailyReport::all(),
        ]);
    }
}
