<div class="space-y-8">
    {{-- index Chart --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Month</label>
                <div class="w-full" wire:ignore x-data="{
                    monthYear: @entangle('index_month_year_filter').live,
                    picker: null,
                    initPicker() {
                        if (!window.flatpickr || !window.monthSelectPlugin) {
                            setTimeout(() => this.initPicker(), 100);
                            return;
                        }
                        if (!this.$refs.monthPicker) {
                            setTimeout(() => this.initPicker(), 50);
                            return;
                        }
                        if (this.$refs.monthPicker._flatpickr) {
                            return;
                        }

                        const alpine = this;

                        this.picker = window.flatpickr(this.$refs.monthPicker, {
                            plugins: [new window.monthSelectPlugin({
                                shorthand: true,
                                dateFormat: 'Y-m',
                                altFormat: 'F Y',
                            })],
                            altInput: true,
                            altInputClass: 'block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                            allowInput: true,
                            defaultDate: (alpine.monthYear && String(alpine.monthYear).length === 7) ? alpine.monthYear : null,
                            appendTo: document.body,
                            onReady(selectedDates, dateStr, instance) {
                                try {
                                    if (instance && instance.calendarContainer) {
                                        instance.calendarContainer.style.zIndex = '9999';
                                    }
                                } catch (e) {}

                                try {
                                    if (instance && instance.altInput) {
                                        instance.altInput.placeholder = 'Select month';
                                    }
                                } catch (e) {}
                            },
                            onChange(selectedDates, dateStr, instance) {
                                if (!selectedDates || selectedDates.length === 0) {
                                    return;
                                }

                                const ym = instance.formatDate(selectedDates[0], 'Y-m');
                                alpine.monthYear = ym;
                                $wire.set('index_month_year_filter', ym);
                            }
                        });
                    },
                }" x-init="$nextTick(() => initPicker())">
                    <input x-ref="monthPicker" type="text" class="w-full" placeholder="Select month" />
                </div>
            </div>
            <div class="text-gray-700 dark:text-gray-200 md:text-right">
                <span>Report at - {{ \Carbon\Carbon::create((int) $index_year_filter, (int) $index_month_filter, 1)->format('M, Y') }}</span>
            </div>
        </div>

        <div class="mt-4" wire:ignore>
            <div id="index-bar-chart" class="w-full"></div>
        </div>
    </div>

    {{-- Sale Gram Line Chart --}}
    @include('livewire.branch-report.sale-gram-chart')

    {{-- Sale Compare (Date Range + Presets) --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3 md:items-end">
            <div class="w-full" wire:ignore x-data="{
                dateFrom: @entangle('sale_compare_from').live,
                dateTo: @entangle('sale_compare_to').live,
                picker: null,
                initPicker() {
                    if (!window.flatpickr) {
                        setTimeout(() => this.initPicker(), 100);
                        return;
                    }
                    if (!this.$refs.saleRange) {
                        setTimeout(() => this.initPicker(), 50);
                        return;
                    }
                    if (this.$refs.saleRange._flatpickr) {
                        return;
                    }

                    const alpine = this;

                    this.picker = window.flatpickr(this.$refs.saleRange, {
                        mode: 'range',
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'M d, Y',
                        altInputClass: 'block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                        defaultDate: (alpine.dateFrom && alpine.dateTo) ? [alpine.dateFrom, alpine.dateTo] : null,
                        allowInput: true,
                        appendTo: document.body,
                        onReady(selectedDates, dateStr, instance) {
                            try {
                                if (instance && instance.calendarContainer) {
                                    instance.calendarContainer.style.zIndex = '9999';
                                }
                            } catch (e) {}

                            try {
                                if (instance && instance.altInput) {
                                    instance.altInput.placeholder = 'Select date range';
                                }
                            } catch (e) {}
                        },
                        onChange(selectedDates, dateStr, instance) {
                            if (!selectedDates || selectedDates.length === 0 || !dateStr || String(dateStr).trim() === '') {
                                alpine.dateFrom = '';
                                alpine.dateTo = '';
                                $wire.set('sale_compare_from', '');
                                $wire.set('sale_compare_to', '');
                                return;
                            }

                            if (selectedDates.length < 2) {
                                return;
                            }

                            const start = instance.formatDate(selectedDates[0], 'Y-m-d');
                            const end = instance.formatDate(selectedDates[1], 'Y-m-d');
                            alpine.dateFrom = start;
                            alpine.dateTo = end;
                            $wire.set('sale_compare_from', start);
                            $wire.set('sale_compare_to', end);
                        }
                    });
                },
            }" x-init="$nextTick(() => initPicker())">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Sale Date Range</label>
                <input x-ref="saleRange" type="text" class="w-full" placeholder="Select date range" />
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Compares by day index across the selected period.</div>
            </div>

            <div class="w-full">
                <x-select
                    label="Branches"
                    placeholder="Select branches"
                    multiselect
                    searchable
                    :options="$jewelryBranches"
                    option-label="name"
                    option-value="id"
                    wire:model.live="sale_compare_branch_ids"
                />
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="presetQuarterlyCompare" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">Quarterly Compare</button>
                <button type="button" wire:click="presetHalfYearCompare" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">Half-Year Compare</button>
                <button type="button" wire:click="presetMonthYoYCompare" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">Month YoY</button>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" wire:click="setSaleCompareMode('prev_period')" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">Compare Prev Period</button>
            <button type="button" wire:click="setSaleCompareMode('yoy')" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">Compare YoY</button>
            <button type="button" wire:click="setSaleCompareMode('none')" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">No Compare</button>
        </div>

        <details class="mt-4 bg-gray-50 rounded-lg p-4 dark:bg-gray-900">
            <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-200">
                How these filters work
            </summary>
            <div class="mt-3 text-sm text-gray-600 dark:text-gray-300 space-y-2">
                <div>
                    <span class="font-medium">Date Range:</span>
                    Select a start and end date. The chart plots total sale grams per day (from daily reports where sale gram is enabled).
                </div>
                <div>
                    <span class="font-medium">Compare Prev Period:</span>
                    Uses the same number of days immediately <span class="font-medium">before</span> your selected range.
                    Example: If you select 10 days, it compares to the previous 10 days.
                </div>
                <div>
                    <span class="font-medium">Compare YoY:</span>
                    Compares the same date range from <span class="font-medium">last year</span>.
                    Example: Dec 1–Dec 31, 2025 compares to Dec 1–Dec 31, 2024.
                </div>
                <div>
                    <span class="font-medium">No Compare:</span>
                    Shows only the selected range.
                </div>
                <div>
                    <span class="font-medium">Quarterly Compare:</span>
                    Auto-fills the range from the start of the current quarter to today, then compares to the previous period.
                </div>
                <div>
                    <span class="font-medium">Half-Year Compare:</span>
                    Auto-fills the range from the start of the current half-year to today, then compares to the previous period.
                </div>
                <div>
                    <span class="font-medium">Month YoY:</span>
                    Auto-fills the range from the start of this month to today and sets compare mode to YoY.
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Tip: For long ranges (like quarterly), the chart is horizontally scrollable.
                </div>
            </div>
        </details>

        <div class="mt-4 bg-gray-50 rounded-lg p-4 dark:bg-gray-900 overflow-x-auto">
            <div id="sale-compare-line-chart" class="w-full" wire:ignore></div>
        </div>
    </div>

  



    {{-- Daily Targets Calendar --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-700 dark:text-gray-200">Daily Branch Targets</h2>
            <div class="flex items-center space-x-2">
                <button wire:click="previousMonth" class="px-3 py-1 text-white bg-blue-500 rounded hover:bg-blue-600">&lt;</button>
                <span class="text-lg font-semibold text-gray-700 dark:text-gray-200">{{ \Carbon\Carbon::create($calendar_year, $calendar_month)->format('F Y') }}</span>
                <button wire:click="nextMonth" class="px-3 py-1 text-white bg-blue-500 rounded hover:bg-blue-600">&gt;</button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1">
            @php
                $startOfMonth = \Carbon\Carbon::create($calendar_year, $calendar_month, 1);
                $daysInMonth = $startOfMonth->daysInMonth;
                $startDayOfWeek = $startOfMonth->dayOfWeek;
            @endphp

            {{-- Day headers --}}
            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                <div class="p-3 text-center font-semibold text-gray-600 dark:text-gray-400">{{ $day }}</div>
            @endforeach

            {{-- Empty cells for days before start of month --}}
            @for ($i = 0; $i < $startDayOfWeek; $i++)
                <div class="p-3"></div>
            @endfor

            {{-- Days of the month --}}
            @foreach($calendarData as $dayData)
                @php
                    $isToday = $dayData['date'] === now()->format('Y-m-d');
                    $cellClass = 'p-3 text-center border border-gray-300 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 dark:border-gray-600';
                    if ($isToday) {
                        $cellClass .= ' bg-blue-50 dark:bg-blue-900 border-blue-300 dark:border-blue-600';
                    } elseif ($dayData['actual_gram'] >= $dayData['target_gram'] && $dayData['target_gram'] > 0) {
                        $cellClass .= ' bg-green-50 dark:bg-green-900 border-green-300 dark:border-green-600';
                    }
                @endphp
                <div wire:click="openTargetModal('{{ $dayData['date'] }}')" class="{{ $cellClass }}">
                    <div class="font-semibold {{ $isToday ? 'text-blue-600 dark:text-blue-400' : ($dayData['actual_gram'] >= $dayData['target_gram'] && $dayData['target_gram'] > 0 ? 'text-green-600 dark:text-green-400' : '') }}">{{ $dayData['day'] }}</div>
                    @if($dayData['target_gram'] > 0 || $dayData['actual_gram'] > 0)
                        <div class="text-xs flex justify-between items-center">
                            <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-1 py-0.5 rounded text-xs font-medium">T: {{ number_format($dayData['target_gram']) }}</span>
                            <span class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-1 py-0.5 rounded text-xs font-medium">A: {{ number_format($dayData['actual_gram']) }}</span>
                        </div>
                    @else
                        <div class="text-xs text-gray-400">-</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Target vs Actual Line Chart --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <h2 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-4">Target vs Actual Sales ({{ \Carbon\Carbon::create($calendar_year, $calendar_month)->format('F Y') }})</h2>
        <div id="target-vs-actual-line-chart" class="w-full" wire:ignore></div>
    </div>

    {{-- Target vs Actual Summary Table --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-4">Monthly Target vs Actual Summary</h3>

        <div class="mb-4" wire:ignore x-data="{
            dateFrom: @entangle('target_actual_from').live,
            dateTo: @entangle('target_actual_to').live,
            picker: null,
            initPicker() {
                if (!window.flatpickr) {
                    setTimeout(() => this.initPicker(), 100);
                    return;
                }
                if (!this.$refs.range) {
                    setTimeout(() => this.initPicker(), 50);
                    return;
                }
                if (this.$refs.range._flatpickr) {
                    return;
                }

                const alpine = this;

                this.picker = window.flatpickr(this.$refs.range, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'M d, Y',
                    altInputClass: 'block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                    defaultDate: (alpine.dateFrom && alpine.dateTo) ? [alpine.dateFrom, alpine.dateTo] : null,
                    allowInput: true,
                    appendTo: document.body,
                    onReady(selectedDates, dateStr, instance) {
                        try {
                            if (instance && instance.calendarContainer) {
                                instance.calendarContainer.style.zIndex = '9999';
                            }
                        } catch (e) {}

                        try {
                            if (instance && instance.altInput) {
                                instance.altInput.placeholder = 'Select date range';
                            }
                        } catch (e) {}
                    },
                    onChange(selectedDates, dateStr, instance) {
                        if (!selectedDates || selectedDates.length === 0 || !dateStr || String(dateStr).trim() === '') {
                            alpine.dateFrom = '';
                            alpine.dateTo = '';
                            $wire.set('target_actual_from', '');
                            $wire.set('target_actual_to', '');
                            return;
                        }

                        if (selectedDates.length < 2) {
                            return;
                        }

                        const start = instance.formatDate(selectedDates[0], 'Y-m-d');
                        const end = instance.formatDate(selectedDates[1], 'Y-m-d');
                        alpine.dateFrom = start;
                        alpine.dateTo = end;
                        $wire.set('target_actual_from', start);
                        $wire.set('target_actual_to', end);
                    }
                });
            },
        }" x-init="$nextTick(() => initPicker())">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date Range</label>
            <input x-ref="range" type="text" class="w-full" placeholder="Select date range" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 bg-white rounded-lg dark:text-gray-200 dark:bg-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-3 py-2">Branch Name</th>
                        <th scope="col" class="px-3 py-2 text-right">Target Gram</th>
                        <th scope="col" class="px-3 py-2 text-right">Actual Gram</th>
                        <th scope="col" class="px-3 py-2 text-right">Gap Gram</th>
                        <th scope="col" class="px-3 py-2 text-right">Shortfall Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totals = $targetVsActualTable['totals'] ?? null;
                        $rows = $targetVsActualTable['rows'] ?? [];

                        $totalPercent = $totals['percent'] ?? null;
                        $totalIsUp = is_null($totalPercent) ? null : ($totalPercent >= 0);
                    @endphp

                    {{-- Grand total row --}}
                    <tr class="font-semibold bg-gray-100 dark:bg-gray-900">
                        <td class="px-3 py-2">Grand Total</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float) ($totals['target_gram'] ?? 0)) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float) ($totals['actual_gram'] ?? 0)) }}</td>
                        @php
                            $tg = (float) ($totals['gap_gram'] ?? 0);
                            $tgClass = $tg >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                        @endphp
                        <td class="px-3 py-2 text-right {{ $tgClass }}">{{ number_format($tg) }}</td>
                        <td class="px-3 py-2 text-right">
                            @if (is_null($totalPercent))
                                <span class="text-gray-400">-</span>
                            @else
                                <span class="{{ $totalIsUp ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $totalIsUp ? '↑' : '↓' }} {{ number_format(abs((float) $totalPercent), 1) }}%
                                </span>
                            @endif
                        </td>
                    </tr>

                    @foreach ($rows as $row)
                        @php
                            $target = (float) ($row['target_gram'] ?? 0);
                            $actual = (float) ($row['actual_gram'] ?? 0);
                            $gap = (float) ($row['gap_gram'] ?? 0);
                            $percent = $row['percent'] ?? null;
                            $isUp = is_null($percent) ? null : ((float) $percent >= 0);
                            $gapClass = $gap >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                        @endphp
                        <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-800 dark:even:bg-gray-900">
                            <td class="px-3 py-2">{{ ucfirst($row['branch_name'] ?? '-') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($target) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($actual) }}</td>
                            <td class="px-3 py-2 text-right {{ $gapClass }}">{{ number_format($gap) }}</td>
                            <td class="px-3 py-2 text-right">
                                @if (is_null($percent))
                                    <span class="text-gray-400">-</span>
                                @else
                                    <span class="{{ $isUp ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $isUp ? '↑' : '↓' }} {{ number_format(abs((float) $percent), 1) }}%
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if (empty($rows))
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">No target/actual data for selected range.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

      <!-- All branch report detail -->
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="mb-4">
            <x-datetime-picker wire:model.live.debounce="report_types_date_filter" without-time='true' label="Date"
                placeholder="Now" />
        </div>

        <div class="mb-4 text-gray-700 dark:text-gray-200">Report at -
            {{ \Carbon\Carbon::parse($report_types_date_filter)->format('M, Y') }}</div>
        @if ($monthlyAllReportTypes)
            <table
                class="w-full mt-2 text-sm text-left text-gray-700 bg-white rounded-lg dark:text-gray-200 dark:bg-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-2 py-1">Type Name</th>
                        @foreach (array_keys($monthlyAllReportTypes['ho'] ?? ($monthlyAllReportTypes['ရွှေ (weight / g)'] ?? [])) as $branchName)
                            <th scope="col" class="px-2 py-1">{{ $branchName }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monthlyAllReportTypes as $typeName => $branchData)
                        <tr class="odd:bg-white even:bg-gray-100 dark:odd:bg-gray-900 dark:even:bg-gray-800">
                            <td class="md:px-4 md:py-2">{{ $typeName }}</td>
                            @foreach ($branchData as $values)
                                <td class="md:px-4 md:py-2">{{ $values[0] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-2 mt-4 text-red-300 rounded-full bg-gray-50 dark:bg-gray-900">No data found yet</div>
        @endif
    </div>

        {{-- specific Report type --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex w-full gap-2 mx-auto mb-4 md:w-1/2">
            <div>
                <x-datetime-picker wire:model.live.debounce="specific_date_filter" without-time='true' label="Date"
                    placeholder="Now" />
            </div>
            <div class="flex flex-col">
                <label for="specific_branch" class="text-gray-700 dark:text-gray-200">Branch</label>
                <select id="specific_branch_id" wire:model.live='specific_branch_id'
                    class="text-gray-700 bg-gray-100 border rounded-lg dark:bg-gray-900 border-gray-50 dark:border-gray-700 dark:text-gray-200">
                    <option value="" selected>All Branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}"> {{ ucfirst($branch->name) }}</option>
                    @endforeach
                </select>
            </div>
            <span
                class="mt-8 text-blue-600 cursor-pointer dark:text-blue-400 hover:underline hover:text-red-900 dark:hover:text-red-400"
                wire:click='specificDateFilterOfReportType'>Generate</span>
        </div>

        <table
            class="w-full mt-2 text-sm text-left text-gray-700 bg-white rounded-lg dark:text-gray-200 dark:bg-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-2 py-1">Type Name</th>
                    @foreach (array_keys($dailyAllReportTypes['ရွှေ (weight / g)'] ?? []) as $branchName)
                        <th class="cursor-pointer hover:text-red-500 dark:hover:text-red-400"
                            wire:click='removeKeyFromSelectedArray("{{ $branchName }}")' scope="col"
                            class="px-2 py-1">{{ str_replace('Branch', 'B', $branchName) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($dailyAllReportTypes as $typeName => $branchData)
                    <tr class="odd:bg-white even:bg-gray-100 dark:odd:bg-gray-900 dark:even:bg-gray-800">
                        <td class="md:px-4 md:py-2">{{ $typeName }}</td>
                        @foreach ($branchData as $values)
                            <td class="md:px-4 md:py-2">{{ $values[0] ?? 0 }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>



    {{-- popular with detail --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex w-full gap-2 mx-auto mb-4 md:w-1/2">
            <x-datetime-picker wire:model.live.debounce="popular_start_date_filter" without-time='true' label="Date"
                placeholder="Now" />
            <x-datetime-picker wire:model.live.debounce="popular_end_date_filter" without-time='true' label="Date"
                placeholder="Now" />
        </div>
        <div class="mb-4 text-gray-700 dark:text-gray-200">
            <span>Report duration - <strong class="font-mono text-blue-500 dark:text-blue-400">
                    {{ \Carbon\Carbon::parse($popular_start_date_filter)->format('j-M-y') }}
                    |
                    {{ \Carbon\Carbon::parse($popular_end_date_filter)->format('j-M-y') }}</strong>
            </span>
            <select wire:model.live='branch_id'
                class="text-gray-700 bg-gray-100 border rounded-lg dark:bg-gray-900 border-gray-50 dark:border-gray-700 dark:text-gray-200">
                <option value="" selected>All Branch</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}"> {{ ucfirst($branch->name) }}</option>
                @endforeach
            </select>
            <select wire:model.live='limit'
                class="text-gray-700 bg-gray-100 border rounded-lg dark:bg-gray-900 border-gray-50 dark:border-gray-700 dark:text-gray-200">
                <option value="5" selected>5</option>
                <option value="7">7</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
            </select>
        </div>

        <table class="w-full bg-white border-collapse rounded-lg table-auto dark:bg-gray-800">
            <thead>
                <tr class="text-left bg-gray-200 dark:bg-gray-700">
                    <th class="p-2 border">Rank</th>
                    <th class="p-2 border">Shape</th>
                    <th class="p-2 border">Total Sale</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($most_popular_summary as $index => $summary)
                    <tr class="{{ $index % 2 == 0 ? 'bg-gray-100 dark:bg-gray-900' : 'bg-white dark:bg-gray-800' }}">
                        <td class="px-4 py-2 font-bold border border-gray-300 dark:border-gray-700">
                            @if ($index == 0)
                                👑
                            @elseif ($index == 1)
                                🥈
                            @elseif ($index == 2)
                                🥉
                            @else
                                {{ $index + 1 }}
                            @endif
                        </td>
                        <td class="px-4 py-2 font-bold border border-gray-300 dark:border-gray-700">
                            {{ $summary->shape }}
                            <i>{{ $summary->weight }}g/{{ $summary->length }} {{ $summary->uom }} </i>
                        </td>
                        <td class="px-4 py-2 font-bold border border-gray-300 dark:border-gray-700">
                            {{ number_format($summary->total_sale) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" class="align-top">
                            <details class="w-full">
                                <summary class="text-sm text-blue-400 cursor-pointer dark:text-blue-300">view details
                                </summary>
                                <div class="p-4 mb-2 rounded-lg shadow-md bg-gray-50 dark:bg-gray-900">
                                    <ul>
                                        @foreach ($most_popular_details as $detail)
                                            @if (
                                                $detail->shape === $summary->shape &&
                                                    $detail->length === $summary->length &&
                                                    $detail->weight === $summary->weight &&
                                                    $detail->uom === $summary->uom)
                                                <li class="flex justify-between shadow-sm">
                                                    <span
                                                        class="p-2 text-gray-700 dark:text-gray-200">{{ $detail->branch }}</span>
                                                    <span class="p-2 text-right text-slate-800 dark:text-slate-200">
                                                        {{ number_format($detail->branch_sale) }} </span>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            </details>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Target Modal --}}
    @if($show_target_modal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-xl dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-bold text-gray-700 dark:text-gray-200">Set Daily Targets for {{ \Carbon\Carbon::parse($selected_date)->format('M j, Y') }}</h3>

                @foreach(\App\Models\Branch::where('is_jewelry_shop', true)->get() as $branch)
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $branch->name }}</label>
                        <input type="number" step="0.01" wire:model="daily_targets.{{ $branch->id }}" placeholder="Target in grams" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                @endforeach

                <div class="flex justify-end space-x-2">
                    <button wire:click="closeTargetModal" class="px-4 py-2 text-gray-600 bg-gray-200 rounded hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200">Cancel</button>
                    <button wire:click="saveTargets" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600">Save Targets</button>
                </div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const initialIndexChartData = @json($indexChartData ?? ['categories' => [], 'series' => []]);
            const initialChartData = @json($targetVsActualData ?? ['categories' => [], 'series' => []]);
            const initialSaleCompareData = @json($saleCompareChart ?? ['categories' => [], 'series' => []]);

            function renderIndexBarChart(chartData) {
                if (typeof window.ApexCharts === 'undefined') {
                    return;
                }

                const el = document.getElementById('index-bar-chart');
                if (!el) {
                    return;
                }

                const categories = (chartData && chartData.categories) ? chartData.categories : [];
                const series = (chartData && chartData.series) ? chartData.series : [];

                const desiredHeight = Math.max(320, categories.length * 34);

                const options = {
                    chart: {
                        type: 'bar',
                        height: desiredHeight,
                        toolbar: { show: false },
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '70%',
                        },
                    },
                    dataLabels: { enabled: false },
                    legend: { position: 'top', horizontalAlign: 'left' },
                    series: series,
                    xaxis: {
                        categories: categories,
                        labels: {
                            formatter: function (val) {
                                return Math.round(val);
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            maxWidth: 220,
                        },
                    },
                    grid: { strokeDashArray: 4 },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: function (val) {
                                return Math.round(val);
                            }
                        }
                    },
                };

                if (window.indexBarChart) {
                    window.indexBarChart.updateOptions(options, true, true);
                    return;
                }

                window.indexBarChart = new window.ApexCharts(el, options);
                window.indexBarChart.render();
            }

            function renderTargetVsActualLineChart(chartData) {
                if (typeof window.ApexCharts === 'undefined') {
                    return;
                }

                const el = document.getElementById('target-vs-actual-line-chart');
                if (!el) {
                    return;
                }

                const categories = (chartData && chartData.categories) ? chartData.categories : [];
                const series = (chartData && chartData.series) ? chartData.series : [];

                const options = {
                    chart: {
                        type: 'line',
                        height: 320,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                    },
                    stroke: { curve: 'smooth', width: 2 },
                    markers: { size: 0 },
                    dataLabels: { enabled: false },
                    legend: { position: 'top', horizontalAlign: 'left' },
                    series: series,
                    xaxis: {
                        categories: categories,
                        labels: { rotate: -45, hideOverlappingLabels: true },
                        tickPlacement: 'on',
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            formatter: function (val) {
                                return Math.round(val);
                            }
                        }
                    },
                    grid: { strokeDashArray: 4 },
                    tooltip: { shared: true, intersect: false },
                };

                if (window.targetVsActualLineChart) {
                    window.targetVsActualLineChart.updateOptions(options, true, true);
                    return;
                }

                window.targetVsActualLineChart = new window.ApexCharts(el, options);
                window.targetVsActualLineChart.render();
            }

            function renderAllInitialCharts() {
                renderIndexBarChart(initialIndexChartData);
                renderTargetVsActualLineChart(initialChartData);
                renderSaleCompareLineChart(initialSaleCompareData);
            }

            function renderSaleCompareLineChart(chartData) {
                if (typeof window.ApexCharts === 'undefined') {
                    return;
                }

                const el = document.getElementById('sale-compare-line-chart');
                if (!el) {
                    return;
                }

                const categories = (chartData && chartData.categories) ? chartData.categories : [];
                const series = (chartData && chartData.series) ? chartData.series : [];

                // For long ranges (e.g., quarterly), render a wider chart so the wrapper can scroll.
                const desiredWidth = Math.max(900, categories.length * 28);

                const options = {
                    chart: {
                        type: 'line',
                        height: 320,
                        width: desiredWidth,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                    },
                    stroke: { curve: 'smooth', width: 2 },
                    markers: { size: 0 },
                    dataLabels: { enabled: false },
                    legend: { position: 'top', horizontalAlign: 'left' },
                    series: series,
                    xaxis: {
                        categories: categories,
                        labels: { rotate: -45, hideOverlappingLabels: true },
                        tickPlacement: 'on',
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            formatter: function (val) {
                                return Math.round(val);
                            }
                        }
                    },
                    grid: { strokeDashArray: 4 },
                    tooltip: { shared: true, intersect: false },
                };

                if (window.saleCompareLineChart) {
                    window.saleCompareLineChart.updateOptions(options, true, true);
                    return;
                }

                window.saleCompareLineChart = new window.ApexCharts(el, options);
                window.saleCompareLineChart.render();
            }

            let initialRenderInterval = null;

            function renderInitialChartsWhenReady() {
                if (typeof window.ApexCharts !== 'undefined') {
                    renderAllInitialCharts();
                    return;
                }

                if (initialRenderInterval) {
                    return;
                }

                let retries = 50;
                initialRenderInterval = setInterval(function () {
                    if (typeof window.ApexCharts !== 'undefined') {
                        clearInterval(initialRenderInterval);
                        initialRenderInterval = null;
                        renderAllInitialCharts();
                    } else if (--retries <= 0) {
                        clearInterval(initialRenderInterval);
                        initialRenderInterval = null;
                    }
                }, 100);
            }

            function scheduleInitialChartRender() {
                try {
                    renderInitialChartsWhenReady();
                    if (typeof requestAnimationFrame === 'function') {
                        requestAnimationFrame(function () {
                            renderInitialChartsWhenReady();
                        });
                    }
                    setTimeout(function () {
                        renderInitialChartsWhenReady();
                    }, 0);
                } catch (e) {
                    // no-op
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                scheduleInitialChartRender();
            });

            // Also run immediately (handles cases where DOMContentLoaded already fired)
            scheduleInitialChartRender();

            // Livewire updates
            document.addEventListener('livewire:init', function () {
                if (window.Livewire && typeof window.Livewire.on === 'function') {
                    window.Livewire.on('index-chart-updated', function (payload) {
                        renderIndexBarChart(payload && payload.chart ? payload.chart : payload);
                    });

                    window.Livewire.on('target-vs-actual-chart-updated', function (payload) {
                        renderTargetVsActualLineChart(payload && payload.chart ? payload.chart : payload);
                    });

                    window.Livewire.on('sale-compare-chart-updated', function (payload) {
                        renderSaleCompareLineChart(payload && payload.chart ? payload.chart : payload);
                    });
                }
            });

            // SPA navigations in Livewire v3
            document.addEventListener('livewire:navigated', function () {
                scheduleInitialChartRender();
            });
        })();
    </script>
</div>
