<?php

namespace App\Http\Controllers\JewelrySales;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ImportBatch;
use App\Services\JewelrySales\BranchMappingStore;
use App\Services\JewelrySales\JewelrySalesImportService;
use App\Services\JewelrySales\UnmatchedBranchesException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

class JewelrySalesImportController extends Controller
{
    public function index(BranchMappingStore $mappingStore): Response
    {
        $batches = ImportBatch::with(['uploader:id,name', 'sourceSystem:id,name'])
            ->latest('id')
            ->paginate(20);

        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $branchNamesById = $branches->pluck('name', 'id');

        $branchMappings = collect($mappingStore->all())
            ->map(fn (int $branchId, string $alias) => [
                'alias' => $alias,
                'branch_id' => $branchId,
                'branch_name' => $branchNamesById->get($branchId, "Branch #{$branchId}"),
            ])
            ->values();

        return Inertia::render('JewelrySales/Import', [
            'batches' => $batches,
            'branches' => $branches,
            'branchMappings' => $branchMappings,
            'unmatchedBranches' => session('unmatched_branches', []),
        ]);
    }

    public function store(Request $request, JewelrySalesImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200'],
        ]);

        $file = $request->file('file');
        $tempPath = $file->getRealPath();
        $originalName = $file->getClientOriginalName();

        try {
            $batch = $importService->import(
                $tempPath,
                $originalName,
                $request->user()?->id
            );
        } catch (UnmatchedBranchesException $e) {
            return redirect()
                ->route('jewelry.sales.import.index')
                ->with('unmatched_branches', $e->unmatchedBranches)
                ->withErrors(['file' => $e->getMessage()]);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('jewelry.sales.import.index')
                ->withErrors(['file' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('Jewelry sales import failed', [
                'file' => $originalName,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('jewelry.sales.import.index')
                ->withErrors(['file' => 'Import failed. Please check the file format and try again.']);
        }

        return redirect()
            ->route('jewelry.sales.import.index')
            ->with(
                'success',
                "Import finished: {$batch->inserted_sales} new invoices ({$batch->updated_sales} overwritten), {$batch->inserted_items} new item rows ({$batch->updated_items} overwritten)."
            );
    }

    public function saveBranchMappings(Request $request, BranchMappingStore $mappingStore): RedirectResponse
    {
        $validated = $request->validate([
            'mappings' => ['required', 'array', 'min:1'],
            'mappings.*.alias' => ['required', 'string', 'max:191'],
            'mappings.*.branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $mappingStore->saveMappings($validated['mappings']);

        return redirect()
            ->route('jewelry.sales.import.index')
            ->with('success', 'Branch mappings saved. Click "Start Import" to re-run your file.');
    }
}
