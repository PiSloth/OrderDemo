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
     * Updates existing items by matching on the provided comparison columns.
     *
     * A row is considered a match if ALL of the following are equal:
     * - total_weight
     * - kyauk_weight
     * - goldsmith_deduction
     * - goldsmith_labor_fee
     * - po_reference (from group_numbers)
     * - stone_price, using the rule: (imported stone price) * 2 == jewelry_items.stone_price
     *   (this matches the UI's "stone_price / 2" value)
     *
     * On match: updates jewelry_items.external_id and jewelry_items.lot_serial.
     *
     * If $branchId is provided, matches are limited to that branch.
     *
     * @return array{updated:int,errors:array<int,string>,warnings:array<int,string>,not_found:array<int,string>}
     */
    public function updateExternalIdAndLotSerialByMatch(string $path, ?int $branchId = null): array
    {
        $errors = [];
        $warnings = [];
        $notFound = [];

        $rows = SimpleExcelReader::create($path)->getRows();

        /** @var array<int,array{rowIndex:int,po_reference:string,quality:string,total_weight:float,kyauk_weight:float,goldsmith_deduction:float,goldsmith_labor_fee:int,stone_price_db:?int,external_id:string,lot_serial:string}> $parsedRows */
        $parsedRows = [];

        $rowIndex = 1;
        /** @var array<string,int> $seenExternalIds */
        $seenExternalIds = [];
        /** @var array<string,int> $seenLotSerials */
        $seenLotSerials = [];
        foreach ($rows as $rawRow) {
            $rowIndex++;
            $r = $this->normalizeExcelRow($rawRow);

            $poRef = trim((string) (
                $r['po_reference']
                ?? $r['po_ref']
                ?? $r['po']
                ?? $r['po_no']
                ?? $r['po_number']
                ?? $r['purchase_order']
                ?? $r['purchaseorder']
                ?? ''
            ));
            if ($poRef === '') {
                $errors[] = "Row {$rowIndex}: PO Ref is required.";
                continue;
            }

            $qualityRaw = trim((string) (
                $r['quality']
                ?? $r['quality_name']
                ?? $r['qualityname']
                ?? $r['qlty']
                ?? $r['q']
                ?? ''
            ));
            $quality = $this->mapExternalQualityToDbQuality($qualityRaw);

            $totalWeight = $this->parseDecimal($r['total_weight'] ?? $r['totalweight'] ?? $r['weight'] ?? null, 3);
            $kyaukWeight = $this->parseDecimal(
                $r['kyauk_weight']
                    ?? ($r['kyauk_gram'] ?? null)
                    ?? ($r['kyaukgram'] ?? null)
                    ?? ($r['kyaukweight'] ?? null)
                    ?? ($r['kyauk_weight_gram'] ?? null)
                    ?? ($r['ကျောက်ချိန်'] ?? null)
                    ?? null,
                3
            );
            $goldsmithDeduction = $this->parseDecimal(
                $r['goldsmith_deduction']
                    ?? ($r['goldsmith_deduciton'] ?? null)
                    ?? ($r['gold_smith_deduction'] ?? null)
                    ?? ($r['gold_smith_detuction'] ?? null)
                    ?? ($r['l_gram'] ?? null)
                    ?? ($r['lgram'] ?? null)
                    ?? ($r['ပန်းထိမ်အလျော့တွက်'] ?? null)
                    ?? null,
                3
            );
            $goldsmithLaborFee = $this->parseInt(
                $r['goldsmith_labor_fee']
                    ?? ($r['labor_fee'] ?? null)
                    ?? ($r['l_mmk'] ?? null)
                    ?? ($r['lmmk'] ?? null)
                    ?? ($r['ပန်းထိမ်_လက်ခ'] ?? null)
                    ?? null
            );

            if (is_null($totalWeight) || is_null($kyaukWeight) || is_null($goldsmithDeduction) || is_null($goldsmithLaborFee)) {
                $errors[] = "Row {$rowIndex}: missing required columns (total_weight, kyauk_weight, goldsmith_deduction, goldsmith_labor_fee).";
                continue;
            }

            // Stone price in the mapping file is assumed to be the UI value (stone_price / 2).
            // Convert it back to DB scale by multiplying by 2.
            $stoneRaw = $r['stone_price'] ?? ($r['ကျောက်ဖိုး'] ?? null) ?? null;
            $stoneDb = $this->parseStoneHalfToDbStone($stoneRaw);
            if (is_null($stoneDb) && !is_null($stoneRaw) && trim((string) $stoneRaw) !== '') {
                $errors[] = "Row {$rowIndex}: stone_price must be numeric (supports .5) to apply the /2 matching rule.";
                continue;
            }

            $externalId = trim((string) (
                $r['external_id']
                ?? $r['externalid']
                ?? $r['external']
                ?? $r['ext_id']
                ?? ''
            ));

            $lotSerial = trim((string) (
                $r['lot_serial']
                ?? $r['lot/serial']
                ?? $r['lot_serial_no']
                ?? $r['lot']
                ?? $r['serial']
                ?? ''
            ));

            if ($externalId === '') {
                $errors[] = "Row {$rowIndex}: external_id is required.";
                continue;
            }

            if (isset($seenExternalIds[$externalId])) {
                $errors[] = "Row {$rowIndex}: external_id '{$externalId}' is duplicated (also in row {$seenExternalIds[$externalId]}). External ID must be unique.";
                continue;
            }
            $seenExternalIds[$externalId] = (int) $rowIndex;

            if ($lotSerial === '') {
                $errors[] = "Row {$rowIndex}: lot/serial is required.";
                continue;
            }

            if (isset($seenLotSerials[$lotSerial])) {
                $errors[] = "Row {$rowIndex}: lot/serial '{$lotSerial}' is duplicated (also in row {$seenLotSerials[$lotSerial]}).";
                continue;
            }
            $seenLotSerials[$lotSerial] = (int) $rowIndex;

            $parsedRows[] = [
                'rowIndex' => (int) $rowIndex,
                'po_reference' => $poRef,
                'quality' => (string) $quality,
                'total_weight' => (float) $totalWeight,
                'kyauk_weight' => (float) $kyaukWeight,
                'goldsmith_deduction' => (float) $goldsmithDeduction,
                'goldsmith_labor_fee' => (int) $goldsmithLaborFee,
                'stone_price_db' => is_null($stoneDb) ? null : (int) $stoneDb,
                'external_id' => $externalId,
                'lot_serial' => $lotSerial,
            ];
        }

        if (empty($parsedRows)) {
            return [
                'updated' => 0,
                'errors' => array_values($errors ?: ['No rows found to import.']),
                'warnings' => [],
                'not_found' => [],
            ];
        }

        $poRefs = array_values(array_unique(array_map(fn($r) => (string) $r['po_reference'], $parsedRows)));

        $dbCount = JewelryItem::query()
            ->join('group_numbers', 'group_numbers.id', '=', 'jewelry_items.group_number_id')
            ->when(!is_null($branchId), fn($q) => $q->where('jewelry_items.branch_id', (int) $branchId))
            ->whereIn('group_numbers.po_reference', $poRefs)
            ->count('jewelry_items.id');

        $excelCount = count($parsedRows);
        if ((int) $dbCount !== (int) $excelCount) {
            return [
                'updated' => 0,
                'errors' => array_values(array_merge($errors, [
                    "Row count mismatch: Excel has {$excelCount} row(s), but DB has {$dbCount} item(s) for the selected PO Ref(s). Import must be 1 Excel row = 1 DB item.",
                ])),
                'warnings' => array_values($warnings),
                'not_found' => array_values($notFound),
            ];
        }

        $externalIds = array_values(array_unique(array_map(fn($r) => (string) $r['external_id'], $parsedRows)));

        /** @var array<string,array<int,int>> $existingItemIdsByExternalId */
        $existingItemIdsByExternalId = [];
        if (!empty($externalIds)) {
            $existing = JewelryItem::query()
                ->whereIn('external_id', $externalIds)
                ->get(['id', 'external_id']);

            foreach ($existing as $ex) {
                $eid = (string) ($ex->external_id ?? '');
                if ($eid === '') {
                    continue;
                }
                if (!isset($existingItemIdsByExternalId[$eid])) {
                    $existingItemIdsByExternalId[$eid] = [];
                }
                $existingItemIdsByExternalId[$eid][] = (int) $ex->id;
            }
        }

        $items = JewelryItem::query()
            ->join('group_numbers', 'group_numbers.id', '=', 'jewelry_items.group_number_id')
            ->when(!is_null($branchId), fn($q) => $q->where('jewelry_items.branch_id', (int) $branchId))
            ->whereIn('group_numbers.po_reference', $poRefs)
            ->get([
                'jewelry_items.id as id',
                'jewelry_items.quality as quality',
                'jewelry_items.total_weight as total_weight',
                'jewelry_items.kyauk_weight as kyauk_weight',
                'jewelry_items.goldsmith_deduction as goldsmith_deduction',
                'jewelry_items.goldsmith_labor_fee as goldsmith_labor_fee',
                'jewelry_items.stone_price as stone_price',
                'group_numbers.po_reference as po_reference',
            ]);

        /** @var array<string,array<int,int>> $itemIdsByKey */
        $itemIdsByKey = [];
        /** @var array<string,array<int,int>> $itemIdsByKeyWithQuality */
        $itemIdsByKeyWithQuality = [];
        foreach ($items as $it) {
            $key = $this->matchKey(
                (string) ($it->po_reference ?? ''),
                (float) $it->total_weight,
                (float) $it->kyauk_weight,
                (float) $it->goldsmith_deduction,
                (int) $it->goldsmith_labor_fee,
                is_null($it->stone_price) ? null : (int) $it->stone_price
            );

            if (!isset($itemIdsByKey[$key])) {
                $itemIdsByKey[$key] = [];
            }
            $itemIdsByKey[$key][] = (int) $it->id;

            $keyWithQuality = $this->matchKeyWithQuality(
                (string) ($it->po_reference ?? ''),
                $this->mapExternalQualityToDbQuality((string) ($it->quality ?? '')),
                (float) $it->total_weight,
                (float) $it->kyauk_weight,
                (float) $it->goldsmith_deduction,
                (int) $it->goldsmith_labor_fee,
                is_null($it->stone_price) ? null : (int) $it->stone_price
            );
            if (!isset($itemIdsByKeyWithQuality[$keyWithQuality])) {
                $itemIdsByKeyWithQuality[$keyWithQuality] = [];
            }
            $itemIdsByKeyWithQuality[$keyWithQuality][] = (int) $it->id;
        }

        $now = now();
        $updated = 0;

        /**
         * First pass: validate 1:1 matching for every row and ensure no DB item is matched twice.
         * If any row fails, do not update anything.
         */
        /** @var array<string,array<int,array<string,mixed>>> $rowsByKey */
        $rowsByKey = [];

        foreach ($parsedRows as $row) {
            $quality = trim((string) ($row['quality'] ?? ''));
            if ($quality !== '') {
                $baseKey = $this->matchKeyWithQuality(
                    (string) $row['po_reference'],
                    (string) $quality,
                    (float) $row['total_weight'],
                    (float) $row['kyauk_weight'],
                    (float) $row['goldsmith_deduction'],
                    (int) $row['goldsmith_labor_fee'],
                    $row['stone_price_db']
                );
                $key = 'Q|' . $baseKey;
            } else {
                $warnings[] = 'Row ' . (int) $row['rowIndex'] . ': Quality is empty; matching without quality.';
                $baseKey = $this->matchKey(
                    (string) $row['po_reference'],
                    (float) $row['total_weight'],
                    (float) $row['kyauk_weight'],
                    (float) $row['goldsmith_deduction'],
                    (int) $row['goldsmith_labor_fee'],
                    $row['stone_price_db']
                );
                $key = 'N|' . $baseKey;
            }

            if (!isset($rowsByKey[$key])) {
                $rowsByKey[$key] = [];
            }
            $rowsByKey[$key][] = $row;
        }

        /** @var array<int,int> $targetIdByRowIndex */
        $targetIdByRowIndex = [];
        /** @var array<int,bool> $alreadyAssignedItemIds */
        $alreadyAssignedItemIds = [];

        foreach ($rowsByKey as $key => $rowsForKey) {
            $isQualityKey = str_starts_with($key, 'Q|');
            $baseKey = substr($key, 2);
            $ids = $isQualityKey ? ($itemIdsByKeyWithQuality[$baseKey] ?? []) : ($itemIdsByKey[$baseKey] ?? []);

            $rowCount = count($rowsForKey);
            $idCount = count($ids);
            if ($rowCount !== $idCount) {
                $errors[] = 'Match bucket size mismatch: ' . $this->describeExternalMatchBucket($baseKey, $isQualityKey)
                    . ": Excel has {$rowCount} row(s) but DB has {$idCount} item(s).";
                continue;
            }

            usort($rowsForKey, fn($a, $b) => (int) ($a['rowIndex'] ?? 0) <=> (int) ($b['rowIndex'] ?? 0));
            $ids = array_values(array_map('intval', $ids));
            sort($ids);

            foreach ($rowsForKey as $i => $row) {
                $targetId = (int) ($ids[$i] ?? 0);
                if ($targetId <= 0) {
                    $errors[] = 'Row ' . (int) $row['rowIndex'] . ' could not be assigned to a DB item.';
                    continue;
                }

                if (isset($alreadyAssignedItemIds[$targetId])) {
                    $errors[] = 'Row ' . (int) $row['rowIndex'] . ' matched a DB item that was already assigned (item id ' . $targetId . ').';
                    continue;
                }
                $alreadyAssignedItemIds[$targetId] = true;

                $externalId = (string) ($row['external_id'] ?? '');
                $existingIds = $existingItemIdsByExternalId[$externalId] ?? [];
                $existingIds = array_values(array_unique(array_map('intval', $existingIds)));
                $otherIds = array_values(array_filter($existingIds, fn($v) => (int) $v !== $targetId));
                if (!empty($otherIds)) {
                    $errors[] = 'Row ' . (int) $row['rowIndex'] . ": external_id '{$externalId}' is already used by another item (ID(s): " . implode(',', array_slice($otherIds, 0, 5)) . ').';
                    continue;
                }

                $targetIdByRowIndex[(int) $row['rowIndex']] = $targetId;
            }
        }

        if (!empty($errors)) {
            return [
                'updated' => 0,
                'errors' => array_values($errors),
                'warnings' => array_values($warnings),
                'not_found' => array_values($notFound),
            ];
        }

        DB::transaction(function () use ($parsedRows, $targetIdByRowIndex, $now, &$updated) {
            foreach ($parsedRows as $row) {
                $rowIndex = (int) $row['rowIndex'];
                $targetId = (int) ($targetIdByRowIndex[$rowIndex] ?? 0);
                if ($targetId <= 0) {
                    continue;
                }

                $payload = [
                    'external_id' => (string) ($row['external_id'] ?? ''),
                    'lot_serial' => (string) ($row['lot_serial'] ?? ''),
                    'updated_at' => $now,
                ];

                $affected = JewelryItem::query()
                    ->where('id', $targetId)
                    ->update($payload);

                $updated += (int) $affected;
            }
        });

        return [
            'updated' => (int) $updated,
            'errors' => array_values($errors),
            'warnings' => array_values($warnings),
            'not_found' => array_values($notFound),
        ];
    }

    /**
     * Updates existing items by barcode.
     *
     * - Only rows with a non-empty barcode are considered.
     * - Only provided (non-null) columns are updated.
     * - If $branchId is provided, updates are limited to that branch.
     *
     * @return array{updated:int,errors:array<int,string>,warnings:array<int,string>,not_found:array<int,string>}
     */
    public function updateExistingByBarcode(string $path, ?int $branchId = null): array
    {
        $errors = [];
        $warnings = [];
        $notFound = [];

        $rows = SimpleExcelReader::create($path)->getRows();

        /** @var array<string,array{rowIndexes:array<int,int>,payload:array<string,mixed>}> $updatesByBarcode */
        $updatesByBarcode = [];
        $rowIndex = 1;
        foreach ($rows as $rawRow) {
            $rowIndex++;
            $r = $this->normalizeExcelRow($rawRow);

            $barcode = trim((string) ($r['barcode'] ?? $r['bar_code'] ?? $r['code'] ?? ''));
            if ($barcode === '') {
                $errors[] = "Row {$rowIndex}: barcode is required.";
                continue;
            }

            $productName = trim((string) ($r['product_name'] ?? $r['product'] ?? $r['name'] ?? ''));
            $quality = trim((string) ($r['quality'] ?? $r['qlty'] ?? $r['q'] ?? ''));

            $totalWeight = $this->parseDecimal($r['total_weight'] ?? $r['totalweight'] ?? $r['weight'] ?? null, 3);
            $goldWeight = $this->parseDecimal($r['gold_weight'] ?? $r['goldweight'] ?? $r['gold'] ?? null, 3);

            $goldsmithDeduction = $this->parseDecimal(
                $r['goldsmith_deduction']
                    ?? ($r['l_gram'] ?? null)
                    ?? ($r['lgram'] ?? null)
                    ?? ($r['ပန်းထိမ်အလျော့တွက်'] ?? null)
                    ?? null,
                3
            );
            $goldsmithLaborFee = $this->parseInt(
                $r['goldsmith_labor_fee']
                    ?? ($r['l_mmk'] ?? null)
                    ?? ($r['lmmk'] ?? null)
                    ?? ($r['ပန်းထိမ်_လက်ခ'] ?? null)
                    ?? ($r['ပန်းထိမ်_လက်ခ'] ?? null)
                    ?? null
            );
            $kyaukWeight = $this->parseDecimal(
                $r['kyauk_weight']
                    ?? ($r['kyauk_gram'] ?? null)
                    ?? ($r['kyaukgram'] ?? null)
                    ?? ($r['ကျောက်ချိန်'] ?? null)
                    ?? null,
                3
            );

            $stonePrice = $this->parseInt($r['stone_price'] ?? ($r['ကျောက်ဖိုး'] ?? null) ?? null);
            $profitLoss = $this->parseDecimal($r['profit_loss'] ?? ($r['အမြတ်အလျော့'] ?? null) ?? null, 2);
            $profitLaborFee = $this->parseInt($r['profit_labor_fee'] ?? ($r['အမြတ်လက်ခ'] ?? null) ?? null);

            $payload = [];
            if ($productName !== '') {
                $payload['product_name'] = $productName;
            }
            if ($quality !== '') {
                $payload['quality'] = $quality;
            }
            if (!is_null($goldWeight)) {
                $payload['gold_weight'] = (float) $goldWeight;
            }
            if (!is_null($totalWeight)) {
                $payload['total_weight'] = (float) $totalWeight;
            }
            if (!is_null($kyaukWeight)) {
                $payload['kyauk_weight'] = (float) $kyaukWeight;
            }
            if (!is_null($goldsmithDeduction)) {
                $payload['goldsmith_deduction'] = (float) $goldsmithDeduction;
            }
            if (!is_null($goldsmithLaborFee)) {
                $payload['goldsmith_labor_fee'] = (int) $goldsmithLaborFee;
            }
            if (!is_null($stonePrice)) {
                $payload['stone_price'] = (int) $stonePrice;
            }
            if (!is_null($profitLoss)) {
                $payload['profit_loss'] = (float) $profitLoss;
            }
            if (!is_null($profitLaborFee)) {
                $payload['profit_labor_fee'] = (int) $profitLaborFee;
            }

            if (empty($payload)) {
                $errors[] = "Row {$rowIndex}: no updatable columns found (besides barcode).";
                continue;
            }

            if (!isset($updatesByBarcode[$barcode])) {
                $updatesByBarcode[$barcode] = ['rowIndexes' => [], 'payload' => []];
            }
            $updatesByBarcode[$barcode]['rowIndexes'][] = (int) $rowIndex;
            // Last row wins for the same barcode.
            $updatesByBarcode[$barcode]['payload'] = $payload;
        }

        if (empty($updatesByBarcode)) {
            return [
                'updated' => 0,
                'errors' => array_values($errors ?: ['No rows found to update.']),
                'warnings' => [],
                'not_found' => [],
            ];
        }

        foreach ($updatesByBarcode as $barcode => $u) {
            if (count($u['rowIndexes']) > 1) {
                $warnings[] = 'Duplicate barcode in file (rows ' . implode(', ', $u['rowIndexes']) . "): {$barcode}. Using last row.";
            }
        }

        $barcodes = array_keys($updatesByBarcode);
        $existingCounts = JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->whereIn('barcode', $barcodes)
            ->select('barcode', DB::raw('COUNT(*) as c'))
            ->groupBy('barcode')
            ->pluck('c', 'barcode');

        $existingSet = [];
        foreach ($existingCounts as $barcode => $c) {
            $existingSet[(string) $barcode] = (int) $c;
        }

        foreach ($barcodes as $barcode) {
            if (!isset($existingSet[$barcode])) {
                $rowsLabel = implode(', ', $updatesByBarcode[$barcode]['rowIndexes'] ?? []);
                $notFound[] = $rowsLabel !== ''
                    ? "Barcode not found (rows {$rowsLabel}): {$barcode}"
                    : "Barcode not found: {$barcode}";
            }
        }

        $now = now();
        $updated = 0;
        DB::transaction(function () use ($updatesByBarcode, $existingSet, $branchId, $now, &$updated, &$warnings) {
            foreach ($updatesByBarcode as $barcode => $u) {
                if (!isset($existingSet[$barcode])) {
                    continue;
                }

                $payload = $u['payload'];
                $payload['updated_at'] = $now;

                $count = JewelryItem::query()
                    ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
                    ->where('barcode', $barcode)
                    ->update($payload);

                $updated += (int) $count;

                $matched = (int) ($existingSet[$barcode] ?? 0);
                if ($matched > 1) {
                    $warnings[] = "Barcode {$barcode} matched {$matched} items; updated all.";
                }
            }
        });

        return [
            'updated' => (int) $updated,
            'errors' => array_values($errors),
            'warnings' => array_values($warnings),
            'not_found' => array_values($notFound),
        ];
    }

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

    private function matchKey(
        string $poReference,
        float $totalWeight,
        float $kyaukWeight,
        float $goldsmithDeduction,
        int $goldsmithLaborFee,
        ?int $stonePriceDb
    ): string {
        $po = strtolower(trim($poReference));
        $stone = is_null($stonePriceDb) ? 'null' : (string) $stonePriceDb;

        return $po
            . '|' . number_format($totalWeight, 3, '.', '')
            . '|' . number_format($kyaukWeight, 3, '.', '')
            . '|' . number_format($goldsmithDeduction, 3, '.', '')
            . '|' . (string) $goldsmithLaborFee
            . '|' . $stone;
    }

    private function matchKeyWithQuality(
        string $poReference,
        string $quality,
        float $totalWeight,
        float $kyaukWeight,
        float $goldsmithDeduction,
        int $goldsmithLaborFee,
        ?int $stonePriceDb
    ): string {
        $po = strtolower(trim($poReference));
        $q = strtolower(trim(preg_replace('/\s+/u', ' ', $quality) ?? $quality));
        $stone = is_null($stonePriceDb) ? 'null' : (string) $stonePriceDb;

        return $po
            . '|' . $q
            . '|' . number_format($totalWeight, 3, '.', '')
            . '|' . number_format($kyaukWeight, 3, '.', '')
            . '|' . number_format($goldsmithDeduction, 3, '.', '')
            . '|' . (string) $goldsmithLaborFee
            . '|' . $stone;
    }

    private function describeExternalMatchBucket(string $baseKey, bool $withQuality): string
    {
        $parts = explode('|', $baseKey);

        if ($withQuality) {
            [$po, $quality, $total, $kyauk, $deduction, $labor, $stone] = array_pad($parts, 7, '');
            return 'PO=' . (string) $po
                . ', Quality=' . (string) $quality
                . ', Total=' . (string) $total
                . ', Kyauk=' . (string) $kyauk
                . ', Deduction=' . (string) $deduction
                . ', LaborFee=' . (string) $labor
                . ', StoneDb=' . (string) $stone;
        }

        [$po, $total, $kyauk, $deduction, $labor, $stone] = array_pad($parts, 6, '');
        return 'PO=' . (string) $po
            . ', Total=' . (string) $total
            . ', Kyauk=' . (string) $kyauk
            . ', Deduction=' . (string) $deduction
            . ', LaborFee=' . (string) $labor
            . ', StoneDb=' . (string) $stone;
    }

    private function mapExternalQualityToDbQuality(string $qualityRaw): string
    {
        $q = trim($qualityRaw);
        if ($q === '') {
            return '';
        }

        $key = strtolower(trim(preg_replace('/\s+/u', ' ', $q) ?? $q));
        $keyNoSpace = str_replace(' ', '', $key);

        $map = [
            '999' => '999 24K',
            '၉၉၉' => '999 24K',
            '၁၄ ပဲရည်' => '၁၄ပဲရည်',
            '၁၅ ပဲရည်' => '၁၅ ပဲရည်',
            '၁၅ ပဲရည်' => '၁၅ပဲရည်',
            'W1 (A Grade)' => '18K GA',
            'W1 (A Grade)' => '18K GAA',
            'W2 (B Grade)' => '18K GB',
            'W2 (B Grade)' => '18K GBB',
            'W3 (C Grade)' => '18K GC',
            'W3 (C Grade)' => '18K GCC',
            'W4 (D Grade)' => '18K GD',
            'W4 (D Grade)' => '18K GDD',
            'W5 (E Grade)' => '18K GE',
            'W6 (F Grade)' => '18K GF',
            'WP (P Grade)' => '18K GP',
            'WP (P Grade)' => '18K GPP',
            'WT (T Grade)' => '18K GT',
            'WU (U Grade)' => '18K GU',
            'W1 (A Grade)' => '18K Mold',
            '22K' => '22K',
            '999 24K' => '999 24K',
            '၁၅ ပဲရည်' => 'A',
            'ဒင်္ဂါး' => 'B',
            'C' => 'C',
            'ဒင်္ဂါး' => 'ဒင်္ဂါး',
            'မီးလင်း' => 'မီးလင်း',
        ];

        if (isset($map[$keyNoSpace])) {
            return (string) $map[$keyNoSpace];
        }
        if (isset($map[$key])) {
            return (string) $map[$key];
        }

        return $q;
    }

    private function parseStoneHalfToDbStone($value): ?int
    {
        if ($value instanceof \DateTimeInterface) {
            return null;
        }

        if (is_null($value)) {
            return null;
        }

        if (is_int($value)) {
            // Treat as half-value in MMK; DB stores full value.
            return (int) ($value * 2);
        }

        if (is_float($value)) {
            $dbl = (float) $value * 2.0;
            if (abs($dbl - round($dbl)) > 0.0001) {
                return null;
            }
            return (int) round($dbl);
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        $s = str_replace(',', '', $s);
        if (!is_numeric($s)) {
            return null;
        }

        $dbl = ((float) $s) * 2.0;
        if (abs($dbl - round($dbl)) > 0.0001) {
            return null;
        }

        return (int) round($dbl);
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
