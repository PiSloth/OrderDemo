<div class="space-y-8" x-data="{ modalOpen: @entangle('show_target_modal').live, compareHelpOpen: false, calendarHelpOpen: false, avgHelpOpen: false, achievementHelpOpen: false }" x-effect="document.body.classList.toggle('overflow-hidden', modalOpen || compareHelpOpen || calendarHelpOpen || avgHelpOpen || achievementHelpOpen)">
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
        <div class="flex items-start justify-between gap-3">
            <div></div>
            <button type="button"
                class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                x-on:click="compareHelpOpen = true">
                အသုံးပြုပုံ (မြန်မာ)
            </button>
        </div>

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

            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Metric</label>
                <select wire:model.live="sale_compare_metric" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="gram">Gram</option>
                    <option value="pcs">Pcs</option>
                </select>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Choose to compare sale gram or sale pcs.</div>
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

        <div class="mt-4 bg-gray-50 rounded-lg p-4 dark:bg-gray-900 overflow-x-auto">
            <div id="sale-compare-line-chart" class="w-full" wire:ignore></div>
        </div>
    </div>

    {{-- Sale Compare Help (Burmese) Modal --}}
    <div
        x-cloak
        x-show="compareHelpOpen"
        x-on:keydown.escape.window="compareHelpOpen = false"
        x-on:click.self="compareHelpOpen = false"
        class="fixed inset-0 z-50 bg-black/50 p-4"
        aria-modal="true"
        role="dialog"
    >
        <div class="w-full max-w-2xl mx-auto bg-white rounded-lg shadow-xl dark:bg-gray-800 overflow-hidden" style="max-height: calc(100vh - 2rem);">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200">Sale Compare Chart အသုံးပြုပုံ</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Date Range + Compare Mode ကိုသုံးပြီး နှိုင်းယှဉ်ကြည့်နိုင်ပါတယ်</div>
                </div>
                <button type="button" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" x-on:click="compareHelpOpen = false">
                    ပိတ်မယ်
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(100vh - 12rem);">
                <div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">Dec 2026 ကို Dec 2025 နဲ့ နှိုင်းချင်ရင် ဘာကိုနှိပ်မလဲ?</div>
                        <div class="space-y-2">
                            <div><span class="font-medium">(1)</span> <span class="font-medium">Sale Date Range</span> မှာ <span class="font-medium">2026-12-01</span> ကနေ <span class="font-medium">2026-12-31</span> အထိရွေးပါ။</div>
                            <div><span class="font-medium">(2)</span> Branch ကို (All / သို့) လိုတဲ့ Branch(တွေ) ရွေးပါ။</div>
                            <div><span class="font-medium">(3)</span> <span class="font-medium">Compare YoY</span> ခလုတ်ကိုနှိပ်ပါ။ (YoY = အရင်နှစ်တူညီတဲ့ကာလနဲ့ နှိုင်းခြင်း)</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ဥပမာ Dec 2026 ရွေးထားရင် Compare YoY က Dec 2025 ကို အလိုအလျောက်နှိုင်းပေးပါတယ်။</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">ခလုတ်တွေ၏ အဓိပ္ပါယ်</div>
                        <div class="space-y-2">
                            <div><span class="font-medium">Compare Prev Period</span> = ရွေးထားတဲ့ ရက်အရေအတွက်အတိုင်း အရင်ကာလနဲ့နှိုင်း (ဥပမာ 10 ရက်ရွေးရင် အရင် 10 ရက်နဲ့)</div>
                            <div><span class="font-medium">Compare YoY</span> = အရင်နှစ် တူညီတဲ့နေ့ရက်အတွဲနဲ့နှိုင်း</div>
                            <div><span class="font-medium">No Compare</span> = ရွေးထားတဲ့ ကာလကိုပဲပြ (နှိုင်းခြင်းမလုပ်)</div>
                            <div><span class="font-medium">Month YoY</span> = ဒီလ(အခုလ) ကို အလိုအလျောက်ရွေးပြီး YoY နဲ့နှိုင်း (Dec လကိုလိုချင်ရင် Date Range ကို ကိုယ်တိုင် Dec ရက်တွေရွေးပြီး Compare YoY နှိပ်ပါ)</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">Chart ကိုဖတ်ပုံ</div>
                        <div class="space-y-2">
                            <div><span class="font-medium">Actual</span> လိုင်း = ရွေးထားတဲ့ Date Range အတွင်း စုစုပေါင်း Sale Gram ကိုနေ့စဉ်အလိုက်ပြပါတယ်။</div>
                            <div><span class="font-medium">Compare</span> လိုင်း = ရွေးထားတဲ့ Mode (Prev/YoY) အလိုက် နှိုင်းတဲ့ကာလကိုနေ့စဉ်အလိုက်ပြပါတယ်။</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  



    {{-- Daily Targets Calendar --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-bold text-gray-700 dark:text-gray-200">Daily Branch Targets</h2>
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="previousMonth" class="px-3 py-1 text-white bg-blue-500 rounded hover:bg-blue-600">&lt;</button>
                <span class="text-lg font-semibold text-gray-700 dark:text-gray-200">{{ \Carbon\Carbon::create($calendar_year, $calendar_month)->format('F Y') }}</span>
                <button wire:click="nextMonth" class="px-3 py-1 text-white bg-blue-500 rounded hover:bg-blue-600">&gt;</button>

                <div class="w-full h-px bg-gray-200 dark:bg-gray-700 md:hidden"></div>

                <button type="button" wire:click="downloadTargetsExcel" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    Download Excel
                </button>

                <button type="button" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" x-on:click="calendarHelpOpen = true">
                    အသုံးပြုပုံ (မြန်မာ)
                </button>

                <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full md:w-auto">
                    <input type="file" wire:model="targets_import_file" accept=".xlsx,.csv,.ods" class="block w-full md:max-w-[220px] text-sm text-gray-700 dark:text-gray-200" />
                    <button type="button" wire:click="importTargetsExcel" class="px-3 py-2 w-full sm:w-auto text-white bg-blue-600 rounded hover:bg-blue-700" wire:loading.attr="disabled" wire:target="targets_import_file,importTargetsExcel" wire:loading.class="opacity-60 cursor-not-allowed">
                        Import
                    </button>
                </div>

                <div class="hidden items-center gap-2 text-sm text-gray-600 dark:text-gray-300" wire:loading.class="flex" wire:loading.class.remove="hidden" wire:target="targets_import_file">
                    <div class="w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
                    <span>Uploading file… please wait</span>
                </div>

                <div class="hidden items-center gap-2 text-sm text-gray-600 dark:text-gray-300" wire:loading.class="flex" wire:loading.class.remove="hidden" wire:target="importTargetsExcel">
                    <div class="w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
                    <span>Importing… please wait</span>
                </div>
            </div>
        </div>

        @error('targets_import_file')
            <div class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
        @enderror
        <div class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            Export includes all days for the selected month. Import requires both gram and pcs for every row.
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
                            <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-1 py-0.5 rounded text-xs font-medium">T: {{ number_format((float) $dayData['target_gram'], 2) }}</span>
                            <span class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-1 py-0.5 rounded text-xs font-medium">A: {{ number_format((float) $dayData['actual_gram'], 2) }}</span>
                        </div>
                    @else
                        <div class="text-xs text-gray-400">-</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Calendar + Monthly Target Import/Export Help (Burmese) Modal --}}
    <div
        x-cloak
        x-show="calendarHelpOpen"
        x-on:keydown.escape.window="calendarHelpOpen = false"
        x-on:click.self="calendarHelpOpen = false"
        class="fixed inset-0 z-50 bg-black/50 p-4"
        aria-modal="true"
        role="dialog"
    >
        <div class="w-full max-w-2xl mx-auto bg-white rounded-lg shadow-xl dark:bg-gray-800 overflow-hidden" style="max-height: calc(100vh - 2rem);">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200">Calendar / Monthly Target အသုံးပြုပုံ</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Target ထည့်နည်း၊ Export/Import ဖိုင်ပုံစံ</div>
                </div>
                <button type="button" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" x-on:click="calendarHelpOpen = false">
                    ပိတ်မယ်
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(100vh - 12rem);">
                <div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">Calendar report က ဘာကိုပြတာလဲ?</div>
                        <div class="space-y-2">
                            <div>နေ့တိုင်းအတွက် <span class="font-medium">Target (T)</span> နဲ့ <span class="font-medium">Actual (A)</span> ကိုပြပါတယ်။</div>
                            <div><span class="font-medium">Blue</span> = ဒီနေ့ (Today)</div>
                            <div><span class="font-medium">Green</span> = Actual ≥ Target (Target ကိုပြည့်/ကျော်)</div>
                            <div>နေ့တစ်နေ့ကိုနှိပ်ရင် Target ကိုပြင်နိုင်ပါတယ်။ (Edit Daily Targets)</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">Download Excel (Export) ဆိုတာဘာလဲ?</div>
                        <div class="space-y-2">
                            <div>ရွေးထားတဲ့ လ (Calendar ထဲက <span class="font-medium">F Y</span>) အတွက် Target ဒေတာတွေကို Excel ဖိုင်နဲ့ထုတ်ပေးပါတယ်။</div>
                            <div>ဒီဖိုင်ကို သင့် Excel မှာပြင်ပြီး <span class="font-medium">Import</span> ပြန်တင်နိုင်ပါတယ်။</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">Import ဆိုတာဘာလဲ? (ဖိုင်တင်ခြင်း)</div>
                        <div class="space-y-2">
                            <div>Download လုပ်ထားတဲ့ Excel ဖိုင်ကို ပြင်ပြီး ပြန်တင်တာပါ။</div>
                            <div><span class="font-medium">အရေးကြီး:</span> Row တစ်ခုချင်းစီမှာ <span class="font-medium">target_gram</span> နဲ့ <span class="font-medium">target_pcs</span> နှစ်ခုလုံးထည့်ထားရပါမယ်။</div>
                            <div>ဖိုင်ထဲက <span class="font-medium">date</span> ဟာ လက်ရှိရွေးထားတဲ့ လထဲကနေ့ရက်တွေဖြစ်ရပါမယ်။</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-2">Excel ဖိုင်ပုံစံ (Format)</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Column ခေါင်းစဉ်တွေကို မပြောင်းပါနဲ့။</div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left border border-gray-200 dark:border-gray-700">
                                <thead class="bg-gray-100 dark:bg-gray-800">
                                    <tr class="text-gray-700 dark:text-gray-200">
                                        <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">date</th>
                                        <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">branch_id</th>
                                        <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">branch_name</th>
                                        <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">target_gram</th>
                                        <th class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">target_pcs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="text-gray-700 dark:text-gray-200">
                                        <td class="px-2 py-1 border-t border-gray-200 dark:border-gray-700">2026-01-01</td>
                                        <td class="px-2 py-1 border-t border-gray-200 dark:border-gray-700">1</td>
                                        <td class="px-2 py-1 border-t border-gray-200 dark:border-gray-700">Branch A</td>
                                        <td class="px-2 py-1 border-t border-gray-200 dark:border-gray-700">5.00</td>
                                        <td class="px-2 py-1 border-t border-gray-200 dark:border-gray-700">5</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Target vs Actual Line Chart --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <h2 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-4">Target vs Actual Sales ({{ \Carbon\Carbon::create($calendar_year, $calendar_month)->format('F Y') }})</h2>
        <div id="target-vs-actual-line-chart" class="w-full" wire:ignore></div>
    </div>

    {{-- Avg Remaining Daily Target (Based on month target + actual-to-date) --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-700 dark:text-gray-200">Remaining Daily Target (Avg)</h2>

                    <button type="button"
                        class="px-2 py-1 rounded-md text-xs font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                        x-on:click="avgHelpOpen = true">
                        အသုံးပြုပုံ (မြန်မာ)
                    </button>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $avgRemainingTargets['month_label'] ?? \Carbon\Carbon::create($calendar_year, $calendar_month)->format('F Y') }}
                    @if (!empty($avgRemainingTargets['as_of']))
                        · As of {{ \Carbon\Carbon::parse($avgRemainingTargets['as_of'])->format('M j, Y') }}
                    @endif
                </div>
            </div>

            <div class="w-full md:w-72">
                <x-select
                    label="Branch"
                    placeholder="All jewelry branches"
                    :options="$jewelryBranches"
                    option-label="name"
                    option-value="id"
                    wire:model.live="avg_target_branch_id"
                />
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                <div class="text-gray-500 dark:text-gray-400">Month Target (g)</div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format((float) ($avgRemainingTargets['month_target_gram'] ?? 0), 2) }}</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                <div class="text-gray-500 dark:text-gray-400">Month Target (pcs)</div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format((float) ($avgRemainingTargets['month_target_pcs'] ?? 0), 2) }}</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                <div class="text-gray-500 dark:text-gray-400">Sold To Date (g / pcs)</div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">
                    {{ number_format((float) ($avgRemainingTargets['actual_to_date_gram'] ?? 0), 2) }} / {{ number_format((float) ($avgRemainingTargets['actual_to_date_pcs'] ?? 0), 2) }}
                </div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                <div class="text-gray-500 dark:text-gray-400">Days Remaining</div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ (int) ($avgRemainingTargets['days_remaining'] ?? 0) }}</div>
            </div>
        </div>

        <div class="mt-4" wire:ignore>
            <div id="avg-remaining-target-chart" class="w-full"></div>
        </div>

    </div>

    {{-- Branch Target Achievement (Heatmap) --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-700 dark:text-gray-200">Branch Target Achievement</h2>
                    <button type="button"
                        class="px-2 py-1 rounded-md text-xs font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                        x-on:click="achievementHelpOpen = true">
                        အသုံးပြုပုံ (မြန်မာ)
                    </button>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Last {{ (int) ($achievement_days ?? 7) }} days · {{ $branchAchievementHeatmap['from'] ?? '' }} to {{ $branchAchievementHeatmap['to'] ?? '' }}
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:w-[28rem]">
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Days (min 7)</label>
                    <input type="number" min="7" max="31" wire:model.live="achievement_days"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Shows daily achievement for every jewelry branch.</div>
                </div>

                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Metric</label>
                    <select wire:model.live="achievement_metric"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="gram">Gram</option>
                        <option value="pcs">Pcs</option>
                    </select>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Achievement% = Actual ÷ Target × 100. Empty cell = no target.</div>
                </div>
            </div>
        </div>

        <div class="mt-4 bg-gray-50 rounded-lg p-4 dark:bg-gray-900 overflow-x-auto">
            <div id="branch-achievement-heatmap" class="w-full" wire:ignore></div>

            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-600 dark:text-gray-300">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded bg-red-600 dark:bg-red-400"></span>
                    <span>&lt;50%</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded bg-amber-500 dark:bg-amber-300"></span>
                    <span>50–79%</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded bg-yellow-500 dark:bg-yellow-300"></span>
                    <span>80–99%</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded bg-green-600 dark:bg-green-400"></span>
                    <span>100–119%</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded bg-blue-600 dark:bg-blue-400"></span>
                    <span>120%+</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded bg-gray-300 dark:bg-gray-600"></span>
                    <span>No target</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Branch Target Achievement (Heatmap) Help (Burmese) Modal --}}
    <div
        x-cloak
        x-show="achievementHelpOpen"
        x-on:keydown.escape.window="achievementHelpOpen = false"
        x-on:click.self="achievementHelpOpen = false"
        class="fixed inset-0 z-50 bg-black/50 p-4"
        aria-modal="true"
        role="dialog"
    >
        <div class="w-full max-w-2xl mx-auto bg-white rounded-lg shadow-xl dark:bg-gray-800 overflow-hidden" style="max-height: calc(100vh - 2rem);">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200">Branch Target Achievement (Heatmap) အသုံးပြုပုံ</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Branch တိုင်းရဲ့ နေ့စဉ် Target ပြည့်မပြည့်ကို အရောင်နဲ့ မြန်မြန်မြင်နိုင်တဲ့ report ပါ</div>
                </div>
                <button type="button" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" x-on:click="achievementHelpOpen = false">
                    ပိတ်မယ်
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(100vh - 12rem);">
                <div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">Heatmap ဆိုတာဘာလဲ?</div>
                        <div class="space-y-2">
                            <div>Heatmap က <span class="font-medium">ဇယားပုံစံ (grid)</span> လိုပါပဲ။ တန်ဖိုးကို အရောင်အလင်း/အမှောင်နဲ့ ပြပေးတဲ့ chart ဖြစ်လို့ အခြေအနေကို အမြန်ဆုံး သိနိုင်ပါတယ်။</div>
                            <div>ဒီ report မှာတော့ <span class="font-medium">နေ့စဉ် Target Achievement%</span> ကို အရောင်နဲ့ ပြပါတယ်။</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">ဘယ်လိုဖတ်မလဲ?</div>
                        <div class="space-y-2">
                            <div><span class="font-medium">Row (အတန်း)</span> တစ်ကြောင်း = Branch တစ်ခု</div>
                            <div><span class="font-medium">Column (အကွက်)</span> တစ်ကော်လံ = တစ်ရက် (နောက်ဆုံး N ရက်)</div>
                            <div><span class="font-medium">Cell (အကွက်)</span> တစ်ခု = အဲဒီ Branch ရဲ့ အဲဒီရက် <span class="font-medium">Achievement%</span></div>
                            <div>အကွက်ပေါ်မှာ <span class="font-medium">မော်စ်တင် (hover)</span> လုပ်ရင် Tooltip မှာ Actual/Target/Percent ကို အသေးစိတ်မြင်နိုင်ပါတယ်။</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">တွက်နည်း (Achievement%)</div>
                        <div class="space-y-2">
                            <div><span class="font-medium">Achievement% = (Actual ÷ Target) × 100</span></div>
                            <div>Target မရှိတဲ့နေ့ (Target = 0) ဆိုရင် အဲဒီကွက်ကို <span class="font-medium">ဗလာ (No target)</span> လို့ပြပါမယ်။</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">အရောင်အဓိပ္ပါယ် (အလွယ်တကူ)</div>
                        <div class="space-y-2">
                            <div>အရောင်က Achievement% အပေါ်မူတည်ပြီး ပြောင်းပါတယ်။ (အောက်က range တွေက chart ထဲမှာ သုံးထားတဲ့ range အတိအကျပါ)</div>
                            <div>
                                <div class="flex flex-col gap-1">
                                    <div><span class="font-medium text-red-600 dark:text-red-400">အနီ</span> = &lt;50% (အရမ်းနည်း)</div>
                                    <div><span class="font-medium text-amber-500 dark:text-amber-300">အဝါ-လိမ္မော်</span> = 50–79%</div>
                                    <div><span class="font-medium text-yellow-500 dark:text-yellow-300">အဝါ</span> = 80–99% (Target မပြည့်သေး)</div>
                                    <div><span class="font-medium text-green-600 dark:text-green-400">အစိမ်း</span> = 100–119% (Target ပြည့်/ကျော်)</div>
                                    <div><span class="font-medium text-blue-600 dark:text-blue-400">အပြာ</span> = 120%+ (Target အလွန်ကျော်)</div>
                                    <div><span class="font-medium">No target</span> = Target မသတ်မှတ်ထားတဲ့နေ့ (Target = 0) / မတွက်နိုင်တဲ့နေ့</div>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">(အရောင် range တွေကို chart ထဲက rule အတိုင်း အုပ်စုခွဲထားပါတယ်)</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">Filter များ</div>
                        <div class="space-y-2">
                            <div><span class="font-medium">Days (min 7)</span> = နောက်ဆုံး ဘယ်နှစ်ရက်ကို ပြမလဲ (အနည်းဆုံး 7 ရက်) ကိုသတ်မှတ်တာပါ။</div>
                            <div><span class="font-medium">Metric</span> = Gram (အလေးချိန်) သို့မဟုတ် Pcs (အရေအတွက်) ကိုရွေးပြီး Achievement% ကိုတွက်ပြပါတယ်။</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Remaining Daily Target (Avg) Help (Burmese) Modal --}}
    <div
        x-cloak
        x-show="avgHelpOpen"
        x-on:keydown.escape.window="avgHelpOpen = false"
        x-on:click.self="avgHelpOpen = false"
        class="fixed inset-0 z-50 bg-black/50 p-4"
        aria-modal="true"
        role="dialog"
    >
        <div class="w-full max-w-2xl mx-auto bg-white rounded-lg shadow-xl dark:bg-gray-800 overflow-hidden" style="max-height: calc(100vh - 2rem);">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200">Remaining Daily Target (Avg) ရဲ့ အဓိပ္ပါယ်</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">ဒီလအတွက် လက်ကျန် Target ကို နေ့စဉ်အလိုက် ဘယ်လောက်လုပ်ရမလဲ ဆိုတာကို တွက်ပြပါတယ်</div>
                </div>
                <button type="button" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" x-on:click="avgHelpOpen = false">
                    ပိတ်မယ်
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(100vh - 12rem);">
                <div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">ဒီ report က ဘာကိုတွက်ပြတာလဲ?</div>
                        <div class="space-y-2">
                            <div>ဒီလအတွက် သတ်မှတ်ထားတဲ့ <span class="font-medium">Target (Gram / Pcs)</span> ကို ယနေ့အထိ ရောင်းပြီးသားပမာဏနဲ့ နှိုင်းပြီး <span class="font-medium">လက်ကျန်</span> ကိုတွက်ပါတယ်။</div>
                            <div>နောက်ကျန်နေတဲ့ ရက်တွေကို ထပ်တူမျှဝေပြီး <span class="font-medium">နေ့စဉ်လိုအပ်တဲ့ Avg Target</span> ကိုပြပါတယ်။</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">တွက်နည်း (Step by step)</div>
                        <div class="space-y-2">
                            <div><span class="font-medium">(1) Month Target</span> = ဒီလအတွင်း ထည့်ထားတဲ့ daily target တွေကို စုစုပေါင်း (Gram / Pcs) လုပ်ပါတယ်။</div>
                            <div><span class="font-medium">(2) Sold To Date</span> = ဒီလအစကနေ ယနေ့အထိ ရောင်းပြီးသား (Gram / Pcs) ကို စုပါတယ်။</div>
                            <div><span class="font-medium">(3) Remaining</span> = Month Target − Sold To Date. အကယ်လို့ အနုတ်ဖြစ်သွားရင် 0 လို့ယူပါတယ်။</div>
                            <div><span class="font-medium">(4) Days Remaining</span> = ဒီလအတွက် ယနေ့နောက်ကျန်တဲ့ ရက်အရေအတွက် (မနက်ဖြန်ကနေ လကုန်အထိ) ပါ။</div>
                            <div><span class="font-medium">(5) Required Avg/Day (Remaining)</span> = Remaining ÷ Days Remaining. Days Remaining = 0 ဖြစ်ရင် 0 ပြပါတယ်။</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">ဥပမာ</div>
                        <div class="space-y-2">
                            <div>ဒီလ Target = 100g / 100pcs</div>
                            <div>ယနေ့အထိ 10 ရက်အတွင်း ရောင်းပြီး = 20g / 20pcs</div>
                            <div>လက်ကျန် = 80g / 80pcs</div>
                            <div>နောက်ကျန်ရက် = 20 ရက်ဆိုရင် Required Avg/Day = 80 ÷ 20 = 4g / 4pcs</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div class="font-semibold mb-1">Branch filter</div>
                        <div class="space-y-2">
                            <div>Branch ကိုရွေးရင် အဲဒီ Branch တစ်ခုအတွက်ပဲတွက်ပြပါတယ်။</div>
                            <div>"All jewelry branches" ဆိုရင် Jewelry branch အားလုံးကို စုပေါင်းတွက်ပြပါတယ်။</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                        <td class="px-3 py-2 text-right">{{ number_format((float) ($totals['target_gram'] ?? 0), 2) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float) ($totals['actual_gram'] ?? 0), 2) }}</td>
                        @php
                            $tg = (float) ($totals['gap_gram'] ?? 0);
                            $tgClass = $tg >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                        @endphp
                        <td class="px-3 py-2 text-right {{ $tgClass }}">{{ number_format($tg, 2) }}</td>
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
                            <td class="px-3 py-2 text-right">{{ number_format($target, 2) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($actual, 2) }}</td>
                            <td class="px-3 py-2 text-right {{ $gapClass }}">{{ number_format($gap, 2) }}</td>
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
            <div class="overflow-x-auto">
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
            </div>
        @else
            <div class="p-2 mt-4 text-red-300 rounded-full bg-gray-50 dark:bg-gray-900">No data found yet</div>
        @endif
    </div>

        {{-- specific Report type --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex flex-col md:flex-row w-full gap-2 mx-auto mb-4 md:w-1/2">
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
                class="md:mt-8 text-blue-600 cursor-pointer dark:text-blue-400 hover:underline hover:text-red-900 dark:hover:text-red-400"
                wire:click='specificDateFilterOfReportType'>Generate</span>
        </div>

        <div class="overflow-x-auto">
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
    </div>



    {{-- popular with detail --}}
    <div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex flex-col md:flex-row w-full gap-2 mx-auto mb-4 md:w-1/2">
            <x-datetime-picker wire:model.live.debounce="popular_start_date_filter" without-time='true' label="Date"
                placeholder="Now" />
            <x-datetime-picker wire:model.live.debounce="popular_end_date_filter" without-time='true' label="Date"
                placeholder="Now" />
        </div>
        <div class="mb-4 text-gray-700 dark:text-gray-200 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <span class="break-words">Report duration - <strong class="font-mono text-blue-500 dark:text-blue-400">
                    {{ \Carbon\Carbon::parse($popular_start_date_filter)->format('j-M-y') }}
                    |
                    {{ \Carbon\Carbon::parse($popular_end_date_filter)->format('j-M-y') }}</strong>
            </span>
            <div class="flex flex-col sm:flex-row gap-2">
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
        </div>

        <div class="overflow-x-auto">
        <table class="w-full bg-white border-collapse rounded-lg table-auto dark:bg-gray-800">
            <thead>
                <tr class="text-left bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
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
    </div>

    {{-- Target Modal --}}
    @if($show_target_modal)
        <div class="fixed inset-0 z-50 bg-black/50 p-4" x-on:keydown.escape.window="$wire.closeTargetModal()" x-on:click.self="$wire.closeTargetModal()" aria-modal="true" role="dialog">
            <div class="w-full max-w-2xl mx-auto bg-white rounded-lg shadow-xl dark:bg-gray-800 overflow-hidden" style="max-height: calc(100vh - 2rem);">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200">Edit Daily Targets</h3>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($selected_date)->format('M j, Y') }}</div>
                    </div>
                    <button type="button" wire:click="closeTargetModal" class="px-3 py-2 rounded-md text-sm font-medium border bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        Close
                    </button>
                </div>

                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(100vh - 12rem);">
                    <div class="grid grid-cols-1 gap-3">
                        @foreach(\App\Models\Branch::where('is_jewelry_shop', true)->orderBy('name')->get() as $branch)
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                    <div class="font-medium text-gray-700 dark:text-gray-200">{{ $branch->name }}</div>
                                    <div class="grid grid-cols-2 gap-2 w-full md:max-w-md">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Target (grams)</label>
                                            <input type="number" step="0.01" wire:model="daily_targets.{{ $branch->id }}" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Target (pcs)</label>
                                            <input type="number" step="1" wire:model="daily_targets_pcs.{{ $branch->id }}" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <button type="button" wire:click="closeTargetModal" class="px-4 py-2 text-gray-600 bg-gray-200 rounded hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200">Cancel</button>
                    <button type="button" wire:click="saveTargets" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Save Targets</button>
                </div>
            </div>
        </div>
    @endif
</div>
    <script>
        (function () {
            const initialIndexChartData = @json($indexChartData ?? ['categories' => [], 'series' => []]);
            const initialChartData = @json($targetVsActualData ?? ['categories' => [], 'series' => []]);
            const initialSaleCompareData = @json($saleCompareChart ?? ['categories' => [], 'series' => []]);
            const initialAvgRemainingTargetsChart = @json($avgRemainingTargetsChart ?? ['categories' => [], 'series' => []]);
            const initialBranchAchievementHeatmap = @json($branchAchievementHeatmap ?? []);

            function formatTwoDecimals(val) {
                if (val === null || typeof val === 'undefined') {
                    return '';
                }

                const num = Number(val);
                if (Number.isNaN(num)) {
                    return '';
                }

                return num.toFixed(2);
            }

            function isDarkMode() {
                return document.documentElement.classList.contains('dark');
            }

            function normalizeCssColor(color) {
                if (!color || typeof color !== 'string') {
                    return color;
                }

                // Some browsers may return space-separated rgb syntax like: "rgb(220 38 38 / 1)"
                // ApexCharts color parsing is more reliable with comma-separated rgb/rgba.
                const trimmed = color.trim();
                if (!trimmed.startsWith('rgb(')) {
                    return trimmed;
                }

                const inner = trimmed.slice(trimmed.indexOf('(') + 1, trimmed.lastIndexOf(')')).trim();
                if (!inner) {
                    return trimmed;
                }

                if (inner.includes('/')) {
                    const parts = inner.split('/');
                    const rgbPart = (parts[0] || '').trim().replace(/,/g, ' ');
                    const aPart = (parts[1] || '').trim();
                    const rgbNums = rgbPart.split(/\s+/).filter(Boolean);
                    const a = Number(aPart);
                    if (rgbNums.length >= 3) {
                        return `rgba(${rgbNums[0]}, ${rgbNums[1]}, ${rgbNums[2]}, ${Number.isNaN(a) ? 1 : a})`;
                    }
                }

                if (inner.includes(' ') && !inner.includes(',')) {
                    const rgbNums = inner.split(/\s+/).filter(Boolean);
                    if (rgbNums.length >= 3) {
                        return `rgb(${rgbNums[0]}, ${rgbNums[1]}, ${rgbNums[2]})`;
                    }
                }

                return trimmed;
            }

            function resolveTailwindColor(className, mode) {
                try {
                    const el = document.createElement('span');
                    el.className = className;
                    el.style.position = 'absolute';
                    el.style.left = '-9999px';
                    el.style.top = '-9999px';
                    el.style.pointerEvents = 'none';
                    document.body.appendChild(el);
                    const color = normalizeCssColor(window.getComputedStyle(el).color);
                    document.body.removeChild(el);
                    return color;
                } catch (e) {
                    return mode === 'dark' ? '#e5e7eb' : '#374151';
                }
            }

            function resolveTailwindBorderColor(className, mode) {
                try {
                    const el = document.createElement('span');
                    el.className = className;
                    el.style.position = 'absolute';
                    el.style.left = '-9999px';
                    el.style.top = '-9999px';
                    el.style.pointerEvents = 'none';
                    el.style.borderTopWidth = '1px';
                    el.style.borderStyle = 'solid';
                    document.body.appendChild(el);
                    const color = normalizeCssColor(window.getComputedStyle(el).borderTopColor);
                    document.body.removeChild(el);
                    return color;
                } catch (e) {
                    return mode === 'dark' ? '#374151' : '#e5e7eb';
                }
            }

            function getChartThemeOptions() {
                const mode = isDarkMode() ? 'dark' : 'light';
                return {
                    mode,
                    foreColor: resolveTailwindColor('text-gray-700 dark:text-gray-200', mode),
                    gridBorderColor: resolveTailwindBorderColor('border border-gray-200 dark:border-gray-700', mode),
                    tooltipTheme: mode,
                };
            }

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

                const theme = getChartThemeOptions();

                const desiredHeight = Math.max(320, categories.length * 34);

                const options = {
                    chart: {
                        type: 'bar',
                        height: desiredHeight,
                        toolbar: { show: false },
                        foreColor: theme.foreColor,
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
                            formatter: formatTwoDecimals,
                        },
                    },
                    yaxis: {
                        labels: {
                            maxWidth: 220,
                        },
                    },
                    grid: { strokeDashArray: 4, borderColor: theme.gridBorderColor },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: formatTwoDecimals,
                        },
                        theme: theme.tooltipTheme,
                    },
                    theme: { mode: theme.mode },
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

                const theme = getChartThemeOptions();

                const options = {
                    chart: {
                        type: 'line',
                        height: 320,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        foreColor: theme.foreColor,
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
                            formatter: formatTwoDecimals,
                        },
                    },
                    grid: { strokeDashArray: 4, borderColor: theme.gridBorderColor },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: formatTwoDecimals,
                        },
                        theme: theme.tooltipTheme,
                    },
                    theme: { mode: theme.mode },
                };

                if (window.targetVsActualLineChart) {
                    window.targetVsActualLineChart.updateOptions(options, true, true);
                    return;
                }

                window.targetVsActualLineChart = new window.ApexCharts(el, options);
                window.targetVsActualLineChart.render();
            }

            function renderAvgRemainingTargetsChart(chartData) {
                if (typeof window.ApexCharts === 'undefined') {
                    return;
                }

                const el = document.getElementById('avg-remaining-target-chart');
                if (!el) {
                    return;
                }

                const categories = (chartData && chartData.categories) ? chartData.categories : [];
                const series = (chartData && chartData.series) ? chartData.series : [];

                const theme = getChartThemeOptions();

                const options = {
                    chart: {
                        type: 'bar',
                        height: 320,
                        toolbar: { show: false },
                        foreColor: theme.foreColor,
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '55%',
                            borderRadius: 4,
                        },
                    },
                    dataLabels: { enabled: false },
                    legend: { position: 'top', horizontalAlign: 'left' },
                    series: series,
                    xaxis: {
                        categories: categories,
                        labels: { rotate: 0 },
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            formatter: formatTwoDecimals,
                        },
                    },
                    grid: { strokeDashArray: 4, borderColor: theme.gridBorderColor },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: { formatter: formatTwoDecimals },
                        theme: theme.tooltipTheme,
                    },
                    theme: { mode: theme.mode },
                };

                if (window.avgRemainingTargetsChart) {
                    window.avgRemainingTargetsChart.updateOptions(options, true, true);
                    return;
                }

                window.avgRemainingTargetsChart = new window.ApexCharts(el, options);
                window.avgRemainingTargetsChart.render();
            }

            function renderBranchAchievementHeatmap(chartData) {
                if (typeof window.ApexCharts === 'undefined') {
                    return;
                }

                const el = document.getElementById('branch-achievement-heatmap');
                if (!el) {
                    return;
                }

                const categories = (chartData && chartData.categories) ? chartData.categories : [];
                const series = (chartData && chartData.series) ? chartData.series : [];

                const theme = getChartThemeOptions();

                const desiredHeight = Math.max(360, (series.length * 26) + 140);
                const desiredWidth = Math.max(900, categories.length * 90);

                // Use fixed hex colors to avoid browser-specific rgb formatting issues affecting Apex color parsing.
                const cLow = '#dc2626';      // red-600
                const cMidLow = '#f59e0b';   // amber-500
                const cMid = '#eab308';      // yellow-500
                const cGood = '#16a34a';     // green-600
                const cOver = '#2563eb';     // blue-600

                const options = {
                    chart: {
                        type: 'heatmap',
                        height: desiredHeight,
                        width: desiredWidth,
                        toolbar: { show: false },
                        foreColor: theme.foreColor,
                    },
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    series: series,
                    xaxis: {
                        type: 'category',
                        categories: categories,
                        labels: { rotate: -45, hideOverlappingLabels: true },
                    },
                    plotOptions: {
                        heatmap: {
                            shadeIntensity: 0.5,
                            radius: 2,
                            useFillColorAsStroke: false,
                            colorScale: {
                                min: 0,
                                ranges: [
                                    { from: 0, to: 49.99, color: cLow, name: '<50%' },
                                    { from: 50, to: 79.99, color: cMidLow, name: '50–79%' },
                                    { from: 80, to: 99.99, color: cMid, name: '80–99%' },
                                    { from: 100, to: 119.99, color: cGood, name: '100–119%' },
                                    { from: 120, to: 100000, color: cOver, name: '120%+' },
                                ],
                            },
                        },
                    },
                    grid: { strokeDashArray: 4, borderColor: theme.gridBorderColor },
                    tooltip: {
                        theme: theme.tooltipTheme,
                        custom: function ({ seriesIndex, dataPointIndex, w }) {
                            try {
                                const branch = (w && w.globals && w.globals.seriesNames) ? (w.globals.seriesNames[seriesIndex] || '') : '';
                                const point = (w && w.config && w.config.series && w.config.series[seriesIndex] && w.config.series[seriesIndex].data)
                                    ? (w.config.series[seriesIndex].data[dataPointIndex] || {})
                                    : {};

                                const x = point.x || '';
                                const date = point.date || '';
                                const y = (typeof point.y === 'number') ? point.y : null;
                                const target = (typeof point.target === 'number') ? point.target : null;
                                const actual = (typeof point.actual === 'number') ? point.actual : null;

                                const metric = (chartData && chartData.metric) ? chartData.metric : '';
                                const metricLabel = metric === 'pcs' ? 'pcs' : 'g';

                                if (y === null || target === null || target <= 0) {
                                    return `<div class="p-2 text-sm"><div class="font-semibold">${branch}</div><div>${x}${date ? ' · ' + date : ''}</div><div class="mt-1 text-xs">No target</div></div>`;
                                }

                                return `<div class="p-2 text-sm"><div class="font-semibold">${branch}</div><div>${x}${date ? ' · ' + date : ''}</div><div class="mt-1">Achievement: <span class="font-semibold">${formatTwoDecimals(y)}%</span></div><div class="mt-1 text-xs">Actual: ${formatTwoDecimals(actual)} ${metricLabel} · Target: ${formatTwoDecimals(target)} ${metricLabel}</div></div>`;
                            } catch (e) {
                                return '';
                            }
                        },
                    },
                    theme: { mode: theme.mode },
                };

                if (window.branchAchievementHeatmapChart) {
                    try {
                        window.branchAchievementHeatmapChart.destroy();
                    } catch (e) {
                        // ignore
                    }
                    window.branchAchievementHeatmapChart = null;
                }

                window.branchAchievementHeatmapChart = new window.ApexCharts(el, options);
                window.branchAchievementHeatmapChart.render();
            }

            function renderAllInitialCharts() {
                renderIndexBarChart(initialIndexChartData);
                renderTargetVsActualLineChart(initialChartData);
                renderSaleCompareLineChart(initialSaleCompareData);
                renderAvgRemainingTargetsChart(initialAvgRemainingTargetsChart);
                renderBranchAchievementHeatmap(initialBranchAchievementHeatmap);
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

                const theme = getChartThemeOptions();

                // For long ranges (e.g., quarterly), render a wider chart so the wrapper can scroll.
                const desiredWidth = Math.max(900, categories.length * 28);

                const options = {
                    chart: {
                        type: 'line',
                        height: 320,
                        width: desiredWidth,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        foreColor: theme.foreColor,
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
                            formatter: formatTwoDecimals,
                        },
                    },
                    grid: { strokeDashArray: 4, borderColor: theme.gridBorderColor },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: formatTwoDecimals,
                        },
                        theme: theme.tooltipTheme,
                    },
                    theme: { mode: theme.mode },
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

                    window.Livewire.on('avg-remaining-target-chart-updated', function (payload) {
                        renderAvgRemainingTargetsChart(payload && payload.chart ? payload.chart : payload);
                    });

                    window.Livewire.on('branch-achievement-heatmap-updated', function (payload) {
                        renderBranchAchievementHeatmap(payload && payload.chart ? payload.chart : payload);
                    });
                }
            });

            // SPA navigations in Livewire v3
            document.addEventListener('livewire:navigated', function () {
                scheduleInitialChartRender();
            });

            // React to dark mode toggles without needing a full reload
            try {
                let lastMode = isDarkMode();
                const observer = new MutationObserver(function () {
                    const current = isDarkMode();
                    if (current !== lastMode) {
                        lastMode = current;
                        scheduleInitialChartRender();
                    }
                });
                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            } catch (e) {
                // no-op
            }
        })();
    </script>

