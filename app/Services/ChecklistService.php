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

        $alreadyGenerated = BranchChecklistHistory::query()
            ->where('user_id', $user->id)
            ->whereDate('checked_at', $today)
            ->exists();

        if ($alreadyGenerated) {
            return 0;
        }

        $templates = BranchChecklist::query()
            ->where('is_active', true)
            ->where('branch_id', $user->branch_id)
            ->orderBy('id')
            ->get();

        if ($templates->isEmpty()) {
            return 0;
        }

        $now = now();
        $rows = $templates->map(fn (BranchChecklist $item) => [
            'check_list_id' => $item->id,
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'department_id' => $user->department_id,
            'location_id' => $user->location_id,
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
                $query->whereIn('branch_id', $branchIds);
            })
            ->when($from, fn (Builder $query) => $query->whereDate('checked_at', '>=', $from->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate('checked_at', '<=', $to->toDateString()))
            ->orderByDesc('checked_at')
            ->orderByDesc('id');
    }
}
