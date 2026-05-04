<div class="space-y-4" id="kpi-certificate-root" x-data="{ open: false, activeImage: null, copied: false }">

    <div class="flex flex-wrap items-end gap-3">
        @can('kpiManageTemplates')
            <div>
                <label class="block text-xs font-medium text-slate-600">Month</label>
                <input type="month" wire:model.live="month"
                    class="rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600">Employee</label>
                <select wire:model.live="selectedUserId"
                    class="rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        @endcan
        <button type="button" onclick="window.print()"
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Print
        </button>
    </div>

    @if (!$selectedUser || !$certificate)
        <div class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500">
            No certificate data available.
        </div>
    @else
        @php
            $overall = $certificate['overall'];
            $groups = $certificate['groups'];
            $overhaul = $overall['percentage'];
            $groupPassCount = $groups->where('group_result', 'Pass')->count();
            $groupFailCount = $groups->where('group_result', 'Fail')->count();
            $directReportLink = route('kpi.certificate', ['month' => $month, 'user_id' => $selectedUserId]);
        @endphp

        <style>
            @media print {
                @page {
                    size: A4;
                    margin: 12mm;
                }
            }
        </style>

        <div
            class="mx-auto w-full max-w-[210mm] rounded-xl border border-slate-300 bg-white p-6 text-slate-900 shadow-sm print:shadow-none">
            <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                <div class="font-semibold text-slate-700">Direct Report Link</div>
                <div class="mt-2 flex items-center gap-3">
                    <button type="button"
                        @click="navigator.clipboard.writeText('{{ $directReportLink }}').then(() => { copied = true; setTimeout(() => copied = false, 1500); })"
                        class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">
                        Copy Link
                    </button>
                    <span x-show="copied" x-transition class="text-xs font-medium text-emerald-600"
                        style="display: none;">
                        Copied!
                    </span>
                </div>
            </div>

            <div class="flex items-start justify-between gap-6 border-b border-slate-200 pb-4">
                <div class="space-y-1 text-sm">
                    <div><span class="font-semibold">Employee Name:</span> {{ $selectedUser->name }}</div>
                    <div><span class="font-semibold">Department:</span> {{ $selectedUser->department?->name ?? '-' }}
                    </div>
                    <div><span class="font-semibold">As Of Certificate:</span>
                        {{ $certificate['month']->format('F Y') }}</div>
                </div>
                <img src="{{ asset('images/logo.png') }}" alt="Company Logo" class="h-16 w-auto object-contain">
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="text-sm font-semibold">Overhaul Percentage</div>
                    <input type="hidden" id="kpi-overhaul-value" value="{{ number_format($overhaul, 2, '.', '') }}">
                    <div class="mt-3 flex items-end gap-4" wire:ignore>
                        <div id="kpi-overhaul-half-chart" class="h-28 w-48"></div>
                        <!-- <div class="text-right" id="kpi-overhaul-meta">
                            <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($overhaul, 2) }}%</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $overall['passed_count'] }} / {{ $overall['must_do_count'] }} passed</div>
                        </div> -->
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="text-sm font-semibold">KPI Score</div>
                    <div class="mt-4 text-4xl font-bold">{{ number_format($overall['kpi_score'], 2) }}%</div>
                    <div class="mt-1 text-xs text-slate-500">Based on approved and on-time submissions.</div>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-left">No</th>
                            <th class="px-3 py-2 text-left">Title</th>
                            <th class="px-3 py-2 text-center">Late count</th>
                            <th class="px-3 py-2 text-center">Absent count</th>
                            <th class="px-3 py-2 text-center">Score</th>
                            <th class="px-3 py-2 text-center">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach ($groups as $group)
                            @if ($group['show_group_result'])
                                @php $rowspan = count($group['templates']) + 1; @endphp
                                <tr class="bg-slate-50 font-semibold dark:bg-slate-800/70">
                                    <td class="px-3 py-2 align-top" rowspan="{{ $rowspan }}">{{ $group['no'] }}
                                    </td>
                                    <td class="px-3 py-2">{{ $group['group_name'] }} (Group)</td>
                                    <td class="px-3 py-2 text-center">{{ $group['summary']['late_count'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $group['summary']['absent_count'] }}</td>
                                    <td class="px-3 py-2 text-center">
                                        {{ number_format($group['summary']['score'], 2) }}%</td>
                                    <td
                                        class="px-3 py-2 text-center {{ $group['group_result'] === 'Pass' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $group['group_result'] }}</td>
                                </tr>
                            @else
                                @php $rowspan = count($group['templates']); @endphp
                            @endif

                            @foreach ($group['templates'] as $templateIndex => $template)
                                <tr>
                                    @if (!$group['show_group_result'] && $templateIndex === 0)
                                        <td class="px-3 py-2 align-top" rowspan="{{ $rowspan }}">
                                            {{ $group['no'] }}</td>
                                    @endif
                                    <td class="px-3 py-2">{{ $template['title'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $template['summary']['late_count'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $template['summary']['absent_count'] }}</td>
                                    <td class="px-3 py-2 text-center">
                                        {{ number_format($template['summary']['score'], 2) }}%</td>
                                    <td
                                        class="px-3 py-2 text-center {{ $template['result'] === 'Pass' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $template['result'] }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="6" class="h-0 border-b-2 border-slate-300 dark:border-slate-600 p-0">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div
                class="mt-3 rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                Master Report: Final KPI Result Pass: <span
                    class="text-emerald-600 dark:text-emerald-400">{{ $groupPassCount }}</span>

            </div>

            <div class="mt-8">
                <h4 class="text-base font-semibold text-slate-900 dark:text-slate-100">Evidence of Passed KPI Group</h4>
                <div class="mt-3 space-y-4">
                    @forelse($passedEvidenceRows as $group)
                        <section class="rounded-xl border-2 border-teal-500 p-4">
                            <h5 class="text-sm font-semibold text-teal-700 dark:text-teal-300">
                                {{ $group['group_name'] }}</h5>
                            <div class="mt-3 space-y-3">
                                @foreach ($group['templates'] as $template)
                                    <article class="rounded-lg border-2 border-emerald-500 p-3">
                                        <div class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                                            {{ $template['template_title'] }}</div>
                                        <div class="mt-3 space-y-3">
                                            @foreach ($template['rows'] as $row)
                                                <div
                                                    class="rounded-md border border-slate-200 p-3 text-sm dark:border-slate-700">
                                                    <div class="grid gap-2 md:grid-cols-2">
                                                        <div><span class="font-semibold">Group Name:</span>
                                                            {{ $row['group_name'] }}</div>
                                                        <div><span class="font-semibold">Template Title:</span>
                                                            {{ $row['template_title'] }}</div>
                                                        <div>
                                                            <span class="font-semibold">Frequency:</span>
                                                            <span
                                                                class="ml-1 rounded-full px-2 py-0.5 text-xs font-semibold uppercase {{ $row['frequency'] === 'daily' ? 'bg-emerald-100 text-emerald-700' : ($row['frequency'] === 'weekly' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700') }}">
                                                                {{ $row['frequency'] }}
                                                            </span>
                                                        </div>
                                                        <div><span class="font-semibold">Requested Date:</span>
                                                            {{ optional($row['requested_date'])->format('Y-m-d H:i') ?? '-' }}
                                                        </div>
                                                    </div>
                                                    <div class="mt-2"><span class="font-semibold">Approve
                                                            Remark:</span> {{ $row['approve_remark'] }}</div>
                                                    <div class="mt-3">
                                                        <div class="mb-2 font-semibold">Evidence Images:</div>
                                                        @if (collect($row['images'])->isNotEmpty())
                                                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                                                @foreach ($row['images'] as $image)
                                                                    <button type="button"
                                                                        @click="activeImage = '{{ $image['url'] }}'; open = true"
                                                                        class="overflow-hidden rounded-md border border-slate-200 dark:border-slate-700">
                                                                        <img src="{{ $image['url'] }}"
                                                                            alt="{{ $image['title'] }}"
                                                                            class="h-24 w-full object-cover">
                                                                    </button>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div class="text-xs text-slate-500">No evidence image.
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div
                            class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                            No passed KPI evidence found for this month.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-12 grid grid-cols-2 gap-10 text-sm">
                <div>
                    <div class="font-semibold">Checked by</div>
                    <div class="mt-10 border-t border-slate-400 pt-1 text-xs text-slate-500">Name / Signature</div>
                </div>
                <div>
                    <div class="font-semibold">Acknowledged by</div>
                    <div class="mt-10 border-t border-slate-400 pt-1 text-xs text-slate-500">Name / Signature</div>
                </div>
            </div>

            <div class="mt-10">
                <h4 class="text-base font-semibold text-slate-900 dark:text-slate-100">Appendix: Approver Remarks</h4>
                <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 dark:bg-slate-800">
                            <tr>
                                <th class="px-3 py-2 text-left">Template</th>
                                <th class="px-3 py-2 text-left">Remark</th>
                                <th class="px-3 py-2 text-left">Remark by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($appendixRows as $templateGroup)
                                @foreach ($templateGroup['rows'] as $index => $row)
                                    <tr>
                                        @if ($index === 0)
                                            <td class="px-3 py-2 align-top font-medium text-slate-900 dark:text-slate-100"
                                                rowspan="{{ $templateGroup['rowspan'] }}">
                                                {{ $templateGroup['template_title'] }}
                                            </td>
                                        @endif
                                        <td class="px-3 py-2">
                                            <button type="button"
                                                wire:click="openSubmissionDetail({{ $row['submission_id'] }})"
                                                class="text-left underline decoration-dotted {{ $row['is_rejected'] ? 'text-rose-700 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200' }}">
                                                {{ $row['remark'] }}
                                            </button>
                                        </td>
                                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                                            {{ $row['remark_by'] }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="3"
                                        class="px-3 py-4 text-center text-slate-500 dark:text-slate-400">
                                        No approver remarks for this month.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
                                @php $fullImagePath = asset('storage/' . ltrim($image->image_path, '/')); @endphp
                                <article @click="activeImage = '{{ $fullImagePath }}'; open = true"
                                    class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                    <img src="{{ $fullImagePath }}" alt="{{ $image->title ?: 'Submission image' }}"
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

    <div x-show="open" x-transition.opacity @keydown.escape.window="open = false"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0" @click="open = false"></div>
        <div class="relative max-w-5xl max-h-screen">
            <button @click="open = false" class="absolute -top-10 right-0 text-3xl text-white">&times;</button>
            <img :src="activeImage" class="max-h-[90vh] max-w-full rounded shadow-2xl">
        </div>
    </div>
</div>

@section('script')
    @parent
    <script>
        function renderKpiOverhaulHalfChart() {
            const el = document.getElementById('kpi-overhaul-half-chart');
            if (!el || typeof window.ApexCharts === 'undefined') return;

            const valueEl = document.getElementById('kpi-overhaul-value');
            const value = Number(valueEl?.value || 0);
            const isDark = document.documentElement.classList.contains('dark');
            const passColor = value >= 50 ? '#16a34a' : '#dc2626';
            const trackColor = isDark ? '#334155' : '#e2e8f0';
            const labelColor = isDark ? '#cbd5e1' : '#475569';

            if (el.__apexchart) {
                el.__apexchart.updateOptions({
                    colors: [passColor],
                    plotOptions: {
                        radialBar: {
                            track: {
                                background: trackColor
                            },
                            dataLabels: {
                                value: {
                                    color: labelColor
                                }
                            }
                        }
                    },
                }, false, true);
                el.__apexchart.updateSeries([value], true);
                return;
            }

            const chart = new window.ApexCharts(el, {
                chart: {
                    type: 'radialBar',
                    height: 170,
                    sparkline: {
                        enabled: true
                    }
                },
                series: [value],
                colors: [passColor],
                plotOptions: {
                    radialBar: {
                        startAngle: -90,
                        endAngle: 90,
                        hollow: {
                            size: '58%'
                        },
                        track: {
                            background: trackColor,
                            strokeWidth: '100%'
                        },
                        dataLabels: {
                            name: {
                                show: false
                            },
                            value: {
                                offsetY: -2,
                                fontSize: '16px',
                                color: labelColor,
                                formatter: (v) => `${v.toFixed(2)}%`
                            }
                        }
                    }
                },
                stroke: {
                    lineCap: 'round'
                }
            });
            el.__apexchart = chart;
            chart.render();
        }

        document.addEventListener('livewire:navigated', renderKpiOverhaulHalfChart);
        document.addEventListener('DOMContentLoaded', renderKpiOverhaulHalfChart);
        document.addEventListener('change', (event) => {
            const target = event.target;
            if (!target) return;
            if (target.matches(
                    'input[type="month"][wire\\:model\\.live="month"], select[wire\\:model\\.live="selectedUserId"]'
                    )) {
                setTimeout(renderKpiOverhaulHalfChart, 80);
            }
        });
        document.addEventListener('livewire:init', () => {
            if (window.Livewire && !window.__kpiOverhaulHookBound) {
                window.__kpiOverhaulHookBound = true;
                window.Livewire.hook('morph.updated', ({
                    el
                }) => {
                    if (
                        el &&
                        (
                            el.id === 'kpi-overhaul-value' ||
                            el.id === 'kpi-overhaul-half-chart' ||
                            (el.querySelector && el.querySelector('#kpi-overhaul-half-chart')) ||
                            (el.closest && el.closest('#kpi-certificate-root'))
                        )
                    ) {
                        renderKpiOverhaulHalfChart();
                    }
                });
            }
        });
    </script>
@endsection
