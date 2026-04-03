<div class="space-y-6">
    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Leaderboard</p>
                <h2 class="mt-2 text-3xl font-semibold">Completion and on-time competition.</h2>
                <p class="mt-3 max-w-3xl text-sm text-slate-200">
                    Rankings are ordered by completion rate first, then on-time rate, then pass rate.
                </p>
            </div>

            <div class="inline-flex rounded-2xl bg-white/10 p-1">
                <button
                    type="button"
                    wire:click="$set('period', 'week')"
                    class="rounded-2xl px-4 py-2 text-sm font-medium transition {{ $period === 'week' ? 'bg-white text-slate-900' : 'text-slate-200 hover:text-white' }}"
                >
                    This Week
                </button>
                <button
                    type="button"
                    wire:click="$set('period', 'month')"
                    class="rounded-2xl px-4 py-2 text-sm font-medium transition {{ $period === 'month' ? 'bg-white text-slate-900' : 'text-slate-200 hover:text-white' }}"
                >
                    This Month
                </button>
            </div>
        </div>
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

    <section class="grid gap-6 {{ $canViewCompanyLeaderboard ? 'xl:grid-cols-2' : '' }}">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Department Ranking</h3>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ count($departmentLeaderboard) }} employee(s)</span>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Rank</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Employee</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Completion</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">On Time</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        @forelse ($departmentLeaderboard as $entry)
                            <tr class="{{ $entry['is_current_user'] ? 'bg-sky-50 dark:bg-sky-950/40' : 'dark:bg-slate-900' }}">
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">#{{ $entry['rank'] }}</td>
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
                                <td colspan="5" class="px-4 py-4 text-center text-slate-500 dark:text-slate-400">No department ranking data for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        @if ($canViewCompanyLeaderboard)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Company Ranking</h3>
                    <span class="text-sm text-slate-500 dark:text-slate-400">{{ count($companyLeaderboard) }} employee(s)</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Rank</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Employee</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Department</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Completion</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">On Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                            @forelse ($companyLeaderboard as $entry)
                                <tr class="{{ $entry['is_current_user'] ? 'bg-sky-50 dark:bg-sky-950/40' : 'dark:bg-slate-900' }}">
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">#{{ $entry['rank'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $entry['profile_photo_url'] }}"
                                                alt="{{ $entry['name'] }}"
                                                class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                                            <p class="font-medium text-slate-900 dark:text-slate-100">{{ $entry['name'] }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $entry['department'] }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($entry['completion_rate'], 2) }}%</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($entry['on_time_rate'], 2) }}%</td>
                                </tr>
                            @empty
                                <tr class="dark:bg-slate-900">
                                    <td colspan="5" class="px-4 py-4 text-center text-slate-500 dark:text-slate-400">No company ranking data for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @else
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Company Ranking</h3>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                    This ranking is visible only to Assistant Manager, Manager, Assistant General Manager, CEO, and Super Admin.
                </p>
            </article>
        @endif
    </section>
</div>
