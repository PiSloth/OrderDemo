<div>
    <div class="my-4 shadow-xl">
        <div class="w-1/2 mx-auto mb-4">
            <x-datetime-picker wire:model.live.debounce="report_types_date_filter" without-time='true' label="Date"
                placeholder="Now" />
        </div>

        <div>Report at - {{ \Carbon\Carbon::parse($report_types_date_filter)->format('M, Y') }}</div>
        @if ($monthlyAllReportTypes)
            <table class="w-full mt-2 text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-2 py-1">Type Name</th>
                        @foreach (array_keys($monthlyAllReportTypes['ရွှေ (weight / g)']) as $branchName)
                            <th scope="col" class="px-2 py-1">{{ $branchName }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monthlyAllReportTypes as $typeName => $branchData)
                        <tr class="odd:bg-white even:bg-gray-100">
                            <td class=" md:px-4 md:py-2">{{ $typeName }}</td>
                            @foreach ($branchData as $values)
                                <td class=" md:px-4 md:py-2">{{ $values[0] ?? 0 }}</td> {{-- Display value or 0 if empty --}}
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-2 mt-4 text-red-300 rounded-full bg-gray-50">No data found yet</div>
        @endif
    </div>

    {{-- specific Rport type --}}
    <div class="my-4 shadow-xl">
        <div class="flex w-1/2 gap-2 mx-auto mb-4">
            <div>
                <x-datetime-picker wire:model.live.debounce="specific_date_filter" without-time='true' label="Date"
                    placeholder="Now" />
            </div>
            <div class="flex flex-col">
                <label for="specific_branch">Branch</label>
                <select id="specific_branch_id" wire:model.live='specific_branch_id'
                    class="bg-gray-100 border rounded-lg border-gray-50">
                    <option value="" selected>All Branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}"> {{ ucfirst($branch->name) }}</option>
                    @endforeach
                </select>
            </div>
            <span class="mt-8 text-blue-600 cursor-pointer hover:underline hover:text-red-900"
                wire:click='specificDateFilterOfReportType'>Generate</span>
        </div>


        {{-- @dd($dailyAllReportTypes) --}}
        <table class="w-full mt-2 text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-2 py-1">Type Name</th>
                    @foreach (array_keys($dailyAllReportTypes['ရွှေ (weight / g)']) as $branchName)
                        <th class="cursor-pointer hover:text-red-500"
                            wire:click='removeKeyFromSelectedArray("{{ $branchName }}")' scope="col"
                            class="px-2 py-1">{{ str_replace('Branch', 'B', $branchName) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($dailyAllReportTypes as $typeName => $branchData)
                    <tr class="odd:bg-white even:bg-gray-100">
                        <td class=" md:px-4 md:py-2">{{ $typeName }}</td>
                        @foreach ($branchData as $values)
                            <td class=" md:px-4 md:py-2">{{ $values[0] ?? 0 }}</td> {{-- Display value or 0 if empty --}}
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{-- Popular Sale --}}
    <div class="my-4 shadow-lg">
        <div class="w-1/2 mx-auto mb-4">
            <x-datetime-picker wire:model.live.debounce="popular_date_filter" without-time='true' label="Date"
                placeholder="Now" />
        </div>
        <div class="mb-4">
            <span> Report at - {{ \Carbon\Carbon::parse($popular_date_filter)->format('M, Y') }}</span>
            <select wire:model.live='branch_id' class="bg-gray-100 border rounded-lg border-gray-50">
                <option value="" selected>All Branch</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}"> {{ ucfirst($branch->name) }}</option>
                @endforeach
            </select>
            <select wire:model.live='ac' class="bg-gray-100 border rounded-lg border-gray-50">
                <option value="desc" selected>အမြင့်ဆုံး</option>
                <option value="asc">အနိမ့်ဆုံး</option>
            </select>
            <select wire:model.live='limit' class="bg-gray-100 border rounded-lg border-gray-50">
                <option value="5" selected>5</option>
                <option value="7">7</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
            </select>
        </div>

        <table class="w-full border border-collapse border-gray-300 table-auto">
            <thead>
                <tr class="bg-gray-200">
                    <th class="px-4 py-2 border border-gray-300">Rank</th>
                    <th class="px-4 py-2 border border-gray-300">Shape</th>
                    <th class="px-4 py-2 border border-gray-300">Sales</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sales as $index => $item)
                    <tr class="text-center {{ $index % 2 == 0 ? 'bg-gray-100' : '' }}">
                        <td class="px-4 py-2 font-bold border border-gray-300">
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
                        <td class="px-4 py-2 border border-gray-300">{{ $item->shape }}
                            (<i class="text-slate-500"> {{ $item->weight }}
                                {{ $item->length }}{{ $item->uom }}</i>)
                        </td>
                        <td class="px-4 py-2 font-semibold border border-gray-300">{{ number_format($item->sale) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- index --}}
    <div class="my-4 shadow-xl">
        <div class="w-1/2 mx-auto mb-4">
            <x-datetime-picker wire:model.live.debounce="index_date_filter" without-time='true' label="Date"
                placeholder="Now" />
        </div>
        <div class="mb-4">
            <span> Report at - {{ \Carbon\Carbon::parse($index_date_filter)->format('M, Y') }}</span>
        </div>
        {{-- <table class="w-full border border-collapse border-gray-300 table-auto">
            <thead>
                <tr class="bg-gray-200">
                    <th class="px-4 py-2 border border-gray-300">Branch</th>
                    <th class="px-4 py-2 border border-gray-300">index</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($indexs as $index => $item)
                    <tr class="text-center {{ $index % 2 == 0 ? 'bg-gray-100' : '' }}">
                        <td class="px-4 py-2 font-bold border border-gray-300">
                            {{ ucfirst($item->branch) }}
                        </td>
                        <td class="px-4 py-2 border border-gray-300">{{ $item->shape }}
                            {{ $item->total_gram }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table> --}}

        <div class="p-4 border border-gray-200 rounded">
            @foreach ($indexs as $sequence => $item)
                @php
                    $branch = strtolower($item->branch);
                    $target = $monthly_target[$item->branch];
                    $achieved = ($item->total_gram * 60) / 100 + ($item->total_quantity * 40) / 100;
                    $progress = ($achieved / (int) $target) * 100; // Ensure max 100%
                @endphp
                <div x-data="{ progress: 0 }" x-init="setTimeout(() => { progress = {{ $progress }} }, 300)" class="mb-2">
                    <!-- Branch Name -->
                    <h2 class="mb-3 text-lg font-bold text-center text-gray-700">{{ ucfirst($item->branch) }}</h2>

                    <!-- Achieved & Target -->
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold text-gray-600">Achieved: {{ number_format($achieved) }}</span>
                        <span class="font-semibold text-gray-600">Target: {{ number_format($target) }}</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative w-full h-6 bg-gray-200 rounded-full">
                        <div class="h-6 text-sm font-bold text-center text-white transition-all duration-700 bg-green-500 rounded-full"
                            :style="'width:' + Math.min(progress, 100) + '%; max-width: 100%'">
                            <span x-text="progress.toFixed(0) + '%'"></span>
                        </div>
                        <div x-show="progress > 100" class="absolute inset-0 flex items-end justify-end">
                            <span class="text-xs font-bold text-red-600">🔥 Over Target!</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
