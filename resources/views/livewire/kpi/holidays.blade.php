<div class="space-y-6">
    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white">
        <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Holidays</p>
        <h2 class="mt-2 text-3xl font-semibold">Employee holiday calendar.</h2>
        <p class="mt-3 max-w-3xl text-sm text-slate-200">
            Holidays exclude one employee from daily KPI tasks for the affected day and gray out that date in the audit matrix.
        </p>
    </section>

    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    {{ $editingHolidayId ? 'Edit Holiday' : 'New Holiday' }}
                </h3>
                @if ($editingHolidayId)
                    <button
                        type="button"
                        wire:click="cancelHoliday"
                        class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                    >
                        Cancel
                    </button>
                @endif
            </div>

            <form wire:submit="{{ $editingHolidayId ? 'updateHoliday' : 'createHoliday' }}" class="mt-4 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Holiday Date</label>
                        <input
                            type="date"
                            wire:model.defer="holidayDate"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        >
                        @error('holidayDate')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Employee</label>
                        <div class="mt-1">
                            <x-select
                                label=""
                                placeholder="Search employee"
                                wire:model="holidayUserId"
                                :async-data="route('users.index')"
                                option-label="name"
                                option-value="id"
                            />
                        </div>
                        @error('holidayUserId')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Holiday Name</label>
                    <input
                        type="text"
                        wire:model.defer="holidayName"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        placeholder="Example: Personal Leave"
                    >
                    @error('holidayName')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Remark</label>
                    <textarea
                        wire:model.defer="holidayRemark"
                        rows="3"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        placeholder="Optional note for this holiday"
                    ></textarea>
                    @error('holidayRemark')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input
                        type="checkbox"
                        wire:model.defer="holidayIsActive"
                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                    >
                    Active
                </label>

                <button
                    type="submit"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                >
                    {{ $editingHolidayId ? 'Update Holiday' : 'Create Holiday' }}
                </button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Holiday List</h3>
                <input
                    type="month"
                    wire:model.live="month"
                    class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                >
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($holidays as $holiday)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $holiday->name }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $holiday->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                        {{ $holiday->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $holiday->holiday_date?->format('Y-m-d') }} - {{ $holiday->user?->name ?? 'Unknown employee' }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $holiday->remark ?: 'No remark' }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    wire:click="editHoliday({{ $holiday->id }})"
                                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    wire:click="deleteHoliday({{ $holiday->id }})"
                                    wire:confirm="Delete this holiday?"
                                    class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-white dark:border-rose-700 dark:text-rose-300 dark:hover:bg-slate-900"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        No holidays found for this month.
                    </div>
                @endforelse
            </div>
        </article>
    </section>
</div>
