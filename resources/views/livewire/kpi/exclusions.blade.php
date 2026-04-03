<div class="space-y-6">
    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white">
        <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Exclusions</p>
        <h2 class="mt-2 text-3xl font-semibold">Day and task exclusion requests.</h2>
        <p class="mt-3 max-w-3xl text-sm text-slate-200">
            Staff can request day-level or daily-task-level exclusion. Pending and approved requests are reflected in the audit matrix.
        </p>
    </section>

    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('message') }}
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">New Exclusion Request</h3>

            <form wire:submit="createRequest" class="mt-4 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Request Type</label>
                        <select wire:model.live="requestType"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <option value="day">Day Exclusion</option>
                            <option value="task">Task Exclusion</option>
                        </select>
                        @error('requestType') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Requested Date</label>
                        <input type="date" wire:model.defer="requestedDate"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('requestedDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if ($requestType === 'task')
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Daily Task</label>
                        <select wire:model.defer="requestTaskAssignmentId"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">Select daily task</option>
                            @foreach ($taskAssignments as $assignment)
                                <option value="{{ $assignment->id }}">
                                    {{ $assignment->template?->title }}{{ $assignment->template?->group ? ' - ' . $assignment->template->group->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Phase 1 allows task-level exclusion only for daily tasks.</p>
                        @error('requestTaskAssignmentId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Reason</label>
                    <textarea wire:model.defer="requestReason" rows="4"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        placeholder="Explain why you need this exclusion"></textarea>
                    @error('requestReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Submit Request
                </button>
            </form>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">My Requests</h3>
                <input type="month" wire:model.live="month"
                    class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($myRequests as $request)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-900 dark:text-slate-100">
                                {{ $request->request_type === 'day' ? 'Day Exclusion' : 'Task Exclusion' }}
                            </p>
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $request->status === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : ($request->status === 'rejected' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300') }}">
                                {{ ucfirst($request->status) }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                            {{ $request->requested_date?->format('Y-m-d') }}
                            @if ($request->assignment?->template?->title)
                                - {{ $request->assignment->template->title }}
                            @endif
                        </p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $request->reason }}</p>
                        @if ($request->reviewer_remark)
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Reviewer remark: {{ $request->reviewer_remark }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        No exclusion requests for this month.
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    @if ($canReviewRequests)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Pending Reviews</h3>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ $pendingReviews->count() }} request(s)</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($pendingReviews as $request)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <img src="{{ $request->user?->profile_photo_url }}"
                                        alt="{{ $request->user?->name }}"
                                        class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $request->user?->name ?? '-' }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $request->user?->department?->name ?? 'No department' }}</p>
                                    </div>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-300">
                                    {{ $request->request_type === 'day' ? 'Day exclusion' : 'Task exclusion' }}
                                    - {{ $request->requested_date?->format('Y-m-d') }}
                                    @if ($request->assignment?->template?->title)
                                        - {{ $request->assignment->template->title }}
                                    @endif
                                </p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $request->reason }}</p>
                                <textarea wire:model.defer="reviewRemarks.{{ $request->id }}" rows="2"
                                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    placeholder="Optional reviewer remark"></textarea>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" wire:click="approveRequest({{ $request->id }})"
                                    class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                    Approve
                                </button>
                                <button type="button" wire:click="rejectRequest({{ $request->id }})"
                                    class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700">
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        No pending exclusion requests for this month.
                    </div>
                @endforelse
            </div>
        </section>
    @endif
</div>
