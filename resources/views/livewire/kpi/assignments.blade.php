<div class="space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Employee Task Assignments</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Assign one KPI task template to one employee with first approver, final approver, active dates, and calendar push rules.
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

    <section class="grid gap-6 xl:grid-cols-[1.05fr_1.35fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    {{ $editingAssignmentId ? 'Edit Assignment' : 'New Assignment' }}
                </h3>
                @if ($editingAssignmentId)
                    <button type="button" wire:click="cancelAssignment" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        Cancel
                    </button>
                @endif
            </div>

            <form wire:submit.prevent="{{ $editingAssignmentId ? 'updateAssignment' : 'createAssignment' }}" class="mt-4 space-y-4">
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
                    @error('assignmentTemplateId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Employee</label>
                    @if ($selectedTemplate?->group?->department)
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Employee list is filtered to {{ $selectedTemplate->group->department->name }} department because of the selected KPI group.
                        </p>
                    @endif
                    <div class="mt-2">
                        <x-select
                            label=""
                            placeholder="Search employee"
                            wire:model.defer="assignmentUserId"
                            :async-data="$employeeAsyncData"
                            option-label="name"
                            option-value="id"
                        />
                    </div>
                    @error('assignmentUserId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">First Approver</label>
                        <div class="mt-2">
                            <x-select
                                label=""
                                placeholder="Search first approver"
                                wire:model.defer="assignmentFirstApproverId"
                                :async-data="$approverAsyncData"
                                option-label="name"
                                option-value="id"
                            />
                        </div>
                        @error('assignmentFirstApproverId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Final Approver</label>
                        <div class="mt-2">
                            <x-select
                                label=""
                                placeholder="Search second approver"
                                wire:model.defer="assignmentFinalApproverId"
                                :async-data="$approverAsyncData"
                                option-label="name"
                                option-value="id"
                                clearable
                            />
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Optional. Leave empty if this task only needs one approver.</p>
                        @error('assignmentFinalApproverId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Start Date</label>
                        <input type="date" wire:model.defer="assignmentStartsOn"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                        @error('assignmentStartsOn') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">End Date</label>
                        <input type="date" wire:model.defer="assignmentEndsOn"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                        @error('assignmentEndsOn') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Calendar Control</p>
                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentCalendarPushEnabled" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Enable calendar push
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentDailyReminderEnabled" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Daily reminder enabled
                        </label>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Reminder Start Time</label>
                            <input type="time" wire:model.defer="assignmentReminderStartTime"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('assignmentReminderStartTime') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Reminder Interval Minutes</label>
                            <input type="number" min="15" max="240" wire:model.defer="assignmentReminderIntervalMinutes"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('assignmentReminderIntervalMinutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentWeeklyMonthlyRefreshEnabled" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Weekly/monthly refresh enabled
                        </label>
                        <div>
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Weekly/Monthly Refresh Time</label>
                            <input type="time" wire:model.defer="assignmentWeeklyMonthlyRefreshTime"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
                            @error('assignmentWeeklyMonthlyRefreshTime') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentPushUntilFinalized" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Push until finalized
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model.defer="assignmentIsActive" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            Assignment active
                        </label>
                    </div>
                </div>

                <button type="submit"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    {{ $editingAssignmentId ? 'Update Assignment' : 'Create Assignment' }}
                </button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Existing Assignments</h3>

            <div class="mt-4 space-y-3">
                @forelse ($assignments as $assignment)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $assignment->template?->title ?? '-' }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $assignment->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                                    </span>
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
                                </div>

                                <div class="rounded-xl bg-white px-3 py-2 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                                    Calendar push: {{ $assignment->calendar_push_enabled ? 'Enabled' : 'Disabled' }}
                                    • Reminder {{ $assignment->calendarControl?->reminder_start_time ? \Illuminate\Support\Str::of($assignment->calendarControl->reminder_start_time)->substr(0, 5) : '08:45' }}
                                    • Every {{ $assignment->calendarControl?->reminder_interval_minutes ?? 60 }} minutes
                                    • Refresh {{ $assignment->calendarControl?->weekly_monthly_refresh_time ? \Illuminate\Support\Str::of($assignment->calendarControl->weekly_monthly_refresh_time)->substr(0, 5) : '09:15' }}
                                </div>

                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                                    {{ $assignment->instances_count }} generated instances
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" wire:click="editAssignment({{ $assignment->id }})"
                                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-white">
                                    Edit
                                </button>
                                <button type="button" wire:click="deleteAssignment({{ $assignment->id }})"
                                    wire:confirm="Delete this assignment?"
                                    class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-white">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        No employee assignments yet.
                    </div>
                @endforelse
            </div>
        </article>
    </section>
</div>
