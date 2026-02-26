<?php

namespace App\Services\Jewelry;

use App\Models\BatchNumberAndGroup;
use App\Models\Branch;
use App\Models\GroupNumber;
use App\Models\JewelryItem;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class JewelryExcelImportService
{
    /**
     * @return array{inserted:int, errors:array<int,string>, batch_ids:array<int,int>}
     */
    public function importIntoGroup(GroupNumber $group, string $path, int $userId): array
    {
        $errors = [];

        $validBranchIds = Branch::query()->pluck('id')->map(fn($v) => (int) $v)->all();
        $validBranchIdSet = array_fill_keys($validBranchIds, true);

        $existingCount = (int) JewelryItem::query()->where('group_number_id', $group->id)->count();
        $existingBranchIds = JewelryItem::query()
            ->where('group_number_id', $group->id)
            ->whereNotNull('branch_id')
            ->distinct()
            ->pluck('branch_id')
            ->map(fn($v) => (int) $v)
            ->values();

        if ($existingBranchIds->count() > 1) {
            return [
                'inserted' => 0,
                'errors' => ['Group has multiple branch IDs already. Please contact admin.'],
                'batch_ids' => [],
            ];
        }

        $groupBranchId = $existingBranchIds->first();
        $existingItems = JewelryItem::query()
            ->where('group_number_id', $group->id)
            ->whereNotNull('batch_id')
            ->get(['branch_id', 'product_name', 'quality', 'total_weight', 'l_gram', 'l_mmk', 'kyauk_gram', 'batch_id']);

        $existingBatchIds = $existingItems->pluck('batch_id')->map(fn($v) => (int) $v)->unique()->values()->all();
        $maxBatchId = (int) ($existingItems->max('batch_id') ?? 0);
        $nextBatchId = $maxBatchId + 1;

        $batchIdByKey = [];
        foreach ($existingItems as $it) {
            $key = $this->batchKey(
                (int) $it->branch_id,
                (string) $it->product_name,
                (string) $it->quality,
                (float) $it->total_weight,
                (float) $it->l_gram,
                (int) $it->l_mmk,
                (float) $it->kyauk_gram,
            );
            $batchIdByKey[$key] = (int) $it->batch_id;
        }

        $rows = SimpleExcelReader::create($path)->getRows();

        $toInsert = [];
        $rowIndex = 1;
        $now = now();

        $fileBranchId = null;

        foreach ($rows as $rawRow) {
            $rowIndex++;
            $r = $this->normalizeExcelRow($rawRow);

            $branchId = $this->parseInt($r['branch_id'] ?? $r['branchid'] ?? $r['branch'] ?? null);

            $productName = trim((string) ($r['product_name'] ?? $r['product'] ?? $r['name'] ?? ''));
            $quality = trim((string) ($r['quality'] ?? $r['qlty'] ?? $r['q'] ?? ''));
            $barcode = trim((string) ($r['barcode'] ?? $r['bar_code'] ?? $r['code'] ?? ''));

            $totalWeight = $this->parseDecimal($r['total_weight'] ?? $r['totalweight'] ?? $r['weight'] ?? null, 3);
            $lGram = $this->parseDecimal($r['l_gram'] ?? $r['lgram'] ?? null, 3);
            $lMmk = $this->parseInt($r['l_mmk'] ?? $r['lmmk'] ?? null);
            $kyaukGram = $this->parseDecimal($r['kyauk_gram'] ?? $r['kyaukgram'] ?? null, 3);

            $explicitBatchId = $this->parseInt(
                $r['batch_id']
                    ?? $r['batch']
                    ?? $r['batch_number']
                    ?? $r['batchno']
                    ?? $r['batch_no']
                    ?? null
            );

            if (!is_null($explicitBatchId) && (int) $explicitBatchId <= 0) {
                $explicitBatchId = null;
            }

            if (is_null($branchId) || $branchId <= 0 || !isset($validBranchIdSet[(int) $branchId])) {
                $errors[] = "Row {$rowIndex}: branch_id is required and must be a valid branches.id.";
                continue;
            }

            if (is_null($fileBranchId)) {
                $fileBranchId = (int) $branchId;
            } elseif ((int) $branchId !== (int) $fileBranchId) {
                $errors[] = "Row {$rowIndex}: branch_id must be the same for all rows in the import file.";
                break;
            }

            if (!is_null($groupBranchId) && (int) $branchId !== (int) $groupBranchId) {
                $errors[] = "Row {$rowIndex}: branch_id does not match this group's existing branch_id.";
                break;
            }

            if ($productName === '' || $quality === '' || is_null($totalWeight) || is_null($lGram) || is_null($lMmk) || is_null($kyaukGram)) {
                $errors[] = "Row {$rowIndex}: missing required columns (branch_id, product_name, quality, total_weight, l_gram, l_mmk, kyauk_gram).";
                continue;
            }

            $key = $this->batchKey((int) $branchId, $productName, $quality, $totalWeight, $lGram, $lMmk, $kyaukGram);

            if (!is_null($explicitBatchId)) {
                $explicitBatchId = (int) $explicitBatchId;
                $batchIdByKey[$key] = $explicitBatchId;
                $nextBatchId = max($nextBatchId, $explicitBatchId + 1);
            } elseif (!isset($batchIdByKey[$key])) {
                $batchIdByKey[$key] = $nextBatchId;
                $nextBatchId++;
            }

            $toInsert[] = [
                'group_number_id' => $group->id,
                'branch_id' => (int) $branchId,
                'product_name' => $productName,
                'quality' => $quality,
                'barcode' => $barcode !== '' ? $barcode : null,
                'total_weight' => $totalWeight,
                'l_gram' => $lGram,
                'l_mmk' => $lMmk,
                'kyauk_gram' => $kyaukGram,
                'batch_id' => (int) $batchIdByKey[$key],
                'is_register' => false,
                'register_by_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($errors)) {
            return ['inserted' => 0, 'errors' => $errors, 'batch_ids' => []];
        }

        if (is_null($fileBranchId)) {
            return ['inserted' => 0, 'errors' => ['No rows found to import.'], 'batch_ids' => []];
        }

        $newCount = count($toInsert);
        if ($newCount === 0) {
            return ['inserted' => 0, 'errors' => ['No rows found to import.'], 'batch_ids' => []];
        }

        if (($existingCount + $newCount) > 120) {
            return ['inserted' => 0, 'errors' => ['Group limit exceeded: maximum 120 total items per group.'], 'batch_ids' => []];
        }

        $batchIdsInInsert = array_values(array_unique(array_map(fn($r) => (int) $r['batch_id'], $toInsert)));
        $uniqueAfter = count(array_unique(array_merge($existingBatchIds, $batchIdsInInsert)));
        if ($uniqueAfter > 12) {
            return ['inserted' => 0, 'errors' => ['Group limit exceeded: maximum 12 unique batch IDs per group.'], 'batch_ids' => []];
        }

        DB::transaction(function () use ($group, $toInsert, $batchIdsInInsert) {
            JewelryItem::query()->insert($toInsert);

            foreach ($batchIdsInInsert as $batchId) {
                BatchNumberAndGroup::firstOrCreate([
                    'group_number_id' => $group->id,
                    'batch_id' => (int) $batchId,
                ], [
                    'is_post' => false,
                    'post_by' => null,
                ]);
            }
        });

        return ['inserted' => $newCount, 'errors' => [], 'batch_ids' => $batchIdsInInsert];
    }

    private function normalizeExcelRow($rawRow): array
    {
        $row = [];
        foreach (($rawRow ?? []) as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));
            $normalizedKey = str_replace([' ', '-', '.'], '_', $normalizedKey);
            $row[$normalizedKey] = is_string($value) ? trim($value) : $value;
        }

        return $row;
    }

    private function parseDecimal($value, int $decimals): ?float
    {
        if ($value instanceof \DateTimeInterface) {
            return null;
        }

        if (is_null($value)) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return round((float) $value, $decimals);
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        $s = str_replace(',', '', $s);
        if (!is_numeric($s)) {
            return null;
        }

        return round((float) $s, $decimals);
    }

    private function parseInt($value): ?int
    {
        if ($value instanceof \DateTimeInterface) {
            return null;
        }

        if (is_null($value)) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        $s = str_replace(',', '', $s);
        if (!is_numeric($s)) {
            return null;
        }

        return (int) round((float) $s);
    }

    private function batchKey(int $branchId, string $productName, string $quality, float $totalWeight, float $lGram, int $lMmk, float $kyaukGram): string
    {
        return (string) $branchId
            . '|' . strtolower(trim($productName))
            . '|' . strtolower(trim($quality))
            . '|' . number_format($totalWeight, 3, '.', '')
            . '|' . number_format($lGram, 3, '.', '')
            . '|' . (string) $lMmk
            . '|' . number_format($kyaukGram, 3, '.', '');
    }
}
