<?php

namespace App\Livewire\Kpi;

use App\Models\Department;
use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskRule;
use App\Models\Kpi\KpiTaskTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Templates extends Component
{
    public $groups;
    public $templates;
    public $departments;

    public ?int $editingGroupId = null;
    public string $groupCode = '';
    public string $groupName = '';
    public string $groupDescription = '';
    public string $groupDepartmentId = '';
    public bool $groupIsActive = true;
    public string $groupRuleType = KpiTaskRule::TYPE_PASS_PERCENTAGE;
    public string $groupTargetPercentage = '';
    public string $groupMaxFailCount = '';
    public string $groupMaxCostAmount = '';

    public ?int $editingTemplateId = null;
    public string $templateGroupId = '';
    public string $templateTitle = '';
    public string $templateDescription = '';
    public string $templateGuideline = '';
    public string $templateFrequency = 'daily';
    public int $templateMonthlyRequiredCount = 1;
    public string $templateCutoffTime = '';
    public bool $templateRequiresImages = false;
    public bool $templateRequiresTable = false;
    public int $templateMinImages = 0;
    public string $templateMaxImages = '';
    public bool $templateImageRemarkRequired = false;
    public bool $templateIsActive = true;
    public string $templateRuleType = KpiTaskRule::TYPE_PASS_PERCENTAGE;
    public string $templateTargetPercentage = '';
    public string $templateMaxFailCount = '';
    public string $templateMaxCostAmount = '';

    public function mount(): void
    {
        $this->departments = Department::orderBy('name')->get();
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->groups = KpiGroup::query()
            ->with('department')
            ->withCount('taskTemplates')
            ->orderBy('name')
            ->get();

        $this->templates = KpiTaskTemplate::query()
            ->with(['group.department', 'rule'])
            ->orderBy('title')
            ->get();
    }

    public function createGroup(): void
    {
        Gate::authorize('kpiManageTemplates');

        $data = $this->validateGroup();

        KpiGroup::create($data);

        $this->resetGroupForm();
        $this->loadData();

        session()->flash('message', 'KPI group created.');
    }

    public function editGroup(int $groupId): void
    {
        Gate::authorize('kpiManageTemplates');

        $group = KpiGroup::findOrFail($groupId);

        $this->editingGroupId = $group->id;
        $this->groupCode = (string) $group->code;
        $this->groupName = $group->name;
        $this->groupDescription = (string) $group->description;
        $this->groupDepartmentId = $group->department_id ? (string) $group->department_id : '';
        $this->groupIsActive = (bool) $group->is_active;
        $this->groupRuleType = $group->rule_type ?: KpiTaskRule::TYPE_PASS_PERCENTAGE;
        $this->groupTargetPercentage = $group->target_percentage !== null ? (string) $group->target_percentage : '';
        $this->groupMaxFailCount = $group->max_fail_count !== null ? (string) $group->max_fail_count : '';
        $this->groupMaxCostAmount = $group->max_cost_amount !== null ? (string) $group->max_cost_amount : '';
    }

    public function updateGroup(): void
    {
        Gate::authorize('kpiManageTemplates');

        if (!$this->editingGroupId) {
            return;
        }

        $group = KpiGroup::findOrFail($this->editingGroupId);
        $group->update($this->validateGroup());

        $this->resetGroupForm();
        $this->loadData();

        session()->flash('message', 'KPI group updated.');
    }

    public function deleteGroup(int $groupId): void
    {
        Gate::authorize('kpiManageTemplates');

        $group = KpiGroup::query()->withCount('taskTemplates')->findOrFail($groupId);

        if ($group->task_templates_count > 0) {
            throw ValidationException::withMessages([
                'groupDelete' => 'Delete or move task templates before deleting this KPI group.',
            ]);
        }

        $group->delete();

        $this->resetGroupForm();
        $this->loadData();

        session()->flash('message', 'KPI group deleted.');
    }

    public function cancelGroup(): void
    {
        $this->resetGroupForm();
    }

    public function createTemplate(): void
    {
        Gate::authorize('kpiManageTemplates');

        $validated = $this->validateTemplate();
        $slug = $this->makeUniqueSlug($validated['title']);

        $template = KpiTaskTemplate::create([
            'kpi_group_id' => $validated['kpi_group_id'],
            'created_by_user_id' => Auth::id(),
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'],
            'guideline' => $validated['guideline'],
            'frequency' => $validated['frequency'],
            'monthly_required_count' => $validated['monthly_required_count'],
            'cutoff_time' => $validated['cutoff_time'] ?: null,
            'reminder_start_time' => '08:45:00',
            'requires_images' => $validated['requires_images'],
            'requires_table' => $validated['requires_table'],
            'min_images' => $validated['min_images'],
            'max_images' => $validated['max_images'] ?: null,
            'image_remark_required' => $validated['image_remark_required'],
            'is_active' => $validated['is_active'],
        ]);

        $this->saveRule($template, $validated);

        $this->resetTemplateForm();
        $this->loadData();

        session()->flash('message', 'Task template created.');
    }

    public function editTemplate(int $templateId): void
    {
        Gate::authorize('kpiManageTemplates');

        $template = KpiTaskTemplate::query()->with('rule')->findOrFail($templateId);

        $this->editingTemplateId = $template->id;
        $this->templateGroupId = (string) $template->kpi_group_id;
        $this->templateTitle = $template->title;
        $this->templateDescription = (string) $template->description;
        $this->templateGuideline = (string) $template->guideline;
        $this->templateFrequency = $template->frequency;
        $this->templateMonthlyRequiredCount = (int) $template->monthly_required_count;
        $this->templateCutoffTime = $template->cutoff_time ? substr((string) $template->cutoff_time, 0, 5) : '';
        $this->templateRequiresImages = (bool) $template->requires_images;
        $this->templateRequiresTable = (bool) $template->requires_table;
        $this->templateMinImages = (int) $template->min_images;
        $this->templateMaxImages = $template->max_images !== null ? (string) $template->max_images : '';
        $this->templateImageRemarkRequired = (bool) $template->image_remark_required;
        $this->templateIsActive = (bool) $template->is_active;
        $this->templateRuleType = $template->rule?->rule_type ?? KpiTaskRule::TYPE_PASS_PERCENTAGE;
        $this->templateTargetPercentage = $template->rule?->target_percentage !== null ? (string) $template->rule->target_percentage : '';
        $this->templateMaxFailCount = $template->rule?->max_fail_count !== null ? (string) $template->rule->max_fail_count : '';
        $this->templateMaxCostAmount = $template->rule?->max_cost_amount !== null ? (string) $template->rule->max_cost_amount : '';
    }

    public function updateTemplate(): void
    {
        Gate::authorize('kpiManageTemplates');

        if (!$this->editingTemplateId) {
            return;
        }

        $template = KpiTaskTemplate::query()->with('rule')->findOrFail($this->editingTemplateId);
        $validated = $this->validateTemplate();

        $template->update([
            'kpi_group_id' => $validated['kpi_group_id'],
            'title' => $validated['title'],
            'slug' => $this->makeUniqueSlug($validated['title'], $template->id),
            'description' => $validated['description'],
            'guideline' => $validated['guideline'],
            'frequency' => $validated['frequency'],
            'monthly_required_count' => $validated['monthly_required_count'],
            'cutoff_time' => $validated['cutoff_time'] ?: null,
            'requires_images' => $validated['requires_images'],
            'requires_table' => $validated['requires_table'],
            'min_images' => $validated['min_images'],
            'max_images' => $validated['max_images'] ?: null,
            'image_remark_required' => $validated['image_remark_required'],
            'is_active' => $validated['is_active'],
        ]);

        $this->saveRule($template, $validated);

        $this->resetTemplateForm();
        $this->loadData();

        session()->flash('message', 'Task template updated.');
    }

    public function deleteTemplate(int $templateId): void
    {
        Gate::authorize('kpiManageTemplates');

        $template = KpiTaskTemplate::findOrFail($templateId);
        $template->delete();

        $this->resetTemplateForm();
        $this->loadData();

        session()->flash('message', 'Task template deleted.');
    }

    public function cancelTemplate(): void
    {
        $this->resetTemplateForm();
    }

    public function render()
    {
        return view('livewire.kpi.templates');
    }

    protected function validateGroup(): array
    {
        $this->validate([
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

        if ($this->groupRuleType === KpiTaskRule::TYPE_PASS_PERCENTAGE && $this->groupTargetPercentage === '') {
            throw ValidationException::withMessages([
                'groupTargetPercentage' => 'Target percentage is required for the pass percentage rule.',
            ]);
        }

        if ($this->groupRuleType === KpiTaskRule::TYPE_FAIL_COUNT && $this->groupMaxFailCount === '') {
            throw ValidationException::withMessages([
                'groupMaxFailCount' => 'Maximum fail count is required for the fail count rule.',
            ]);
        }

        if ($this->groupRuleType === KpiTaskRule::TYPE_SPEND_COST_LTE && $this->groupMaxCostAmount === '') {
            throw ValidationException::withMessages([
                'groupMaxCostAmount' => 'Maximum cost amount is required for the spend cost rule.',
            ]);
        }

        return [
            'code' => $this->groupCode !== '' ? $this->groupCode : null,
            'name' => $this->groupName,
            'description' => $this->groupDescription !== '' ? $this->groupDescription : null,
            'department_id' => $this->groupDepartmentId !== '' ? (int) $this->groupDepartmentId : null,
            'rule_type' => $this->groupRuleType,
            'target_percentage' => $this->groupTargetPercentage !== '' ? (float) $this->groupTargetPercentage : null,
            'max_fail_count' => $this->groupMaxFailCount !== '' ? (int) $this->groupMaxFailCount : null,
            'max_cost_amount' => $this->groupMaxCostAmount !== '' ? (float) $this->groupMaxCostAmount : null,
            'is_active' => $this->groupIsActive,
        ];
    }

    protected function validateTemplate(): array
    {
        $validated = $this->validate([
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

        if (!$this->templateRequiresImages && !$this->templateRequiresTable) {
            throw ValidationException::withMessages([
                'templateRequiresImages' => 'Select at least one evidence type: images or custom table.',
            ]);
        }

        if ($this->templateMaxImages !== '' && (int) $this->templateMaxImages < (int) $this->templateMinImages) {
            throw ValidationException::withMessages([
                'templateMaxImages' => 'Maximum images must be greater than or equal to minimum images.',
            ]);
        }

        if ($this->templateRuleType === KpiTaskRule::TYPE_PASS_PERCENTAGE && $this->templateTargetPercentage === '') {
            throw ValidationException::withMessages([
                'templateTargetPercentage' => 'Target percentage is required for the pass percentage rule.',
            ]);
        }

        if ($this->templateRuleType === KpiTaskRule::TYPE_FAIL_COUNT && $this->templateMaxFailCount === '') {
            throw ValidationException::withMessages([
                'templateMaxFailCount' => 'Maximum fail count is required for the fail count rule.',
            ]);
        }

        if ($this->templateRuleType === KpiTaskRule::TYPE_SPEND_COST_LTE && $this->templateMaxCostAmount === '') {
            throw ValidationException::withMessages([
                'templateMaxCostAmount' => 'Maximum cost amount is required for the spend cost rule.',
            ]);
        }

        return [
            'kpi_group_id' => (int) $validated['templateGroupId'],
            'title' => $validated['templateTitle'],
            'description' => $validated['templateDescription'] !== '' ? $validated['templateDescription'] : null,
            'guideline' => $validated['templateGuideline'] !== '' ? $validated['templateGuideline'] : null,
            'frequency' => $validated['templateFrequency'],
            'monthly_required_count' => (int) $validated['templateMonthlyRequiredCount'],
            'cutoff_time' => $validated['templateCutoffTime'],
            'requires_images' => (bool) $validated['templateRequiresImages'],
            'requires_table' => (bool) $validated['templateRequiresTable'],
            'min_images' => (int) $validated['templateMinImages'],
            'max_images' => $validated['templateMaxImages'] !== '' ? (int) $validated['templateMaxImages'] : null,
            'image_remark_required' => (bool) $validated['templateImageRemarkRequired'],
            'is_active' => (bool) $validated['templateIsActive'],
            'rule_type' => $validated['templateRuleType'],
            'target_percentage' => $validated['templateTargetPercentage'] !== '' ? (float) $validated['templateTargetPercentage'] : null,
            'max_fail_count' => $validated['templateMaxFailCount'] !== '' ? (int) $validated['templateMaxFailCount'] : null,
            'max_cost_amount' => $validated['templateMaxCostAmount'] !== '' ? (float) $validated['templateMaxCostAmount'] : null,
        ];
    }

    protected function saveRule(KpiTaskTemplate $template, array $validated): void
    {
        $template->rule()->updateOrCreate(
            ['task_template_id' => $template->id],
            [
                'rule_type' => $validated['rule_type'],
                'target_percentage' => $validated['rule_type'] === KpiTaskRule::TYPE_PASS_PERCENTAGE ? $validated['target_percentage'] : null,
                'max_fail_count' => $validated['rule_type'] === KpiTaskRule::TYPE_FAIL_COUNT ? $validated['max_fail_count'] : null,
                'max_cost_amount' => $validated['rule_type'] === KpiTaskRule::TYPE_SPEND_COST_LTE ? $validated['max_cost_amount'] : null,
            ]
        );
    }

    protected function resetGroupForm(): void
    {
        $this->editingGroupId = null;
        $this->groupCode = '';
        $this->groupName = '';
        $this->groupDescription = '';
        $this->groupDepartmentId = '';
        $this->groupIsActive = true;
        $this->groupRuleType = KpiTaskRule::TYPE_PASS_PERCENTAGE;
        $this->groupTargetPercentage = '';
        $this->groupMaxFailCount = '';
        $this->groupMaxCostAmount = '';
        $this->resetErrorBag();
    }

    protected function resetTemplateForm(): void
    {
        $this->editingTemplateId = null;
        $this->templateGroupId = '';
        $this->templateTitle = '';
        $this->templateDescription = '';
        $this->templateGuideline = '';
        $this->templateFrequency = 'daily';
        $this->templateMonthlyRequiredCount = 1;
        $this->templateCutoffTime = '';
        $this->templateRequiresImages = false;
        $this->templateRequiresTable = false;
        $this->templateMinImages = 0;
        $this->templateMaxImages = '';
        $this->templateImageRemarkRequired = false;
        $this->templateIsActive = true;
        $this->templateRuleType = KpiTaskRule::TYPE_PASS_PERCENTAGE;
        $this->templateTargetPercentage = '';
        $this->templateMaxFailCount = '';
        $this->templateMaxCostAmount = '';
        $this->resetErrorBag();
    }

    protected function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $baseSlug = $base !== '' ? $base : 'kpi-task';
        $slug = $baseSlug;
        $counter = 2;

        while (
            KpiTaskTemplate::query()
                ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
