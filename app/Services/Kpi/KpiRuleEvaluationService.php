<?php

namespace App\Services\Kpi;

use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskRule;

class KpiRuleEvaluationService
{
    public function evaluateGroup(KpiGroup $group, array $metrics): array
    {
        return $this->evaluateRule(
            $group->rule_type,
            $metrics,
            $group->target_percentage,
            $group->max_fail_count,
            $group->max_cost_amount
        );
    }

    public function evaluateTemplate(?KpiTaskRule $rule, array $metrics): array
    {
        if (!$rule) {
            return $this->notSetResult();
        }

        return $this->evaluateRule(
            $rule->rule_type,
            $metrics,
            $rule->target_percentage,
            $rule->max_fail_count,
            $rule->max_cost_amount
        );
    }

    public function evaluateRule(
        ?string $ruleType,
        array $metrics,
        ?float $targetPercentage = null,
        ?int $maxFailCount = null,
        ?float $maxCostAmount = null
    ): array {
        if (!$ruleType) {
            return $this->notSetResult();
        }

        return match ($ruleType) {
            KpiTaskRule::TYPE_FAIL_COUNT => $this->evaluateFailCount(
                (int) ($metrics['failed_count'] ?? 0),
                $maxFailCount
            ),
            KpiTaskRule::TYPE_SPEND_COST_LTE => $this->evaluateSpendCost(
                (float) ($metrics['total_spend_cost'] ?? 0),
                $maxCostAmount
            ),
            default => $this->evaluatePassPercentage(
                (float) ($metrics['pass_rate'] ?? 0),
                $targetPercentage
            ),
        } + ['rule_type' => $ruleType];
    }

    protected function notSetResult(): array
    {
        return [
            'rule_type' => null,
            'target_value' => null,
            'actual_value' => null,
            'target_display' => '-',
            'actual_display' => '-',
            'passes_rule' => null,
            'summary' => 'Not set',
        ];
    }

    protected function evaluatePassPercentage(float $actual, ?float $target): array
    {
        $target = $target ?? 0;

        return [
            'target_value' => $target,
            'actual_value' => $actual,
            'target_display' => number_format($target, 2) . '%',
            'actual_display' => number_format($actual, 2) . '%',
            'passes_rule' => $actual >= $target,
            'summary' => number_format($actual, 2) . '% / ' . number_format($target, 2) . '%',
        ];
    }

    protected function evaluateFailCount(int $actual, ?int $target): array
    {
        $target = $target ?? 0;

        return [
            'target_value' => $target,
            'actual_value' => $actual,
            'target_display' => '<= ' . $target,
            'actual_display' => (string) $actual,
            'passes_rule' => $actual <= $target,
            'summary' => $actual . ' / <= ' . $target,
        ];
    }

    protected function evaluateSpendCost(float $actual, ?float $target): array
    {
        $target = $target ?? 0;

        return [
            'target_value' => $target,
            'actual_value' => $actual,
            'target_display' => '<= ' . number_format($target, 2),
            'actual_display' => number_format($actual, 2),
            'passes_rule' => $actual <= $target,
            'summary' => number_format($actual, 2) . ' / <= ' . number_format($target, 2),
        ];
    }
}
