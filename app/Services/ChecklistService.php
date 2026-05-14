<?php

namespace App\Services;

use App\Models\BranchChecklist;
use App\Models\BranchChecklistHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ChecklistService
{
    public function generateForToday(User $user): int
    {
        $today = Carbon::today();
        $branchId = $user->branch_id;
        $departmentId = $user->department_id;
        $locationId = $user->location_id;

        $templates = BranchChecklist::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($branchId) {
                $query->whereNull('branch_id');

                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->where(function (Builder $query) use ($departmentId) {
                $query->whereNull('department_id');

                if ($departmentId) {
                    $query->orWhere('department_id', $departmentId);
                }
            })
            ->where(function (Builder $query) use ($locationId) {
                $query->whereNull('location_id');

                if ($locationId) {
                    $query->orWhere('location_id', $locationId);
                }
            })
            ->orderBy('id')
            ->get();

        if ($templates->isEmpty()) {
            return 0;
        }

        $existingChecklistIds = BranchChecklistHistory::query()
            ->where('user_id', $user->id)
            ->whereDate('checked_at', $today)
            ->pluck('check_list_id')
            ->all();

        $templatesToGenerate = $templates->reject(
            fn(BranchChecklist $item) => in_array($item->id, $existingChecklistIds, true)
        );

        if ($templatesToGenerate->isEmpty()) {
            return 0;
        }

        $now = now();
        $rows = $templatesToGenerate->map(fn(BranchChecklist $item) => [
            'check_list_id' => $item->id,
            'user_id' => $user->id,
            'branch_id' => $item->branch_id,
            'department_id' => $item->department_id,
            'location_id' => $item->location_id,
            'remark' => null,
            'is_done' => false,
            'checked_at' => $today->toDateString(),
            'created_at' => $now,
        ])->all();

        BranchChecklistHistory::query()->insert($rows);

        return count($rows);
    }

    public function pendingForToday(User $user): Collection
    {
        return BranchChecklistHistory::query()
            ->with('checklist')
            ->where('user_id', $user->id)
            ->whereDate('checked_at', Carbon::today())
            ->where('is_done', false)
            ->orderBy('id')
            ->get();
    }

    public function mark(BranchChecklistHistory $history, bool $isDone, ?string $remark): BranchChecklistHistory
    {
        $history->update([
            'is_done' => $isDone,
            'remark' => $remark,
        ]);

        return $history->refresh();
    }

    public function reportQuery(array $branchIds, ?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        return BranchChecklistHistory::query()
            ->with(['checklist', 'user', 'branch', 'department', 'location'])
            ->when(!empty($branchIds), function (Builder $query) use ($branchIds) {
                // We look for the 'branches' relationship we just defined in the User model
                $query->whereHas('user', function ($q) use ($branchIds) {
                    $q->whereIn('branch_id', $branchIds);
                });
            })
            ->when($from, fn(Builder $query) => $query->whereDate('checked_at', '>=', $from->toDateString()))
            ->when($to, fn(Builder $query) => $query->whereDate('checked_at', '<=', $to->toDateString()))
            ->orderByDesc('checked_at')
            ->orderByDesc('id');
    }
}
