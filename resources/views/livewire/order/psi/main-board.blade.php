<div>
    <div class="flex flex-wrap gap-4">
        {{-- <x-button href="{{ route('oos') }}" label="OoS" negative icon="view-grid-add" wire:navigate /> --}}
        <x-button href="{{ route('psi_product') }}" label="new PSI Product" green icon="view-grid-add" wire:navigate />
        <x-button href="{{ route('psi-report') }}" label="report" teal icon="view-grid-add" wire:navigate />
        <x-button href="{{ route('stock-update') }}" label="Stock" positive icon="truck" wire:navigate />

        {{-- Marketing --}}
        @can('isMarketing')
            <x-dropdown align='left'>
                <x-slot name="trigger">
                    <x-button label="Digital Marketing" primary />
                </x-slot>
                <x-dropdown.header label="Actions">
                    <x-dropdown.item href="{{ route('shooting') }}" wire:navigate>ဓာတ်ပုံရိုက်ရန်
                        @if ($jobs4Dm > 0)
                            <x-badge.circle negative label="{{ $jobs4Dm }}" />
                        @endif
                    </x-dropdown.item>
                </x-dropdown.header>
            </x-dropdown>
        @endcan

        @can('isBranchSupervisor')
            <x-dropdown align='left'>
                <x-slot name="trigger">
                    <x-button label="Branch Operation" sky />
                </x-slot>
                <x-dropdown.header label="Actions">
                    <x-dropdown.item href="{{ route('orders', ['stus' => 8]) }}" wire:navigate>Receiving
                        @if ($jobs4Br > 0)
                            <x-badge.circle negative label="{{ $jobs4Br }}" />
                        @endif
                    </x-dropdown.item>
                </x-dropdown.header>
            </x-dropdown>
        @endcan

        @can('isInventory')
            <x-dropdown align='left'>
                <x-slot name="trigger">
                    <x-button label="Inventory" sky />
                </x-slot>
                <x-dropdown.header label="Actions">
                    <x-dropdown.item href="{{ route('orders', ['stus' => 5]) }}" wire:navigate>To Register
                    </x-dropdown.item>
                    <x-dropdown.item href="{{ route('orders', ['stus' => 6]) }}" wire:navigate>Processing in Registeration
                    </x-dropdown.item>
                </x-dropdown.header>
            </x-dropdown>
        @endcan

        <x-button href="{{ route('daily_sale') }}" label="Daily Sale" wire:navigate />
    </div>
    {{-- End of Action button  --}}
    {{-- Tempoary off --}}
    <div class="hidden my-4">
        <div class="flex gap-2 text-blue-400 text-md flex-warp opacity-80">
            @foreach ($selectedTag as $data)
                <div class="flex gap-1 cursor-pointer group" wire:click="removeTag({{ $data['key'] }})">
                    #
                    <span class="group-hover:text-red-700">
                        {{ $data['name'] }}
                    </span>
                    {{-- <x-icon name="x"
                        class="hidden w-4 h-4 mt-1 text-red-400 border-gray-300 rounded group-hover:block hover:border hover:text-red-800" /> --}}
                </div>
            @endforeach
        </div>
    </div>
    <div class="hidden">
        <div class="w-48">
            <x-select wire:model.live="filter_hashtag_id" placeholder="#hash-tag" :async-data="route('hashtag')" option-label="name"
                option-value="id">
                <x-slot name="afterOptions" class="flex justify-center p-2" x-show="displayOptions.length === 0">
                    <x-button x-on:click='close' primary flat full>
                        <span x-html="`<b>${search}</b> No found`"></span>
                    </x-button>
                </x-slot>
            </x-select>
        </div>
        <div>
            <x-button id="save" icon="save" wire:click='selectTag' />
        </div>
    </div>
    {{-- ! tempoary off Hash Tag Filter  --}}

    {{-- Showing Chart --}}
    <div class="py-4">
        {{-- <x-button icon="chart-bar" solid label="chart" /> --}}
        {{-- chart sample --}}
        @php
            $total_branch_sale = 0;
            foreach ($branch_sales as $sale) {
                $total_branch_sale += $sale->total;
            }
        @endphp

    </div>

    {{-- Monthly Actual Sale Report (months selectable) --}}
    <div class="my-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <div class="p-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">Monthly Actual Sale</div>
                    <div class="text-sm text-gray-500 dark:text-gray-300">Last {{ (int) ($monthly_months ?? 6) }} months by product</div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Branch</label>
                    <select wire:model.live="monthly_branch_id"
                        class="block w-56 border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">All branches</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Metric</label>
                    <select wire:model.live="report_metric"
                        class="block w-44 border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="qty">Quantity</option>
                        <option value="grams">Total grams</option>
                        <option value="index">Index</option>
                    </select>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Months</label>
                    <select wire:model.live="monthly_months"
                        class="block w-24 border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="3">3</option>
                        <option value="6">6</option>
                        <option value="9">9</option>
                        <option value="12">12</option>
                    </select>
                </div>
            </div>

            <div class="px-4 pb-4">
                <details class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">
                        အသုံးပြုပုံ (Manual)
                    </summary>
                    <div class="px-4 pb-4 text-sm text-gray-700 dark:text-gray-200 space-y-2">
                        <div>
                            <span class="font-semibold">Branch Filter</span> — “All branches” ဆိုရင် ဆိုင်ခွဲအားလုံးရဲ့ sale ကိုပေါင်းပြမယ်။ ဆိုင်ခွဲတစ်ခုရွေးရင် အဲဒီ branch ရဲ့ sale ပဲပြမယ်။
                        </div>
                        <div>
                            <span class="font-semibold">Metric</span> — Quantity / Total grams / Index ကိုရွေးပြီး ဇယားထဲက amount တွေကို အဲဒီ metric အတိုင်းတွက်ပြပါတယ်။
                        </div>
                        <div>
                            <span class="font-semibold">Months Filter</span> — နောက်ဆုံး ဘယ်နှစ်လ (3/6/9/12) ကိုပြမလဲ ရွေးနိုင်ပါတယ်။
                        </div>
                        <div>
                            <span class="font-semibold">Column (Months)</span> — လက်ရှိလမှ နောက်ဆုံး <span class="font-semibold">{{ (int) ($monthly_months ?? 6) }} လ</span> အတွင်း Product တစ်ခုချင်းစီရဲ့ Monthly Actual Sale (qty) ကိုပြထားပါတယ်။
                        </div>
                        <div>
                            <span class="font-semibold">အရောင်အလင်းပြ (Highlight)</span> — အစိမ်းနု cell က အဲဒီ Product အတွက် “အမြင့်ဆုံး (Highest)” လ ဖြစ်ပါတယ်။ အနီနု cell က “အနိမ့်ဆုံး (Lowest)” လ ဖြစ်ပါတယ်။
                        </div>
                        <div>
                            <span class="font-semibold">Highest / Lowest</span> — ဘယ်လမှာ အများဆုံး/အနည်းဆုံးဖြစ်လဲ + amount ကို အကွက်အခြားအဖြစ် ပြထားပါတယ်။
                        </div>
                        <div>
                            <span class="font-semibold">Focus</span> — Product ရဲ့ Focus Qty (သတ်မှတ်ထားတဲ့ focus) ကို Metric အလိုက်တွက်ပြထားပါတယ်။
                        </div>
                        <div>
                            <span class="font-semibold">Current (Situation)</span> — လက်ရှိလ sale qty ကိုပြပြီး၊ အရင်လနဲ့ယှဉ်ပြီး <span class="font-semibold">Up / Down / Flat</span> + % ပြထားပါတယ်။
                        </div>
                        <div>
                            <span class="font-semibold">Current vs Focus</span> — လက်ရှိလ Actual − Focus ကိုပြပြီး <span class="font-semibold">Above / Below / On focus</span> ဆိုပြီး status ပြထားပါတယ်။
                        </div>
                    </div>
                </details>
            </div>

            <div class="overflow-auto max-h-[60vh]">
                @php
                    $metric = $report_metric ?? 'qty';

                    $metricDecimals = in_array($metric, ['grams', 'index'], true) ? 2 : 0;

                    $metricLabel = match ($metric) {
                        'grams' => 'grams',
                        'index' => 'index',
                        default => 'qty',
                    };

                    $calcMetric = function (float $qty, float $weight) use ($metric): float {
                        $grams = $qty * max(0, $weight);
                        return match ($metric) {
                            'grams' => $grams,
                            'index' => ($qty * 0.4) + ($grams * 0.6),
                            default => $qty,
                        };
                    };

                    $monthLabelByKey = [];
                    foreach (($monthlyReport['months'] ?? []) as $m) {
                        $monthLabelByKey[$m['key']] = $m['label'];
                    }

                    $monthKeys = array_map(fn($m) => $m['key'], ($monthlyReport['months'] ?? []));
                    $currentKey = !empty($monthKeys) ? $monthKeys[count($monthKeys) - 1] : null;
                    $prevKey = count($monthKeys) >= 2 ? $monthKeys[count($monthKeys) - 2] : null;

                    $focusDays = 0;
                    if ($currentKey) {
                        try {
                            $focusDays = \Carbon\Carbon::createFromFormat('Y-m', $currentKey)->daysInMonth;
                        } catch (\Throwable $t) {
                            $focusDays = 0;
                        }
                    }

                    $totalsByMonth = [];
                    foreach ($monthKeys as $k) $totalsByMonth[$k] = 0.0;
                    $totalFocus = 0.0;

                    foreach (($monthlyReport['rows'] ?? []) as $r) {
                        $w = (float) ($r['weight'] ?? 0);
                        $fq = (float) ($r['focus_qty'] ?? 0);
                        $totalFocus += $calcMetric($fq * max(0, $focusDays), $w);
                        foreach ($monthKeys as $k) {
                            $totalsByMonth[$k] += $calcMetric((float) (($r['sales'][$k] ?? 0)), $w);
                        }
                    }

                    $totalMaxKey = null;
                    $totalMinKey = null;
                    $totalMaxAmount = null;
                    $totalMinAmount = null;
                    foreach ($monthKeys as $k) {
                        $amt = (float) ($totalsByMonth[$k] ?? 0);
                        if ($totalMaxAmount === null || $amt > $totalMaxAmount) { $totalMaxAmount = $amt; $totalMaxKey = $k; }
                        if ($totalMinAmount === null || $amt < $totalMinAmount) { $totalMinAmount = $amt; $totalMinKey = $k; }
                    }

                    $totalCurrent = $currentKey ? (float) ($totalsByMonth[$currentKey] ?? 0) : 0.0;
                    $totalPrev = $prevKey ? (float) ($totalsByMonth[$prevKey] ?? 0) : 0.0;
                    $totalTrend = $totalCurrent <=> $totalPrev;
                    $totalDelta = $totalCurrent - $totalPrev;
                    $totalDeltaPct = $totalPrev > 0 ? ($totalDelta / $totalPrev) * 100.0 : ($totalCurrent > 0 ? 100.0 : 0.0);
                    $totalTrendText = $totalTrend > 0 ? 'Up' : ($totalTrend < 0 ? 'Down' : 'Flat');
                    $totalTrendClass = $totalTrend > 0
                        ? 'text-emerald-700 dark:text-emerald-300'
                        : ($totalTrend < 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300');
                @endphp

                <table class="min-w-full text-sm text-left text-gray-700 dark:text-gray-200">
                    <thead class="sticky top-0 z-10 text-xs uppercase bg-gray-50/80 backdrop-blur supports-backdrop-blur:backdrop-blur-sm dark:bg-gray-800/80 dark:text-gray-300">
                        <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                            <th class="px-4 py-3 font-semibold">Product</th>
                            @foreach (($monthlyReport['months'] ?? []) as $m)
                                <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">{{ $m['label'] }}</th>
                            @endforeach
                            <th class="px-3 py-3 font-semibold whitespace-nowrap">Focus ({{ $metricLabel }})</th>
                            <th class="px-3 py-3 font-semibold whitespace-nowrap">Highest</th>
                            <th class="px-3 py-3 font-semibold whitespace-nowrap">Lowest</th>
                            <th class="px-3 py-3 font-semibold whitespace-nowrap">Current</th>
                            <th class="px-3 py-3 font-semibold whitespace-nowrap">Current vs Focus</th>
                        </tr>

                        <tr class="divide-x divide-gray-200 dark:divide-gray-700 bg-white/60 dark:bg-gray-900/40">
                            <th class="px-4 py-2 font-semibold text-gray-900 dark:text-white">Grand Total</th>
                            @foreach (($monthlyReport['months'] ?? []) as $m)
                                @php
                                    $mk = $m['key'];
                                    $amt = (float) ($totalsByMonth[$mk] ?? 0);
                                    $cellClass = $mk === $totalMaxKey
                                        ? 'bg-emerald-50 dark:bg-emerald-900/20'
                                        : ($mk === $totalMinKey ? 'bg-red-50 dark:bg-red-900/20' : '');
                                @endphp
                                <th class="px-3 py-2 text-right tabular-nums {{ $cellClass }}">{{ number_format($amt, $metricDecimals) }}</th>
                            @endforeach
                            <th class="px-3 py-2 tabular-nums">{{ number_format($totalFocus, $metricDecimals) }}</th>
                            <th class="px-3 py-2 whitespace-nowrap">
                                <span class="font-medium">{{ $monthLabelByKey[$totalMaxKey] ?? ($totalMaxKey ?? '-') }}</span>
                                <span class="text-gray-500 dark:text-gray-400">({{ number_format((float) ($totalMaxAmount ?? 0), $metricDecimals) }})</span>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap">
                                <span class="font-medium">{{ $monthLabelByKey[$totalMinKey] ?? ($totalMinKey ?? '-') }}</span>
                                <span class="text-gray-500 dark:text-gray-400">({{ number_format((float) ($totalMinAmount ?? 0), $metricDecimals) }})</span>
                            </th>
                            <th class="px-3 py-2 whitespace-nowrap">
                                <div class="font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($totalCurrent, $metricDecimals) }}</div>
                                <div class="text-xs {{ $totalTrendClass }}">
                                    {{ $totalTrendText }}
                                    @if ($totalPrev > 0)
                                        ({{ number_format($totalDeltaPct, 1) }}%)
                                    @elseif ($totalCurrent > 0)
                                        (+100%)
                                    @else
                                        (0%)
                                    @endif
                                </div>
                            </th>
                            @php
                                $totalFocusDiff = (float) $totalCurrent - (float) $totalFocus;
                                $totalFocusTrendText = $totalFocusDiff > 0 ? 'Above' : ($totalFocusDiff < 0 ? 'Below' : 'On');
                                $totalFocusTrendClass = $totalFocusDiff > 0
                                    ? 'text-emerald-700 dark:text-emerald-300'
                                    : ($totalFocusDiff < 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300');
                            @endphp
                            <th class="px-3 py-2 whitespace-nowrap">
                                <div class="font-semibold tabular-nums {{ $totalFocusTrendClass }}">
                                    {{ $totalFocusDiff >= 0 ? '+' : '' }}{{ number_format($totalFocusDiff, $metricDecimals) }}
                                </div>
                                <div class="text-xs {{ $totalFocusTrendClass }}">{{ $totalFocusTrendText }} focus</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse (($monthlyReport['rows'] ?? []) as $row)
                            @php
                                $weight = (float) ($row['weight'] ?? 0);
                                $focusQty = (float) ($row['focus_qty'] ?? 0);

                                $rowMaxKey = null;
                                $rowMinKey = null;
                                $rowMaxAmount = null;
                                $rowMinAmount = null;
                                foreach (($monthlyReport['months'] ?? []) as $m) {
                                    $mk = $m['key'];
                                    $amt = $calcMetric((float) (($row['sales'][$mk] ?? 0)), $weight);
                                    if ($rowMaxAmount === null || $amt > $rowMaxAmount) { $rowMaxAmount = $amt; $rowMaxKey = $mk; }
                                    if ($rowMinAmount === null || $amt < $rowMinAmount) { $rowMinAmount = $amt; $rowMinKey = $mk; }
                                }

                                $current = $currentKey ? $calcMetric((float) (($row['sales'][$currentKey] ?? 0)), $weight) : 0.0;
                                $prev = $prevKey ? $calcMetric((float) (($row['sales'][$prevKey] ?? 0)), $weight) : 0.0;
                                $trend = $current <=> $prev;
                                $delta = $current - $prev;
                                $deltaPct = $prev > 0 ? ($delta / $prev) * 100.0 : ($current > 0 ? 100.0 : 0.0);

                                $trendText = $trend > 0 ? 'Up' : ($trend < 0 ? 'Down' : 'Flat');
                                $trendClass = $trend > 0
                                    ? 'text-emerald-700 dark:text-emerald-300'
                                    : ($trend < 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300');

                                $focusAmount = (float) $calcMetric($focusQty * max(0, $focusDays), $weight);
                                $focusDiff = (float) $current - $focusAmount;
                                $focusTrendText = $focusDiff > 0 ? 'Above' : ($focusDiff < 0 ? 'Below' : 'On');
                                $focusTrendClass = $focusDiff > 0
                                    ? 'text-emerald-700 dark:text-emerald-300'
                                    : ($focusDiff < 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300');
                            @endphp
                            <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ $row['product'] ?? '-' }}
                                </td>

                                @foreach (($monthlyReport['months'] ?? []) as $m)
                                    @php
                                        $mk = $m['key'];
                                        $amount = (float) $calcMetric((float) (($row['sales'][$mk] ?? 0)), $weight);
                                        $cellClass = $mk === $rowMaxKey
                                            ? 'bg-emerald-50 dark:bg-emerald-900/20'
                                            : ($mk === $rowMinKey ? 'bg-red-50 dark:bg-red-900/20' : '');
                                    @endphp
                                    <td class="px-3 py-3 text-right tabular-nums {{ $cellClass }}">
                                        {{ number_format($amount, $metricDecimals) }}
                                    </td>
                                @endforeach

                                <td class="px-3 py-3 whitespace-nowrap tabular-nums">
                                    {{ number_format($focusAmount, $metricDecimals) }}
                                </td>

                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="font-medium">
                                        {{ $monthLabelByKey[$rowMaxKey] ?? ($rowMaxKey ?? '-') }}
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400">({{ number_format((float) ($rowMaxAmount ?? 0), $metricDecimals) }})</span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="font-medium">
                                        {{ $monthLabelByKey[$rowMinKey] ?? ($rowMinKey ?? '-') }}
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400">({{ number_format((float) ($rowMinAmount ?? 0), $metricDecimals) }})</span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($current, $metricDecimals) }}</div>
                                    <div class="text-xs {{ $trendClass }}">
                                        {{ $trendText }}
                                        @if ($prev > 0)
                                            ({{ number_format($deltaPct, 1) }}%)
                                        @elseif ($current > 0)
                                            (+100%)
                                        @else
                                            (0%)
                                        @endif
                                    </div>
                                        {{-- <span class="font-semibold">Focus</span> — Focus Qty က <span class="font-semibold">တစ်နေ့စာ focus</span> ဖြစ်ပါတယ်။ ဒီဇယားမှာ Focus column ကို <span class="font-semibold">လက်ရှိလရဲ့ ရက်အရေအတွက် × တစ်နေ့စာ focus</span> အတိုင်းတွက်ပြထားပါတယ်။ --}}

                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="font-semibold tabular-nums {{ $focusTrendClass }}">
                                        {{ $focusDiff >= 0 ? '+' : '' }}{{ number_format($focusDiff, $metricDecimals) }}
                                    </div>
                                    <div class="text-xs {{ $focusTrendClass }}">{{ $focusTrendText }} focus</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-300">
                                    No sales data for the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Custom Date Range Compare (Flatpickr) --}}
    <div class="my-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <div class="p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">Date Range Compare</div>
                        <div class="text-sm text-gray-500 dark:text-gray-300">Compare actual sale totals by product</div>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-button
                            sm
                            teal
                            flat
                            icon="information-circle"
                            label="အသုံးပြုပုံ"
                            x-on:click="$openModal('rangeManualModal')"
                        />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Branch</label>
                            <select wire:model.live="monthly_branch_id"
                                class="block w-full border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                <option value="">All branches</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2" wire:ignore>
                            <div
                                x-data="{
                                    from: @entangle('sale_range_from').live,
                                    to: @entangle('sale_range_to').live,
                                    picker: null,
                                    initPicker() {
                                        if (!window.flatpickr) { setTimeout(() => this.initPicker(), 100); return; }
                                        if (!this.$refs.rangeA) { setTimeout(() => this.initPicker(), 50); return; }
                                        if (this.$refs.rangeA._flatpickr) return;

                                        const alpine = this;
                                        this.picker = window.flatpickr(this.$refs.rangeA, {
                                            mode: 'range',
                                            dateFormat: 'Y-m-d',
                                            altInput: true,
                                            altFormat: 'M d, Y',
                                            altInputClass: 'block w-full border-gray-300 rounded-md shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                                            defaultDate: (alpine.from && alpine.to) ? [alpine.from, alpine.to] : null,
                                            allowInput: true,
                                            appendTo: document.body,
                                            onReady(selectedDates, dateStr, instance) {
                                                try { instance.calendarContainer.style.zIndex = '9999'; } catch (e) {}
                                                try { instance.altInput.placeholder = 'Select range A'; } catch (e) {}
                                            },
                                            onChange(selectedDates, dateStr, instance) {
                                                if (!selectedDates || selectedDates.length === 0 || !dateStr || String(dateStr).trim() === '') {
                                                    alpine.from = '';
                                                    alpine.to = '';
                                                    $wire.set('sale_range_from', '');
                                                    $wire.set('sale_range_to', '');
                                                    return;
                                                }
                                                if (selectedDates.length < 2) return;
                                                const start = instance.formatDate(selectedDates[0], 'Y-m-d');
                                                const end = instance.formatDate(selectedDates[1], 'Y-m-d');
                                                alpine.from = start;
                                                alpine.to = end;
                                                $wire.set('sale_range_from', start);
                                                $wire.set('sale_range_to', end);
                                            }
                                        });
                                    }
                                }"
                                x-init="$nextTick(() => initPicker())"
                            >
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Range A</label>
                                <input x-ref="rangeA" type="text" class="w-full" placeholder="Select range" />
                            </div>
                        </div>

                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Compare</label>
                            <select wire:model.live="sale_compare_mode"
                                class="block w-full border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                <option value="last_month">Last month (same days)</option>
                                <option value="prev">Previous period</option>
                                <option value="custom">Custom range</option>
                            </select>
                        </div>

                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Metric</label>
                            <select wire:model.live="report_metric"
                                class="block w-full border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                <option value="qty">Quantity</option>
                                <option value="grams">Total grams</option>
                                <option value="index">Index</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2" wire:ignore>
                            <div
                                x-data="{
                                    mode: @entangle('sale_compare_mode').live,
                                    from: @entangle('sale_compare_from').live,
                                    to: @entangle('sale_compare_to').live,
                                    picker: null,
                                    initPicker() {
                                        if (!window.flatpickr) { setTimeout(() => this.initPicker(), 100); return; }
                                        if (!this.$refs.rangeB) { setTimeout(() => this.initPicker(), 50); return; }
                                        if (this.$refs.rangeB._flatpickr) return;

                                        const alpine = this;
                                        this.picker = window.flatpickr(this.$refs.rangeB, {
                                            mode: 'range',
                                            dateFormat: 'Y-m-d',
                                            altInput: true,
                                            altFormat: 'M d, Y',
                                            altInputClass: 'block w-full border-gray-300 rounded-md shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                                            defaultDate: (alpine.from && alpine.to) ? [alpine.from, alpine.to] : null,
                                            allowInput: true,
                                            appendTo: document.body,
                                            onReady(selectedDates, dateStr, instance) {
                                                try { instance.calendarContainer.style.zIndex = '9999'; } catch (e) {}
                                                try { instance.altInput.placeholder = 'Select range B'; } catch (e) {}
                                            },
                                            onChange(selectedDates, dateStr, instance) {
                                                if (!selectedDates || selectedDates.length === 0 || !dateStr || String(dateStr).trim() === '') {
                                                    alpine.from = '';
                                                    alpine.to = '';
                                                    $wire.set('sale_compare_from', '');
                                                    $wire.set('sale_compare_to', '');
                                                    return;
                                                }
                                                if (selectedDates.length < 2) return;
                                                const start = instance.formatDate(selectedDates[0], 'Y-m-d');
                                                const end = instance.formatDate(selectedDates[1], 'Y-m-d');
                                                alpine.from = start;
                                                alpine.to = end;
                                                $wire.set('sale_compare_from', start);
                                                $wire.set('sale_compare_to', end);
                                            }
                                        });
                                    }
                                }"
                                x-init="$nextTick(() => initPicker())"
                                x-show="mode === 'custom'"
                                style="display:none"
                            >
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Range B</label>
                                <input x-ref="rangeB" type="text" class="w-full" placeholder="Select range" />
                            </div>

                            <div
                                x-show="@entangle('sale_compare_mode').live === 'last_month'"
                                class="text-xs text-gray-500 dark:text-gray-400 pt-6"
                                style="display:none"
                            >
                                Range B auto = last month (same days)
                            </div>

                            <div
                                x-show="@entangle('sale_compare_mode').live === 'prev'"
                                class="text-xs text-gray-500 dark:text-gray-400 pt-6"
                                style="display:none"
                            >
                                Range B auto = previous period
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Tip: Default Range A = last 7 days. Default compare = last month (same days). Focus A/B = focus per-day × days.
                </div>
            </div>

            @if ($rangeCompare)
                <div class="px-4 pb-4 text-sm text-gray-700 dark:text-gray-200">
                    <span class="font-semibold">Range A:</span> {{ $rangeCompare['rangeA']['from'] }} → {{ $rangeCompare['rangeA']['to'] }}
                    <span class="mx-2 text-gray-300">|</span>
                    <span class="font-semibold">Range B:</span> {{ $rangeCompare['rangeB']['from'] }} → {{ $rangeCompare['rangeB']['to'] }}
                </div>

                <div class="overflow-auto max-h-[60vh]">
                    @php
                        $metric = $report_metric ?? 'qty';

                        $metricDecimals = in_array($metric, ['grams', 'index'], true) ? 2 : 0;

                        $rangeDays = function (?string $from, ?string $to): int {
                            if (!$from || !$to) return 0;
                            try {
                                $s = \Carbon\Carbon::parse($from);
                                $e = \Carbon\Carbon::parse($to);
                                if ($e->lt($s)) return 0;
                                return $s->diffInDays($e) + 1;
                            } catch (\Throwable $t) {
                                return 0;
                            }
                        };

                        $daysA = $rangeDays($rangeCompare['rangeA']['from'] ?? null, $rangeCompare['rangeA']['to'] ?? null);
                        $daysB = $rangeDays($rangeCompare['rangeB']['from'] ?? null, $rangeCompare['rangeB']['to'] ?? null);

                        $metricLabel = match ($metric) {
                            'grams' => 'grams',
                            'index' => 'index',
                            default => 'qty',
                        };
                        $calcMetric = function (float $qty, float $weight) use ($metric): float {
                            $grams = $qty * max(0, $weight);
                            return match ($metric) {
                                'grams' => $grams,
                                'index' => ($qty * 0.4) + ($grams * 0.6),
                                default => $qty,
                            };
                        };

                        $totalA = 0.0;
                        $totalB = 0.0;
                        $totalFocusA = 0.0;
                        $totalFocusB = 0.0;
                        foreach (($rangeCompare['rows'] ?? []) as $r) {
                            $w = (float) ($r['weight'] ?? 0);
                            $totalA += $calcMetric((float) ($r['a'] ?? 0), $w);
                            $totalB += $calcMetric((float) ($r['b'] ?? 0), $w);

                            $fq = (float) ($r['focus_qty'] ?? 0);
                            $totalFocusA += $calcMetric($fq * max(0, $daysA), $w);
                            $totalFocusB += $calcMetric($fq * max(0, $daysB), $w);
                        }
                        $totalDelta = $totalA - $totalB;
                        $totalTrend = $totalA <=> $totalB;
                        $totalPct = $totalB > 0 ? ($totalDelta / $totalB) * 100.0 : ($totalA > 0 ? 100.0 : 0.0);
                        $totalTrendText = $totalTrend > 0 ? 'Up' : ($totalTrend < 0 ? 'Down' : 'Flat');
                        $totalTrendClass = $totalTrend > 0
                            ? 'text-emerald-700 dark:text-emerald-300'
                            : ($totalTrend < 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300');
                    @endphp

                    <table class="min-w-full text-sm text-left text-gray-700 dark:text-gray-200">
                        <thead class="sticky top-0 z-10 text-xs uppercase bg-gray-50/80 backdrop-blur supports-backdrop-blur:backdrop-blur-sm dark:bg-gray-800/80 dark:text-gray-300">
                            <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                                <th class="px-4 py-3 font-semibold">Product</th>
                                <th class="px-3 py-3 font-semibold whitespace-nowrap">Focus A ({{ $metricLabel }})</th>
                                <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">Range A</th>
                                <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">A vs Focus</th>
                                <th class="px-3 py-3 font-semibold whitespace-nowrap">Focus B ({{ $metricLabel }})</th>
                                <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">Range B</th>
                                <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">Δ</th>
                                <th class="px-3 py-3 font-semibold whitespace-nowrap">Situation</th>
                            </tr>

                            <tr class="divide-x divide-gray-200 dark:divide-gray-700 bg-white/60 dark:bg-gray-900/40">
                                <th class="px-4 py-2 font-semibold text-gray-900 dark:text-white">Grand Total</th>
                                <th class="px-3 py-2 tabular-nums">{{ number_format($totalFocusA, $metricDecimals) }}</th>
                                <th class="px-3 py-2 text-right tabular-nums">{{ number_format($totalA, $metricDecimals) }}</th>
                                @php
                                    $totalAFocusDiff = (float) $totalA - (float) $totalFocusA;
                                    $totalAFocusTrendText = $totalAFocusDiff > 0 ? 'Above' : ($totalAFocusDiff < 0 ? 'Below' : 'On');
                                    $totalAFocusTrendClass = $totalAFocusDiff > 0
                                        ? 'text-emerald-700 dark:text-emerald-300'
                                        : ($totalAFocusDiff < 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300');
                                @endphp
                                <th class="px-3 py-2 text-right tabular-nums {{ $totalAFocusTrendClass }}">
                                    {{ $totalAFocusDiff >= 0 ? '+' : '' }}{{ number_format($totalAFocusDiff, $metricDecimals) }}
                                </th>
                                <th class="px-3 py-2 tabular-nums">{{ number_format($totalFocusB, $metricDecimals) }}</th>
                                <th class="px-3 py-2 text-right tabular-nums">{{ number_format($totalB, $metricDecimals) }}</th>
                                <th class="px-3 py-2 text-right tabular-nums {{ $totalTrendClass }}">
                                    {{ $totalDelta >= 0 ? '+' : '' }}{{ number_format($totalDelta, $metricDecimals) }}
                                </th>
                                <th class="px-3 py-2 whitespace-nowrap">
                                    <span class="font-semibold {{ $totalTrendClass }}">{{ $totalTrendText }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ number_format($totalPct, 1) }}%)</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($rangeCompare['rows'] as $row)
                                @php
                                    $trend = (int) ($row['trend'] ?? 0);
                                    $weight = (float) ($row['weight'] ?? 0);
                                    $focusQty = (float) ($row['focus_qty'] ?? 0);

                                    $a = (float) $calcMetric((float) ($row['a'] ?? 0), $weight);
                                    $b = (float) $calcMetric((float) ($row['b'] ?? 0), $weight);
                                    $delta = $a - $b;
                                    $pct = $b > 0 ? ($delta / $b) * 100.0 : ($a > 0 ? 100.0 : 0.0);

                                    $trendText = $trend > 0 ? 'Up' : ($trend < 0 ? 'Down' : 'Flat');
                                    $trendClass = $trend > 0
                                        ? 'text-emerald-700 dark:text-emerald-300'
                                        : ($trend < 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300');
                                @endphp
                                <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $row['product'] ?? '-' }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap tabular-nums">
                                        @php
                                            $focusAQty = (float) $focusQty * max(0, $daysA);
                                            $focusBQty = (float) $focusQty * max(0, $daysB);

                                            $focusAAmount = (float) $calcMetric($focusAQty, $weight);
                                            $focusBAmount = (float) $calcMetric($focusBQty, $weight);

                                            $aFocusDiff = (float) $a - $focusAAmount;
                                            $aFocusTrendClass = $aFocusDiff > 0
                                                ? 'text-emerald-700 dark:text-emerald-300'
                                                : ($aFocusDiff < 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300');
                                        @endphp
                                        {{ number_format($focusAAmount, $metricDecimals) }}
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ number_format($a, $metricDecimals) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums {{ $aFocusTrendClass }}">
                                        {{ $aFocusDiff >= 0 ? '+' : '' }}{{ number_format($aFocusDiff, $metricDecimals) }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap tabular-nums">{{ number_format($focusBAmount, $metricDecimals) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ number_format($b, $metricDecimals) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums {{ $trendClass }}">
                                        {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, $metricDecimals) }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <span class="font-semibold {{ $trendClass }}">{{ $trendText }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            ({{ number_format($pct, 1) }}%)
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-4 pb-4 text-sm text-gray-500 dark:text-gray-300">
                    Select Range A to see comparison.
                </div>
            @endif
        </div>
    </div>

    {{-- Focus vs Actual (Line Chart) --}}
    <div class="my-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <div class="p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">Focus vs Actual (Line Chart)</div>
                        <div class="text-sm text-gray-500 dark:text-gray-300">Custom date range (default: current month)</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Branch</label>
                            <select wire:model.live="focus_chart_branch_id"
                                class="block w-full border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                <option value="">All branches</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Metric</label>
                            <select wire:model.live="focus_chart_metric"
                                class="block w-full border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                <option value="qty">Quantity</option>
                                <option value="grams">Total grams</option>
                                <option value="index">Index</option>
                            </select>
                        </div>

                        <div wire:ignore>
                            <div
                                x-data="{
                                    from: @entangle('focus_chart_from').live,
                                    to: @entangle('focus_chart_to').live,
                                    picker: null,
                                    initPicker() {
                                        if (!window.flatpickr) { setTimeout(() => this.initPicker(), 100); return; }
                                        if (!this.$refs.range) { setTimeout(() => this.initPicker(), 50); return; }
                                        if (this.$refs.range._flatpickr) return;

                                        const alpine = this;
                                        this.picker = window.flatpickr(this.$refs.range, {
                                            mode: 'range',
                                            dateFormat: 'Y-m-d',
                                            altInput: true,
                                            altFormat: 'M d, Y',
                                            altInputClass: 'block w-full border-gray-300 rounded-md shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                                            defaultDate: (alpine.from && alpine.to) ? [alpine.from, alpine.to] : null,
                                            allowInput: true,
                                            appendTo: document.body,
                                            onReady(selectedDates, dateStr, instance) {
                                                try { instance.calendarContainer.style.zIndex = '9999'; } catch (e) {}
                                                try { instance.altInput.placeholder = 'Select date range'; } catch (e) {}
                                            },
                                            onChange(selectedDates, dateStr, instance) {
                                                if (!selectedDates || selectedDates.length === 0 || !dateStr || String(dateStr).trim() === '') {
                                                    alpine.from = '';
                                                    alpine.to = '';
                                                    $wire.set('focus_chart_from', '');
                                                    $wire.set('focus_chart_to', '');
                                                    return;
                                                }
                                                if (selectedDates.length < 2) return;
                                                const start = instance.formatDate(selectedDates[0], 'Y-m-d');
                                                const end = instance.formatDate(selectedDates[1], 'Y-m-d');
                                                alpine.from = start;
                                                alpine.to = end;
                                                $wire.set('focus_chart_from', start);
                                                $wire.set('focus_chart_to', end);
                                            }
                                        });
                                    }
                                }"
                                x-init="$nextTick(() => initPicker())"
                            >
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date Range</label>
                                <input x-ref="range" type="text" class="w-full" placeholder="Select range" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-4 pb-4" wire:ignore>
                <div
                    x-data="{
                        from: @entangle('focus_chart_from').live,
                        to: @entangle('focus_chart_to').live,
                        branch: @entangle('focus_chart_branch_id').live,
                        metric: @entangle('focus_chart_metric').live,
                        chart: null,
                        loading: false,
                        decimalsFor(metric) { return metric === 'qty' ? 0 : 2; },
                        fmt(val) {
                            const d = this.decimalsFor(this.metric);
                            const num = Number(val ?? 0);
                            return isFinite(num) ? num.toFixed(d) : '0';
                        },
                        async load() {
                            this.loading = true;
                            try {
                                const payload = await $wire.call('focusActualChartData');
                                const labels = (payload && payload.labels) ? payload.labels : [];
                                const actual = (payload && payload.actual) ? payload.actual : [];
                                const focus = (payload && payload.focus) ? payload.focus : [];

                                if (this.chart) {
                                    this.chart.updateOptions({
                                        xaxis: { categories: labels },
                                        yaxis: { labels: { formatter: (v) => this.fmt(v) } },
                                        tooltip: { y: { formatter: (v) => this.fmt(v) } },
                                    }, false, true);
                                    this.chart.updateSeries([
                                        { name: 'Actual', data: actual },
                                        { name: 'Focus (per-day)', data: focus },
                                    ], true);
                                }
                            } catch (e) {
                                console.error('focusActualChartData failed', e);
                            } finally {
                                this.loading = false;
                            }
                        },
                        initChart() {
                            if (!window.ApexCharts) return;
                            this.chart = new window.ApexCharts(this.$refs.chart, {
                                chart: { type: 'line', height: 340, toolbar: { show: true } },
                                stroke: { curve: 'smooth', width: 3 },
                                dataLabels: { enabled: false },
                                series: [
                                    { name: 'Actual', data: [] },
                                    { name: 'Focus (per-day)', data: [] },
                                ],
                                colors: ['#2563eb', '#f59e0b'],
                                xaxis: { categories: [] },
                                yaxis: { labels: { formatter: (v) => this.fmt(v) } },
                                tooltip: { y: { formatter: (v) => this.fmt(v) } },
                                legend: { position: 'top' },
                            });
                            this.chart.render();
                        },
                        init() {
                            this.initChart();
                            this.load();

                            this.$watch('from', () => this.load());
                            this.$watch('to', () => this.load());
                            this.$watch('branch', () => this.load());
                            this.$watch('metric', () => this.load());
                        }
                    }"
                    x-init="init()"
                    class="rounded-md border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900"
                >
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Daily totals</div>
                        <div x-show="loading" class="text-xs text-gray-500 dark:text-gray-400" style="display:none">Loading…</div>
                    </div>
                    <div x-ref="chart"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stock-out Monthly Report --}}
    <div class="my-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <div class="p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">Stock-out Monthly Report</div>
                        <div class="text-sm text-gray-500 dark:text-gray-300">
                            {{ $stockoutReport['range']['from'] ?? '' }} → {{ $stockoutReport['range']['to'] ?? '' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Branch</label>
                            <select wire:model.live="stockout_branch_id"
                                class="block w-full border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                <option value="">All branches</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div wire:ignore>
                            <div
                                x-data="{
                                    month: @entangle('stockout_month').live,
                                    picker: null,
                                    initPicker() {
                                        if (!window.flatpickr || !window.monthSelectPlugin) { setTimeout(() => this.initPicker(), 100); return; }
                                        if (!this.$refs.month) { setTimeout(() => this.initPicker(), 50); return; }
                                        if (this.$refs.month._flatpickr) return;

                                        const alpine = this;
                                        this.picker = window.flatpickr(this.$refs.month, {
                                            dateFormat: 'Y-m',
                                            altInput: true,
                                            altFormat: 'F Y',
                                            altInputClass: 'block w-full border-gray-300 rounded-md shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                                            defaultDate: alpine.month ? (alpine.month + '-01') : null,
                                            plugins: [
                                                window.monthSelectPlugin({
                                                    shorthand: true,
                                                    dateFormat: 'Y-m',
                                                    altFormat: 'F Y',
                                                })
                                            ],
                                            onChange(selectedDates, dateStr, instance) {
                                                const v = String(dateStr || '').trim();
                                                if (!v) return;
                                                alpine.month = v;
                                                $wire.set('stockout_month', v);
                                            }
                                        });
                                    }
                                }"
                                x-init="$nextTick(() => initPicker())"
                            >
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Month</label>
                                <input x-ref="month" type="text" class="w-full" placeholder="Select month" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-auto max-h-[60vh]">
                <table class="min-w-full text-sm text-left text-gray-700 dark:text-gray-200">
                    <thead class="sticky top-0 z-10 text-xs uppercase bg-gray-50/80 backdrop-blur supports-backdrop-blur:backdrop-blur-sm dark:bg-gray-800/80 dark:text-gray-300">
                        <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                            <th class="px-4 py-3 font-semibold">Product</th>
                            <th class="px-4 py-3 font-semibold">Remark</th>
                            <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">Opening</th>
                            <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">Refill</th>
                            <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">Sales</th>
                            <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">Closing</th>
                            <th class="px-3 py-3 font-semibold text-right whitespace-nowrap">0-stock days</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">0-stock intervals</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse (($stockoutReport['rows'] ?? []) as $r)
                            <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ $r['product'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $r['remark'] ?? '' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) ($r['opening'] ?? 0), 0) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) ($r['refill'] ?? 0), 0) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) ($r['sale'] ?? 0), 0) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) ($r['closing'] ?? 0), 0) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">
                                    <span class="font-semibold {{ ((int) ($r['zero_days'] ?? 0)) > 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-700 dark:text-gray-200' }}">
                                        {{ (int) ($r['zero_days'] ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                    @php $intervals = $r['zero_intervals'] ?? []; @endphp
                                    @if (is_array($intervals) && count($intervals) > 0)
                                        {{ implode(' | ', $intervals) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-300">
                                    No data for selected month.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-modal.card title="Range Report Manual (အသုံးပြုပုံ)" blur wire:model="rangeManualModal">
        <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
            <div>
                <div class="font-semibold">Default Setting (ပုံမှန်)</div>
                <div class="mt-1">
                    Range A = လက်ရှိနေ့အပါအဝင် နောက်ဆုံး <span class="font-semibold">၇ ရက်</span>
                    (ဥပမာ: Today နှင့် အရင် ၆ ရက်)
                </div>
                <div>
                    Compare = <span class="font-semibold">Last month (same days)</span>
                    (ဥပမာ: Jan 14–20 ကို Dec 14–20 နဲ့ ယှဉ်မယ်)
                </div>
            </div>

            <div>
                <div class="font-semibold">How to Use</div>
                <ol class="list-decimal pl-5 space-y-1">
                    <li>Branch ကို “All branches” (စုစုပေါင်း) သို့မဟုတ် Branch တစ်ခုရွေးပါ</li>
                    <li>Range A ကို ရက်ကွက် (date range) ရွေးပါ (Flatpickr)</li>
                    <li>Metric (Quantity / grams / index) ကိုရွေးပါ</li>
                    <li>Compare ကို ရွေးပါ:
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            - Last month (same days): အဲဒီ range ကို လွန်ခဲ့တဲ့လတူညီတဲ့နေ့တွေကိုယှဉ်
                            <br>- Previous period: Range A မတိုင်ခင် အလျားတူကာလကိုယှဉ်
                            <br>- Custom range: Range B ကိုကိုယ်တိုင်ရွေး
                        </div>
                    </li>
                </ol>
            </div>

            <div>
                <div class="font-semibold">Table Meaning</div>
                <div class="mt-1">
                    <span class="font-semibold">Range A</span> = ရွေးထားတဲ့ကာလအတွင်း total sale (Metric အလိုက်)
                    ၊ <span class="font-semibold">Range B</span> = ယှဉ်လို့ရတဲ့ကာလ total sale (Metric အလိုက်)
                </div>
                <div>
                    <span class="font-semibold">Focus A</span> / <span class="font-semibold">Focus B</span> = Focus qty (per-day) × Range days ကို Metric အလိုက်တွက်ပြထားတာပါ
                </div>
                <div>
                    <span class="font-semibold">A vs Focus</span> = Range A − Focus A
                </div>
                <div>
                    <span class="font-semibold">Δ</span> = Range A − Range B ၊ <span class="font-semibold">Situation</span> = Up/Down/Flat + %
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-x-3">
                <x-button flat label="Close" x-on:click="close" />
            </div>
        </x-slot>
    </x-modal.card>

    {{-- Sticky Table  --}}
    <div
        class="relative mx-auto my-6 overflow-auto max-h-[75vh] rounded-lg border border-gray-200 dark:border-gray-700">
        {{-- <div class="my-3 font-bold text-blue-500">Branch အလိုက် Signature Product များထားရှိခြင်းပြ ဇယား</div> --}}

        <div>
            {{-- Shape filter --}}
            <div class="pb-4 m-4 bg-white dark:bg-gray-800">
                <label for="table-search" class="sr-only">Search</label>
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 flex items-center pointer-events-none rtl:inset-r-0 start-0 ps-3">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live="shape_detail" id="table-search"
                        class="block w-full pt-2 text-sm text-gray-900 border border-gray-300 rounded-lg sm:w-80 ps-10 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Search for items">
                </div>
            </div>
            <table id="pageproducts"
                class="min-w-full text-sm text-left text-gray-700 table-auto dark:text-gray-200 rtl:text-right">
                <thead
                    class="sticky top-0 z-10 text-xs text-gray-700 uppercase bg-gray-50/80 backdrop-blur supports-backdrop-blur:backdrop-blur-sm dark:bg-gray-800/80 dark:text-gray-300">
                    <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                        <th scope="col" class="w-40 px-4 py-3 font-semibold md:px-6">
                            <span class="sr-only">Image</span>
                        </th>
                        <th scope="col" class="px-4 py-3 font-semibold md:px-6">
                            Product
                        </th>
                        <th scope="col" class="px-4 py-3 font-semibold md:px-6">
                            Weight/g
                        </th>
                        <th scope="col" class="px-4 py-3 font-semibold md:px-6">
                            Size
                        </th>
                        @foreach ($branches as $branch)
                            <th scope="col" class="px-4 py-3 font-semibold md:px-6">
                                {{ ucfirst($branch->name) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr
                            class="bg-white border-b odd:bg-gray-50 dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60">
                            <td class="w-40 p-4">
                                <img wire:click='initializeProductId({{ $product->id }})'
                                    class="w-32 max-w-full max-h-full rounded-md md:w-32 cursor-help"
                                    src="{{ asset('storage/' . $product->image) }}" alt="product image"
                                    @click="$openModal('productSummaryModal')" />
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-900 md:px-6 dark:text-white">
                                {{ $product->shape }}
                            </td>
                            <td class="px-4 py-3 md:px-6">
                                <span>{{ $product->weight }}</span>
                            </td>

                            <td class="px-4 py-3 font-semibold text-gray-900 md:px-6 dark:text-white">
                                <div class="flex items-center">
                                    {{ $product->length }} {{ $product->uom }}
                                </div>
                            </td>

                            @foreach ($branches as $branch)
                                <td class="px-4 py-3 font-semibold text-center text-gray-900 md:px-6 dark:text-white">
                                    @if ($product->{'index' . $branch->id} > 0)
                                        <a href="#"
                                            class="flex flex-col items-center content-center gap-1 px-2 py-1 hover:rounded hover:bg-gray-100 dark:hover:bg-gray-700/60"
                                            wire:click='propsToLink({{ $product->id }},{{ $branch->id }})'>
                                            @if ($product->{'status' . $branch->id})
                                                <div class="w-6 h-6 rounded-full"
                                                    style="background: {{ $product->{'color' . $branch->id} }}">
                                                </div>
                                                <span
                                                    class="text-xs rounded ">{{ $product->{'status' . $branch->id} }}
                                                </span>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-400"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                                </svg>
                                            @endif

                                        </a>
                                    @else
                                        <button
                                            wire:click="setBranchPsiProduct({{ $product->id }},{{ $branch->id }})"
                                            @click="$openModal('psiProduct')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                                {{-- @dd($product->{'index' . $branch->id}) --}}
                            @endforeach
                        </tr>
                    @endforeach

                </tbody>
            </table>
            {{-- <div class="p-4">{{ $products->onEachSide(1)->links(data: ['scrollTo' => '#pageproducts']) }}</div> --}}
            {{-- <div class="flex-1 overflow-x-auto overflow-y-auto">
                <table class="w-full table-fixed">
                </table>
            </div> --}}
        </div>
    </div>



    <x-modal.card title="{{ $productSummary['detail'] }}" wire:model='productSummaryModal'>
        {{-- <div class="flex gap-2 text-blue-400 text-md flex-warp opacity-80">
            @foreach ($tags as $data)
                <div class="flex gap-1 cursor-pointer group">#<span
                        class="group-hover:text-blue-700">{{ $data->hashtag->name }}</span> <x-icon name="x"
                        class="hidden w-4 h-4 mt-1 text-red-400 border-gray-300 rounded group-hover:block hover:border hover:text-red-800" />
                </div>
            @endforeach
        </div> --}}
        <x-button  red icon="pencil" label="Edit Product" href="{{ route('edit_product', ['selected' => $productIdFilter]) }}" />
        <div class="my-2 text-xl text-teal-500">{{ $productSummary['remark'] ?? '-' }}</div>
        @can('isAGM')
            <x-input class="w-1/2 my-2" wire:model='remark' wire:keydown.enter='updateRemark'
                placeholder="update product remark" />
        @endcan
        <div class="container mx-auto my-2 overflow-x-auto">
            <div style="display: none" class="grid grid-cols-2 gap-2 my-2 bg-white dark:bg-gray-900">
                <x-select wire:model.live="hashtag_id" placeholder="#hash-tag" :async-data="route('hashtag')" option-label="name"
                    option-value="id">
                    <x-slot name="afterOptions" class="flex justify-center p-2" x-show="displayOptions.length === 0">
                        <x-button x-on:click='close' wire:click='createTag' primary flat full>
                            <span x-html="`<b>${search}</b> ကို အသစ်ဖန်တီးမယ်`"></span>
                        </x-button>
                    </x-slot>
                </x-select>
                <div>
                    <x-button id="save" icon="save" wire:click='addTagToProduct' />
                </div>
            </div>

            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400 overflow-x-auto">
                <thead>
                    <tr class="p-1 border border-gray-300">
                        <th scope="col" class="px-6 py-3">
                            Branch
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Focus
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Avg Sale (qty)
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Balance (qty)
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Stock Duration (Days)
                        </th>
                        <th scope="col" class="px-6 py-3">
                            To Order Due Date
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productSummary['branches'] as $data)
                        <tr
                            class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-3 uppercase" title="Branch location">
                                {{ $data['branch_name'] }}
                            </td>
                            <td class="px-6 py-4" title="Latest focus quantity">{{ $data['latest_focus_qty'] ?? 0 }}</td>
                            <td class="px-6 py-4" title="Average daily sales">{{ number_format($data['avg_sales']) }}</td>
                            <td class="px-6 py-4" title="Current inventory balance">{{ number_format($data['balance']) }}</td>
                            <td class="px-6 py-4 {{ $data['remaining_days'] !== null && $data['remaining_days'] < 7 ? 'text-red-500 font-bold' : '' }}" title="Days until stock depletion">
                                {!! $data['remaining_days'] !== null ? $data['remaining_days'] . ' <small>days</small>' : 'N/A' !!}
                            </td>
                            <td class="px-6 py-4" title="Next reorder due date">
                                {{ \Carbon\Carbon::parse($data['due_date'])->format('(D) d-M-Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No branch data available for this product.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <article
                class="relative flex flex-col justify-end px-8 pt-4 pb-8 mx-auto mt-4 overflow-hidden h-96 hover:cursor-pointer isolate rounded-2xl">
                <img class="absolute inset-0 object-cover w-full h-full max-w-xl rounded-lg"
                    src="{{ asset('storage/' . $productSummary['image']) }}" alt="product photo" />
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40">
                </div>
                <h3 class="z-10 mt-3 text-xl font-bold text-white lg:text-3xl md:text-3xl">
                    {{ $productSummary['weight'] }} <i class="hidden md:inline">g</i></h3>
                <div class="z-10 overflow-hidden text-sm leading-6 text-gray-300 gap-y-1">
                    {{ $productSummary['detail'] }}
                </div>
            </article>
        </div>
    </x-modal.card>



    <x-modal.card title="Add this Product to PSI" blur wire:model="psiProduct">
        <x-input label="Remark" wire:model.live='remark' placeholder="Here remark please" />
        <x-slot name="footer">
            <div class="flex justify-between gap-x-4">
                <x-button flat negative label="Delete" wire:click="cancle" />

                <div class="flex">
                    <x-button flat label="Cancel" wire:click='cancle' x-on:click="close" />
                    <x-button primary label="Save" wire:click="createBranchPsiProduct" />
                </div>
            </div>
        </x-slot>
    </x-modal.card>




    {{-- Modal trigger form js  --}}
    <x-modal wire:model="defaultModal" blur>
        <x-card title="Choose a function">

            <ol class="">
                <li class="hover:text-gray-500 hover:underline">
                    <a href="{{ route('focus', ['brch' => $branchId, 'prod' => $productId]) }}" wire:navigate>Product
                        Focus</a>
                </li>
                <li class="hover:text-gray-500 hover:underline">
                    <a href="{{ route('focus', ['brch' => $branchId, 'prod' => $productId]) }}" wire:navigate>Daily
                        Sale</a>
                </li>
                <li>
                    <a class="hover:text-gray-500 hover:underline"
                        href="{{ route('price', ['prod' => $productId, 'bch' => $branchId]) }}" class="flex"
                        wire:navigate><span>Order</span>
                    </a>
                    @if ($orderCount)
                        {{-- <x-icon name="arrow-narrow-right" class="w-5 h-5" solid /> --}}
                        <x-button flat blue right-icon="arrow-narrow-right" @click="$openModal('orderModal')"
                            class="underline">
                            Order found
                            {{ $orderCount }}</x-button>
                    @endif
                </li>
                {{-- <li>
                    <a href="{{ route('oos', ['bch' => $branchId, 'prod' => $productId]) }}" wire:navigate>OOS
                        Analysis</a>
                </li> --}}
            </ol>

            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                </div>
            </x-slot>
        </x-card>
    </x-modal>

    {{-- ORDER HISTORY MODAL --}}
    <x-modal.card title="Add this Product to PSI" blur wire:model="orderModal">
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Supplier name
                    </th>
                    <th scope="col" class="px-6 py-3">
                        အရေအတွက်
                    </th>
                    <th scope="col" class="px-6 py-3">မှာယူခဲ့သော ရက်စွဲ</th>
                    <th scope="col" class="px-6 py-4">
                        အခြေအနေ
                    </th>

                    <th scope="col" class="px-6 py-3">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($psiOrders as $data)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $data->psiPrice->supplier->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $data->order_qty }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->created_at }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->psiStatus->name }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('order_detail', ['ord' => $data->id, 'brch' => $branchId, 'prod' => $productId]) }}"
                                class="text-blue-500" wire:navigate>
                                Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">

                            <center>There's no records yet</center>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-modal.card>

    {{-- <x-modal.card title="Product Summary" wire:model='porductSummaryModal'>
        <table>
            <thead>
                <tr class="p-1 border border-gray-300">
                    <th scope="col" class="px-6 py-3">
                        Branch
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Focus
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Avg Sale
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Balance
                    </th>
                    <th scope="col" class="px-6 py-3">
                        To Order Due Date
                    </th>
                </tr>
            </thead>
            <tbody class="p-1 border border-gray-400">

                @foreach ($productSummary as $data)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">

                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $data['branch_name'] }}</th>
                        <td class="px-6 py-4">{{ $data['latest_focus_qty'] }}</td>
                        <td class="px-6 py-4">{{ (int) $data['avg_sales'] }}</td>
                        <td class="px-6 py-4">{{ $data['balance'] }}</td>
                        <td class="px-6 py-4">
                            {{ \Carbon\Carbon::parse($data['due_date'])->format('(D) d-M-Y') }}
                        </td>

                    </tr>
            </tbody>
        </table>
        @endforeach --}}

    {{-- </x-modal.card> --}}
</div>
@section('script')
    <script>
        const saleData = JSON.parse('{!! addslashes($sales) !!}');

        const data = Object.entries(saleData).map(([branch, total]) => ({
            x: branch,
            y: parseFloat(total)
        }));

        console.log(data);

        const options = {
            colors: ["#1A56DB", "#FDBA8C"],
            series: [{
                    name: "Sales",
                    color: "#1A56DB",
                    data: data,
                },

            ],
            chart: {
                type: "bar",
                height: "320px",
                fontFamily: "Inter, sans-serif",
                toolbar: {
                    show: false,
                },
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: "70%",
                    borderRadiusApplication: "end",
                    borderRadius: 8,
                },
            },
            tooltip: {
                shared: true,
                intersect: false,
                style: {
                    fontFamily: "Inter, sans-serif",
                },
            },
            states: {
                hover: {
                    filter: {
                        type: "darken",
                        value: 1,
                    },
                },
            },
            stroke: {
                show: true,
                width: 0,
                colors: ["transparent"],
            },
            grid: {
                show: false,
                strokeDashArray: 4,
                padding: {
                    left: 2,
                    right: 2,
                    top: -14
                },
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                show: false,
            },
            xaxis: {
                floating: false,
                labels: {
                    show: true,
                    style: {
                        fontFamily: "Inter, sans-serif",
                        cssClass: 'text-xs font-normal fill-gray-500 dark:fill-gray-400'
                    }
                },
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                },
            },
            yaxis: {
                show: false,
            },
            fill: {
                opacity: 1,
            },
        }

        if (document.getElementById("column-chart") && typeof ApexCharts !== 'undefined') {
            const chart = new ApexCharts(document.getElementById("column-chart"), options);
            chart.render();
        }
    </script>
@endsection
