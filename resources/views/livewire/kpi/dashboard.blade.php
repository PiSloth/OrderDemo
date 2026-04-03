<div class="space-y-6">
    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white">
        <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Dashboard</p>
        <h2 class="mt-2 text-3xl font-semibold">Live KPI view for this month.</h2>
        <p class="mt-3 max-w-3xl text-sm text-slate-200">
            This dashboard is built from current task instances and approval queues, so staff and managers can see real progress now.
        </p>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['value'] }}</p>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $card['hint'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">My KPI Groups This Month</h3>
                <a href="{{ route('kpi.tasks') }}" class="text-sm font-medium text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-slate-100">
                    Open My Tasks
                </a>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">KPI Group</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Must Do</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Passed</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Failed</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Completion</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Pass Rate</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Rule</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Target</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Actual</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        @forelse ($groupStats as $group)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $group['group_name'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $group['must_do_count'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $group['passed_count'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $group['failed_count'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($group['completion_rate'], 2) }}%</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($group['pass_rate'], 2) }}%</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ str_replace('_', ' ', $group['rule_type'] ?? 'not set') }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $group['target_display'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $group['actual_display'] }}</td>
                                <td class="px-4 py-3">
                                    @if ($group['passes_rule'] === null)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">Not set</span>
                                    @elseif ($group['passes_rule'])
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Pass</span>
                                    @else
                                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Fail</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="dark:bg-slate-900">
                                <td colspan="10" class="px-4 py-4 text-center text-slate-500 dark:text-slate-400">No KPI group data for this month yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Today Tasks</h3>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ $todayTasks->count() }} item(s)</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($todayTasks as $task)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $task->template?->title }}</p>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $task->group?->name ?? 'No KPI Group' }}</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Due {{ $task->due_at ? $task->due_at->format('H:i') : 'No cutoff' }} • {{ str_replace('_', ' ', $task->status) }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No daily task instances for today.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">My Approval Queue</h3>
                <a href="{{ route('kpi.approvals') }}" class="text-sm font-medium text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-slate-100">
                    Open Approvals
                </a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($pendingApprovals as $step)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $step->submission?->instance?->template?->title }}</p>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $step->submission?->instance?->user?->name ?? '-' }}</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Step {{ $step->step_order }} • Submitted {{ $step->submission?->submitted_at?->format('Y-m-d H:i') ?? '-' }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No pending approvals assigned to you.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Department Top 5</h3>
                <a href="{{ route('kpi.leaderboard') }}" class="text-sm font-medium text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-slate-100">
                    Open Leaderboard
                </a>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Employee</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Completion</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">On Time</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        @forelse ($departmentLeaderboard as $entry)
                            <tr class="{{ $entry['is_current_user'] ? 'bg-sky-50 dark:bg-sky-950/40' : 'dark:bg-slate-900' }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $entry['profile_photo_url'] }}"
                                            alt="{{ $entry['name'] }}"
                                            class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                                        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $entry['name'] }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($entry['completion_rate'], 2) }}%</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($entry['on_time_rate'], 2) }}%</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($entry['pass_rate'], 2) }}%</td>
                            </tr>
                        @empty
                            <tr class="dark:bg-slate-900">
                                <td colspan="4" class="px-4 py-4 text-center text-slate-500 dark:text-slate-400">No department leaderboard data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    @if ($canViewCompanyLeaderboard)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Company Top 5</h3>
                <a href="{{ route('kpi.leaderboard') }}" class="text-sm font-medium text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-slate-100">
                    Open Company Leaderboard
                </a>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Employee</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Completion</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">On Time</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        @forelse ($companyLeaderboard as $entry)
                            <tr class="{{ $entry['is_current_user'] ? 'bg-sky-50 dark:bg-sky-950/40' : 'dark:bg-slate-900' }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $entry['profile_photo_url'] }}"
                                            alt="{{ $entry['name'] }}"
                                            class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                                        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $entry['name'] }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($entry['completion_rate'], 2) }}%</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($entry['on_time_rate'], 2) }}%</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($entry['pass_rate'], 2) }}%</td>
                            </tr>
                        @empty
                            <tr class="dark:bg-slate-900">
                                <td colspan="4" class="px-4 py-4 text-center text-slate-500 dark:text-slate-400">No company leaderboard data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
