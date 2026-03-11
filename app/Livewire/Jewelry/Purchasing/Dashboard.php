<?php

namespace App\Livewire\Jewelry\Purchasing;

use App\Models\Branch;
use App\Models\GroupNumber;
use App\Models\ItemCategory;
use App\Models\JewelryItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Jewelry Purchasing Dashboard')]
class Dashboard extends Component
{
    public ?int $branchId = null;
    /** @var array<int,array{id:int,name:string}> */
    public array $branches = [];

    public ?int $summaryCategoryId = null;

    /** @var array<int,array{id:int,name:string}> */
    public array $categories = [];

    public array $purchaseStatusChart = ['labels' => [], 'series' => []];
    public array $registerStatusChart = ['labels' => [], 'series' => []];

    public array $today = [];
    public array $predictive = [];

    /** @var array{group_id?:int,group_number?:string,user_id?:int,user_name?:string,mins?:int,grade_value?:int,grade_label?:string,items_count?:int,registered_count?:int} */
    public array $dailySkillWinner = [];

    /** @var array<int,array{user_id:int,user_name:string,registered_count:int}> */
    public array $dailyTopRegistrars = [];

    /**
     * Bootcamp results for today + previous 3 days.
     *
     * @var array<int,array{date:string,is_today:bool,skill_winner:array,top_registrars:array}>
     */
    public array $dailyBootcampHistory = [];

    /** @var array<int,array{product_name:string,total_count:int,total_gram:float,registered_count:int}> */
    public array $itemRegisterSummary = [];

    /** @var array<int,array{category_id:int|null,category_name:string,total_count:int,total_gram:float,purchased_groups_count:int,purchased_batches_count:int,purchased_count:int,registered_count:int}> */
    public array $categoryRegisterSummary = [];

    /** @var array<int,array{product_name:string,total_count:int,total_gram:float,registered_count:int}> */
    public array $productRegisterSummary = [];

    /** @var array{purchased_groups:int,purchased_batches:int} */
    public array $purchaseSummaryTotals = ['purchased_groups' => 0, 'purchased_batches' => 0];

    public function mount(): void
    {
        $this->branches = Branch::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => ['id' => (int) $b->id, 'name' => (string) $b->name])
            ->all();

        $this->refreshStats();
    }

    public function updatedBranchId($value): void
    {
        $this->branchId = $value ? (int) $value : null;
        $this->refreshStats();
    }

    public function updatedSummaryCategoryId($value): void
    {
        $this->summaryCategoryId = $value !== '' && !is_null($value) ? (int) $value : null;
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $branchId = $this->branchId;

        $groupQuery = GroupNumber::query();
        if (!is_null($branchId)) {
            $groupQuery->whereExists(function ($q) use ($branchId) {
                $q->selectRaw('1')
                    ->from('jewelry_items')
                    ->whereColumn('jewelry_items.group_number_id', 'group_numbers.id')
                    ->where('jewelry_items.branch_id', (int) $branchId);
            });
        }

        $purchaseStatuses = GroupNumber::query()
            ->select('purchase_status', DB::raw('COUNT(*) as c'))
            ->groupBy('purchase_status')
            ->pluck('c', 'purchase_status')
            ->all();

        if (!is_null($branchId)) {
            $purchaseStatuses = (clone $groupQuery)
                ->select('purchase_status', DB::raw('COUNT(*) as c'))
                ->groupBy('purchase_status')
                ->pluck('c', 'purchase_status')
                ->all();
        }

        $purchaseLabels = ['not_started', 'processing', 'done'];
        $this->purchaseStatusChart = [
            'labels' => $purchaseLabels,
            'series' => array_map(fn($k) => (int) ($purchaseStatuses[$k] ?? 0), $purchaseLabels),
        ];

        $registered = (int) JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->where('is_register', true)
            ->count();
        $notRegistered = (int) JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->where('is_register', false)
            ->count();
        $this->registerStatusChart = [
            'labels' => ['registered', 'not_registered'],
            'series' => [$registered, $notRegistered],
        ];

        $todayDate = Carbon::today();
        $finishedGroupIdsToday = (clone $groupQuery)
            ->whereNotNull('finished_at')
            ->whereDate('finished_at', $todayDate)
            ->pluck('id')
            ->all();

        $finishedGroupsToday = count($finishedGroupIdsToday);
        $finishedItemsToday = $finishedGroupsToday
            ? (int) JewelryItem::query()
                ->whereIn('group_number_id', $finishedGroupIdsToday)
                ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
                ->count()
            : 0;

        $groupsCreatedToday = (int) (clone $groupQuery)->whereDate('created_at', $todayDate)->count();
        $purchaseCompletionRate = $groupsCreatedToday > 0 ? round(($finishedGroupsToday / $groupsCreatedToday) * 100, 1) : null;

        $registeredItemsToday = (int) JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->where('is_register', true)
            ->whereDate('updated_at', $todayDate)
            ->count();

        $this->today = [
            'finished_groups' => $finishedGroupsToday,
            'finished_items' => $finishedItemsToday,
            'groups_created' => $groupsCreatedToday,
            'purchase_completion_rate' => $purchaseCompletionRate,
            'registered_items' => $registeredItemsToday,
        ];

        $this->dailySkillWinner = $this->buildDailySkillWinner($todayDate, $branchId);
        $this->dailyTopRegistrars = $this->buildDailyTopRegistrars($todayDate, $branchId);

        $this->dailyBootcampHistory = [];
        for ($i = 0; $i < 4; $i++) {
            $d = $todayDate->copy()->subDays($i);
            $this->dailyBootcampHistory[] = [
                'date' => $d->toDateString(),
                'is_today' => $i === 0,
                'skill_winner' => $this->buildDailySkillWinner($d, $branchId),
                'top_registrars' => $this->buildDailyTopRegistrars($d, $branchId),
            ];
        }

        $this->predictive = $this->buildPredictive();

        $this->categories = ItemCategory::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => (int) $c->id, 'name' => (string) $c->name])
            ->all();

        $this->categoryRegisterSummary = $this->buildCategoryRegisterSummary($branchId);
        $this->productRegisterSummary = $this->buildProductRegisterSummary($branchId, $this->summaryCategoryId);
        $this->purchaseSummaryTotals = $this->buildPurchaseSummaryTotals($branchId, $this->summaryCategoryId);

        $this->itemRegisterSummary = JewelryItem::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->select(
                'product_name',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(total_weight) as total_gram'),
                DB::raw('SUM(CASE WHEN is_register = 1 THEN 1 ELSE 0 END) as registered_count')
            )
            ->groupBy('product_name')
            ->orderBy('product_name')
            ->get()
            ->map(fn($r) => [
                'product_name' => (string) ($r->product_name ?? ''),
                'total_count' => (int) ($r->total_count ?? 0),
                'total_gram' => (float) ($r->total_gram ?? 0),
                'registered_count' => (int) ($r->registered_count ?? 0),
            ])
            ->all();

        $this->dispatch('jewelry-purchase-status-chart-updated', chart: $this->purchaseStatusChart);
        $this->dispatch('jewelry-register-status-chart-updated', chart: $this->registerStatusChart);
    }

    private function buildPurchaseSummaryTotals(?int $branchId, ?int $categoryId): array
    {
        $purchasedGroups = (int) JewelryItem::query()
            ->join('group_numbers', 'group_numbers.id', '=', 'jewelry_items.group_number_id')
            ->where('group_numbers.purchase_status', 'done')
            ->when(!is_null($branchId), fn($q) => $q->where('jewelry_items.branch_id', (int) $branchId))
            ->when(!is_null($categoryId), fn($q) => $q->where('jewelry_items.item_category_id', (int) $categoryId))
            ->selectRaw('COUNT(DISTINCT jewelry_items.group_number_id) as c')
            ->value('c');

        $purchasedBatches = (int) JewelryItem::query()
            ->join('group_numbers', 'group_numbers.id', '=', 'jewelry_items.group_number_id')
            ->where('group_numbers.purchase_status', 'done')
            ->whereNotNull('jewelry_items.batch_id')
            ->when(!is_null($branchId), fn($q) => $q->where('jewelry_items.branch_id', (int) $branchId))
            ->when(!is_null($categoryId), fn($q) => $q->where('jewelry_items.item_category_id', (int) $categoryId))
            ->selectRaw("COUNT(DISTINCT CONCAT(jewelry_items.group_number_id, '-', jewelry_items.batch_id)) as c")
            ->value('c');

        return [
            'purchased_groups' => $purchasedGroups,
            'purchased_batches' => $purchasedBatches,
        ];
    }

    private function buildCategoryRegisterSummary(?int $branchId): array
    {
        return JewelryItem::query()
            ->leftJoin('item_categories', 'item_categories.id', '=', 'jewelry_items.item_category_id')
            ->join('group_numbers', 'group_numbers.id', '=', 'jewelry_items.group_number_id')
            ->when(!is_null($branchId), fn($q) => $q->where('jewelry_items.branch_id', (int) $branchId))
            ->select(
                'jewelry_items.item_category_id as category_id',
                'item_categories.name as category_name',
                DB::raw('COUNT(jewelry_items.id) as total_count'),
                DB::raw('SUM(jewelry_items.total_weight) as total_gram'),
                DB::raw("COUNT(DISTINCT CASE WHEN group_numbers.purchase_status = 'done' THEN jewelry_items.group_number_id ELSE NULL END) as purchased_groups_count"),
                DB::raw("COUNT(DISTINCT CASE WHEN group_numbers.purchase_status = 'done' AND jewelry_items.batch_id IS NOT NULL THEN CONCAT(jewelry_items.group_number_id, '-', jewelry_items.batch_id) ELSE NULL END) as purchased_batches_count"),
                DB::raw("SUM(CASE WHEN group_numbers.purchase_status = 'done' THEN 1 ELSE 0 END) as purchased_count"),
                DB::raw('SUM(CASE WHEN jewelry_items.is_register = 1 THEN 1 ELSE 0 END) as registered_count'),
            )
            ->groupBy('jewelry_items.item_category_id', 'item_categories.name')
            ->orderByRaw('item_categories.name IS NULL')
            ->orderBy('item_categories.name')
            ->get()
            ->map(fn($r) => [
                'category_id' => is_null($r->category_id) ? null : (int) $r->category_id,
                'category_name' => (string) ($r->category_name ?? 'Uncategorized'),
                'total_count' => (int) ($r->total_count ?? 0),
                'total_gram' => (float) ($r->total_gram ?? 0),
                'purchased_groups_count' => (int) ($r->purchased_groups_count ?? 0),
                'purchased_batches_count' => (int) ($r->purchased_batches_count ?? 0),
                'purchased_count' => (int) ($r->purchased_count ?? 0),
                'registered_count' => (int) ($r->registered_count ?? 0),
            ])
            ->all();
    }

    private function buildProductRegisterSummary(?int $branchId, ?int $categoryId): array
    {
        return JewelryItem::query()
            ->join('group_numbers', 'group_numbers.id', '=', 'jewelry_items.group_number_id')
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->when(!is_null($categoryId), fn($q) => $q->where('item_category_id', (int) $categoryId))
            ->where('product_name', '!=', '')
            ->select(
                'product_name',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(total_weight) as total_gram'),
                DB::raw("SUM(CASE WHEN group_numbers.purchase_status = 'done' THEN 1 ELSE 0 END) as purchased_count"),
                DB::raw('SUM(CASE WHEN is_register = 1 THEN 1 ELSE 0 END) as registered_count')
            )
            ->groupBy('product_name')
            ->orderBy('product_name')
            ->get()
            ->map(fn($r) => [
                'product_name' => (string) ($r->product_name ?? ''),
                'total_count' => (int) ($r->total_count ?? 0),
                'total_gram' => (float) ($r->total_gram ?? 0),
                'purchased_count' => (int) ($r->purchased_count ?? 0),
                'registered_count' => (int) ($r->registered_count ?? 0),
            ])
            ->all();
    }

    private function buildDailySkillWinner(Carbon $todayDate, ?int $branchId): array
    {
        $groupQuery = GroupNumber::query();
        if (!is_null($branchId)) {
            $groupQuery->whereExists(function ($q) use ($branchId) {
                $q->selectRaw('1')
                    ->from('jewelry_items')
                    ->whereColumn('jewelry_items.group_number_id', 'group_numbers.id')
                    ->where('jewelry_items.branch_id', (int) $branchId);
            });
        }

        $winner = (clone $groupQuery)
            ->with('purchaseBy')
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->whereDate('finished_at', $todayDate)
            ->select([
                'group_numbers.id',
                'group_numbers.number',
                'group_numbers.purchase_by',
                'group_numbers.started_at',
                'group_numbers.finished_at',
            ])
            ->selectRaw('TIMESTAMPDIFF(MINUTE, started_at, finished_at) as mins')
            ->selectRaw('CASE
                WHEN TIMESTAMPDIFF(MINUTE, started_at, finished_at) <= 10 THEN 1
                WHEN TIMESTAMPDIFF(MINUTE, started_at, finished_at) <= 13 THEN 2
                ELSE 3
            END as grade_value')
            ->orderBy('grade_value')
            ->orderBy('mins')
            ->orderBy('finished_at')
            ->first();

        if (!$winner) {
            return [];
        }

        $gradeValue = (int) ($winner->grade_value ?? 0);
        $gradeLabel = match ($gradeValue) {
            1 => 'Excellent',
            2 => 'Good',
            3 => 'Fighting',
            default => null,
        };

        $itemsCount = (int) JewelryItem::query()
            ->where('group_number_id', (int) $winner->id)
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->count();

        $registeredCount = (int) JewelryItem::query()
            ->where('group_number_id', (int) $winner->id)
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->where('is_register', true)
            ->count();

        return [
            'group_id' => (int) $winner->id,
            'group_number' => (string) ($winner->number ?? ''),
            'user_id' => (int) ($winner->purchase_by ?? 0),
            'user_name' => (string) ($winner->purchaseBy?->name ?? '—'),
            'mins' => is_null($winner->mins) ? null : (int) $winner->mins,
            'grade_value' => $gradeValue,
            'grade_label' => $gradeLabel,
            'items_count' => $itemsCount,
            'registered_count' => $registeredCount,
        ];
    }

    /**
     * Daily top 3 by register count (based on jewelry_items.updated_at).
     */
    private function buildDailyTopRegistrars(Carbon $todayDate, ?int $branchId): array
    {
        $rows = JewelryItem::query()
            ->join('users', 'users.id', '=', 'jewelry_items.register_by_id')
            ->where('jewelry_items.is_register', true)
            ->whereNotNull('jewelry_items.register_by_id')
            ->whereDate('jewelry_items.updated_at', $todayDate)
            ->when(!is_null($branchId), fn($q) => $q->where('jewelry_items.branch_id', (int) $branchId))
            ->groupBy('jewelry_items.register_by_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(3)
            ->get([
                'jewelry_items.register_by_id as user_id',
                'users.name as user_name',
                DB::raw('COUNT(*) as registered_count'),
            ]);

        return $rows
            ->map(fn($r) => [
                'user_id' => (int) ($r->user_id ?? 0),
                'user_name' => (string) ($r->user_name ?? '—'),
                'registered_count' => (int) ($r->registered_count ?? 0),
            ])
            ->all();
    }

    private function buildPredictive(): array
    {
        $days = 7;
        $start = Carbon::today()->subDays($days - 1);
        $end = Carbon::today();

        $branchId = $this->branchId;

        $groupQuery = GroupNumber::query();
        if (!is_null($branchId)) {
            $groupQuery->whereExists(function ($q) use ($branchId) {
                $q->selectRaw('1')
                    ->from('jewelry_items')
                    ->whereColumn('jewelry_items.group_number_id', 'group_numbers.id')
                    ->where('jewelry_items.branch_id', (int) $branchId);
            });
        }

        $finishedByDay = (clone $groupQuery)
            ->select(DB::raw('DATE(finished_at) as d'), DB::raw('COUNT(*) as group_count'))
            ->whereNotNull('finished_at')
            ->whereBetween('finished_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy(DB::raw('DATE(finished_at)'))
            ->pluck('group_count', 'd')
            ->all();

        $finishedItemByDay = JewelryItem::query()
            ->select(DB::raw('DATE(group_numbers.finished_at) as d'), DB::raw('COUNT(jewelry_items.id) as items'))
            ->join('group_numbers', 'group_numbers.id', '=', 'jewelry_items.group_number_id')
            ->whereNotNull('group_numbers.finished_at')
            ->whereBetween('group_numbers.finished_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when(!is_null($branchId), fn($q) => $q->where('jewelry_items.branch_id', (int) $branchId))
            ->groupBy(DB::raw('DATE(group_numbers.finished_at)'))
            ->pluck('items', 'd')
            ->all();

        $regByDay = JewelryItem::query()
            ->select(DB::raw('DATE(updated_at) as d'), DB::raw('COUNT(*) as items'))
            ->where('is_register', true)
            ->whereBetween('updated_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->pluck('items', 'd')
            ->all();

        $sumPurchaseItems = 0;
        $sumRegItems = 0;
        for ($i = 0; $i < $days; $i++) {
            $d = $start->copy()->addDays($i)->format('Y-m-d');
            $sumPurchaseItems += (int) ($finishedItemByDay[$d] ?? 0);
            $sumRegItems += (int) ($regByDay[$d] ?? 0);
        }

        $avgPurchasePerDay = $days > 0 ? round($sumPurchaseItems / $days, 2) : 0.0;
        $avgRegisterPerDay = $days > 0 ? round($sumRegItems / $days, 2) : 0.0;

        $remainingPurchaseItems = (int) JewelryItem::query()
            ->join('group_numbers', 'group_numbers.id', '=', 'jewelry_items.group_number_id')
            ->where('group_numbers.purchase_status', '!=', 'done')
            ->when(!is_null($branchId), fn($q) => $q->where('jewelry_items.branch_id', (int) $branchId))
            ->count();

        $remainingRegisterItems = (int) JewelryItem::query()
            ->where('is_register', false)
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', (int) $branchId))
            ->count();

        $daysToClearPurchase = $avgPurchasePerDay > 0 ? (int) ceil($remainingPurchaseItems / $avgPurchasePerDay) : null;
        $daysToClearRegister = $avgRegisterPerDay > 0 ? (int) ceil($remainingRegisterItems / $avgRegisterPerDay) : null;

        return [
            'window_days' => $days,
            'avg_purchase_items_per_day' => $avgPurchasePerDay,
            'avg_register_items_per_day' => $avgRegisterPerDay,
            'remaining_purchase_items' => $remainingPurchaseItems,
            'remaining_register_items' => $remainingRegisterItems,
            'days_to_clear_purchase' => $daysToClearPurchase,
            'days_to_clear_register' => $daysToClearRegister,
        ];
    }

    public function render()
    {
        return view('livewire.jewelry.purchasing.dashboard');
    }
}
