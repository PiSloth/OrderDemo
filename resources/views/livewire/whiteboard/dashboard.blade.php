<div class="space-y-4">
    @php
        $summary = $dashboard['summary'];
        $departmentTrend = $dashboard['department_trend_chart'];
        $trendCategories = $departmentTrend['categories'] ?? [];
        $trendSeries = $departmentTrend['series'] ?? [];
        $trendMax = max(1, collect($trendSeries)->flatMap(fn($series) => $series['data'])->max() ?? 0);
        $chartWidth = 760;
        $chartHeight = 280;
        $chartPaddingLeft = 44;
        $chartPaddingRight = 18;
        $chartPaddingTop = 18;
        $chartPaddingBottom = 34;
        $plotWidth = $chartWidth - $chartPaddingLeft - $chartPaddingRight;
        $plotHeight = $chartHeight - $chartPaddingTop - $chartPaddingBottom;
        $trendCount = count($trendCategories);
        $gridSteps = 4;
    @endphp

    <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Whiteboard Dashboard</h1>
            <p class="text-sm text-slate-500">Content flow, decision pressure, and recipient engagement across the whiteboard.</p>
        </div>
        <div class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm">
            Most sent department:
            <span class="ml-1.5 font-semibold text-slate-900">
                {{ $departmentTrend['top_department'] ?? 'No data yet' }}
            </span>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visible Content</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $summary['visible_contents'] }}</p>
            <p class="mt-2 text-xs text-slate-500">All active items in the whiteboard feed.</p>
        </article>

        <article class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Unread</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $summary['unread_contents'] }}</p>
            <p class="mt-2 text-xs text-slate-500">Items not yet read by your matched recipients.</p>
        </article>

        <article class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pending Decision</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $summary['pending_decisions'] }}</p>
            <p class="mt-2 text-xs text-slate-500">Decision-required content with no final decision yet.</p>
        </article>

        <article class="rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Due Today</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $summary['due_today'] }}</p>
            <p class="mt-2 text-xs text-slate-500">Decision items scheduled for today.</p>
        </article>

        <article class="rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Overdue</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $summary['overdue_decisions'] }}</p>
            <p class="mt-2 text-xs text-slate-500">Past due and still waiting for a decision.</p>
        </article>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.8fr)_minmax(0,1fr)]">
        <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Top Sent Departments Trend</h2>
                    <p class="text-sm text-slate-500">Unique content deliveries by department across the most recent whiteboard activity dates.</p>
                </div>
                @if (($departmentTrend['top_department'] ?? null) && ($departmentTrend['top_count'] ?? 0) > 0)
                    <div class="rounded-2xl bg-teal-50 px-3 py-2 text-right">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-teal-700">Leader</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $departmentTrend['top_department'] }}</p>
                        <p class="text-xs text-slate-500">{{ $departmentTrend['top_count'] }} sends</p>
                    </div>
                @endif
            </div>

            @if ($trendSeries !== [] && $trendCategories !== [])
                <div class="mt-5 overflow-x-auto">
                    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="h-[18rem] w-full min-w-[42rem]">
                        @for ($step = 0; $step <= $gridSteps; $step++)
                            @php
                                $gridY = $chartPaddingTop + (($plotHeight / $gridSteps) * $step);
                                $gridValue = (int) round($trendMax - (($trendMax / $gridSteps) * $step));
                            @endphp
                            <line x1="{{ $chartPaddingLeft }}" y1="{{ $gridY }}" x2="{{ $chartWidth - $chartPaddingRight }}"
                                y2="{{ $gridY }}" stroke="#E2E8F0" stroke-dasharray="4 6" />
                            <text x="{{ $chartPaddingLeft - 10 }}" y="{{ $gridY + 4 }}" text-anchor="end"
                                class="fill-slate-400 text-[11px]">{{ $gridValue }}</text>
                        @endfor

                        @foreach ($trendCategories as $index => $label)
                            @php
                                $x = $trendCount > 1
                                    ? $chartPaddingLeft + (($plotWidth / max($trendCount - 1, 1)) * $index)
                                    : $chartPaddingLeft + ($plotWidth / 2);
                            @endphp
                            <text x="{{ $x }}" y="{{ $chartHeight - 8 }}" text-anchor="middle"
                                class="fill-slate-400 text-[11px]">{{ $label }}</text>
                        @endforeach

                        @foreach ($trendSeries as $series)
                            @php
                                $points = collect($series['data'])->map(function ($value, $index) use (
                                    $trendCount,
                                    $chartPaddingLeft,
                                    $plotWidth,
                                    $chartPaddingTop,
                                    $plotHeight,
                                    $trendMax
                                ) {
                                    $x = $trendCount > 1
                                        ? $chartPaddingLeft + (($plotWidth / max($trendCount - 1, 1)) * $index)
                                        : $chartPaddingLeft + ($plotWidth / 2);
                                    $y = $chartPaddingTop + $plotHeight - (($value / max($trendMax, 1)) * $plotHeight);

                                    return round($x, 2) . ',' . round($y, 2);
                                });
                            @endphp

                            <polyline fill="none" stroke="{{ $series['color'] }}" stroke-width="3"
                                stroke-linecap="round" stroke-linejoin="round"
                                points="{{ $points->implode(' ') }}" />

                            @foreach ($series['data'] as $pointIndex => $value)
                                @php
                                    $pointX = $trendCount > 1
                                        ? $chartPaddingLeft + (($plotWidth / max($trendCount - 1, 1)) * $pointIndex)
                                        : $chartPaddingLeft + ($plotWidth / 2);
                                    $pointY = $chartPaddingTop + $plotHeight - (($value / max($trendMax, 1)) * $plotHeight);
                                @endphp
                                <circle cx="{{ round($pointX, 2) }}" cy="{{ round($pointY, 2) }}" r="4.5"
                                    fill="{{ $series['color'] }}" stroke="white" stroke-width="2" />
                            @endforeach
                        @endforeach
                    </svg>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($trendSeries as $series)
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $series['color'] }};"></span>
                            <span>{{ $series['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                    No department trend data is available yet.
                </div>
            @endif
        </article>

        <article class="rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-5 text-white shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold">Operational Health</h2>
                    <p class="mt-1 text-sm text-slate-300">Popular dashboard signals for follow-up, responsiveness, and decision velocity.</p>
                </div>
                <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-200">
                    {{ $summary['sent_departments'] }} departments
                </span>
            </div>

            <div class="mt-6 space-y-5">
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-300">Recipient Read Rate</span>
                        <span class="font-semibold text-white">{{ $summary['read_rate'] }}%</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-white/10">
                        <div class="h-2 rounded-full bg-emerald-400" style="width: {{ min(100, max(0, $summary['read_rate'])) }}%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-300">Decision Completion</span>
                        <span class="font-semibold text-white">{{ $summary['decision_completion_rate'] }}%</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-white/10">
                        <div class="h-2 rounded-full bg-sky-400" style="width: {{ min(100, max(0, $summary['decision_completion_rate'])) }}%;"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-white/5 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-300">Avg Decision Turnaround</p>
                        <p class="mt-2 text-xl font-semibold text-white">{{ $summary['avg_decision_turnaround_label'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-300">Decision Backlog</p>
                        <p class="mt-2 text-xl font-semibold text-white">{{ $summary['pending_decisions'] }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-300">Priority Snapshot</p>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-slate-400">Due today</p>
                            <p class="mt-1 font-semibold text-white">{{ $summary['due_today'] }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400">Overdue</p>
                            <p class="mt-1 font-semibold text-white">{{ $summary['overdue_decisions'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Top Content Types</h2>
                <span class="text-xs text-slate-400">Popular mix</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($dashboard['top_content_types'] as $typeRow)
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-slate-700">{{ $typeRow['label'] }}</span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            {{ $typeRow['count'] }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No content type activity yet.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Most Active Reporters</h2>
                <span class="text-xs text-slate-400">Content creators</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($dashboard['top_reporters'] as $reporterRow)
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-slate-700">{{ $reporterRow['label'] }}</span>
                        <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                            {{ $reporterRow['count'] }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No reporter activity yet.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Top Flags</h2>
                <span class="text-xs text-slate-400">Escalation mix</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($dashboard['top_flags'] as $flagRow)
                    <div class="flex items-center justify-between gap-3">
                        <div class="inline-flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $flagRow['color'] }};"></span>
                            <span class="text-sm font-medium text-slate-700">{{ $flagRow['label'] }}</span>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold text-slate-700"
                            style="background-color: {{ $flagRow['color'] }}20;">
                            {{ $flagRow['count'] }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No flagged content in this view.</p>
                @endforelse
            </div>
        </article>
    </div>
</div>
