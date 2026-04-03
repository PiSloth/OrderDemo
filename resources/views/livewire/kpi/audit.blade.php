<div class="space-y-6">
    <section
        class="rounded-3xl border border-slate-200 bg-white px-6 py-7 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <p class="text-sm uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Audit Matrix</p>
        <h2 class="mt-2 text-3xl font-semibold text-slate-900 dark:text-slate-100">Monthly employee task audit.</h2>
        {{-- <p class="mt-3 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
            Tasks are shown in rows and month days in columns. Approved tasks mark the submitted date, failed tasks mark the missed or late date, and holidays or exclusions are grayed out.
        </p> --}}
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="grid gap-4 lg:grid-cols-[13rem_minmax(0,1fr)]">
            <div>
                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Month</label>
                <input type="month" wire:model.live="month"
                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-500">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Employee</label>
                <div class="mt-2 audit-employee-select">
                    <x-select label="" placeholder="Search employee" wire:model.live="selectedUserId"
                        :async-data="route('users.index')" option-label="name" option-value="id" />
                </div>
            </div>
        </div>
    </section>

    @if ($selectedUser)
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <img src="{{ $selectedUser->profile_photo_url }}" alt="{{ $selectedUser->name }}"
                        class="h-12 w-12 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $selectedUser->name }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $selectedUser->department?->name ?? 'No department' }}</p>
                    </div>
                </div>
            </article>
            <article
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">Task Rows</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $rows->count() }}</p>
            </article>
            <article
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">Approved Marks</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">
                    {{ $rows->sum(fn($row) => $row['summary']['passed']) }}</p>
            </article>
            <article
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">Failed Marks</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">
                    {{ $rows->sum(fn($row) => $row['summary']['failed']) }}</p>
            </article>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article
                class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/20">
                <p class="text-sm text-emerald-700 dark:text-emerald-300">KPI Groups Passed</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-800 dark:text-emerald-200">
                    {{ $groupCards['passed'] ?? 0 }}</p>
            </article>
            <article
                class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/20">
                <p class="text-sm text-rose-700 dark:text-rose-300">KPI Groups Failed</p>
                <p class="mt-3 text-3xl font-semibold text-rose-800 dark:text-rose-200">{{ $groupCards['failed'] ?? 0 }}
                </p>
            </article>
            <article
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">KPI Groups Not Set</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">
                    {{ $groupCards['not_set'] ?? 0 }}</p>
            </article>
        </section>

        <section
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">KPI Group Result</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">A KPI group passes only when the group rule passes
                    and every template under that group passes too.</p>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">KPI Group
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Templates
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Passed</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Failed</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Group Rule
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Target</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Actual</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($groupSummaries as $groupSummary)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                    {{ $groupSummary['group_name'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                    {{ $groupSummary['template_pass_count'] }} /
                                    {{ $groupSummary['template_total_count'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $groupSummary['passed'] }}
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $groupSummary['failed'] }}
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                    {{ str_replace('_', ' ', $groupSummary['group_rule_evaluation']['rule_type'] ?? 'not set') }}
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                    {{ $groupSummary['group_rule_evaluation']['target_display'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                    {{ $groupSummary['group_rule_evaluation']['actual_display'] }}</td>
                                <td class="px-4 py-3">
                                    @if ($groupSummary['passes_rule'] === null)
                                        <span
                                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">Not
                                            set</span>
                                    @elseif ($groupSummary['passes_rule'])
                                        <span
                                            class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Pass</span>
                                    @else
                                        <span
                                            class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Fail</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-4 text-center text-slate-500 dark:text-slate-400">No
                                    KPI group data for this month.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($selectedSubmission)
        <div class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm" wire:click="closeSubmissionDetail"></div>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <section
                class="max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-3xl border border-sky-200 bg-white p-5 shadow-2xl dark:border-sky-900 dark:bg-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                                {{ $selectedSubmission->instance?->template?->title }}</h3>
                            <span
                                class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.15em] text-sky-700">
                                {{ $selectedSubmission->status }}
                            </span>
                            <span
                                class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.15em] text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $selectedSubmission->instance?->template?->group?->name ?? 'No KPI Group' }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            Employee: {{ $selectedSubmission->instance?->user?->name ?? '-' }}
                        </p>
                        <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                            <p>Submitted: {{ $selectedSubmission->submitted_at?->format('Y-m-d H:i') ?? '-' }}</p>
                            <p>Due: {{ $selectedSubmission->instance?->due_at?->format('Y-m-d H:i') ?? 'No cutoff' }}
                            </p>
                            <p>On Time: {{ $selectedSubmission->is_late ? 'Late' : 'On time' }}</p>
                            <p>Submitted By: {{ $selectedSubmission->submittedBy?->name ?? '-' }}</p>
                        </div>
                    </div>

                    <button type="button" wire:click="closeSubmissionDetail"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-400 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-slate-100">
                        Close
                    </button>
                </div>

                @if ($selectedSubmission->employee_remark)
                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                            Employee Remark</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">
                            {{ $selectedSubmission->employee_remark }}</p>
                    </div>
                @endif

                <div class="mt-5 space-y-3">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Submitted Photos</p>
                    @if ($selectedSubmission->images->isNotEmpty())
                        <div class="grid gap-4 lg:grid-cols-2">
                            @foreach ($selectedSubmission->images as $image)
                                <article
                                    class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                    <img src="{{ asset('storage/' . ltrim($image->image_path, '/')) }}"
                                        alt="{{ $image->title ?: 'Submission image' }}"
                                        class="h-56 w-full object-cover">
                                    <div class="space-y-2 p-4">
                                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                            {{ $image->title ?: 'No title' }}</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-300">
                                            {{ $image->remark ?: 'No remark' }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                            No images found on this submission.
                        </div>
                    @endif
                </div>

                <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                        Approval Steps</p>
                    <div class="mt-3 space-y-2">
                        @foreach ($selectedSubmission->approvalSteps->sortBy('step_order') as $step)
                            <div
                                class="flex flex-col gap-1 rounded-2xl bg-white px-4 py-3 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-slate-100">Step
                                        {{ $step->step_order }} - {{ $step->role_label ?: 'Approver' }}</p>
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
            </section>
        </div>
    @endif

    <section
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Legend</h3>
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach ($legendItems as $item)
                <div class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm {{ $item['classes'] }}">
                    <span
                        class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white/60 px-2 text-sm font-semibold dark:bg-slate-900/40">
                        @if ($item['type'] === 'approved')
                            &#10003;
                        @else
                            @if ($item['type'] === 'failed')
                                &#10005;
                            @else
                                @if ($item['type'] === 'pending')
                                    &bull;
                                @else
                                    @if ($item['type'] === 'inactive')
                                        --
                                    @endif
                                @endif
                            @endif
                        @endif
                    </span>
                    <span>{{ $item['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80">
                        <th
                            class="sticky left-0 z-20 min-w-[18rem] border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-left font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                            Task
                        </th>
                        @foreach ($days as $day)
                            <th
                                class="min-w-[4.5rem] border-b border-r border-slate-200 px-2 py-3 text-center font-medium text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                <div>{{ $day->format('d') }}</div>
                                <div class="mt-1 text-[11px] uppercase">{{ $day->format('D') }}</div>
                            </th>
                        @endforeach
                        <th
                            class="min-w-max whitespace-nowrap border-b border-l border-slate-200 bg-slate-50 px-4 py-3 text-left font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                            Summary
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($assignment = $row['assignment'])
                        <tr class="align-top">
                            <td
                                class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-4 py-4 dark:border-slate-700 dark:bg-slate-900">
                                <p class="font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $assignment->template?->title ?? '-' }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                                    {{ $assignment->template?->group?->name ?? 'No KPI Group' }}
                                </p>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    {{ ucfirst($assignment->template?->frequency ?? 'task') }}
                                    @if ($assignment->template?->cutoff_time)
                                        - Cutoff
                                        {{ \Illuminate\Support\Str::of($assignment->template->cutoff_time)->substr(0, 5) }}
                                    @endif
                                </p>
                            </td>

                            @foreach ($row['cells'] as $cell)
                                <td
                                    class="border-b border-r border-slate-200 px-1 py-2 align-middle dark:border-slate-700 {{ $cell['classes'] }}">
                                    <div
                                        class="flex min-h-[4rem] flex-wrap items-center justify-center gap-1 text-center">
                                        @if ($cell['markers']->isNotEmpty())
                                            @foreach ($cell['markers'] as $marker)
                                                @if ($marker['submission_id'])
                                                    <button type="button"
                                                        wire:click="openSubmissionDetail({{ $marker['submission_id'] }})"
                                                        title="{{ $marker['label'] }}"
                                                        class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full px-2 text-xs font-semibold transition hover:scale-105 {{ $marker['classes'] }}">
                                                        @if ($marker['type'] === 'approved')
                                                            &#10003;
                                                        @else
                                                            @if ($marker['type'] === 'failed')
                                                                &#10005;
                                                            @else
                                                                @if ($marker['type'] === 'pending')
                                                                    &bull;
                                                                @else
                                                                    @if ($marker['type'] === 'rejected')
                                                                        !
                                                                    @else
                                                                        &bull;
                                                                    @endif
                                                                @endif
                                                            @endif
                                                        @endif
                                                    </button>
                                                @else
                                                    <span title="{{ $marker['label'] }}"
                                                        class="inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full px-2 text-xs font-semibold {{ $marker['classes'] }}">
                                                        @if ($marker['type'] === 'approved')
                                                            &#10003;
                                                        @else
                                                            @if ($marker['type'] === 'failed')
                                                                &#10005;
                                                            @else
                                                                @if ($marker['type'] === 'pending')
                                                                    &bull;
                                                                @else
                                                                    @if ($marker['type'] === 'rejected')
                                                                        !
                                                                    @else
                                                                        &bull;
                                                                    @endif
                                                                @endif
                                                            @endif
                                                        @endif
                                                    </span>
                                                @endif
                                            @endforeach
                                        @else
                                            @if ($cell['label'])
                                                <span class="px-2 text-[11px] font-medium">
                                                    {{ $cell['label'] }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endforeach

                            <td
                                class="border-l border-slate-200 bg-white px-4 py-4 text-sm dark:border-slate-700 dark:bg-slate-900">
                                <div class="min-w-max whitespace-nowrap text-slate-500 dark:text-slate-400">
                                    <span
                                        class="font-medium text-slate-900 dark:text-slate-100">{{ $row['summary']['passed'] }}
                                        / {{ $row['summary']['must_do'] }} passed</span>
                                    <span class="mx-2">|</span>
                                    <span>{{ $row['summary']['failed'] }} failed</span>
                                    @if ($row['summary']['excluded'] > 0)
                                        <span class="mx-2">|</span>
                                        <span>{{ $row['summary']['excluded'] }} excluded</span>
                                    @endif
                                    <span class="mx-2">|</span>
                                    <span>{{ number_format($row['summary']['percentage'], 2) }}%</span>
                                    <span class="mx-2">|</span>
                                    <span>Rule:
                                        {{ str_replace('_', ' ', $row['rule_evaluation']['rule_type'] ?? 'not set') }}</span>
                                    <span class="mx-2">|</span>
                                    <span>Target {{ $row['rule_evaluation']['target_display'] }} / Actual
                                        {{ $row['rule_evaluation']['actual_display'] }}</span>
                                    <span class="mx-2">|</span>
                                    @if ($row['rule_evaluation']['passes_rule'] === null)
                                        <span
                                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">Not
                                            set</span>
                                    @elseif ($row['rule_evaluation']['passes_rule'])
                                        <span
                                            class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Template
                                            Pass</span>
                                    @else
                                        <span
                                            class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Template
                                            Fail</span>
                                    @endif
                                    @if ($row['summary']['pending'] > 0)
                                        <span class="mx-2">|</span>
                                        <span
                                            class="text-xs text-amber-600 dark:text-amber-300">{{ $row['summary']['pending'] }}
                                            pending</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $days->count() + 2 }}"
                                class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                No task assignments available for this employee and month.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
