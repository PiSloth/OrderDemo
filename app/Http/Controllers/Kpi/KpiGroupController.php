<?php

namespace App\Http\Controllers\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskRule;
use App\Models\Kpi\KpiTaskTemplate;
use App\Models\User;
use App\Services\Kpi\KpiGroupService;
use App\Services\Kpi\KpiTaskTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KpiGroupController extends Controller
{
    public function index(Request $request, KpiGroupService $groupService): Response
    {
        $templateEmployeeFilter = $request->query('templateEmployeeFilter', '');

        $departments = Department::orderBy('name')->get(['id', 'name']);

        $templateEmployees = User::query()
            ->whereIn('id', function ($query) {
                $query->from('kpi_task_assignments')
                    ->select('user_id')
                    ->whereNotNull('user_id')
                    ->where('is_active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $templates = KpiTaskTemplate::query()
            ->with([
                'group.department',
                'rule',
                'taskAssignments' => function ($assignmentQuery) {
                    $assignmentQuery->where('is_active', true);
                },
                'taskAssignments.user',
                'taskAssignments.firstApprover',
                'taskAssignments.finalApprover',
            ])
            ->when($templateEmployeeFilter !== '', function ($query) use ($templateEmployeeFilter) {
                $query->whereHas('taskAssignments', function ($assignmentQuery) use ($templateEmployeeFilter) {
                    $assignmentQuery
                        ->where('user_id', (int) $templateEmployeeFilter)
                        ->where('is_active', true);
                });
            })
            ->orderByRaw("FIELD(frequency, 'daily', 'weekly', 'monthly')")
            ->get();

        return Inertia::render('Kpi/Templates', [
            'groups' => $groupService->getPaginatedGroups(6),
            'allGroups' => $groupService->getAllGroups(),
            'departments' => $departments,
            'templateEmployees' => $templateEmployees,
            'templates' => $templates,
            'filters' => [
                'templateEmployeeFilter' => $templateEmployeeFilter,
            ],
        ]);
    }

    public function storeGroup(Request $request, KpiGroupService $groupService)
    {
        Gate::authorize('kpiManageTemplates');

        $data = $this->validateGroupPayload($request);
        $groupService->createGroup($data);

        return redirect()->back()->with('message', 'KPI group created.');
    }

    public function updateGroup(Request $request, KpiGroup $group, KpiGroupService $groupService)
    {
        Gate::authorize('kpiManageTemplates');

        $data = $this->validateGroupPayload($request);
        $groupService->updateGroup($group, $data);

        return redirect()->back()->with('message', 'KPI group updated.');
    }

    public function destroyGroup(KpiGroup $group, KpiGroupService $groupService)
    {
        Gate::authorize('kpiManageTemplates');

        try {
            $groupService->deleteGroup($group);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()->back()->with('message', 'KPI group deleted.');
    }

    public function storeTemplate(Request $request, KpiTaskTemplateService $templateService)
    {
        Gate::authorize('kpiManageTemplates');

        $validated = $this->validateTemplatePayload($request);
        $templateService->createTemplate($validated, Auth::id());

        return redirect()->back()->with('message', 'Task template created.');
    }

    public function updateTemplate(Request $request, KpiTaskTemplate $template, KpiTaskTemplateService $templateService)
    {
        Gate::authorize('kpiManageTemplates');

        $validated = $this->validateTemplatePayload($request);

        try {
            $templateService->updateTemplate($template, $validated);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()->back()->with('message', 'Task template updated.');
    }

    public function destroyTemplate(KpiTaskTemplate $template, KpiTaskTemplateService $templateService)
    {
        Gate::authorize('kpiManageTemplates');

        try {
            $templateService->deleteTemplate($template);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()->back()->with('message', 'Task template deleted.');
    }

    protected function validateGroupPayload(Request $request): array
    {
        $validated = $request->validate([
            'groupCode' => ['nullable', 'string', 'max:50'],
            'groupName' => ['required', 'string', 'max:255'],
            'groupDescription' => ['nullable', 'string'],
            'groupDepartmentId' => ['nullable', 'exists:departments,id'],
            'groupIsActive' => ['boolean'],
            'groupRuleType' => ['required', Rule::in([
                KpiTaskRule::TYPE_PASS_PERCENTAGE,
                KpiTaskRule::TYPE_FAIL_COUNT,
                KpiTaskRule::TYPE_SPEND_COST_LTE,
            ])],
            'groupTargetPercentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'groupMaxFailCount' => ['nullable', 'integer', 'min:0'],
            'groupMaxCostAmount' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'groupCode' => 'group code',
            'groupName' => 'group name',
            'groupDepartmentId' => 'department',
            'groupRuleType' => 'group rule type',
        ]);

        $ruleType = $validated['groupRuleType'];
        $targetPct = $validated['groupTargetPercentage'] ?? null;
        $maxFail = $validated['groupMaxFailCount'] ?? null;
        $maxCost = $validated['groupMaxCostAmount'] ?? null;

        if ($ruleType === KpiTaskRule::TYPE_PASS_PERCENTAGE && ($targetPct === null || $targetPct === '')) {
            throw ValidationException::withMessages([
                'groupTargetPercentage' => 'Target percentage is required for the pass percentage rule.',
            ]);
        }

        if ($ruleType === KpiTaskRule::TYPE_FAIL_COUNT && ($maxFail === null || $maxFail === '')) {
            throw ValidationException::withMessages([
                'groupMaxFailCount' => 'Maximum fail count is required for the fail count rule.',
            ]);
        }

        if ($ruleType === KpiTaskRule::TYPE_SPEND_COST_LTE && ($maxCost === null || $maxCost === '')) {
            throw ValidationException::withMessages([
                'groupMaxCostAmount' => 'Maximum cost amount is required for the spend cost rule.',
            ]);
        }

        return [
            'code' => !empty($validated['groupCode']) ? $validated['groupCode'] : null,
            'name' => $validated['groupName'],
            'description' => !empty($validated['groupDescription']) ? $validated['groupDescription'] : null,
            'department_id' => !empty($validated['groupDepartmentId']) ? (int) $validated['groupDepartmentId'] : null,
            'rule_type' => $ruleType,
            'target_percentage' => $targetPct !== null && $targetPct !== '' ? (float) $targetPct : null,
            'max_fail_count' => $maxFail !== null && $maxFail !== '' ? (int) $maxFail : null,
            'max_cost_amount' => $maxCost !== null && $maxCost !== '' ? (float) $maxCost : null,
            'is_active' => (bool) ($validated['groupIsActive'] ?? true),
        ];
    }

    protected function validateTemplatePayload(Request $request): array
    {
        $validated = $request->validate([
            'templateGroupId' => ['required', 'exists:kpi_groups,id'],
            'templateTitle' => ['required', 'string', 'max:255'],
            'templateDescription' => ['nullable', 'string'],
            'templateGuideline' => ['nullable', 'string'],
            'templateFrequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'templateMonthlyRequiredCount' => ['required', 'integer', 'min:1', 'max:31'],
            'templateCutoffTime' => ['nullable', 'date_format:H:i'],
            'templateRequiresImages' => ['boolean'],
            'templateRequiresTable' => ['boolean'],
            'templateMinImages' => ['required', 'integer', 'min:0', 'max:20'],
            'templateMaxImages' => ['nullable', 'integer', 'min:0', 'max:20'],
            'templateImageRemarkRequired' => ['boolean'],
            'templateIsActive' => ['boolean'],
            'templateRuleType' => ['required', Rule::in([
                KpiTaskRule::TYPE_PASS_PERCENTAGE,
                KpiTaskRule::TYPE_FAIL_COUNT,
                KpiTaskRule::TYPE_SPEND_COST_LTE,
            ])],
            'templateTargetPercentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'templateMaxFailCount' => ['nullable', 'integer', 'min:0'],
            'templateMaxCostAmount' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'templateGroupId' => 'KPI group',
            'templateTitle' => 'task title',
            'templateCutoffTime' => 'cutoff time',
            'templateRuleType' => 'rule type',
        ]);

        $maxImg = $validated['templateMaxImages'] ?? null;
        $minImg = (int) $validated['templateMinImages'];
        if ($maxImg !== null && $maxImg !== '' && (int) $maxImg < $minImg) {
            throw ValidationException::withMessages([
                'templateMaxImages' => 'Maximum images must be greater than or equal to minimum images.',
            ]);
        }

        $ruleType = $validated['templateRuleType'];
        $targetPct = $validated['templateTargetPercentage'] ?? null;
        $maxFail = $validated['templateMaxFailCount'] ?? null;
        $maxCost = $validated['templateMaxCostAmount'] ?? null;

        if ($ruleType === KpiTaskRule::TYPE_PASS_PERCENTAGE && ($targetPct === null || $targetPct === '')) {
            throw ValidationException::withMessages([
                'templateTargetPercentage' => 'Target percentage is required for the pass percentage rule.',
            ]);
        }

        if ($ruleType === KpiTaskRule::TYPE_FAIL_COUNT && ($maxFail === null || $maxFail === '')) {
            throw ValidationException::withMessages([
                'groupMaxFailCount' => 'Maximum fail count is required for the fail count rule.',
            ]);
        }

        if ($ruleType === KpiTaskRule::TYPE_SPEND_COST_LTE && ($maxCost === null || $maxCost === '')) {
            throw ValidationException::withMessages([
                'groupMaxCostAmount' => 'Maximum cost amount is required for the spend cost rule.',
            ]);
        }

        return [
            'kpi_group_id' => (int) $validated['templateGroupId'],
            'title' => $validated['templateTitle'],
            'description' => !empty($validated['templateDescription']) ? $validated['templateDescription'] : null,
            'guideline' => !empty($validated['templateGuideline']) ? $validated['templateGuideline'] : null,
            'frequency' => $validated['templateFrequency'],
            'monthly_required_count' => (int) $validated['templateMonthlyRequiredCount'],
            'cutoff_time' => $validated['templateCutoffTime'] ?? null,
            'requires_images' => (bool) ($validated['templateRequiresImages'] ?? false),
            'requires_table' => (bool) ($validated['templateRequiresTable'] ?? false),
            'min_images' => $minImg,
            'max_images' => $maxImg !== null && $maxImg !== '' ? (int) $maxImg : null,
            'image_remark_required' => (bool) ($validated['templateImageRemarkRequired'] ?? false),
            'is_active' => (bool) ($validated['templateIsActive'] ?? true),
            'rule_type' => $ruleType,
            'target_percentage' => $targetPct !== null && $targetPct !== '' ? (float) $targetPct : null,
            'max_fail_count' => $maxFail !== null && $maxFail !== '' ? (int) $maxFail : null,
            'max_cost_amount' => $maxCost !== null && $maxCost !== '' ? (float) $maxCost : null,
        ];
    }
}
