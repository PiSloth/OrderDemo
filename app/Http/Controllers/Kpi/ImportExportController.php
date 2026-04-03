<?php

namespace App\Http\Controllers\Kpi;

use App\Exceptions\KpiWorkbookImportException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Kpi\KpiWorkbookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportExportController extends Controller
{
    public function __construct(
        protected KpiWorkbookService $workbooks,
    ) {
    }

    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        Gate::forUser($request->user())->authorize('kpiManageImports');

        $path = $this->workbooks->createBlankTemplateWorkbook($request->user());

        return response()->download($path, 'kpi-import-template.xlsx')->deleteFileAfterSend(true);
    }

    public function exportEmployee(Request $request): BinaryFileResponse
    {
        Gate::forUser($request->user())->authorize('kpiManageImports');

        $request->validate([
            'employee_id' => ['required', 'exists:users,id'],
        ]);

        $employee = User::query()->findOrFail((int) $request->input('employee_id'));
        $path = $this->workbooks->createEmployeeExportWorkbook($request->user(), $employee);

        return response()->download(
            $path,
            'kpi-employee-' . $employee->id . '-export.xlsx'
        )->deleteFileAfterSend(true);
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('kpiManageImports');

        $validated = $request->validate([
            'workbook' => ['required', 'file', 'mimes:xlsx'],
        ]);

        try {
            $summary = $this->workbooks->importWorkbook(
                $request->user(),
                $validated['workbook']->getRealPath()
            );

            return redirect()
                ->route('kpi.import-export')
                ->with('message', sprintf(
                    'Imported workbook for %s. Processed %d task row(s), %d holiday row(s), and %d table field row(s).',
                    $summary['employee'],
                    $summary['assignment_count'],
                    $summary['holiday_count'],
                    $summary['table_field_count']
                ));
        } catch (KpiWorkbookImportException $exception) {
            return redirect()
                ->route('kpi.import-export')
                ->with('error', 'Import failed. Review the CSV report for the exact sheet and row errors.')
                ->with('kpi_import_error_report', $exception->errorReportFile);
        }
    }

    public function downloadErrorReport(Request $request, string $file): BinaryFileResponse
    {
        Gate::forUser($request->user())->authorize('kpiManageImports');

        $path = $this->workbooks->errorReportPath($file);

        abort_unless(is_file($path), 404);

        return response()->download($path, basename($path));
    }
}
