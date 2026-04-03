<div class="space-y-6">
    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('message') }}
        </div>
    @endif

    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white">
        <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Approvals</p>
        <h2 class="mt-2 text-3xl font-semibold">Review KPI submissions in sequence.</h2>
        <p class="mt-3 max-w-3xl text-sm text-slate-200">
            The second approver only sees submissions after the first approver has approved them.
        </p>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    @if ($selectedStep)
        <section class="rounded-3xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-900 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $selectedStep->submission?->instance?->template?->title }}</h3>
                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.15em] text-sky-700">
                            Step {{ $selectedStep->step_order }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.15em] text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ $selectedStep->submission?->instance?->template?->group?->name ?? 'No KPI Group' }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Employee: {{ $selectedStep->submission?->instance?->user?->name ?? '-' }}
                    </p>
                    <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                        <p>Submitted: {{ $selectedStep->submission?->submitted_at?->format('Y-m-d H:i') ?? '-' }}</p>
                        <p>Due: {{ $selectedStep->submission?->instance?->due_at?->format('Y-m-d H:i') ?? 'No cutoff' }}</p>
                        <p>On Time: {{ $selectedStep->submission?->is_late ? 'Late' : 'On time' }}</p>
                        <p>Submitted By: {{ $selectedStep->submission?->submittedBy?->name ?? '-' }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="cancelDecision"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-400 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-slate-100"
                >
                    Close
                </button>
            </div>

            @if ($selectedStep->submission?->employee_remark)
                <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Employee Remark</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ $selectedStep->submission->employee_remark }}</p>
                </div>
            @endif

            <div class="mt-5 space-y-3">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Submitted Photos</p>
                @if ($selectedStep->submission?->images?->isNotEmpty())
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach ($selectedStep->submission->images as $image)
                            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                <img
                                    src="{{ asset('storage/' . ltrim($image->image_path, '/')) }}"
                                    alt="{{ $image->title ?: 'Submission image' }}"
                                    class="h-56 w-full object-cover"
                                >
                                <div class="space-y-2 p-4">
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $image->title ?: 'No title' }}</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $image->remark ?: 'No remark' }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        No images found on this submission.
                    </div>
                @endif
            </div>

            <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Approval Steps</p>
                <div class="mt-3 space-y-2">
                    @foreach ($selectedStep->submission?->approvalSteps?->sortBy('step_order') ?? [] as $step)
                        <div class="flex flex-col gap-1 rounded-2xl bg-white px-4 py-3 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-slate-100">Step {{ $step->step_order }} - {{ $step->role_label ?: 'Approver' }}</p>
                                <p>{{ $step->approver?->name ?? 'Unassigned' }}</p>
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">
                                <p>Status: {{ str_replace('_', ' ', $step->status) }}</p>
                                <p>{{ $step->acted_at?->format('Y-m-d H:i') ?? 'Pending' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-5">
                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Approval Remark</label>
                <textarea
                    wire:model.defer="decisionRemark"
                    rows="3"
                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    placeholder="Optional when approving, required when rejecting"
                ></textarea>
                @error('decisionRemark')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    wire:click="rejectSelected"
                    class="inline-flex items-center justify-center rounded-2xl bg-rose-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-rose-700"
                >
                    Reject
                </button>
                <button
                    type="button"
                    wire:click="approveSelected"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800"
                >
                    Approve
                </button>
            </div>
        </section>
    @endif

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">First-Step Queue</h3>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ $pendingFirstSteps->count() }} item(s)</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($pendingFirstSteps as $step)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ $step->submission?->instance?->template?->title }}</p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $step->submission?->instance?->user?->name ?? '-' }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Submitted {{ $step->submission?->submitted_at?->format('Y-m-d H:i') ?? '-' }}
                                </p>
                            </div>

                            <button
                                type="button"
                                wire:click="openStep({{ $step->id }})"
                                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                            >
                                Review
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No pending first-step approvals.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Final-Step Queue</h3>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ $pendingFinalSteps->count() }} item(s)</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($pendingFinalSteps as $step)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ $step->submission?->instance?->template?->title }}</p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $step->submission?->instance?->user?->name ?? '-' }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    First approved {{ $step->submission?->first_approved_at?->format('Y-m-d H:i') ?? '-' }}
                                </p>
                            </div>

                            <button
                                type="button"
                                wire:click="openStep({{ $step->id }})"
                                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                            >
                                Review
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No pending final approvals.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Recent Decisions</h3>
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $recentSteps->count() }} item(s)</span>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($recentSteps as $step)
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900 dark:text-slate-100">{{ $step->submission?->instance?->template?->title }}</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $step->submission?->instance?->user?->name ?? '-' }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $step->acted_at?->format('Y-m-d H:i') ?? '-' }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-medium uppercase tracking-[0.15em] {{ $step->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            {{ $step->status }}
                        </span>
                    </div>

                    @if ($step->remark)
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $step->remark }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500 dark:text-slate-400">No recent approval actions yet.</p>
            @endforelse
        </div>
    </section>
</div>
