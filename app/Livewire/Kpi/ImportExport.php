<?php

namespace App\Livewire\Kpi;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class ImportExport extends Component
{
    public string $selectedEmployeeId = '';

    public function mount(): void
    {
        Gate::authorize('kpiManageImports');
    }

    public function render()
    {
        return view('livewire.kpi.import-export', [
            'employeeAsyncData' => $this->getEmployeeAsyncDataProperty(),
            'selectedEmployeeExportUrl' => $this->getSelectedEmployeeExportUrlProperty(),
            'errorReportUrl' => $this->getErrorReportUrlProperty(),
        ]);
    }

    public function getEmployeeAsyncDataProperty(): array
    {
        $params = [];
        $user = Auth::user();

        if ($user && strtolower((string) optional($user->position)->name) !== 'super admin' && $user->department_id) {
            $params['department_id'] = (int) $user->department_id;
        }

        return [
            'api' => route('users.index'),
            'method' => 'GET',
            'params' => $params,
            'alwaysFetch' => false,
        ];
    }

    public function getSelectedEmployeeExportUrlProperty(): ?string
    {
        if ($this->selectedEmployeeId === '') {
            return null;
        }

        return route('kpi.import-export.employee', [
            'employee_id' => $this->selectedEmployeeId,
        ]);
    }

    public function getErrorReportUrlProperty(): ?string
    {
        $file = session('kpi_import_error_report');

        if (!$file) {
            return null;
        }

        return route('kpi.import-export.errors', ['file' => $file]);
    }
}
