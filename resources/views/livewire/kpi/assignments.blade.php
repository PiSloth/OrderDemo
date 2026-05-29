<div class="space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Employee Task Assignments</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Assign one KPI task template to one employee with first approver, final approver, active dates, and
                    calendar push rules.
                </p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">
                Manual employee assignment
            </span>
        </div>
    </section>

    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('message') }}
        </div>
    @endif

    @error('assignmentDelete')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror
    @error('assignmentGenerator')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <section class="grid gap-6 ">
        <div>
            <x-button label="New Assignment" primary @click="$openModal('assignmentModal')" right-icon="plus" />
        </div>
        <article
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Existing Assignments</h3>
            <div class="md:w-1/3 w-full">
                <x-select label="" placeholder="Search employee" wire:model.live="selectedUserId"
                    :async-data="route('users.index')" option-label="name" option-value="id" />
                <x-checkbox label="Active only" wire:model.live="is_active" class="mt-2" />
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($assignments as $assignment)
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900 dark:text-slate-100">
                                        {{ $assignment->template?->title ?? '-' }}</p>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs {{ $assignment->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs {{ $assignment->ends_on?->isPast() ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ $assignment->ends_on?->isPast() ? 'Expired' : 'Ongoing' }}
                                    </span>
                                    <span
                                        class="uppercase px-2 py-0.5 space-x-2 dark:text-salte-200 text-yellow-600 rounded-full bg-yellow-50 text-xs ">{{ $assignment->template?->frequency ?? '-' }}</span>
                                </div>

                                <div class="grid gap-2 text-sm text-slate-600 dark:text-slate-300 md:grid-cols-2">
                                    <p>
                                        Employee:
                                        {{ $assignment->user?->name ?? '-' }}
                                        @if ($assignment->user?->position?->name)
                                            • {{ $assignment->user->position->name }}
                                        @endif
                                    </p>
                                    <p>Group: {{ $assignment->template?->group?->name ?? '-' }}</p>
                                    <p>First Approver: {{ $assignment->firstApprover?->name ?? '-' }}</p>
                                    <p>Final Approver: {{ $assignment->finalApprover?->name ?? 'Not required' }}</p>
                                    <p>Start: {{ $assignment->starts_on?->format('Y-m-d') ?? 'Open' }}</p>
                                    <p>End: {{ $assignment->ends_on?->format('Y-m-d') ?? 'Open' }}</p>
                                    <p> {{ $assignment->template->guideline }}</p>
                                </div>

                                <div
                                    class="rounded-xl bg-white px-3 py-2 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                                    Calendar push: {{ $assignment->calendar_push_enabled ? 'Enabled' : 'Disabled' }}
                                    • Reminder
                                    {{ $assignment->calendarControl?->reminder_start_time ? \Illuminate\Support\Str::of($assignment->calendarControl->reminder_start_time)->substr(0, 5) : '08:45' }}
                                    • Every {{ $assignment->calendarControl?->reminder_interval_minutes ?? 60 }}
                                    minutes
                                    • Refresh
                                    {{ $assignment->calendarControl?->weekly_monthly_refresh_time ? \Illuminate\Support\Str::of($assignment->calendarControl->weekly_monthly_refresh_time)->substr(0, 5) : '09:15' }}
                                </div>

                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                                    {{ $assignment->instances_count }} generated instances
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" wire:click="editAssignment({{ $assignment->id }})"
                                    @click="$openModal('assignmentModal')"
                                    class="rounded-lg border border-yellow-300 px-3 py-1.5 text-sm font-medium text-yellow-700 hover:bg-yellow-50">
                                    <x-icon teal name="pencil" class="h-4 w-4" />
                                </button>
                                <button type="button" wire:click="deleteAssignment({{ $assignment->id }})"
                                    wire:confirm="Delete this assignment?"
                                    class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                                    <x-icon red name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        No employee assignments yet.
                    </div>
                @endforelse
            </div>
        </article>

        @if ($canManageInstances)
            <article
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Created Task Instances</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            Super Admin can review, edit, and delete generated KPI instances per employee.
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <x-select label="Who?" placeholder="Filter employee" wire:model.live="instanceUserId"
                            :async-data="route('users.index')" option-label="name" option-value="id" />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Status</label>
                        <select wire:model.live="instanceStatusFilter"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @foreach ($this->instanceStatusOptions as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Task Date</label>
                        <input type="date" wire:model.live="instanceDateFilter"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Search</label>
                        <input type="text" wire:model.live.debounce.300ms="instanceSearch"
                            placeholder="Template, employee, group, status"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                    </div>
                </div>

                <div class="mt-3 flex justify-end">
                    <button type="button" wire:click="clearInstanceFilters"
                        class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        Clear filters
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($instances as $instance)
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $instance->template?->title ?? '-' }}
                                        </p>
                                        <span class="rounded-full px-2 py-0.5 text-xs bg-blue-100 text-blue-700">
                                            {{ strtoupper($instance->period_type ?? '-') }}
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-xs bg-amber-100 text-amber-700">
                                            {{ $instance->status ?? '-' }}
                                        </span>
                                    </div>

                                    <div class="grid gap-2 text-sm text-slate-600 dark:text-slate-300 md:grid-cols-2">
                                        <p>Employee: {{ $instance->user?->name ?? '-' }}</p>
                                        <p>Group: {{ $instance->template?->group?->name ?? '-' }}</p>
                                        <p>Task Date: {{ $instance->task_date?->format('Y-m-d') ?? '-' }}</p>
                                        <p>Due: {{ $instance->due_at?->format('Y-m-d H:i') ?? '-' }}</p>
                                        <p>Period Start: {{ $instance->period_start?->format('Y-m-d') ?? '-' }}</p>
                                        <p>Period End: {{ $instance->period_end?->format('Y-m-d') ?? '-' }}</p>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" wire:click="editInstance({{ $instance->id }})"
                                        @click="$openModal('instanceModal')"
                                        class="rounded-lg border border-yellow-300 px-3 py-1.5 text-sm font-medium text-yellow-700 hover:bg-yellow-50">
                                        <x-icon teal name="pencil" class="h-4 w-4" />
                                    </button>
                                    <button type="button" wire:click="deleteInstance({{ $instance->id }})"
                                        wire:confirm="Delete this task instance?"
                                        class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                                        <x-icon red name="trash" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                            No generated instances found.
                        </div>
                    @endforelse
                </div>
            </article>
        @endif
    </section>

    <x-modal wire:model="assignmentModal">
        <x-card title="Employee Assignment">
            <form wire:submit.prevent="{{ $editingAssignmentId ? 'updateAssignment' : 'createAssignment' }}">
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Task Template</label>
                    <select wire:model.defer="assignmentTemplateId"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                        <option value="">Select task template</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}">
                                {{ $template->title }}{{ $template->group ? ' • ' . $template->group->name : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('assignmentTemplateId')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Employee</label>
                    @if ($selectedTemplate?->group?->department)
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Employee list is filtered to {{ $selectedTemplate->group->department->name }}
                            department
                            because of the selected KPI group.
                        </p>
                    @endif
                    <div class="mt-2">
                        <x-select label="" placeholder="Search employee" wire:model.defer="assignmentUserId"
                            :async-data="$employeeAsyncData" option-label="name" option-value="id" />
                    </div>
                    @error('assignmentUserId')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">First Approver</label>
                        <div class="mt-2">
                            <x-select label="" placeholder="Search first approver"
                                wire:model.defer="assignmentFirstApproverId" :async-data="$approverAsyncData" option-label="name"
                                option-value="id" />
                        </div>
                        @error('assignmentFirstApproverId')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Final Approver</label>
                        <div class="mt-2">
                            <x-select label="" placeholder="Search second approver"
                                wire:model.defer="assignmentFinalApproverId" :async-data="$approverAsyncData" option-label="name"
                                option-value="id" clearable />
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Optional. Leave empty if this
                            task
                            only needs one approver.</p>
                        @error('assignmentFinalApproverId')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Start Date</label>
                        <input type="date" wire:model.defer="assignmentStartsOn"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                        @error('assignmentStartsOn')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">End Date</label>
                        <input type="date" wire:model.defer="assignmentEndsOn"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                        @error('assignmentEndsOn')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Calendar Control</p>
                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentCalendarPushEnabled"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Enable calendar push
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentDailyReminderEnabled"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Daily reminder enabled
                        </label>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Reminder Start
                                Time</label>
                            <input type="time" wire:model.defer="assignmentReminderStartTime"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('assignmentReminderStartTime')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Reminder Interval
                                Minutes</label>
                            <input type="number" min="15" max="240"
                                wire:model.defer="assignmentReminderIntervalMinutes"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('assignmentReminderIntervalMinutes')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentWeeklyMonthlyRefreshEnabled"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Weekly/monthly refresh enabled
                        </label>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Weekly/Monthly
                                Refresh
                                Time</label>
                            <input type="time" wire:model.defer="assignmentWeeklyMonthlyRefreshTime"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('assignmentWeeklyMonthlyRefreshTime')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentPushUntilFinalized"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Push until finalized
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentIsActive"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Assignment active
                        </label>
                    </div>
                </div>

                <button type="submit"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    {{ $editingAssignmentId ? 'Update Assignment' : 'Create Assignment' }}
                </button>
            </form>


            {{-- <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='updateSale' green />
                </div>
            </x-slot> --}}
        </x-card>
    </x-modal>

    <x-modal wire:model="instanceModal">
        <x-card title="Edit Task Instance">
            <form wire:submit.prevent="updateInstance" class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Status</label>
                    <select wire:model.defer="instanceStatus"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="pending">pending</option>
                        <option value="rejected">rejected</option>
                        <option value="waiting_first_approval">waiting_first_approval</option>
                        <option value="waiting_final_approval">waiting_final_approval</option>
                        <option value="passed">passed</option>
                        <option value="failed_late">failed_late</option>
                        <option value="failed_missed">failed_missed</option>
                        <option value="excluded">excluded</option>
                    </select>
                    @error('instanceStatus')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($editingSubmissionId)
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" wire:model.defer="instanceIsLate"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                        Mark this approved submission as late
                    </label>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Task Date</label>
                        <input type="date" wire:model.defer="instanceTaskDate"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('instanceTaskDate')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Due At</label>
                        <input type="datetime-local" wire:model.defer="instanceDueAt"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('instanceDueAt')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Period Start</label>
                        <input type="date" wire:model.defer="instancePeriodStart"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('instancePeriodStart')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Period End</label>
                        <input type="date" wire:model.defer="instancePeriodEnd"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('instancePeriodEnd')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if ($editingSubmissionId)
                    <div class="space-y-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Submission Photos</p>

                        @if (count($existingSubmissionImages) > 0)
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($existingSubmissionImages as $image)
                                    @php $markedRemove = in_array($image['id'], $removeSubmissionImageIds, true); @endphp
                                    <div
                                        class="rounded-xl border p-3 {{ $markedRemove ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900' }}">
                                        <img src="{{ $image['url'] }}" alt="Submission image {{ $image['id'] }}"
                                            class="h-32 w-full rounded-lg object-cover">
                                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-300">
                                            {{ $image['title'] ?: 'No title' }}</p>
                                        <button type="button"
                                            wire:click="markSubmissionImageRemoval({{ $image['id'] }})"
                                            class="mt-2 rounded-lg border px-2 py-1 text-xs {{ $markedRemove ? 'border-emerald-300 text-emerald-700' : 'border-rose-300 text-rose-700' }}">
                                            {{ $markedRemove ? 'Keep' : 'Remove' }}
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Add New
                                Photos</label>
                            <input type="file" wire:model="newSubmissionPhotos" multiple accept="image/*"
                                class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('newSubmissionPhotos.*')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if (count($newSubmissionPhotos) > 0)
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($newSubmissionPhotos as $index => $photo)
                                    <div
                                        class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                        @if (method_exists($photo, 'temporaryUrl'))
                                            <img src="{{ $photo->temporaryUrl() }}"
                                                alt="New photo {{ $index + 1 }}"
                                                class="h-32 w-full rounded-lg object-cover">
                                        @endif
                                        <input type="text"
                                            wire:model.defer="newSubmissionPhotoTitles.{{ $index }}"
                                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                            placeholder="Title">
                                        <textarea wire:model.defer="newSubmissionPhotoRemarks.{{ $index }}" rows="2"
                                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                            placeholder="Remark"></textarea>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                        Update Instance
                    </button>
                    <button type="button" wire:click="cancelInstance"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                        Cancel
                    </button>
                </div>
            </form>
        </x-card>
    </x-modal>
    <script>
        Livewire.on('closeModal', (name) => {
            $closeModal(name);
        });
        Livewire.on('openModal', (name) => {
            $openModal(name);
        });
    </script>
</div>
