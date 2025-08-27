<div x-data="{ open: true, summary: true }" class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
        <x-button pink label="Add Report" @click="$openModal('addReportModal')" />
        <x-button teal label="Export" @click="$openModal('exportModal')" />
        @can('isAGM')
            <x-dropdown align='left'>
                <x-slot name="trigger">
                    <x-button icon="cog" label="Configure" sky />
                </x-slot>
                <x-dropdown.header label="Actions">
                    <x-dropdown.item @click="$openModal('addReportTypeModal')">New Report Type
                    </x-dropdown.item>
                </x-dropdown.header>
            </x-dropdown>
        @endcan
    </div>
    <div class="mt-2">
        <div class="flex flex-wrap items-center gap-3">
            <label for="date" class="text-sm font-medium text-slate-700 dark:text-slate-300">Report Date</label>
            <input type="date" id="date" wire:model.live='report_date'
                class="w-44 rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
            <span class="text-sm text-slate-500 dark:text-slate-400">Report Date ရွေးချယ်ပါ</span>
        </div>

        <label class="inline-flex items-center mt-3 cursor-pointer select-none">
            <input x-model="open" type="checkbox" value="" class="sr-only peer">
            <div
                class="relative h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-blue-600 dark:bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 after:absolute after:top-[2px] after:start-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full">
            </div>
            <span class="ms-3 text-sm font-medium text-slate-900 dark:text-slate-300">နေ့စဉ် ဆိုင်ခွဲများ report</span>
        </label>
    </div>

    <div class="max-w-xl">
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-200/40 dark:border-white/10 bg-white/40 dark:bg-slate-800/30 backdrop-blur-md shadow-lg">
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Daily Summary</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ \Carbon\Carbon::parse($daily_spirit_metrics['date'])->format('M j, Y') }} vs
                        {{ \Carbon\Carbon::parse($daily_spirit_metrics['prev_date'])->format('M j, Y') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 px-5 pb-5">
                @php($sm = $daily_spirit_metrics['sale'] ?? null)
                <div
                    class="rounded-xl p-4 bg-white/40 dark:bg-slate-900/30 border border-slate-200/40 dark:border-white/10">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Sale (g)</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                            {{ number_format($sm['today'] ?? ($daily_spirit[0]->total_sale ?? 0), 2) }}</div>
                        @if ($sm)
                            @if ($sm['dir'] > 0)
                                <span
                                    class="inline-flex items-center text-emerald-700 bg-emerald-100/80 dark:text-emerald-300 dark:bg-emerald-900/40 rounded-md px-2 py-0.5 text-xs font-medium">
                                    <x-icon name="trending-up" class="w-4 h-4 mr-1" />
                                    {{ number_format($sm['delta_pct'], 1) }}%
                                </span>
                            @elseif ($sm['dir'] < 0)
                                <span
                                    class="inline-flex items-center text-rose-700 bg-rose-100/80 dark:text-rose-300 dark:bg-rose-900/40 rounded-md px-2 py-0.5 text-xs font-medium">
                                    <x-icon name="trending-down" class="w-4 h-4 mr-1" />
                                    {{ number_format($sm['delta_pct'], 1) }}%
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center text-slate-600 bg-slate-100/80 dark:text-slate-300 dark:bg-slate-900/50 rounded-md px-2 py-0.5 text-xs font-medium">
                                    <x-icon name="minus" class="w-4 h-4 mr-1" />
                                    0%
                                </span>
                            @endif
                        @endif
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Prev:
                        {{ number_format($sm['prev'] ?? 0, 2) }}</div>
                </div>

                @php($rm = $daily_spirit_metrics['repurchase'] ?? null)
                <div
                    class="rounded-xl p-4 bg-white/40 dark:bg-slate-900/30 border border-slate-200/40 dark:border-white/10">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Repurchase (g)</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ number_format($rm['today'] ?? ($daily_spirit[0]->total_repurchase ?? 0), 2) }}</div>
                        @if ($rm)
                            @if ($rm['dir'] > 0)
                                <span
                                    class="inline-flex items-center text-emerald-700 bg-emerald-100/80 dark:text-emerald-300 dark:bg-emerald-900/40 rounded-md px-2 py-0.5 text-xs font-medium">
                                    <x-icon name="trending-up" class="w-4 h-4 mr-1" />
                                    {{ number_format($rm['delta_pct'], 1) }}%
                                </span>
                            @elseif ($rm['dir'] < 0)
                                <span
                                    class="inline-flex items-center text-rose-700 bg-rose-100/80 dark:text-rose-300 dark:bg-rose-900/40 rounded-md px-2 py-0.5 text-xs font-medium">
                                    <x-icon name="trending-down" class="w-4 h-4 mr-1" />
                                    {{ number_format($rm['delta_pct'], 1) }}%
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center text-slate-600 bg-slate-100/80 dark:text-slate-300 dark:bg-slate-900/50 rounded-md px-2 py-0.5 text-xs font-medium">
                                    <x-icon name="minus" class="w-4 h-4 mr-1" />
                                    0%
                                </span>
                            @endif
                        @endif
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Prev:
                        {{ number_format($rm['prev'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-3 my-4 grid-cols-1 lg:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-2" x-show="open" x-transition>

        {{-- All branch specific report --}}
        @forelse ($daily_branch_reports as $report)
            <!-- Main Card Container -->
            <div
                class="w-full h-full rounded-lg border border-slate-200 bg-white p-4 text-slate-800 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 space-y-4">

                <!-- Card Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $report['key'] }}</h2>
                    </div>
                </div>

                <!-- Key Metrics Section -->
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Key Metrics</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Sales KPI -->
                        <div
                            class="flex flex-col justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-900/40 overflow-hidden w-full">
                            <div class="flex items-center text-slate-500 dark:text-slate-400 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                                </svg>
                                <span class="text-sm truncate">Total Sales (g)</span>
                            </div>
                            <div class="mt-1.5 flex items-baseline gap-2 flex-wrap min-w-0">
                                <p
                                    class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 shrink-0 leading-none">
                                    {{ $report['__metrics']['sales_gram']['today'] ?? $report['ရွှေ (weight / g)'] + $report['Pandora (weihgt / g)'] + $report['18K (weihgt / g)'] }}
                                </p>
                                @php($m = $report['__metrics']['sales_gram'] ?? null)
                                @if ($m)
                                    @if ($m['dir'] > 0)
                                        <span
                                            class="inline-flex items-center text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30 rounded-md px-2 py-0.5 text-xs font-medium shrink-0 whitespace-nowrap">
                                            <x-icon name="trending-up" class="w-4 h-4 mr-1" />
                                            {{ number_format($m['delta_pct'], 1) }}%
                                        </span>
                                    @elseif ($m['dir'] < 0)
                                        <span
                                            class="inline-flex items-center text-rose-700 bg-rose-100 dark:text-rose-300 dark:bg-rose-900/30 rounded-md px-2 py-0.5 text-xs font-medium shrink-0 whitespace-nowrap">
                                            <x-icon name="trending-down" class="w-4 h-4 mr-1" />
                                            {{ number_format($m['delta_pct'], 1) }}%
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center text-slate-600 bg-slate-100 dark:text-slate-300 dark:bg-slate-900/40 rounded-md px-2 py-0.5 text-xs font-medium shrink-0 whitespace-nowrap">
                                            <x-icon name="minus" class="w-4 h-4 mr-1" />
                                            0%
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <!-- Repurchase KPI -->
                        <div
                            class="flex flex-col justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-900/40 overflow-hidden w-full">
                            <div class="flex items-center text-slate-500 dark:text-slate-400 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.899 2.186l-2.387-.597a4.002 4.002 0 00-7.652-1.282V4a1 1 0 01-2 0v1.5a1 1 0 01-1 1H2a1 1 0 01-1-1V3a1 1 0 011-1zm14 4.899A7.002 7.002 0 012.101 15.1l2.387.597a4.002 4.002 0 007.652 1.282V18a1 1 0 112 0v-1.5a1 1 0 011-1h1.5a1 1 0 011 1V19a1 1 0 11-2 0v-2.101z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm truncate">Repurchase (g)</span>
                            </div>
                            <div class="mt-1.5 flex items-baseline gap-2 flex-wrap min-w-0">
                                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 shrink-0 leading-none">
                                    {{ $report['__metrics']['repurchase_gram']['today'] ?? $report['Repurchase (weight / g )'] }}
                                </p>
                                @php($r = $report['__metrics']['repurchase_gram'] ?? null)
                                @if ($r)
                                    @if ($r['dir'] > 0)
                                        <span
                                            class="inline-flex items-center text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30 rounded-md px-2 py-0.5 text-xs font-medium shrink-0 whitespace-nowrap">
                                            <x-icon name="trending-up" class="w-4 h-4 mr-1" />
                                            {{ number_format($r['delta_pct'], 1) }}%
                                        </span>
                                    @elseif ($r['dir'] < 0)
                                        <span
                                            class="inline-flex items-center text-rose-700 bg-rose-100 dark:text-rose-300 dark:bg-rose-900/30 rounded-md px-2 py-0.5 text-xs font-medium shrink-0 whitespace-nowrap">
                                            <x-icon name="trending-down" class="w-4 h-4 mr-1" />
                                            {{ number_format($r['delta_pct'], 1) }}%
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center text-slate-600 bg-slate-100 dark:text-slate-300 dark:bg-slate-900/40 rounded-md px-2 py-0.5 text-xs font-medium shrink-0 whitespace-nowrap">
                                            <x-icon name="minus" class="w-4 h-4 mr-1" />
                                            0%
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <!-- Customer KPI -->
                        <div class="flex flex-col justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-900/40">
                            <div class="flex items-center text-slate-500 dark:text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm-1.559 5.56a.75.75 0 01.03-1.06 4.5 4.5 0 00-5.962 0 .75.75 0 01-1.09-1.03 6 6 0 018.142 0 .75.75 0 01-1.09 1.03.75.75 0 01-1.121.03zM16.25 11.75a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z" />
                                </svg>
                                <span class="text-sm">Customers In</span>
                            </div>
                            <p class="mt-1.5 text-2xl font-bold text-sky-600 dark:text-sky-400">
                                {{ $report['Customer အဝင် ဦးရေ'] +
                                    $report['Customer (viber) အဝင်ဦးရေ'] +
                                    $report['Customer (Telegram) အဝင်ဦးရေ'] +
                                    $report['Customer (tik tok) အဝင်ဦးရေ'] +
                                    $report['Customer (messenger)အဝင်ဦးရေ'] +
                                    $report['Customer လူဝင်ဦးရေ (Pawn)'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Item Breakdown Section -->
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Item Breakdown
                    </h3>
                    <div class="space-y-2 text-sm">
                        <!-- Header Row -->
                        <div
                            class="grid grid-cols-2 gap-3 px-2 font-semibold text-slate-600 dark:text-slate-400 text-xs">
                            <span>Item Type</span>
                            <span class="text-center">Sales (pcs / g)</span>
                            {{-- <span class="text-center">Repurchase (pcs / g / vr)</span> --}}
                        </div>
                        <!-- Data Rows -->
                        <div
                            class="grid items-center grid-cols-2 gap-3 p-2 rounded-md bg-slate-50 dark:bg-slate-900/40 text-sm">
                            <span class="font-medium">ရွှေ</span>
                            <span class="text-center">{{ $report['ရွှေ (pcs)'] }} /
                                {{ $report['ရွှေ (weight / g)'] }}</span>
                            {{-- <span class="text-center text-slate-300">{{ $report['Repurchase ပစ္စည်းအခုရေ'] }} / --}}
                            {{-- {{ $report['Repurchase (weight / g )'] }} / {{ $report['Repurchase အစောင်ရေ'] }}</span> --}}
                        </div>
                        <div
                            class="grid items-center grid-cols-2 gap-3 p-2 rounded-md bg-slate-50 dark:bg-slate-900/40 text-sm">
                            <span class="font-medium">18K</span>
                            <span class="text-center">{{ $report['18K (pcs)'] }} /
                                {{ $report['18K (weihgt / g)'] }}</span>
                            {{-- <span class="text-center text-slate-300">8 / 4.33</span> --}}
                        </div>
                        <div
                            class="grid items-center grid-cols-2 gap-3 p-2 rounded-md bg-slate-50 dark:bg-slate-900/40 text-sm">
                            <span class="font-medium">Pandora</span>
                            <span class="text-center">{{ $report['Pandora (pcs)'] }} /
                                {{ $report['Pandora (weihgt / g)'] }}</span>
                            {{-- <span class="text-center text-slate-300">50 / 34.51</span> --}}
                        </div>
                    </div>
                </div>

                <!-- Pawn Activity Section -->
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Pawn Activity
                    </h3>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-900/40">
                            <p class="text-xs text-slate-500 dark:text-slate-400">New (အသစ်ဝင်)</p>
                            <p class="mt-1 text-xl font-bold">{{ $report['အစောင်ရေ အသစ် (Pawn)'] }}</p>
                        </div>
                        <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-900/40">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Redeem (အတိုးဆပ်)</p>
                            <p class="mt-1 text-xl font-bold">
                                {{ $report['အတိုးသွင်း/လက်မှတ်လဲအစောင်ရေ (Pawn)'] }}</p>
                        </div>
                        <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-900/40">
                            <p class="text-xs text-slate-500 dark:text-slate-400">အရွေးအစောင်ရေ</p>
                            <p class="mt-1 text-xl font-bold">{{ $report['အရွေးအစောင်ရေ (Pawn)'] }}</p>
                        </div>
                    </div>
                </div>

            </div>
        @empty
        @endforelse

    </div>
    <hr />

    <div class="mt-4">
        <div class="flex flex-wrap items-center gap-3 mx-auto mb-4 max-w-xl">
            <x-datetime-picker wire:model.live.debounce="start_date_summary" without-time='true' label="Start"
                placeholder="Now" />
            <x-datetime-picker wire:model.live.debounce="end_date_summary" without-time='true' label="End"
                placeholder="Now" />
        </div>

        <label class="inline-flex items-center mt-2 cursor-pointer select-none">
            <input x-model="summary" type="checkbox" value="" class="sr-only peer">
            <div
                class="relative h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-blue-600 dark:bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 after:absolute after:top-[2px] after:start-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full">
            </div>
            <span class="ms-3 text-sm font-medium text-slate-900 dark:text-slate-300">Reports အမျိုးအစားအလိုက်
                အကျဉ်းချုပ်</span>
        </label>

    </div>


    {{-- Summarize table --}}
    <div class="flex flex-wrap gap-4" x-show="summary" x-transition>
        <div>
            <x-card title="Daily Summary" class="border border-slate-200 dark:border-slate-700">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-slate-600 dark:text-slate-300">
                        <thead
                            class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50 text-slate-700 dark:text-slate-300">
                            <tr>
                                <th scope="col" class="px-6 py-3">Sale</th>
                                <th scope="col" class="px-6 py-3">Repurchase</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach ($impSummaryTotalGram as $data)
                                <tr
                                    class="odd:bg-white even:bg-slate-50 dark:odd:bg-slate-800 dark:even:bg-slate-900/40">
                                    <td class="px-6 py-3 font-medium text-slate-900 dark:text-slate-100">
                                        {{ $data->total_sale ?? 0 }}</td>
                                    <td class="px-6 py-3 font-medium text-slate-900 dark:text-slate-100">
                                        {{ $data->total_repurchase ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        @foreach ($impSummaryData as $type => $data)
            <div class="rounded-lg">
                <x-card title="{{ $type }}" class="border border-slate-200 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-slate-600 dark:text-slate-300 rtl:text-right">
                            <thead
                                class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50 text-slate-700 dark:text-slate-300">
                                <tr>
                                    <th scope="col" class="px-6 py-3">
                                        Branch
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Quantity
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                @foreach ($data as $item)
                                    <tr
                                        class="odd:bg-white even:bg-slate-50 dark:odd:bg-slate-800 dark:even:bg-slate-900/40">
                                        <th scope="row"
                                            class="px-6 py-3 font-medium text-slate-900 whitespace-nowrap dark:text-slate-100">
                                            {{ $item['name'] }}
                                        </th>
                                        <td class="px-6 py-3">
                                            {{ $item['total'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        @endforeach
    </div>

    {{-- Charts removed for now --}}

    {{-- Charts removed for now --}}

    {{-- Charts removed for now --}}

    {{-- Create a Report --}}
    <x-modal.card title="New Report" wire:model='addReportModal'>
        <div>
            <input
                class="rounded-lg border border-slate-300 bg-white text-slate-900 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-600"
                type="date" wire:model.live='report_date' />
            @can('isAGM')
                <select wire:model.live='branch_id'
                    class="rounded-lg border border-slate-300 bg-white text-slate-900 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-600">
                    <option value="" selected disabled>Select</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}"> {{ ucfirst($branch->name) }}</option>
                    @endforeach
                </select>
            @endcan
            <button wire:click='crateNewRecord'
                class="px-4 py-2 mt-4 text-white bg-gray-900 rounded-lg hover:bg-gray-950 hover:shadow-lg"><x-icon
                    name="check" solid class="inline w-4 h-4 mr-2" />{{ __('GENERATE') }}</button>

            <hr />
            @if ($entry_modal !== null)
                <div class="flex gap-6 p-2 rounded shadow-sm">
                    <x-button.circle teal label="S" wire:click="scopeChange('S')" />
                    <x-button.circle amber label="O" wire:click="scopeChange('O')" />
                    <x-button.circle sky label="P" wire:click="scopeChange('P')" />
                </div>
                <table>
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Number</th>
                            <th class="px-4 py-2 sr-only">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daily_entries as $entry)
                            <tr>
                                <td class="px-4 py-2">{{ $entry->dailyReport->name }}</td>
                                <td class="px-4 py-2">{{ $entry->number }}</td>

                                @if ($edit_id == $entry->id)
                                    <td class="flex gap-2 px-4 py-2">
                                        <x-input type='number' step=0.01 wire:model.live='update_number'
                                            placeholder="number" wire:keydown.enter="update({{ $entry->id }})" />
                                        <div class="hidden lg:block xl:block">
                                            <kbd
                                                class="px-2 py-1.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">Enter</kbd>
                                        </div>
                                        <a href="#" class="text-blue-300 underline"
                                            wire:click="update({{ $entry->id }})">{{ __('Update') }}</a>
                                    </td>
                                @else
                                    <td class="px-4 py-2">
                                        <a href="#"
                                            wire:click='edit({{ $entry->id }})'>{{ __('Edit') }}</a>
                                        @can('isAGM')
                                            <a href="#" class="text-red-500 hover:underline hover:text-red-700"
                                                wire:click='delete({{ $entry->id }})'>{{ __('delete') }}</a>
                                        @endcan
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </x-modal.card>

    {{-- Create a report type --}}
    <x-modal.card title="New Report Type" wire:model='addReportTypeModal'>
        <form wire:submit="createReportType">
            <x-input label="Name" wire:model='name' />
            <x-input label="Description" wire:model='description' />
            <hr />
            <button class="px-4 py-2 mt-4 text-white bg-gray-900 rounded-lg hover:bg-gray-950 hover:shadow-lg"><x-icon
                    name="check" solid class="inline w-4 h-4 mr-2" />{{ __('SAVE') }}</button>
        </form>
        <table class="w-full text-sm text-left text-slate-600 rtl:text-right dark:text-slate-300">
            <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50 text-slate-700 dark:text-slate-300">
                <tr>
                    <th scope="col" class="px-4 py-2">Name</th>
                    <th scope="col" class="px-4 py-2">Desc</th>
                    <th scope="col" class="px-4 py-2 sr-only">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($types as $type)
                    <tr>
                        <td class="px-4 py-2 text-slate-900 dark:text-slate-100">{{ $type->name }}</td>
                        <td class="px-4 py-2 text-slate-900 dark:text-slate-100">{{ $type->description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-modal.card>

    {{-- Export data --}}
    <x-modal.card title="Export data" wire:model='exportModal'>
        <select wire:model='export_branch_id'
            class="ml-4 rounded-lg border border-slate-300 bg-white text-slate-900 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-100">
            <option value="" selected>All Branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}"> {{ ucfirst($branch->name) }}</option>
            @endforeach
        </select>
        <div class="grid grid-cols-1 gap-4 p-4 mb-4 rounded sm:grid-cols-2">
            <x-datetime-picker label="Start Date" placeholder="Start Date" parse-format="YYYY-MM-DD HH:mm"
                wire:model="start_date" without-time=true />
            <x-datetime-picker label="End Date" placeholder="End Date" parse-format="YYYY-MM-DD HH:mm"
                wire:model="end_date" without-time=true />
        </div>
        <x-button green right-icon="download" class="m-2" wire:click='export'>Exprot</x-button>
    </x-modal.card>
</div>

@section('script')
    <script>
        Livewire.on('closeModal', (name) => {
            $closeModal(name);
        });

        Livewire.on('openModal', (name) => {
            $openModal(name);
        });
    </script>
@endsection
