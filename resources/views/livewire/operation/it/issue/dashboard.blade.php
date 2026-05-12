<div class="mx-auto max-w-7xl px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Issue Dashboard</h1>
        <a href="{{ route('operation.it.issues.index') }}" wire:navigate
            class="rounded-xl bg-slate-900 px-4 py-2 text-white text-sm">Back to Issues</a>
    </div>

    @if ($showFollowUpAlert)
        <div class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-rose-700">
            Third-party follow-up today is <span class="font-semibold">{{ $todayFollowUpCount }}</span>. Minimum is 2 per day.
        </div>
    @elseif(!$isSunday)
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-700">
            Third-party follow-up target met for today ({{ $todayFollowUpCount }} items).
        </div>
    @endif

    <section class="rounded-2xl border bg-white p-4">
        <h2 class="text-lg font-semibold mb-3">Branch Summary (Without Third-Party)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-2 text-left">Branch</th>
                        <th class="p-2 text-left bg-yellow-100">Not Closed</th>
                        <th class="p-2 text-left bg-emerald-100">Closed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($branchStatusSummary as $row)
                        <tr class="border-t">
                            <td class="p-2">{{ $row->name }}</td>
                            <td class="p-2 font-semibold text-amber-700">{{ $row->not_closed_count }}</td>
                            <td class="p-2 font-semibold text-emerald-700">{{ $row->closed_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-2 text-slate-500">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border bg-white p-4">
            <h2 class="text-lg font-semibold mb-3">Status Summary (Without Third-Party)</h2>
            <div class="space-y-2">
                @forelse($statusSummaryWithoutThirdParty as $row)
                    <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                        <span>{{ $row->name }}</span>
                        <span class="font-semibold">{{ $row->total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No data.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border bg-white p-4">
            <h2 class="text-lg font-semibold mb-3">Third-Party Resolver Summary (Each Status)</h2>
            <div class="space-y-2">
                @forelse($thirdPartyStatusSummary as $row)
                    <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                        <span>{{ $row->name }}</span>
                        <span class="font-semibold">{{ $row->total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No data.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border bg-white p-4">
            <h2 class="text-lg font-semibold mb-3">Top 3 Sequence List</h2>
            <div class="space-y-2">
                @forelse($topSequenceIssues as $issue)
                    <div class="rounded-lg border px-3 py-2">
                        <div class="font-medium">#{{ $issue->resolution_sequence }} - {{ $issue->title }}</div>
                        <div class="text-xs text-slate-500">
                            {{ $issue->priority?->name ?? '-' }} / {{ $issue->importance?->name ?? '-' }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No sequenced issues.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border bg-white p-4">
            <h2 class="text-lg font-semibold mb-3">Today Follow-Up Items (Third-Party)</h2>
            <div class="space-y-2">
                @forelse($todayFollowUpItems as $issue)
                    <div class="rounded-lg border px-3 py-2">
                        <div class="font-medium">{{ $issue->title }}</div>
                        <div class="text-xs text-slate-500">
                            {{ $issue->follow_up_date?->format('Y-m-d H:i') ?? '-' }} by {{ $issue->followUpUpdater?->name ?? '-' }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No follow-up items for today.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border bg-white p-4">
            <h2 class="text-lg font-semibold mb-3">Daily Solved Items</h2>
            <div class="space-y-2">
                @forelse($dailySolvedItems as $issue)
                    <div class="rounded-lg border px-3 py-2">
                        <div class="font-medium">{{ $issue->title }}</div>
                        <div class="text-xs text-slate-500">
                            {{ $issue->creator?->name ?? '-' }} ({{ $issue->creator?->branch?->name ?? '-' }}) - {{ $issue->closed_date?->format('Y-m-d H:i') ?? '-' }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No solved items today.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border bg-white p-4">
            <h2 class="text-lg font-semibold mb-3">This Month Root Cause by Branch</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="p-2 text-left">Root Cause</th>
                            <th class="p-2 text-left">Branch</th>
                            <th class="p-2 text-left">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthRootCauseByBranch as $row)
                            <tr class="border-t">
                                <td class="p-2">{{ $row->root_cause_name }}</td>
                                <td class="p-2">{{ $row->branch_name }}</td>
                                <td class="p-2 font-semibold">{{ $row->total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-2 text-slate-500">No root-cause logs this month.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="rounded-2xl border bg-white p-4">
        <h2 class="text-lg font-semibold mb-3">Overdue Active Issues</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-2 text-left">Issue</th>
                        <th class="p-2 text-left">Due Date</th>
                        <th class="p-2 text-left">Priority</th>
                        <th class="p-2 text-left">Importance</th>
                        <th class="p-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overdueIssues as $issue)
                        <tr class="border-t">
                            <td class="p-2">{{ $issue->title }}</td>
                            <td class="p-2">{{ $issue->due_date?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="p-2">{{ $issue->priority?->name ?? '-' }}</td>
                            <td class="p-2">{{ $issue->importance?->name ?? '-' }}</td>
                            <td class="p-2">{{ $issue->status?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-2 text-slate-500">No overdue active issues.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

