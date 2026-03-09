<?php

namespace App\Livewire\Jewelry\Purchasing\Groups;

use App\Models\BatchNumberAndGroup;
use App\Models\GroupNumber;
use App\Models\GroupNumberComment;
use App\Models\JewelryItem;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\Jewelry\JewelryExcelImportService;
use WireUi\Traits\Actions;

#[Layout('components.layouts.app')]
#[Title('Group Detail')]
class Show extends Component
{
    use WithFileUploads;
    use Actions;

    public GroupNumber $group;

    public string $po_reference = '';
    public $importFile;
    public array $importErrors = [];
    public int $importedCount = 0;

    public string $commentContent = '';

    /** @var array<int,bool> */
    public array $batchPostState = [];

    public function mount(GroupNumber $group): void
    {
        $this->group = $group->load('purchaseBy');
        $this->po_reference = (string) ($this->group->po_reference ?? '');

        $this->syncBatchPostState();
    }

    public function savePoReference(): void
    {
        $validated = $this->validate([
            'po_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $this->group->update([
            'po_reference' => trim((string) ($validated['po_reference'] ?? '')) ?: null,
        ]);

        session()->flash('success', 'PO reference updated.');
    }

    public function start(): void
    {
        if (!$this->group->started_at) {
            $purchaseBy = $this->group->purchase_by ?: auth()->id();
            $this->group->update([
                'started_at' => now(),
                'purchase_status' => 'processing',
                'purchase_by' => $purchaseBy,
            ]);
        }
    }

    private function allBatchesPosted(): bool
    {
        $batchIds = JewelryItem::query()
            ->where('group_number_id', $this->group->id)
            ->selectRaw('COALESCE(batch_id, 0) as batch_id')
            ->distinct()
            ->pluck('batch_id')
            ->map(fn($v) => (int) $v)
            ->values();

        if ($batchIds->isEmpty()) {
            return true;
        }

        $posted = BatchNumberAndGroup::query()
            ->where('group_number_id', $this->group->id)
            ->whereIn('batch_id', $batchIds)
            ->pluck('is_post', 'batch_id');

        foreach ($batchIds as $batchId) {
            if (empty($posted[$batchId])) {
                return false;
            }
        }

        return true;
    }

    public function finish(): void
    {
        $po = trim((string) $this->po_reference);
        if ($po === '') {
            $this->addError('po_reference', 'PO reference is required to finish.');
            $this->notification([
                'icon' => 'error',
                'title' => 'Cannot finish',
                'description' => 'Please enter PO reference before finishing.',
            ]);
            return;
        }

        if (!$this->allBatchesPosted()) {
            $this->notification([
                'icon' => 'error',
                'title' => 'Cannot finish',
                'description' => 'Please post all batches before finishing.',
            ]);
            return;
        }

        if (!$this->group->started_at) {
            $this->group->started_at = now();
        }

        $finishedAt = now();
        $this->group->finished_at = $finishedAt;
        $this->group->purchase_status = 'done';
        $this->group->is_purchase = true;
        $this->group->po_reference = $po;
        $this->group->entry_skill_grade = $this->group->calculatedSkillGrade();
        $this->group->save();
    }

    public function updatedBatchPostState($value, $key): void
    {
        $batchId = (int) $key;
        $isPost = (bool) $value;

        BatchNumberAndGroup::updateOrCreate(
            ['group_number_id' => $this->group->id, 'batch_id' => $batchId],
            ['is_post' => $isPost, 'post_by' => $isPost ? auth()->id() : null]
        );
    }

    public function toggleRegister(int $itemId): void
    {
        $item = JewelryItem::query()
            ->where('group_number_id', $this->group->id)
            ->findOrFail($itemId);

        $new = !$item->is_register;
        $item->update([
            'is_register' => $new,
            'register_by_id' => $new ? auth()->id() : null,
        ]);
    }

    public function registerItem(int $itemId): void
    {
        $item = JewelryItem::query()
            ->where('group_number_id', $this->group->id)
            ->findOrFail($itemId);

        if ((bool) $item->is_register) {
            return;
        }

        $item->update([
            'is_register' => true,
            'register_by_id' => auth()->id(),
        ]);
    }

    public function addComment(): void
    {
        $validated = $this->validate([
            'commentContent' => ['required', 'string', 'max:2000'],
        ]);

        GroupNumberComment::create([
            'group_number_id' => $this->group->id,
            'user_id' => auth()->id(),
            'content' => trim((string) $validated['commentContent']),
        ]);

        $this->commentContent = '';
        session()->flash('success', 'Comment added.');
    }

    public function import(): void
    {
        $this->resetValidation();
        $this->importErrors = [];
        $this->importedCount = 0;

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,csv,ods'],
        ]);

        $path = method_exists($this->importFile, 'getRealPath') ? $this->importFile->getRealPath() : null;
        $path = $path ?: (method_exists($this->importFile, 'getPathname') ? $this->importFile->getPathname() : null);
        if (!$path) {
            $this->addError('importFile', 'Could not read uploaded file.');
            return;
        }

        $service = app(JewelryExcelImportService::class);
        $result = $service->importIntoGroup($this->group, $path, (int) auth()->id());
        if (!empty($result['errors'])) {
            $this->importErrors = $result['errors'];
            $this->notification([
                'icon' => 'error',
                'title' => 'Import failed',
                'description' => $this->importErrors[0] ?? 'Import failed',
            ]);
            return;
        }

        $this->importedCount = (int) ($result['inserted'] ?? 0);
        $this->importFile = null;
        $this->syncBatchPostState();

        $groups = $result['groups'] ?? [];
        $newGroups = array_values(array_filter($groups, fn($g) => !empty($g['is_new'])));
        $primaryInserted = (int) ($result['primary_group_inserted'] ?? $this->importedCount);

        if (count($newGroups) > 0) {
            $numbers = array_map(fn($g) => (string) ($g['number'] ?? ''), $newGroups);
            $numbers = array_values(array_filter($numbers, fn($v) => $v !== ''));
            $suffix = empty($numbers) ? '' : (' New groups: ' . implode(', ', $numbers));
            session()->flash('success', "Imported {$this->importedCount} items across " . count($groups) . " groups. This group received {$primaryInserted}." . $suffix);
        } else {
            session()->flash('success', "Imported {$this->importedCount} items.");
        }
    }

    private function syncBatchPostState(): void
    {
        $links = BatchNumberAndGroup::query()
            ->where('group_number_id', $this->group->id)
            ->get(['batch_id', 'is_post']);

        $state = [];
        foreach ($links as $l) {
            $state[(int) $l->batch_id] = (bool) $l->is_post;
        }
        $this->batchPostState = $state;
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

    private function batchKey(string $productName, string $quality, float $totalWeight, float $lGram, int $lMmk, float $kyaukGram): string
    {
        return strtolower(trim($productName))
            . '|' . strtolower(trim($quality))
            . '|' . number_format($totalWeight, 3, '.', '')
            . '|' . number_format($lGram, 3, '.', '')
            . '|' . (string) $lMmk
            . '|' . number_format($kyaukGram, 3, '.', '');
    }

    public function render()
    {
        $items = JewelryItem::query()
            ->where('group_number_id', $this->group->id)
            ->orderByRaw('batch_id is null')
            ->orderBy('batch_id')
            ->orderBy('id')
            ->get();

        $batchLinks = BatchNumberAndGroup::query()
            ->where('group_number_id', $this->group->id)
            ->get()
            ->keyBy(fn($r) => (int) $r->batch_id);

        $batchSummaries = [];
        $grouped = $items->groupBy(fn($i) => (int) ($i->batch_id ?? 0));
        foreach ($grouped as $batchId => $rows) {
            $batchSummaries[] = [
                'batch_id' => (int) $batchId,
                'count' => (int) $rows->count(),
                'product_name' => (string) ($rows->first()->product_name ?? ''),
                'quality' => (string) ($rows->first()->quality ?? ''),
                'gold_weight' => (float) $rows->sum(fn($r) => (float) $r->gold_weight),
                'total_weight' => (float) $rows->sum(fn($r) => (float) $r->total_weight),
                'goldsmith_deduction' => (float) $rows->sum(fn($r) => (float) $r->goldsmith_deduction),
                'goldsmith_labor_fee' => (int) $rows->sum(fn($r) => (int) $r->goldsmith_labor_fee),
                'kyauk_weight' => (float) $rows->sum(fn($r) => (float) $r->kyauk_weight),
                'stone_price' => (int) $rows->sum(fn($r) => (int) ($r->stone_price ?? 0)),
                'profit_loss' => (float) $rows->sum(fn($r) => (float) ($r->profit_loss ?? 0)),
                'profit_labor_fee' => (int) $rows->sum(fn($r) => (int) ($r->profit_labor_fee ?? 0)),
                'is_post' => (bool) ($batchLinks[$batchId]->is_post ?? false),
            ];
        }

        $totalWeight = (float) $items->sum(fn($r) => (float) $r->total_weight);
        $totalGoldsmithDeduction = (float) $items->sum(fn($r) => (float) $r->goldsmith_deduction);

        $footer = [
            'item_count' => (int) $items->count(),
            'total_weight' => $totalWeight,
            'goldsmith_deduction' => $totalGoldsmithDeduction,
            'total_weight_plus_deduction' => $totalWeight + $totalGoldsmithDeduction,
        ];

        $poOk = trim((string) $this->po_reference) !== '';
        $allPosted = true;
        if ($items->isNotEmpty()) {
            foreach ($batchSummaries as $b) {
                if (empty($b['is_post'])) {
                    $allPosted = false;
                    break;
                }
            }
        }
        $canFinish = $poOk && $allPosted;

        $group = $this->group->fresh(['purchaseBy']);
        $mins = $group->durationMinutes();

        $comments = GroupNumberComment::query()
            ->where('group_number_id', $this->group->id)
            ->with(['user:id,name'])
            ->latest('id')
            ->get();

        return view('livewire.jewelry.purchasing.groups.show', [
            'group' => $group,
            'items' => $items,
            'batchSummaries' => $batchSummaries,
            'footer' => $footer,
            'canFinish' => $canFinish,
            'durationMinutes' => $mins,
            'gradeLabel' => $group->skillGradeLabel(),
            'gradeValue' => $group->calculatedSkillGrade(),
            'comments' => $comments,
        ]);
    }
}
