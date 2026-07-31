<?php

namespace App\Services\Kpi;

use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskRule;
use App\Models\Kpi\KpiTaskTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KpiTaskTemplateService
{
    /**
     * Create a new KPI task template with its associated performance rule.
     */
    public function createTemplate(array $data, int $userId): KpiTaskTemplate
    {
        return DB::transaction(function () use ($data, $userId) {
            $slug = $this->makeUniqueSlug($data['title']);

            $template = KpiTaskTemplate::create([
                'kpi_group_id' => $data['kpi_group_id'],
                'created_by_user_id' => $userId,
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'guideline' => $data['guideline'] ?? null,
                'frequency' => $data['frequency'],
                'monthly_required_count' => $data['monthly_required_count'],
                'cutoff_time' => !empty($data['cutoff_time']) ? $data['cutoff_time'] : null,
                'reminder_start_time' => '08:45:00',
                'requires_images' => (bool) ($data['requires_images'] ?? false),
                'requires_table' => (bool) ($data['requires_table'] ?? false),
                'min_images' => (int) ($data['min_images'] ?? 0),
                'max_images' => !empty($data['max_images']) ? (int) $data['max_images'] : null,
                'image_remark_required' => (bool) ($data['image_remark_required'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->savePerformanceRule($template, $data);

            return $template;
        });
    }

    /**
     * Update an existing KPI task template.
     * Protects historical evaluation by blocking rule/group changes if past instances exist.
     * Never re-opens closed instances or duplicates generated tasks.
     *
     * @throws ValidationException
     */
    public function updateTemplate(KpiTaskTemplate|int $template, array $data): KpiTaskTemplate
    {
        $templateModel = $template instanceof KpiTaskTemplate ? $template : KpiTaskTemplate::findOrFail($template);
        $templateModel->load('rule');

        if ($this->isRuleOrGroupChanging($templateModel, $data) && $this->hasPreviousMonthInstances($templateModel)) {
            throw ValidationException::withMessages([
                'templateRuleType' => 'Cannot modify task group or performance rule thresholds because task evaluation records exist from previous months.',
            ]);
        }

        return DB::transaction(function () use ($templateModel, $data) {
            $slug = $this->makeUniqueSlug($data['title'], $templateModel->id);

            $templateModel->update([
                'kpi_group_id' => $data['kpi_group_id'],
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'guideline' => $data['guideline'] ?? null,
                'frequency' => $data['frequency'],
                'monthly_required_count' => $data['monthly_required_count'],
                'cutoff_time' => !empty($data['cutoff_time']) ? $data['cutoff_time'] : null,
                'requires_images' => (bool) ($data['requires_images'] ?? false),
                'requires_table' => (bool) ($data['requires_table'] ?? false),
                'min_images' => (int) ($data['min_images'] ?? 0),
                'max_images' => !empty($data['max_images']) ? (int) $data['max_images'] : null,
                'image_remark_required' => (bool) ($data['image_remark_required'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->savePerformanceRule($templateModel, $data);

            // Safely sync non-breaking parameters to pending current-month task instances
            $this->syncPendingCurrentMonthInstances($templateModel, $data);

            return $templateModel;
        });
    }

    /**
     * Delete a task template if no child task assignments or task instances exist.
     *
     * @throws ValidationException
     */
    public function deleteTemplate(KpiTaskTemplate|int $template): void
    {
        $templateModel = $template instanceof KpiTaskTemplate ? $template : KpiTaskTemplate::findOrFail($template);

        if ($templateModel->taskAssignments()->exists()) {
            throw ValidationException::withMessages([
                'templateDelete' => 'Cannot delete task template because active task assignments exist for this template.',
            ]);
        }

        if (KpiTaskInstance::where('task_template_id', $templateModel->id)->exists()) {
            throw ValidationException::withMessages([
                'templateDelete' => 'Cannot delete task template because generated task instances exist.',
            ]);
        }

        DB::transaction(function () use ($templateModel) {
            $templateModel->rule()->delete();
            $templateModel->delete();
        });
    }

    /**
     * Check if performance rule parameters or KPI group assignments are changing.
     */
    public function isRuleOrGroupChanging(KpiTaskTemplate $template, array $data): bool
    {
        $template->load('rule');

        if (isset($data['kpi_group_id']) && (int) $data['kpi_group_id'] !== (int) $template->kpi_group_id) {
            return true;
        }

        $existingRule = $template->rule;
        if (!$existingRule) {
            return true;
        }

        if (isset($data['rule_type']) && $data['rule_type'] !== $existingRule->rule_type) {
            return true;
        }

        if (array_key_exists('target_percentage', $data) && $data['target_percentage'] !== null && $data['target_percentage'] !== '') {
            if (abs((float) $data['target_percentage'] - (float) $existingRule->target_percentage) > 0.001) {
                return true;
            }
        }

        if (array_key_exists('max_fail_count', $data) && $data['max_fail_count'] !== null && $data['max_fail_count'] !== '') {
            if ((int) $data['max_fail_count'] !== (int) $existingRule->max_fail_count) {
                return true;
            }
        }

        if (array_key_exists('max_cost_amount', $data) && $data['max_cost_amount'] !== null && $data['max_cost_amount'] !== '') {
            if (abs((float) $data['max_cost_amount'] - (float) $existingRule->max_cost_amount) > 0.001) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a task template has task instances generated in previous months (excluding the current month).
     */
    public function hasPreviousMonthInstances(KpiTaskTemplate $template): bool
    {
        $startOfCurrentMonth = now()->startOfMonth()->toDateString();

        return KpiTaskInstance::query()
            ->where('task_template_id', $template->id)
            ->where(function ($query) use ($startOfCurrentMonth) {
                $query->where('task_date', '<', $startOfCurrentMonth)
                    ->orWhere('period_start', '<', $startOfCurrentMonth)
                    ->orWhere(function ($q) use ($startOfCurrentMonth) {
                        $q->whereNull('task_date')
                            ->whereNull('period_start')
                            ->where('created_at', '<', $startOfCurrentMonth);
                    });
            })
            ->exists();
    }

    /**
     * Safely update pending current-month task instances (e.g. cutoff time due_at).
     * Never touches closed, submitted, or finalized task instances.
     */
    protected function syncPendingCurrentMonthInstances(KpiTaskTemplate $template, array $data): void
    {
        if (empty($data['cutoff_time'])) {
            return;
        }

        $startOfCurrentMonth = now()->startOfMonth()->toDateString();

        $pendingInstances = KpiTaskInstance::query()
            ->where('task_template_id', $template->id)
            ->whereNull('submitted_at')
            ->whereNull('finalized_at')
            ->where('task_date', '>=', $startOfCurrentMonth)
            ->get();

        foreach ($pendingInstances as $instance) {
            if ($instance->task_date) {
                $newDueAt = Carbon::parse($instance->task_date->toDateString() . ' ' . $data['cutoff_time']);
                $instance->update(['due_at' => $newDueAt]);
            }
        }
    }

    /**
     * Save or update task performance rule for the given template.
     */
    protected function savePerformanceRule(KpiTaskTemplate $template, array $data): void
    {
        $ruleType = $data['rule_type'] ?? KpiTaskRule::TYPE_PASS_PERCENTAGE;

        $targetPct = array_key_exists('target_percentage', $data) && $data['target_percentage'] !== null && $data['target_percentage'] !== ''
            ? (float) $data['target_percentage']
            : null;

        $maxFail = array_key_exists('max_fail_count', $data) && $data['max_fail_count'] !== null && $data['max_fail_count'] !== ''
            ? (int) $data['max_fail_count']
            : null;

        $maxCost = array_key_exists('max_cost_amount', $data) && $data['max_cost_amount'] !== null && $data['max_cost_amount'] !== ''
            ? (float) $data['max_cost_amount']
            : null;

        $template->rule()->updateOrCreate(
            ['task_template_id' => $template->id],
            [
                'rule_type' => $ruleType,
                'target_percentage' => $targetPct,
                'max_fail_count' => $maxFail,
                'max_cost_amount' => $maxCost,
            ]
        );

        $template->load('rule');
    }

    /**
     * Generate a unique slug for the task template.
     */
    protected function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (
            KpiTaskTemplate::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
