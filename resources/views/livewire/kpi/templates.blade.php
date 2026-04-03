<div class="space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">KPI Template Configuration</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Create KPI groups and recurring task templates with rule type, cutoff time, and evidence requirements.
                </p>
            </div>
            @can('kpiManageTemplates')
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">
                    You can manage templates
                </span>
            @else
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    Read-only access
                </span>
            @endcan
        </div>
    </section>

    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('message') }}
        </div>
    @endif

    @error('groupDelete')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <section class="grid gap-6 xl:grid-cols-[1fr_1.4fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">KPI Groups</h3>
                @if ($editingGroupId)
                    <button type="button" wire:click="cancelGroup" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        Cancel
                    </button>
                @endif
            </div>

            @can('kpiManageTemplates')
                <form wire:submit.prevent="{{ $editingGroupId ? 'updateGroup' : 'createGroup' }}" class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Group Code</label>
                            <input type="text" wire:model.defer="groupCode"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('groupCode') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Department</label>
                            <select wire:model.defer="groupDepartmentId"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                <option value="">All Departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('groupDepartmentId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Group Name</label>
                        <input type="text" wire:model.defer="groupName"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                        @error('groupName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Description</label>
                        <textarea wire:model.defer="groupDescription" rows="3"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500"></textarea>
                        @error('groupDescription') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Group Rule Type</label>
                            <select wire:model.live="groupRuleType"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                <option value="pass_percentage">Pass Percentage</option>
                                <option value="fail_count">Fail Count</option>
                                <option value="spend_cost_lte">Spend Cost &lt;= Target</option>
                            </select>
                            @error('groupRuleType') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            @if ($groupRuleType === 'pass_percentage')
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Target Percentage</label>
                                <input type="number" step="0.01" min="0" max="100" wire:model.defer="groupTargetPercentage"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                @error('groupTargetPercentage') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            @elseif ($groupRuleType === 'fail_count')
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Max Fail Count</label>
                                <input type="number" min="0" wire:model.defer="groupMaxFailCount"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                @error('groupMaxFailCount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            @else
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Max Cost Amount</label>
                                <input type="number" min="0" step="0.01" wire:model.defer="groupMaxCostAmount"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                @error('groupMaxCostAmount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            @endif
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" wire:model.defer="groupIsActive" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                        Active
                    </label>

                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                        {{ $editingGroupId ? 'Update Group' : 'Create Group' }}
                    </button>
                </form>
            @endcan

            <div class="mt-6 space-y-3">
                @forelse ($groups as $group)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $group->name }}</p>
                                    @if ($group->code)
                                        <span class="rounded-full bg-white px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-900 dark:text-slate-400">{{ $group->code }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $group->description ?: 'No description' }}</p>
                                <p class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                                    {{ $group->department?->name ?? 'All departments' }} • {{ $group->task_templates_count }} templates
                                </p>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                    Rule: {{ str_replace('_', ' ', $group->rule_type ?? 'not set') }}
                                    @if ($group->rule_type === 'pass_percentage')
                                        • Target {{ $group->target_percentage !== null ? number_format((float) $group->target_percentage, 2) . '%' : '-' }}
                                    @elseif ($group->rule_type === 'fail_count')
                                        • Max {{ $group->max_fail_count ?? '-' }} fail(s)
                                    @elseif ($group->rule_type === 'spend_cost_lte')
                                        • Max {{ $group->max_cost_amount !== null ? number_format((float) $group->max_cost_amount, 2) : '-' }}
                                    @endif
                                </p>
                            </div>
                            @can('kpiManageTemplates')
                                <div class="flex gap-2">
                                    <button type="button" wire:click="editGroup({{ $group->id }})"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-white">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="deleteGroup({{ $group->id }})"
                                        wire:confirm="Delete this KPI group?"
                                        class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-white">
                                        Delete
                                    </button>
                                </div>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        No KPI groups yet.
                    </div>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Task Templates</h3>
                @if ($editingTemplateId)
                    <button type="button" wire:click="cancelTemplate" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        Cancel
                    </button>
                @endif
            </div>

            @can('kpiManageTemplates')
                <form wire:submit.prevent="{{ $editingTemplateId ? 'updateTemplate' : 'createTemplate' }}" class="mt-4 space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">KPI Group</label>
                            <select wire:model.defer="templateGroupId"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                <option value="">Select Group</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                            @error('templateGroupId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Title</label>
                            <input type="text" wire:model.defer="templateTitle"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('templateTitle') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Frequency</label>
                            <select wire:model.defer="templateFrequency"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                            @error('templateFrequency') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Monthly Required Count</label>
                            <input type="number" min="1" max="31" wire:model.defer="templateMonthlyRequiredCount"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('templateMonthlyRequiredCount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Cutoff Time</label>
                            <input type="time" wire:model.defer="templateCutoffTime"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('templateCutoffTime') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Rule Type</label>
                            <select wire:model.live="templateRuleType"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                <option value="pass_percentage">Pass Percentage</option>
                                <option value="fail_count">Fail Count</option>
                                <option value="spend_cost_lte">Spend Cost <= Target</option>
                            </select>
                            @error('templateRuleType') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Target Percentage</label>
                            <input type="number" step="0.01" min="0" max="100" wire:model.defer="templateTargetPercentage"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('templateTargetPercentage') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Max Fail Count</label>
                            <input type="number" min="0" wire:model.defer="templateMaxFailCount"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('templateMaxFailCount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Max Cost Amount</label>
                            <input type="number" min="0" step="0.01" wire:model.defer="templateMaxCostAmount"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('templateMaxCostAmount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Description</label>
                            <textarea wire:model.defer="templateDescription" rows="3"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500"></textarea>
                            @error('templateDescription') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Guideline</label>
                            <textarea wire:model.defer="templateGuideline" rows="3"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500"></textarea>
                            @error('templateGuideline') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Evidence Settings</p>
                        <div class="mt-3 grid gap-4 md:grid-cols-2">
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                                <input type="checkbox" wire:model.live="templateRequiresImages" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                                Require Images
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                                <input type="checkbox" wire:model.live="templateRequiresTable" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                                Require Custom Table
                            </label>
                            <div>
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Minimum Images</label>
                                <input type="number" min="0" max="20" wire:model.defer="templateMinImages"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                @error('templateMinImages') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Maximum Images</label>
                                <input type="number" min="0" max="20" wire:model.defer="templateMaxImages"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                                @error('templateMaxImages') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <label class="mt-4 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="templateImageRemarkRequired" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Require remark for each image
                        </label>
                        @error('templateRequiresImages') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="templateIsActive" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Active
                        </label>
                    </div>

                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                        {{ $editingTemplateId ? 'Update Template' : 'Create Template' }}
                    </button>
                </form>
            @endcan

            <div class="mt-6 space-y-3">
                @forelse ($templates as $template)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $template->title }}</p>
                                    <span class="rounded-full bg-white px-2 py-0.5 text-xs uppercase tracking-[0.15em] text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                                        {{ $template->frequency }}
                                    </span>
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $template->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-300">{{ $template->description ?: 'No description' }}</p>
                                <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                                    <p>Group: {{ $template->group?->name ?? '-' }}</p>
                                    <p>Cutoff: {{ $template->cutoff_time ? \Illuminate\Support\Str::of($template->cutoff_time)->substr(0, 5) : 'No cutoff' }}</p>
                                    <p>Rule: {{ str_replace('_', ' ', $template->rule?->rule_type ?? 'not set') }}</p>
                                    <p>
                                        Evidence:
                                        {{ $template->requires_images ? 'Images' : '' }}{{ $template->requires_images && $template->requires_table ? ' + ' : '' }}{{ $template->requires_table ? 'Table' : '' }}
                                    </p>
                                </div>
                                @if ($template->guideline)
                                    <p class="rounded-xl bg-white px-3 py-2 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                                        <span class="font-medium text-slate-800 dark:text-slate-100">Guideline:</span> {{ $template->guideline }}
                                    </p>
                                @endif
                            </div>

                            @can('kpiManageTemplates')
                                <div class="flex gap-2">
                                    <button type="button" wire:click="editTemplate({{ $template->id }})"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="deleteTemplate({{ $template->id }})"
                                        wire:confirm="Delete this task template?"
                                        class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-white dark:border-rose-700 dark:text-rose-300 dark:hover:bg-slate-900">
                                        Delete
                                    </button>
                                </div>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        No task templates yet.
                    </div>
                @endforelse
            </div>
        </article>
    </section>
</div>
