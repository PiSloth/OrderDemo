<?php

namespace App\Services\Kpi;

use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskTemplate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KpiMonthlyAuditService
{
    public function __construct(
        protected KpiAvailabilityService $availability,
        protected KpiRuleEvaluationService $ruleEvaluator,
        protected KpiMonthlySuccessService $monthlySuccessService
    ) {
    }

    /**
     * Builds complete monthly matrix and summary evaluation data for a user.
     */
    public function buildAuditMatrix(int $userId, Carbon $monthStart, Carbon $monthEnd): array
    {
        $days = collect(range(1, $monthEnd->day))
            ->map(fn (int $day) => $monthStart->copy()->day($day)->format('Y-m-d'));

        $instances = $this->queryInstancesForUser($userId, $monthStart, $monthEnd);
        $assignments = $this->resolveAssignments($userId, $monthStart, $monthEnd, $instances);
        $rows = $this->buildRows($userId, $monthStart, $monthEnd, $assignments, $instances);

        $groupSummaries = $this->buildGroupSummaries($rows);
        $groupCards = [
            'passed' => $groupSummaries->where('passes_rule', true)->count(),
            'failed' => $groupSummaries->where('passes_rule', false)->count(),
            'not_set' => $groupSummaries->where('passes_rule', null)->count(),
        ];

        $mustDo = $rows->sum(fn ($r) => (int) ($r['summary']['must_do'] ?? 0));
        $passed = $rows->sum(fn ($r) => (int) ($r['summary']['passed'] ?? 0));
        $percentage = $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0;

        $overall = [
            'must_do_count' => $mustDo,
            'passed_count' => $passed,
            'percentage' => $percentage,
            'kpi_score' => $percentage,
        ];

        return [
            'days' => $days->all(),
            'instances' => $instances,
            'assignments' => $assignments,
            'rows' => $rows,
            'group_summaries' => $groupSummaries,
            'group_cards' => $groupCards,
            'overall' => $overall,
        ];
    }

    public function queryInstancesForUser(int $userId, Carbon $monthStart, Carbon $monthEnd): Collection
    {
        return KpiTaskInstance::query()
            ->with([
                'latestSubmission.images',
                'latestSubmission.submittedBy',
                'latestSubmission.approvalSteps.approver',
                'template.group.department',
                'template.rule',
                'group.department',
                'todoList',
                'assignment.template.group.department',
                'assignment.template.rule',
            ])
            ->where(function (Builder $query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('assignment', fn (Builder $q) => $q->where('user_id', $userId));
            })
            ->where(function (Builder $q) use ($monthStart, $monthEnd) {
                $q->where(function (Builder $sub) use ($monthStart, $monthEnd) {
                    $sub->whereDate('period_start', '<=', $monthEnd->toDateString())
                        ->whereDate('period_end', '>=', $monthStart->toDateString());
                })
                ->orWhere(function (Builder $sub) use ($monthStart, $monthEnd) {
                    $sub->whereDate('task_date', '>=', $monthStart->toDateString())
                        ->whereDate('task_date', '<=', $monthEnd->toDateString());
                })
                ->orWhere(function (Builder $sub) use ($monthStart, $monthEnd) {
                    $sub->whereDate('due_at', '>=', $monthStart->toDateString())
                        ->whereDate('due_at', '<=', $monthEnd->toDateString());
                })
                ->orWhere(function (Builder $sub) use ($monthStart, $monthEnd) {
                    $sub->whereDate('submitted_at', '>=', $monthStart->toDateString())
                        ->whereDate('submitted_at', '<=', $monthEnd->toDateString());
                });
            })
            ->get();
    }

    public function resolveAssignments(int $userId, Carbon $monthStart, Carbon $monthEnd, Collection $instances): Collection
    {
        $activeAssignments = KpiTaskAssignment::query()
            ->with(['template.group.department', 'template.rule'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereHas('template', fn (Builder $query) => $query->where('is_active', true))
            ->where(function (Builder $query) use ($monthEnd): void {
                $query->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $monthEnd->toDateString());
            })
            ->where(function (Builder $query) use ($monthStart): void {
                $query->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $monthStart->toDateString());
            })
            ->get();

        $activeTemplateIds = $activeAssignments->map(fn ($a) => $a->task_template_id ?? $a->template?->id)->filter()->unique()->all();

        $instanceTemplateIds = $instances->map(function (KpiTaskInstance $inst) {
            return $inst->task_template_id
                ?? $inst->template?->id
                ?? $inst->assignment?->task_template_id
                ?? $inst->assignment?->template?->id;
        })->filter()->unique()->values();

        $missingTemplateIds = $instanceTemplateIds->diff($activeTemplateIds)->values();

        $additionalAssignments = collect();
        if ($missingTemplateIds->isNotEmpty()) {
            $existingAssignments = KpiTaskAssignment::query()
                ->with(['template.group.department', 'template.rule'])
                ->where('user_id', $userId)
                ->whereIn('task_template_id', $missingTemplateIds)
                ->get()
                ->keyBy('task_template_id');

            foreach ($missingTemplateIds as $templateId) {
                if ($existingAssignments->has($templateId)) {
                    $additionalAssignments->push($existingAssignments->get($templateId));
                } else {
                    $sampleInstance = $instances->first(function ($inst) use ($templateId) {
                        return ($inst->task_template_id == $templateId)
                            || ($inst->template?->id == $templateId)
                            || ($inst->assignment?->task_template_id == $templateId);
                    });

                    $template = $sampleInstance?->template
                        ?? $sampleInstance?->assignment?->template
                        ?? KpiTaskTemplate::with(['group.department', 'rule'])->find($templateId);

                    if ($template) {
                        $syntheticAssignment = new KpiTaskAssignment([
                            'id' => $sampleInstance?->task_assignment_id,
                            'task_template_id' => $template->id,
                            'user_id' => $userId,
                            'is_active' => (bool) $template->is_active,
                            'starts_on' => null,
                            'ends_on' => null,
                        ]);
                        $syntheticAssignment->setRelation('template', $template);
                        $additionalAssignments->push($syntheticAssignment);
                    }
                }
            }
        }

        $capturedAssignmentIds = $activeAssignments->pluck('id')->merge($additionalAssignments->pluck('id'))->filter()->all();
        $missingAssignmentIds = $instances->pluck('task_assignment_id')->filter()->diff($capturedAssignmentIds)->unique()->values();
        if ($missingAssignmentIds->isNotEmpty()) {
            $extraByAssignment = KpiTaskAssignment::query()
                ->with(['template.group.department', 'template.rule'])
                ->whereIn('id', $missingAssignmentIds)
                ->get();
            $additionalAssignments = $additionalAssignments->merge($extraByAssignment);
        }

        // Guarantee all remaining instances have an assignment entry
        $resolvedTemplateIds = $activeAssignments->concat($additionalAssignments)->map(fn ($a) => $a->task_template_id ?? $a->template?->id)->filter()->all();
        $unhandledInstances = $instances->filter(function ($inst) use ($resolvedTemplateIds) {
            $tId = $inst->task_template_id ?? $inst->template?->id ?? $inst->assignment?->task_template_id ?? $inst->assignment?->template?->id;
            return !$tId || !in_array($tId, $resolvedTemplateIds);
        });

        foreach ($unhandledInstances as $inst) {
            $template = $inst->template ?? $inst->assignment?->template;
            $syntheticAssignment = new KpiTaskAssignment([
                'id' => $inst->task_assignment_id,
                'task_template_id' => $inst->task_template_id ?? $template?->id,
                'user_id' => $userId,
                'is_active' => true,
                'starts_on' => null,
                'ends_on' => null,
            ]);
            if ($template) {
                $syntheticAssignment->setRelation('template', $template);
            }
            $additionalAssignments->push($syntheticAssignment);
        }

        return $activeAssignments->concat($additionalAssignments)
            ->unique(function (KpiTaskAssignment $a) {
                $tId = $a->task_template_id ?? $a->template?->id;
                return $tId ? ('template_' . $tId) : ('assign_' . $a->id);
            })
            ->sortBy(function (KpiTaskAssignment $assignment) use ($instances) {
                $assignmentInstances = $this->filterInstancesForAssignment($instances, $assignment);
                $instanceGroup = $assignmentInstances->first(fn ($inst) => !empty($inst->kpi_group_id) && !empty($inst->group))?->group
                    ?? $assignmentInstances->first(fn ($inst) => !empty($inst->template?->group))?->template?->group
                    ?? $assignmentInstances->first(fn ($inst) => !empty($inst->assignment?->template?->group))?->assignment?->template?->group;
                $groupName = (string) optional($instanceGroup ?? $assignment->template?->group)->name;
                return sprintf('%s|%s', $groupName, (string) optional($assignment->template)->title);
            })
            ->values();
    }

    public function buildRows(int $userId, Carbon $monthStart, Carbon $monthEnd, Collection $assignments, Collection $instances): Collection
    {
        $evaluationEnd = $this->evaluationEnd($monthStart, $monthEnd);
        $holidayMap = $this->availability->holidayMapForUser($userId, $monthStart, $monthEnd);
        $exclusionMaps = $this->availability->exclusionMapsForUser($userId, $monthStart, $monthEnd);
        $daysCarbon = collect(range(1, $monthEnd->day))->map(fn (int $day) => $monthStart->copy()->day($day));

        $rows = $assignments->map(function (KpiTaskAssignment $assignment) use ($instances, $daysCarbon, $holidayMap, $exclusionMaps, $evaluationEnd): array {
            $assignmentInstances = $this->filterInstancesForAssignment($instances, $assignment)->values();

            $instanceGroup = $assignmentInstances->first(fn ($inst) => !empty($inst->kpi_group_id) && !empty($inst->group))?->group
                ?? $assignmentInstances->first(fn ($inst) => !empty($inst->template?->group))?->template?->group
                ?? $assignmentInstances->first(fn ($inst) => !empty($inst->assignment?->template?->group))?->assignment?->template?->group;
            $effectiveGroup = $instanceGroup ?? $assignment->template?->group;

            $template = $assignment->template
                ?? $assignmentInstances->first(fn ($inst) => !empty($inst->template))?->template
                ?? $assignmentInstances->first(fn ($inst) => !empty($inst->assignment?->template))?->assignment?->template;

            $cells = $daysCarbon->map(fn (Carbon $day) => $this->buildCell($assignment, $assignmentInstances, $day, $holidayMap, $exclusionMaps));
            $summary = $this->buildSummary($assignment, $assignmentInstances, $cells, $evaluationEnd);
            $ruleEvaluation = $this->ruleEvaluator->evaluateTemplate($template?->rule, [
                'pass_rate' => (float) ($summary['percentage'] ?? 0),
                'failed_count' => (int) ($summary['failed'] ?? 0),
                'total_spend_cost' => 0,
            ]);

            return [
                'has_instances' => $assignmentInstances->isNotEmpty() || $cells->contains(fn ($c) => !empty($c['markers'])),
                'assignment' => [
                    'id' => $assignment->id,
                    'task_template_id' => $assignment->task_template_id ?? $template?->id,
                    'starts_on' => $assignment->starts_on?->toDateString(),
                    'ends_on' => $assignment->ends_on?->toDateString(),
                    'template' => $template ? [
                        'id' => $template->id,
                        'kpi_group_id' => $effectiveGroup?->id ?? $template->kpi_group_id,
                        'title' => $template->title,
                        'description' => $template->description,
                        'guideline' => $template->guideline,
                        'frequency' => $template->frequency,
                        'monthly_required_count' => (int) $template->monthly_required_count,
                        'cutoff_time' => $template->cutoff_time,
                        'requires_images' => (bool) $template->requires_images,
                        'requires_table' => (bool) $template->requires_table,
                        'min_images' => (int) $template->min_images,
                        'max_images' => $template->max_images,
                        'image_remark_required' => (bool) $template->image_remark_required,
                        'is_active' => (bool) $template->is_active,
                        'rule' => $template->rule ? [
                            'rule_type' => $template->rule->rule_type,
                            'target_percentage' => $template->rule->target_percentage,
                            'max_fail_count' => $template->rule->max_fail_count,
                            'max_cost_amount' => $template->rule->max_cost_amount,
                        ] : null,
                        'group' => $effectiveGroup ? [
                            'id' => $effectiveGroup->id,
                            'name' => $effectiveGroup->name,
                        ] : null,
                    ] : null,
                ],
                'cells' => $cells->all(),
                'summary' => $summary,
                'rule_evaluation' => $ruleEvaluation,
            ];
        });

        // Filter out template rows where user was not assigned with any generated instances in that month
        return $rows->filter(function (array $row) use ($monthStart): bool {
            if (!empty($row['has_instances'])) {
                return true;
            }

            $cells = collect($row['cells'] ?? []);
            $hasInstances = $cells->contains(fn ($c) => !empty($c['markers']));

            if ($hasInstances) {
                return true;
            }

            $mustDo = (int) ($row['summary']['must_do'] ?? 0);
            $hasActiveDailySchedule = $cells->contains(fn ($c) => in_array($c['label'], ['.', 'X'], true));

            $isPastMonth = now()->startOfMonth()->gt($monthStart);
            if ($isPastMonth) {
                return false;
            }

            return ($mustDo > 0 && $cells->contains(fn ($c) => $c['label'] !== '--')) || $hasActiveDailySchedule;
        })->values();
    }

    public function buildGroupSummaries(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (array $row) => (int) ($row['assignment']['template']['group']['id'] ?? 0))
            ->map(function (Collection $groupRows): array {
                $group = $groupRows->first()['assignment']['template']['group'] ?? null;
                $passed = $groupRows->sum(fn (array $row) => (int) ($row['summary']['passed'] ?? 0));
                $failed = $groupRows->sum(fn (array $row) => (int) ($row['summary']['failed'] ?? 0));
                $pending = $groupRows->sum(fn (array $row) => (int) ($row['summary']['pending'] ?? 0));
                $excluded = $groupRows->sum(fn (array $row) => (int) ($row['summary']['excluded'] ?? 0));
                $mustDo = $groupRows->sum(fn (array $row) => (int) ($row['summary']['must_do'] ?? 0));
                $templatePassCount = $groupRows->where('rule_evaluation.passes_rule', true)->count();
                $templateTotalCount = $groupRows->count();
                $allTemplatesPass = $templatePassCount === $templateTotalCount && $templateTotalCount > 0;

                return [
                    'group' => $group,
                    'group_name' => $group['name'] ?? 'No KPI Group',
                    'passed' => $passed,
                    'failed' => $failed,
                    'pending' => $pending,
                    'excluded' => $excluded,
                    'must_do' => $mustDo,
                    'percentage' => $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0,
                    'template_total_count' => $templateTotalCount,
                    'template_pass_count' => $templatePassCount,
                    'all_templates_pass' => $allTemplatesPass,
                ];
            })
            ->map(function (array $summary): array {
                $groupModel = isset($summary['group']['id']) ? KpiGroup::find($summary['group']['id']) : null;
                $groupEvaluation = $groupModel
                    ? $this->ruleEvaluator->evaluateGroup($groupModel, [
                        'pass_rate' => $summary['percentage'],
                        'failed_count' => $summary['failed'],
                        'total_spend_cost' => 0,
                    ])
                    : $this->ruleEvaluator->evaluateRule(null, []);

                return $summary + [
                    'group_rule_evaluation' => $groupEvaluation,
                    'passes_rule' => $groupEvaluation['passes_rule'] === null
                        ? null
                        : ($groupEvaluation['passes_rule'] && $summary['all_templates_pass']),
                ];
            })
            ->sortBy('group_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function buildCertificateGroups(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (array $row) => (int) ($row['assignment']['template']['group']['id'] ?? 0))
            ->values()
            ->map(function (Collection $groupRows, int $index): array {
                $firstRow = $groupRows->first();
                $groupData = $firstRow['assignment']['template']['group'] ?? null;
                $groupId = $groupData['id'] ?? null;
                $groupModel = $groupId ? KpiGroup::find($groupId) : null;

                $templateCount = $groupRows->count();
                $passed = $groupRows->sum(fn ($r) => (int) ($r['summary']['passed'] ?? 0));
                $failed = $groupRows->sum(fn ($r) => (int) ($r['summary']['failed'] ?? 0));
                $mustDo = $groupRows->sum(fn ($r) => (int) ($r['summary']['must_do'] ?? 0));
                $score = $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0;

                $groupEvaluation = $groupModel
                    ? $this->ruleEvaluator->evaluateGroup($groupModel, [
                        'pass_rate' => $score,
                        'failed_count' => $failed,
                        'total_spend_cost' => 0,
                    ])
                    : $this->ruleEvaluator->evaluateRule(null, []);

                $allTemplatePass = $groupRows->every(fn ($r) => ($r['rule_evaluation']['passes_rule'] ?? null) === true);
                $groupPass = $templateCount > 1
                    ? (($groupEvaluation['passes_rule'] === true) && $allTemplatePass)
                    : (($firstRow['rule_evaluation']['passes_rule'] ?? null) === true);

                $templates = $groupRows->map(fn ($r) => [
                    'title' => $r['assignment']['template']['title'] ?? 'Untitled Task',
                    'result' => ($r['rule_evaluation']['passes_rule'] ?? false) ? 'Pass' : 'Fail',
                    'summary' => [
                        'passed_count' => $r['summary']['passed'] ?? 0,
                        'must_do_count' => $r['summary']['must_do'] ?? 0,
                        'late_count' => $r['summary']['failed'] ?? 0,
                        'absent_count' => 0,
                        'score' => $r['summary']['percentage'] ?? 0,
                    ],
                ])->values()->all();

                return [
                    'no' => $index + 1,
                    'group_name' => $groupData['name'] ?? 'No KPI Group',
                    'template_count' => $templateCount,
                    'show_group_result' => $templateCount > 1,
                    'group_rule' => $groupEvaluation,
                    'group_result' => $groupPass ? 'Pass' : 'Fail',
                    'summary' => [
                        'passed_count' => $passed,
                        'must_do_count' => $mustDo,
                        'late_count' => $failed,
                        'absent_count' => 0,
                        'score' => $score,
                    ],
                    'templates' => $templates,
                ];
            });
    }

    public function filterInstancesForAssignment(Collection $instances, KpiTaskAssignment $assignment): Collection
    {
        return $instances->filter(function (KpiTaskInstance $inst) use ($assignment) {
            $tId = $assignment->task_template_id ?? $assignment->template?->id;
            $instTId = $inst->task_template_id ?? $inst->template?->id ?? $inst->assignment?->task_template_id ?? $inst->assignment?->template?->id;
            if ($tId && $instTId && $tId == $instTId) {
                return true;
            }
            if ($assignment->id && $inst->task_assignment_id && $assignment->id == $inst->task_assignment_id) {
                return true;
            }
            return false;
        });
    }

    public function buildCell(
        KpiTaskAssignment $assignment,
        Collection $instances,
        Carbon $day,
        Collection $holidayMap,
        array $exclusionMaps
    ): array {
        $dateKey = $day->toDateString();

        $holiday = $holidayMap->get($dateKey);
        $dayRequest = $exclusionMaps['day'][$dateKey] ?? null;
        $taskRequest = $assignment->id ? ($exclusionMaps['task'][$assignment->id][$dateKey] ?? null) : null;
        if (!$taskRequest && $assignment->task_template_id) {
            foreach ($exclusionMaps['requests'] ?? [] as $req) {
                if ($req->request_type === 'task' && $req->requested_date?->toDateString() === $dateKey && $req->assignment?->task_template_id == $assignment->task_template_id) {
                    $taskRequest = $req;
                    break;
                }
            }
        }

        $isExclusion = (bool) ($holiday || $dayRequest || $taskRequest);

        $markers = $instances
            ->map(fn (KpiTaskInstance $instance) => $this->markerForInstanceOnDate($instance, $dateKey))
            ->filter()
            ->values();

        if ($isExclusion) {
            $approvedMarker = $markers->first(fn ($m) => $m['type'] === 'approved');
            if ($approvedMarker) {
                return [
                    'date' => $dateKey,
                    'markers' => [$approvedMarker],
                    'label' => null,
                    'classes' => $this->defaultCellClasses($assignment, $day, false),
                ];
            }

            return [
                'date' => $dateKey,
                'markers' => [],
                'label' => $holiday?->name ?? ($dayRequest ? 'Day exclusion' : 'Task exclusion'),
                'classes' => 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
            ];
        }

        if ($markers->isNotEmpty()) {
            $hasExcludedOnly = $markers->every(fn ($m) => $m['type'] === 'excluded');
            if ($hasExcludedOnly) {
                return [
                    'date' => $dateKey,
                    'markers' => [],
                    'label' => 'Exclusion',
                    'classes' => 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                ];
            }

            return [
                'date' => $dateKey,
                'markers' => $markers->all(),
                'label' => null,
                'classes' => $this->defaultCellClasses($assignment, $day, false),
            ];
        }

        if (!$this->assignmentIsActiveOnDate($assignment, $day)) {
            return [
                'date' => $dateKey,
                'markers' => [],
                'label' => '--',
                'classes' => 'bg-slate-100 text-slate-400 dark:bg-slate-950 dark:text-slate-600',
            ];
        }

        return [
            'date' => $dateKey,
            'markers' => [],
            'label' => $this->defaultCellLabel($assignment, $day),
            'classes' => $this->defaultCellClasses($assignment, $day, true),
        ];
    }

    public function markerForInstanceOnDate(KpiTaskInstance $instance, string $dateKey): ?array
    {
        $status = (string) $instance->status;
        $latestSubmissionDate = $instance->latestSubmission?->submitted_at?->toDateString()
            ?? $instance->submitted_at?->toDateString();
        $anchorDate = $latestSubmissionDate
            ?? $instance->task_date?->toDateString()
            ?? $instance->due_at?->toDateString()
            ?? $instance->period_end?->toDateString()
            ?? $instance->period_start?->toDateString()
            ?? $instance->created_at?->toDateString();

        if (
            $instance->due_at
            && Carbon::parse($instance->due_at)->lt(now())
            && in_array($status, ['pending', 'rejected'], true)
        ) {
            $overdueDate = Carbon::parse($instance->due_at)->toDateString();

            if ($overdueDate === $dateKey) {
                return [
                    'type' => 'overdue',
                    'label' => 'Overdue',
                    'classes' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                    'instance' => $this->serializeInstance($instance),
                ];
            }
        }

        $markDate = match ($status) {
            'passed', 'failed_late', 'rejected' => $latestSubmissionDate ?? $anchorDate,
            'failed_missed' => $instance->due_at?->toDateString() ?? $instance->task_date?->toDateString() ?? $anchorDate,
            'waiting_first_approval', 'waiting_final_approval', 'first_approval', 'final_approval', 'waiting_approval', 'submitted', 'pending_approval' => $latestSubmissionDate ?? $anchorDate,
            'pending' => $anchorDate,
            default => $latestSubmissionDate ?? $anchorDate,
        };

        if ($markDate !== $dateKey) {
            return null;
        }

        if ($status === 'excluded') {
            return [
                'type' => 'excluded',
                'label' => 'Excluded',
                'classes' => 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                'instance' => $this->serializeInstance($instance),
            ];
        }

        return match ($status) {
            'passed' => [
                'type' => 'approved',
                'label' => 'Approved',
                'classes' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                'instance' => $this->serializeInstance($instance),
            ],
            'failed_late', 'failed_missed' => [
                'type' => 'failed',
                'label' => 'Failed',
                'classes' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                'instance' => $this->serializeInstance($instance),
            ],
            'waiting_first_approval', 'waiting_final_approval', 'first_approval', 'final_approval', 'waiting_approval', 'submitted', 'pending_approval' => [
                'type' => 'pending',
                'label' => 'Pending Approval',
                'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                'instance' => $this->serializeInstance($instance),
            ],
            'rejected' => [
                'type' => 'rejected',
                'label' => 'Rejected',
                'classes' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                'instance' => $this->serializeInstance($instance),
            ],
            'pending' => [
                'type' => 'upcoming',
                'label' => 'Upcoming',
                'classes' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                'instance' => $this->serializeInstance($instance),
            ],
            default => [
                'type' => 'pending',
                'label' => ucfirst(str_replace('_', ' ', $status ?: 'Pending')),
                'classes' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                'instance' => $this->serializeInstance($instance),
            ],
        };
    }

    public function serializeInstance(KpiTaskInstance $instance): array
    {
        $latest = $instance->latestSubmission;
        $template = $instance->template ?? $instance->assignment?->template;
        $group = $instance->group ?? $template?->group ?? $instance->assignment?->template?->group;

        return [
            'id' => $instance->id,
            'status' => $instance->status,
            'failure_reason' => $instance->failure_reason,
            'task_date' => $instance->task_date?->toDateString(),
            'due_at' => $instance->due_at?->toIso8601String(),
            'submitted_at' => $instance->submitted_at?->toIso8601String(),
            'finalized_at' => $instance->finalized_at?->toIso8601String(),
            'template' => $template ? [
                'id' => $template->id,
                'title' => $template->title,
                'description' => $template->description,
                'guideline' => $template->guideline,
                'frequency' => $template->frequency,
                'group' => $group ? [
                    'id' => $group->id,
                    'name' => $group->name,
                ] : null,
            ] : null,
            'latest_submission' => $latest ? [
                'id' => $latest->id,
                'status' => $latest->status,
                'submitted_at' => $latest->submitted_at?->toIso8601String(),
                'remarks' => $latest->remarks,
                'submitted_by' => $latest->submittedBy ? [
                    'id' => $latest->submittedBy->id,
                    'name' => $latest->submittedBy->name,
                ] : null,
                'images' => $latest->images ? $latest->images->map(fn ($img) => [
                    'id' => $img->id,
                    'image_path' => $img->image_path,
                    'file_url' => asset('storage/' . $img->image_path),
                    'url' => asset('storage/' . $img->image_path),
                    'title' => $img->title,
                    'label' => $img->title,
                    'remark' => $img->remark,
                ])->all() : [],
                'approval_steps' => $latest->approvalSteps ? $latest->approvalSteps->map(fn ($step) => [
                    'id' => $step->id,
                    'step' => $step->step,
                    'step_order' => $step->step_order ?? $step->step,
                    'status' => $step->status,
                    'remarks' => $step->remarks ?? $step->remark,
                    'approver_user_id' => $step->approver_user_id,
                    'approver' => $step->approver ? [
                        'id' => $step->approver->id,
                        'name' => $step->approver->name,
                    ] : null,
                ])->all() : [],
            ] : null,
            'todo_list_id' => $instance->todo_list_id,
            'todo_list' => $instance->todoList ? [
                'id' => $instance->todoList->id,
                'task' => $instance->todoList->task,
            ] : null,
        ];
    }

    public function buildSummary(
        KpiTaskAssignment $assignment,
        Collection $instances,
        Collection $cells,
        Carbon $evaluationEnd
    ): array {
        if ($assignment->template?->frequency === 'daily') {
            return $this->buildDailySummary($cells, $evaluationEnd);
        }

        $eligibleInstances = $instances
            ->filter(function (KpiTaskInstance $instance) use ($evaluationEnd): bool {
                $anchorDate = $instance->task_date
                    ?? $instance->period_start
                    ?? $instance->period_end
                    ?? $instance->due_at
                    ?? $instance->submitted_at;

                return $anchorDate ? $anchorDate->copy()->startOfDay()->lte($evaluationEnd) : false;
            })
            ->values();

        $summary = $this->monthlySuccessService->summarize($eligibleInstances);

        return [
            'passed' => $summary['passed_count'],
            'failed' => $summary['late_count'] + $summary['absent_count'],
            'excluded' => $summary['excluded_count'],
            'pending' => $summary['pending_count'],
            'must_do' => $summary['must_do_count'],
            'percentage' => $summary['score'],
        ];
    }

    public function buildDailySummary(Collection $cells, Carbon $evaluationEnd): array
    {
        $passed = 0;
        $failed = 0;
        $excluded = 0;
        $pending = 0;
        $today = now()->startOfDay();

        foreach ($cells as $cell) {
            $date = Carbon::parse($cell['date'])->startOfDay();

            if ($cell['label'] === '--') {
                continue;
            }

            if (str_contains((string) $cell['classes'], 'bg-slate-200') || in_array((string) $cell['label'], ['Day exclusion', 'Task exclusion', 'Exclusion'], true)) {
                $excluded++;
                continue;
            }

            if ($date->gt($today) && empty($cell['markers'])) {
                $passed++;
                continue;
            }

            if (!empty($cell['markers'])) {
                $hasApproved = false;
                $hasFailed = false;
                $hasPending = false;
                $hasExcluded = false;

                foreach ($cell['markers'] as $marker) {
                    if ($marker['type'] === 'approved') {
                        $hasApproved = true;
                    } elseif ($marker['type'] === 'excluded') {
                        $hasExcluded = true;
                    } elseif ($marker['type'] === 'failed') {
                        $hasFailed = true;
                    } elseif (in_array($marker['type'], ['pending', 'rejected', 'overdue', 'upcoming'], true)) {
                        if ($date->lte($evaluationEnd)) {
                            $hasPending = true;
                        }
                    }
                }

                if ($hasApproved) {
                    $passed++;
                } elseif ($hasExcluded) {
                    $excluded++;
                } elseif ($hasFailed) {
                    $failed++;
                } elseif ($hasPending) {
                    $pending++;
                }

                continue;
            }

            if ($cell['label'] === 'X') {
                $failed++;
                continue;
            }

            if ($cell['label'] === '.' && $date->lte($evaluationEnd)) {
                $pending++;
            }
        }

        $mustDo = $passed + $failed + $pending;

        return [
            'passed' => $passed,
            'failed' => $failed,
            'excluded' => $excluded,
            'pending' => $pending,
            'must_do' => $mustDo,
            'percentage' => $mustDo > 0 ? round(($passed / $mustDo) * 100, 2) : 0,
        ];
    }

    public function assignmentIsActiveOnDate(KpiTaskAssignment $assignment, Carbon $day): bool
    {
        if (!$assignment->is_active || ($assignment->template && !$assignment->template->is_active)) {
            return false;
        }

        if ($assignment->starts_on && $day->lt($assignment->starts_on)) {
            return false;
        }

        if ($assignment->ends_on && $day->gt($assignment->ends_on)) {
            return false;
        }

        return true;
    }

    public function defaultCellLabel(KpiTaskAssignment $assignment, Carbon $day): ?string
    {
        if ($assignment->template?->frequency !== 'daily') {
            return null;
        }

        $today = now()->startOfDay();

        if ($day->lt($today)) {
            return 'X';
        }

        return '.';
    }

    public function defaultCellClasses(KpiTaskAssignment $assignment, Carbon $day, bool $isEmpty): string
    {
        if (!$isEmpty) {
            return 'bg-white dark:bg-slate-900';
        }

        if ($assignment->template?->frequency !== 'daily') {
            return 'bg-white dark:bg-slate-900';
        }

        $today = now()->startOfDay();

        if ($day->lt($today)) {
            return 'bg-rose-50 text-rose-600 dark:bg-rose-950/20 dark:text-rose-300';
        }

        return 'bg-amber-50 text-amber-600 dark:bg-amber-950/20 dark:text-amber-300';
    }

    public function evaluationEnd(Carbon $monthStart, Carbon $monthEnd): Carbon
    {
        $today = now()->startOfDay();

        if ($today->lt($monthStart)) {
            return $monthStart->copy()->subDay();
        }

        return $today->lt($monthEnd) ? $today : $monthEnd->copy()->startOfDay();
    }
}
