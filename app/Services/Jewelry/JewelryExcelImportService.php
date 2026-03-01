<?php

namespace App\Services\Jewelry;

use App\Models\BatchNumberAndGroup;
use App\Models\Branch;
use App\Models\GroupNumber;
use App\Models\JewelryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;

class JewelryExcelImportService
{
    /**
     * Imports items into the provided group.
     *
     * If the group reaches its limits (120 items and/or 12 unique batches), the importer will
     * automatically create new groups and continue importing until all rows are processed.
     *
     * @return array{
     *   inserted:int,
     *   errors:array<int,string>,
     *   batch_ids:array<int,int>,
     *   primary_group_inserted?:int,
     *   groups?:array<int,array{id:int,number:string,inserted:int,is_new:bool}>
     * }
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
            ->get([
                'branch_id',
                'product_name',
                'quality',
                'total_weight',
                'goldsmith_deduction',
                'goldsmith_labor_fee',
                'kyauk_weight',
                'batch_id',
            ]);

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
                (float) $it->goldsmith_deduction,
                (int) $it->goldsmith_labor_fee,
                (float) $it->kyauk_weight,
            );
            $batchIdByKey[$key] = (int) $it->batch_id;
        }

        $rows = SimpleExcelReader::create($path)->getRows();

        /** @var array<int,array{rowIndex:int,branch_id:int,product_name:string,quality:string,barcode:?string,gold_weight:float,total_weight:float,kyauk_weight:float,goldsmith_deduction:float,goldsmith_labor_fee:int,stone_price:?int,profit_loss:?float,profit_labor_fee:?int,explicit_batch_id:?int}> $parsedRows */
        $parsedRows = [];
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
            $goldWeight = $this->parseDecimal($r['gold_weight'] ?? $r['goldweight'] ?? $r['gold'] ?? null, 3);

            $goldsmithDeduction = $this->parseDecimal(
                $r['goldsmith_deduction']
                    ?? ($r['l_gram'] ?? null)
                    ?? ($r['lgram'] ?? null)
                    ?? $r['ပန်းထိမ်အလျော့တွက်']
                    ?? null,
                3
            );
            $goldsmithLaborFee = $this->parseInt(
                $r['goldsmith_labor_fee']
                    ?? ($r['l_mmk'] ?? null)
                    ?? ($r['lmmk'] ?? null)
                    ?? $r['ပန်းထိမ်_လက်ခ']
                    ?? null
            );
            $kyaukWeight = $this->parseDecimal(
                $r['kyauk_weight']
                    ?? ($r['kyauk_gram'] ?? null)
                    ?? ($r['kyaukgram'] ?? null)
                    ?? $r['ကျောက်ချိန်']
                    ?? null,
                3
            );

            $stonePrice = $this->parseInt($r['stone_price'] ?? $r['ကျောက်ဖိုး'] ?? null);
            $profitLoss = $this->parseDecimal($r['profit_loss'] ?? $r['အမြတ်အလျော့'] ?? null, 2);
            $profitLaborFee = $this->parseInt($r['profit_labor_fee'] ?? $r['အမြတ်လက်ခ'] ?? null);

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

            if ($productName === '' || $quality === '' || is_null($totalWeight) || is_null($goldsmithDeduction) || is_null($goldsmithLaborFee) || is_null($kyaukWeight)) {
                $errors[] = "Row {$rowIndex}: missing required columns (branch_id, product_name, quality, total_weight, ပန်းထိမ်အလျော့တွက်, ပန်းထိမ် လက်ခ, ကျောက်ချိန်).";
                continue;
            }

            if (is_null($goldWeight)) {
                // Backward-compatible default: previously we only had total_weight.
                $goldWeight = $totalWeight;
            }

            $parsedRows[] = [
                'rowIndex' => $rowIndex,
                'branch_id' => (int) $branchId,
                'product_name' => $productName,
                'quality' => $quality,
                'barcode' => $barcode !== '' ? $barcode : null,
                'gold_weight' => (float) $goldWeight,
                'total_weight' => (float) $totalWeight,
                'kyauk_weight' => (float) $kyaukWeight,
                'goldsmith_deduction' => (float) $goldsmithDeduction,
                'goldsmith_labor_fee' => (int) $goldsmithLaborFee,
                'stone_price' => is_null($stonePrice) ? null : (int) $stonePrice,
                'profit_loss' => is_null($profitLoss) ? null : (float) $profitLoss,
                'profit_labor_fee' => is_null($profitLaborFee) ? null : (int) $profitLaborFee,
                'explicit_batch_id' => is_null($explicitBatchId) ? null : (int) $explicitBatchId,
            ];
        }

        if (!empty($errors)) {
            return ['inserted' => 0, 'errors' => $errors, 'batch_ids' => []];
        }

        if (is_null($fileBranchId)) {
            return ['inserted' => 0, 'errors' => ['No rows found to import.'], 'batch_ids' => []];
        }

        $newCount = count($parsedRows);
        if ($newCount === 0) {
            return ['inserted' => 0, 'errors' => ['No rows found to import.'], 'batch_ids' => []];
        }

        $result = DB::transaction(function () use (
            $group,
            $parsedRows,
            $existingCount,
            $existingBatchIds,
            $batchIdByKey,
            $nextBatchId,
            $now
        ) {
            $primaryGroupId = (int) $group->id;

            /** @var array<int,array{group:GroupNumber,existingCount:int,batchIdByKey:array<string,int>,nextBatchId:int,batchIdSet:array<int,bool>,toInsert:array<int,array<string,mixed>>}> $plans */
            $plans = [];
            $plans[] = [
                'group' => $group,
                'existingCount' => (int) $existingCount,
                'batchIdByKey' => $batchIdByKey,
                'nextBatchId' => (int) $nextBatchId,
                'batchIdSet' => array_fill_keys(array_map(fn($v) => (int) $v, $existingBatchIds), true),
                'toInsert' => [],
            ];

            foreach ($parsedRows as $row) {
                while (true) {
                    $planIndex = count($plans) - 1;
                    $plan = &$plans[$planIndex];

                    $groupId = (int) $plan['group']->id;

                    $key = $this->batchKey(
                        (int) $row['branch_id'],
                        (string) $row['product_name'],
                        (string) $row['quality'],
                        (float) $row['total_weight'],
                        (float) $row['goldsmith_deduction'],
                        (int) $row['goldsmith_labor_fee'],
                        (float) $row['kyauk_weight']
                    );

                    $explicitBatchId = $row['explicit_batch_id'];
                    $hasKey = isset($plan['batchIdByKey'][$key]);

                    $batchId = null;
                    $proposedNextBatchId = (int) $plan['nextBatchId'];
                    $shouldSetKey = false;

                    if (!is_null($explicitBatchId)) {
                        $batchId = (int) $explicitBatchId;
                        $shouldSetKey = true;
                        $proposedNextBatchId = max($proposedNextBatchId, $batchId + 1);
                    } elseif ($hasKey) {
                        $batchId = (int) $plan['batchIdByKey'][$key];
                    } else {
                        $batchId = (int) $plan['nextBatchId'];
                        $shouldSetKey = true;
                        $proposedNextBatchId = $batchId + 1;
                    }

                    $currentCount = (int) $plan['existingCount'] + count($plan['toInsert']);
                    $wouldCount = $currentCount + 1;
                    if ($wouldCount > 120) {
                        $plans[] = $this->newEmptyPlan($this->createAutoGroup());
                        continue;
                    }

                    $uniqueCount = count($plan['batchIdSet']);
                    $wouldAddUnique = !isset($plan['batchIdSet'][(int) $batchId]);
                    if (($uniqueCount + ($wouldAddUnique ? 1 : 0)) > 12) {
                        $plans[] = $this->newEmptyPlan($this->createAutoGroup());
                        continue;
                    }

                    if ($shouldSetKey) {
                        $plan['batchIdByKey'][$key] = (int) $batchId;
                        $plan['nextBatchId'] = $proposedNextBatchId;
                    }
                    $plan['batchIdSet'][(int) $batchId] = true;

                    $plan['toInsert'][] = [
                        'group_number_id' => $groupId,
                        'branch_id' => (int) $row['branch_id'],
                        'product_name' => (string) $row['product_name'],
                        'quality' => (string) $row['quality'],
                        'barcode' => $row['barcode'],
                        'gold_weight' => (float) $row['gold_weight'],
                        'total_weight' => (float) $row['total_weight'],
                        'kyauk_weight' => (float) $row['kyauk_weight'],
                        'goldsmith_deduction' => (float) $row['goldsmith_deduction'],
                        'goldsmith_labor_fee' => (int) $row['goldsmith_labor_fee'],
                        'stone_price' => $row['stone_price'],
                        'profit_loss' => $row['profit_loss'],
                        'profit_labor_fee' => $row['profit_labor_fee'],
                        'batch_id' => (int) $batchId,
                        'is_register' => false,
                        'register_by_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    unset($plan);
                    break;
                }
            }

            $allGroups = [];
            $totalInserted = 0;
            $primaryInserted = 0;
            $primaryBatchIdsInInsert = [];

            foreach ($plans as $plan) {
                $toInsert = $plan['toInsert'];
                $count = count($toInsert);
                if ($count > 0) {
                    JewelryItem::query()->insert($toInsert);

                    $batchIdsInInsert = array_values(array_unique(array_map(fn($r) => (int) $r['batch_id'], $toInsert)));
                    foreach ($batchIdsInInsert as $batchId) {
                        BatchNumberAndGroup::firstOrCreate([
                            'group_number_id' => $plan['group']->id,
                            'batch_id' => (int) $batchId,
                        ], [
                            'is_post' => false,
                            'post_by' => null,
                        ]);
                    }

                    if ((int) $plan['group']->id === $primaryGroupId) {
                        $primaryBatchIdsInInsert = $batchIdsInInsert;
                        $primaryInserted = $count;
                    }
                }

                $totalInserted += $count;
                $allGroups[] = [
                    'id' => (int) $plan['group']->id,
                    'number' => (string) $plan['group']->number,
                    'inserted' => $count,
                    'is_new' => (int) $plan['group']->id !== $primaryGroupId,
                ];
            }

            return [
                'inserted' => $totalInserted,
                'errors' => [],
                'batch_ids' => $primaryBatchIdsInInsert,
                'primary_group_inserted' => $primaryInserted,
                'groups' => $allGroups,
            ];
        });

        return $result;
    }

    private function newEmptyPlan(GroupNumber $group): array
    {
        return [
            'group' => $group,
            'existingCount' => 0,
            'batchIdByKey' => [],
            'nextBatchId' => 1,
            'batchIdSet' => [],
            'toInsert' => [],
        ];
    }

    private function createAutoGroup(): GroupNumber
    {
        $tmpNumber = 'TMP-' . (string) Str::uuid();

        $group = GroupNumber::create([
            'number' => $tmpNumber,
            'po_reference' => null,
            'purchase_by' => null,
            'is_purchase' => false,
            'purchase_status' => 'not_started',
        ]);

        $group->update([
            'number' => 'JV-' . str_pad((string) $group->id, 6, '0', STR_PAD_LEFT),
        ]);

        return $group;
    }

    private function normalizeExcelRow($rawRow): array
    {
        $row = [];
        foreach (($rawRow ?? []) as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));
            // Remove parenthesized notes from headers, e.g. "ကျောက်ချိန် (Previous Kyauk Gram)" => "ကျောက်ချိန်"
            $normalizedKey = preg_replace('/\s*\(.*\)\s*/u', '', $normalizedKey) ?? $normalizedKey;
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

    private function batchKey(
        int $branchId,
        string $productName,
        string $quality,
        float $totalWeight,
        float $goldsmithDeduction,
        int $goldsmithLaborFee,
        float $kyaukWeight
    ): string {
        return (string) $branchId
            . '|' . strtolower(trim($productName))
            . '|' . strtolower(trim($quality))
            . '|' . number_format($totalWeight, 3, '.', '')
            . '|' . number_format($goldsmithDeduction, 3, '.', '')
            . '|' . (string) $goldsmithLaborFee
            . '|' . number_format($kyaukWeight, 3, '.', '');
    }
}
