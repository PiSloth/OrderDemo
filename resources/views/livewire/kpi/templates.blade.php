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
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Task Templates</h3>
                <span class="text-sm text-slate-500 dark:text-slate-400">Create new task templates here</span>
            </div>

            @can('kpiManageTemplates')
                @if (!$editingTemplateId)
                <form wire:submit.prevent="createTemplate" class="mt-4 space-y-4">
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
                                <option value="spend_cost_lte">Spend Cost &lt;= Target</option>
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
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            Leave both unchecked if this template does not require evidence.
                        </p>
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
                        Create Template
                    </button>
                </form>
                @endif
            @endcan
        </article>
    </section>

    @if ($editingTemplateId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6">
            <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Edit Task Template</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Update the selected template in this modal.</p>
                    </div>
                    <button type="button" wire:click="cancelTemplate" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        Close
                    </button>
                </div>

                <form wire:submit.prevent="updateTemplate" class="mt-6 space-y-4">
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
                                <option value="spend_cost_lte">Spend Cost &lt;= Target</option>
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
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            Leave both unchecked if this template does not require evidence.
                        </p>
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

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="cancelTemplate"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                            Cancel
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                            Update Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <section x-data="{ activeTable: 'groups' }" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Created Groups & Templates</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    Use the swipe-style toggle to switch between the created group table and the created template table.
                </p>
            </div>

            <div class="inline-flex rounded-full bg-slate-100 p-1 dark:bg-slate-800">
                <div class="relative grid grid-cols-2">
                    <span
                        class="absolute inset-y-0 w-1/2 rounded-full bg-white shadow-sm transition-transform duration-300 ease-out dark:bg-slate-700"
                        :class="activeTable === 'groups' ? 'translate-x-0' : 'translate-x-full'"
                    ></span>
                    <button
                        type="button"
                        @click="activeTable = 'groups'"
                        :class="activeTable === 'groups' ? 'text-slate-900 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400'"
                        class="relative z-10 rounded-full px-4 py-2 text-sm font-medium transition"
                    >
                        Created Groups
                    </button>
                    <button
                        type="button"
                        @click="activeTable = 'templates'"
                        :class="activeTable === 'templates' ? 'text-slate-900 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400'"
                        class="relative z-10 rounded-full px-4 py-2 text-sm font-medium transition"
                    >
                        Created Templates
                    </button>
                </div>
            </div>
        </div>

        <div x-show="activeTable === 'groups'" x-transition.opacity.duration.200ms class="mt-6 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Group</th>
                            <th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3">Templates</th>
                            <th class="px-4 py-3">Rule</th>
                            <th class="px-4 py-3">Status</th>
                            @can('kpiManageTemplates')
                                <th class="px-4 py-3 text-right">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white text-sm dark:divide-slate-800 dark:bg-slate-900">
                        @forelse ($groups as $group)
                            <tr class="align-top">
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $group->name }}</div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $group->code ?: 'No code' }}</div>
                                    <div class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $group->description ?: 'No description' }}</div>
                                </td>
                                <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $group->department?->name ?? 'All departments' }}</td>
                                <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $group->task_templates_count }}</td>
                                <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                    {{ str_replace('_', ' ', $group->rule_type ?? 'not set') }}
                                    @if ($group->rule_type === 'pass_percentage')
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Target {{ $group->target_percentage !== null ? number_format((float) $group->target_percentage, 2) . '%' : '-' }}</div>
                                    @elseif ($group->rule_type === 'fail_count')
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Max {{ $group->max_fail_count ?? '-' }} fail(s)</div>
                                    @elseif ($group->rule_type === 'spend_cost_lte')
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Max {{ $group->max_cost_amount !== null ? number_format((float) $group->max_cost_amount, 2) : '-' }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $group->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                        {{ $group->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                @can('kpiManageTemplates')
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" wire:click="editGroup({{ $group->id }})"
                                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                                                Edit
                                            </button>
                                            <button type="button" wire:click="deleteGroup({{ $group->id }})"
                                                wire:confirm="Delete this KPI group?"
                                                class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-slate-800">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="@can('kpiManageTemplates') 6 @else 5 @endcan" class="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                                    No KPI groups yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="activeTable === 'templates'" x-transition.opacity.duration.200ms class="mt-6 space-y-4">
            <div class="flex flex-col gap-3 sm:max-w-xs">
                <label for="template-employee-filter" class="text-sm font-medium text-slate-700 dark:text-slate-200">Assigned User</label>
                <select
                    id="template-employee-filter"
                    wire:model.live="templateEmployeeFilter"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500"
                >
                    <option value="">All Assigned Users</option>
                    @foreach ($templateEmployees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-800/80">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                <th class="px-4 py-3">Template</th>
                                <th class="px-4 py-3">Group</th>
                                <th class="px-4 py-3">Assigned User</th>
                                <th class="px-4 py-3">First Approver</th>
                                <th class="px-4 py-3">Final Approver</th>
                                <th class="px-4 py-3">Evidence</th>
                                <th class="px-4 py-3">Cutoff</th>
                                <th class="px-4 py-3">Status</th>
                                @can('kpiManageTemplates')
                                    <th class="px-4 py-3 text-right">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white text-sm dark:divide-slate-800 dark:bg-slate-900">
                            @forelse ($templates as $template)
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $template->title }}</div>
                                        <div class="mt-1 text-xs uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400">{{ $template->frequency }}</div>
                                        <div class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $template->description ?: 'No description' }}</div>
                                        @if ($template->guideline)
                                            <div class="mt-2 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                Guideline: {{ $template->guideline }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $template->group?->name ?? '-' }}</td>
                                    <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                        @php
                                            $assignedUsers = $template->taskAssignments
                                                ->pluck('user.name')
                                                ->filter()
                                                ->unique()
                                                ->values();
                                        @endphp
                                        {{ $assignedUsers->isNotEmpty() ? $assignedUsers->join(', ') : 'Unassigned' }}
                                    </td>
                                    <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                        @php
                                            $firstApprovers = $template->taskAssignments
                                                ->pluck('firstApprover.name')
                                                ->filter()
                                                ->unique()
                                                ->values();
                                        @endphp
                                        {{ $firstApprovers->isNotEmpty() ? $firstApprovers->join(', ') : '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                        @php
                                            $finalApprovers = $template->taskAssignments
                                                ->pluck('finalApprover.name')
                                                ->filter()
                                                ->unique()
                                                ->values();
                                        @endphp
                                        {{ $finalApprovers->isNotEmpty() ? $finalApprovers->join(', ') : '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                        @if ($template->requires_images || $template->requires_table)
                                            {{ $template->requires_images ? 'Images' : '' }}{{ $template->requires_images && $template->requires_table ? ' + ' : '' }}{{ $template->requires_table ? 'Table' : '' }}
                                        @else
                                            None
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-slate-600 dark:text-slate-300">{{ $template->cutoff_time ? \Illuminate\Support\Str::of($template->cutoff_time)->substr(0, 5) : 'No cutoff' }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $template->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                            {{ str_replace('_', ' ', $template->rule?->rule_type ?? 'not set') }}
                                        </div>
                                    </td>
                                    @can('kpiManageTemplates')
                                        <td class="px-4 py-4">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" wire:click="editTemplate({{ $template->id }})"
                                                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                                                    Edit
                                                </button>
                                                <button type="button" wire:click="deleteTemplate({{ $template->id }})"
                                                    wire:confirm="Delete this task template?"
                                                    class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-slate-800">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="@can('kpiManageTemplates') 9 @else 8 @endcan" class="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                                        No task templates found for the selected assigned user.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
