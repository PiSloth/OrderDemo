<?php

namespace App\Livewire\Operation\Branch\BranchChecklist;

use App\Models\Branch;
use App\Services\ChecklistService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\SimpleExcel\SimpleExcelWriter;

#[Layout('components.layouts.operation')]
#[Title('Branch Checklist Report')]
class Report extends Component
{
    use WithPagination;
    public array $selectedBranchIds = [];
    public string $dateRange = '';

    public function export(ChecklistService $service)
    {
        $rows = $service->reportQuery($this->selectedBranchIds, $this->fromDate(), $this->toDate())->get();

        if ($rows->isEmpty()) {
            return;
        }

        $tempFilePath = tempnam(sys_get_temp_dir(), 'checklist_report_') . '.xlsx';

        $writer = SimpleExcelWriter::create($tempFilePath)
            ->addHeader(['Date', 'Branch', 'Department', 'Location', 'Checklist Title', 'User', 'Status', 'Remark']);

        foreach ($rows as $row) {
            $writer->addRow([
                optional($row->checked_at)->format('Y-m-d'),
                $row->branch?->name,
                $row->department?->name,
                $row->location?->name,
                $row->checklist?->title,
                $row->user?->name,
                $row->is_done ? 'Done' : 'Not Done',
                $row->remark,
            ]);
        }

        $writer->close();

        return Response::download($tempFilePath, 'checklist-report.xlsx')->deleteFileAfterSend(true);
    }

    protected function fromDate(): ?Carbon
    {
        if (trim($this->dateRange) === '' || !str_contains($this->dateRange, ' to ')) {
            return null;
        }

        [$from] = explode(' to ', $this->dateRange);

        return Carbon::parse($from)->startOfDay();
    }

    protected function toDate(): ?Carbon
    {
        if (trim($this->dateRange) === '') {
            return null;
        }

        if (!str_contains($this->dateRange, ' to ')) {
            return Carbon::parse($this->dateRange)->endOfDay();
        }

        [, $to] = explode(' to ', $this->dateRange);

        return Carbon::parse($to)->endOfDay();
    }

    public function render(ChecklistService $service)
    {
        return view('livewire.operation.branch.branch-checklist.report', [
            'branches' => Branch::query()->orderBy('name')->get(),
            'rows' => $service->reportQuery($this->selectedBranchIds, $this->fromDate(), $this->toDate())
                ->paginate(15),
        ]);
    }
}
