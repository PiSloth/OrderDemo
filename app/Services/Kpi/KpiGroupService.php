<?php

namespace App\Services\Kpi;

use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskInstance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class KpiGroupService
{
    /**
     * Retrieve all KPI groups with department relationship and task template counts.
     *
     * @return Collection<int, KpiGroup>
     */
    public function getAllGroups(): Collection
    {
        return KpiGroup::query()
            ->with('department')
            ->withCount('taskTemplates')
            ->orderByRaw('CAST(name AS UNSIGNED) ASC')
            ->get();
    }

    /**
     * Retrieve paginated KPI groups with department relationship and task template counts.
     */
    public function getPaginatedGroups(int $perPage = 6)
    {
        return KpiGroup::query()
            ->with('department')
            ->withCount('taskTemplates')
            ->orderByRaw('CAST(name AS UNSIGNED) ASC')
            ->paginate($perPage, ['*'], 'groupsPage');
    }

    /**
     * Find a KPI group by ID or fail.
     */
    public function findGroup(int $groupId): KpiGroup
    {
        return KpiGroup::findOrFail($groupId);
    }

    /**
     * Create a new KPI group.
     */
    public function createGroup(array $data): KpiGroup
    {
        return KpiGroup::create($data);
    }

    /**
     * Update an existing KPI group.
     * Disallows changing rule type or percentage/threshold parameters if task instances exist from previous months.
     *
     * @throws ValidationException
     */
    public function updateGroup(KpiGroup|int $group, array $data): KpiGroup
    {
        $groupModel = $group instanceof KpiGroup ? $group : $this->findGroup($group);

        if ($this->isRuleConfigurationChanging($groupModel, $data) && $this->hasPreviousMonthInstances($groupModel)) {
            throw ValidationException::withMessages([
                'groupRuleType' => 'Cannot modify rule type, percentage, or target thresholds because task records exist from previous months.',
            ]);
        }

        $groupModel->update($data);

        return $groupModel;
    }

    /**
     * Delete a KPI group if it has no associated child task templates or task instances.
     *
     * @throws ValidationException
     */
    public function deleteGroup(KpiGroup|int $group): void
    {
        $groupModel = $group instanceof KpiGroup ? $group : $this->findGroup($group);

        if ($groupModel->taskTemplates()->exists()) {
            throw ValidationException::withMessages([
                'groupDelete' => 'Cannot delete KPI group because child task templates exist. Delete or reassign templates first.',
            ]);
        }

        if ($groupModel->taskInstances()->exists()) {
            throw ValidationException::withMessages([
                'groupDelete' => 'Cannot delete KPI group because generated task instances exist.',
            ]);
        }

        $groupModel->delete();
    }

    /**
     * Check if any rule-defining configuration parameter is changing.
     */
    public function isRuleConfigurationChanging(KpiGroup $group, array $data): bool
    {
        if (isset($data['rule_type']) && $data['rule_type'] !== $group->rule_type) {
            return true;
        }

        if (array_key_exists('target_percentage', $data) && $data['target_percentage'] !== null) {
            if ((float) $data['target_percentage'] !== (float) $group->target_percentage) {
                return true;
            }
        }

        if (array_key_exists('max_fail_count', $data) && $data['max_fail_count'] !== null) {
            if ((int) $data['max_fail_count'] !== (int) $group->max_fail_count) {
                return true;
            }
        }

        if (array_key_exists('max_cost_amount', $data) && $data['max_cost_amount'] !== null) {
            if ((float) $data['max_cost_amount'] !== (float) $group->max_cost_amount) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a KPI group has task instances generated in previous months (excluding the current month).
     */
    public function hasPreviousMonthInstances(KpiGroup $group): bool
    {
        $startOfCurrentMonth = now()->startOfMonth()->toDateString();

        return KpiTaskInstance::query()
            ->where('kpi_group_id', $group->id)
            ->where(function ($query) use ($startOfCurrentMonth) {
                $query->where('task_date', '<', $startOfCurrentMonth)
                    ->orWhere(function ($q) use ($startOfCurrentMonth) {
                        $q->whereNull('task_date')
                            ->where('created_at', '<', $startOfCurrentMonth);
                    });
            })
            ->exists();
    }
}
