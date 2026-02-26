<?php

namespace App\Livewire\Jewelry\Purchasing;

use App\Models\Branch;
use App\Models\GroupNumber;
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

    public array $purchaseStatusChart = ['labels' => [], 'series' => []];
    public array $registerStatusChart = ['labels' => [], 'series' => []];

    public array $today = [];
    public array $predictive = [];

    /** @var array<int,array{product_name:string,total_count:int,total_gram:float,registered_count:int}> */
    public array $itemRegisterSummary = [];

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

        $this->predictive = $this->buildPredictive();

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
