<div>
    <div class="p-3 mb-4 text-sm bg-yellow-100 rounded">
        <span class="font-semibold">Branch Analytics Report</span>
        <span class="ml-2">Real sales period: {{ $since }} to today ({{ $periodDays }} days)</span>
    </div>

    <div class="flex flex-wrap items-end justify-between gap-3 px-4 py-3 mb-4 bg-white border rounded shadow-sm">
        <div class="flex flex-wrap gap-3">
            <div class="w-64">
                <label class="block mb-1 text-xs text-gray-500">Search Product</label>
                <x-input wire:model.debounce.500ms="search" placeholder="Search product name..." />
            </div>
            <div class="w-48">
                <label class="block mb-1 text-xs text-gray-500">Filter Status</label>
                <select wire:model="status" class="w-full px-3 py-2 text-sm border rounded">
                    <option value="all">All</option>
                    <option value="OVER">Over</option>
                    <option value="SHORT">Short</option>
                    <option value="OUT">Out</option>
                    <option value="NO SALES">No Sales</option>
                    <option value="OK">OK</option>
                </select>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-button sm outline slate label="Manual" wire:click="$set('manualModal', true)" />
        </div>
    </div>

    <div class="p-4 mb-4 bg-white border rounded shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="font-semibold text-gray-900">
                {{ $grandTotal['label'] }}
                <span class="ml-2 text-xs font-normal text-gray-500">({{ $grandTotal['product_count'] }}
                    products)</span>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2 mt-3 text-xs sm:grid-cols-6">
            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Total Stock</div>
                <div class="font-semibold text-gray-900">{{ number_format($grandTotal['total_inventory'], 0) }}</div>
            </div>
            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Min (Focus)</div>
                <div class="font-semibold text-gray-900">{{ number_format($grandTotal['total_min_with_focus'], 0) }}
                </div>
            </div>
            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Min (Real)</div>
                <div class="font-semibold text-gray-900">{{ number_format($grandTotal['total_min_with_real'], 0) }}
                </div>
            </div>
            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Diff Qty</div>
                <div class="font-semibold {{ $grandTotal['min_diff_qty'] >= 0 ? 'text-amber-700' : 'text-blue-700' }}">
                    {{ number_format($grandTotal['min_diff_qty'], 0) }}
                    @if ($grandTotal['min_diff_percent'] !== null)
                        <span
                            class="ml-1 text-[10px] text-gray-500">({{ number_format($grandTotal['min_diff_percent'], 1) }}%)</span>
                    @endif
                </div>
            </div>
            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Real Avg/Day</div>
                <div class="font-semibold text-gray-900">{{ number_format($grandTotal['total_real_avg_per_day'], 2) }}
                </div>
            </div>
            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Over/Under vs Min(Focus)</div>
                <div
                    class="font-semibold {{ $grandTotal['over_under_vs_min_focus'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    {{ number_format($grandTotal['over_under_vs_min_focus'], 0) }}
                </div>
            </div>

            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Healthy Branch</div>
                <div class="font-semibold text-green-700">
                    {{ number_format($grandTotal['branch_line_green'] ?? 0, 0) }}
                    <span class="ml-1 text-[10px] text-gray-500">/
                        {{ number_format($grandTotal['branch_line_total'] ?? 0, 0) }}</span>
                </div>
            </div>
            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Below Safety Stock</div>
                <div class="font-semibold text-red-700">
                    {{ number_format($grandTotal['branch_line_red'] ?? 0, 0) }}
                    <span class="ml-1 text-[10px] text-gray-500">/
                        {{ number_format($grandTotal['branch_line_total'] ?? 0, 0) }}</span>
                </div>
            </div>
            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Healthy %</div>
                <div class="font-semibold text-green-700">
                    @if (($grandTotal['fulfill_percent'] ?? null) !== null)
                        {{ number_format($grandTotal['fulfill_percent'], 1) }}%
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="px-2 py-2 rounded bg-slate-50">
                <div class="text-gray-500">Shortage %</div>
                <div class="font-semibold text-red-700">
                    @if (($grandTotal['fail_percent'] ?? null) !== null)
                        {{ number_format($grandTotal['fail_percent'], 1) }}%
                    @else
                        -
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <h2 class="px-4 py-3 text-lg font-semibold">Branch Stock Healthy Condition</h2>

        <div class="px-4 pb-4">
            @forelse ($trendProducts as $p)
                @php
                    $stockBaseline = (float) ($p['total_base_min_focus'] ?? 0);
                    $stockIsGreen = ((float) ($p['total_inventory'] ?? 0)) > $stockBaseline;
                    $stockEmoji = $stockIsGreen ? '🙂' : '😢';
                    $stockBg = $stockIsGreen
                        ? 'bg-gradient-to-r from-green-50 to-green-100'
                        : 'bg-gradient-to-r from-red-50 to-red-100';

                    $branchList = collect($p['branches'] ?? []);
                    $greenBranches = $branchList->filter(function ($b) {
                        return ((float) ($b['inventory_balance'] ?? 0)) > ((float) ($b['base_min_focus'] ?? 0));
                    });
                    $redBranches = $branchList->filter(function ($b) {
                        return ((float) ($b['inventory_balance'] ?? 0)) <= ((float) ($b['base_min_focus'] ?? 0));
                    });

                    $greenCount = (int) $greenBranches->count();
                    $redCount = (int) $redBranches->count();
                    $redBranchNames = (string) $redBranches
                        ->pluck('branch_name')
                        ->filter()
                        ->map(fn($n) => ucfirst(trim((string) $n)))
                        ->unique()
                        ->implode(', ');

                    $overUnderBig = ($p['over_under_vs_min_focus'] ?? 0) > ($p['total_real_avg_per_day'] ?? 0);
                    $overUnderEmoji = $overUnderBig ? '🙂' : '😢';
                    $overUnderBg = $overUnderBig
                        ? 'bg-gradient-to-r from-green-50 to-green-100'
                        : 'bg-gradient-to-r from-red-50 to-red-100';
                @endphp
                <details class="p-3 mb-3 bg-white border rounded">
                    <summary class="flex items-center gap-3 cursor-pointer">
                        <div class="w-16 h-16 overflow-hidden rounded bg-slate-200 shrink-0">
                            @if (!empty($p['photo']))
                                <img src="{{ asset('storage/' . $p['photo']) }}" class="object-cover w-16 h-16" />
                            @endif
                        </div>

                        <div class="flex-1">
                            <div class="font-semibold text-gray-900">
                                {{ $p['product_name'] }}
                                <span class="ml-2 text-sm font-normal text-gray-500">{{ $p['weight'] }}</span>
                            </div>
                            <div class="mt-1 text-xs text-gray-600">
                                <span class="font-semibold text-green-700">Green: {{ $greenCount }}</span>
                                <span class="ml-2 font-semibold text-red-700">Red: {{ $redCount }}</span>
                                @if ($redCount > 0 && $redBranchNames !== '')
                                    <span class="ml-2 text-gray-500">Red branches: {{ $redBranchNames }}</span>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-2 text-xs sm:grid-cols-5">
                                <div class="px-2 py-1 rounded {{ $stockBg }}">
                                    <div class="text-gray-500">Total Stock {{ $stockEmoji }}</div>
                                    <div class="font-semibold text-gray-900">
                                        {{ number_format($p['total_inventory'], 0) }}</div>
                                </div>
                                <div class="px-2 py-1 rounded bg-slate-50">
                                    <div class="text-gray-500">Min (Focus)</div>
                                    <div class="font-semibold text-gray-900">
                                        {{ number_format($p['total_min_with_focus'], 0) }}</div>
                                </div>
                                <div class="px-2 py-1 rounded bg-slate-50">
                                    <div class="text-gray-500">Min (Real)</div>
                                    <div class="font-semibold text-gray-900">
                                        {{ number_format($p['total_min_with_real'], 0) }}</div>
                                </div>
                                <div class="px-2 py-1 rounded bg-slate-50">
                                    <div class="text-gray-500">Diff Qty</div>
                                    <div
                                        class="font-semibold {{ $p['min_diff_qty'] >= 0 ? 'text-amber-700' : 'text-blue-700' }}">
                                        {{ number_format($p['min_diff_qty'], 0) }}
                                        @if (($p['min_diff_percent'] ?? null) !== null)
                                            <span
                                                class="ml-1 text-[10px] text-gray-500">({{ number_format($p['min_diff_percent'], 1) }}%)</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="px-2 py-1 rounded {{ $overUnderBg }}">
                                    <div class="text-gray-500">Over/Under vs Min (Focus) {{ $overUnderEmoji }}
                                    </div>
                                    <div
                                        class="font-semibold {{ $p['over_under_vs_min_focus'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                        {{ number_format($p['over_under_vs_min_focus'], 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </summary>

                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                            <thead
                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-2">Branch</th>
                                    <th scope="col" class="px-4 py-2">Condition</th>
                                    <th scope="col" class="px-4 py-2">Stock</th>
                                    <th scope="col" class="px-4 py-2">Min(Focus)</th>
                                    <th scope="col" class="px-4 py-2">Min(Real)</th>
                                    <th scope="col" class="px-4 py-2">Focus/Day</th>
                                    <th scope="col" class="px-4 py-2">Real Avg/Day</th>
                                    <th scope="col" class="px-4 py-2">Diff Qty</th>
                                    <th scope="col" class="px-4 py-2">Over/Under vs Min(Focus)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($p['branches'] as $b)
                                    @php
                                        $cond = $b['condition'] ?? 'OK';
                                        $condColor = match ($cond) {
                                            'OUT' => 'text-red-600',
                                            'SHORT' => 'text-amber-700',
                                            'OVER' => 'text-green-700',
                                            'NO SALES' => 'text-gray-400',
                                            default => 'text-slate-700',
                                        };

                                        $rowIsGreen =
                                            ((float) ($b['inventory_balance'] ?? 0)) >
                                            ((float) ($b['base_min_focus'] ?? 0));
                                        $rowBg = $rowIsGreen
                                            ? 'bg-gradient-to-r from-green-50 to-green-100'
                                            : 'bg-gradient-to-r from-red-50 to-red-100';

                                        $priceUrl = route('price', [
                                            'prod' => $p['psi_product_id'],
                                            'bch' => $b['branch_id'],
                                        ]);
                                    @endphp
                                    <tr class="border-b cursor-pointer hover:bg-slate-100 {{ $rowBg }}"
                                        role="link" tabindex="0"
                                        onclick="window.location='{{ $priceUrl }}'"
                                        onkeydown="if (event.key === 'Enter') { window.location='{{ $priceUrl }}' }">
                                        <th scope="row"
                                            class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ ucfirst(trim((string) $b['branch_name'])) }}
                                        </th>
                                        <td class="px-4 py-2">
                                            <span
                                                class="font-semibold {{ $condColor }}">{{ $cond }}</span>
                                        </td>
                                        <td class="px-4 py-2">{{ number_format($b['inventory_balance'], 0) }}</td>
                                        <td class="px-4 py-2">{{ number_format($b['min_with_focus'], 0) }}</td>
                                        <td class="px-4 py-2">{{ number_format($b['min_with_real'], 0) }}</td>
                                        <td class="px-4 py-2">{{ number_format($b['focus_qty'], 2) }}</td>
                                        <td class="px-4 py-2">{{ number_format($b['real_avg_per_day'], 2) }}</td>
                                        <td class="px-4 py-2">
                                            <span
                                                class="{{ $b['min_diff_qty'] >= 0 ? 'text-amber-700' : 'text-blue-700' }}">
                                                {{ number_format($b['min_diff_qty'], 0) }}
                                                @if (($b['min_diff_percent'] ?? null) !== null)
                                                    <span
                                                        class="ml-1 text-[10px] text-gray-500">({{ number_format($b['min_diff_percent'], 1) }}%)</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <span
                                                class="{{ $b['min_gap_qty'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                                {{ number_format($b['min_gap_qty'], 0) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @empty
                <div class="py-8 text-center text-gray-400">No trend data found</div>
            @endforelse
        </div>
    </div>

    <x-modal.card title="Manual (မြန်မာ)" blur wire:model="manualModal" max-width="3xl">
        <div class="space-y-3 text-sm text-gray-700">
            <div class="font-semibold text-gray-900">အဓိပ္ပါယ် (Conditions)</div>
            <div class="space-y-1">
                <div>1) <span class="font-semibold">Min (Focus)</span> = (Deliver Day + Safety Day) × Focus Qty +
                    Display Qty</div>
                <div>2) <span class="font-semibold">Min (Real)</span> = (Deliver Day + Safety Day) × Real Avg/Day +
                    Display Qty</div>
                <div>3) <span class="font-semibold">Diff Qty</span> = Min (Focus) − Min (Real)</div>
                <div>4) <span class="font-semibold">Diff %</span> = Diff Qty ÷ Min (Real) × 100 (Min(Real)=0 ဖြစ်ရင် %
                    မပြ)</div>
                <div>5) <span class="font-semibold">Over/Under vs Min(Focus)</span> = Stock − Min (Focus)</div>
            </div>

            <div class="font-semibold text-gray-900">အရောင်/Emoji အဓိပ္ပါယ်</div>
            <div class="space-y-1">
                <div><span class="font-semibold">Min (Focus)</span> အနီ/အစိမ်း (🙂/😢): Deliver Day ပါလား/မပါလား ကို ပြ
                </div>
                <div><span class="font-semibold">Over/Under</span> အနီ/အစိမ်း (🙂/😢): (Stock − Min(Focus)) က Real
                    Avg/Day ထက် ကြီးလား/မကြီးလား</div>
                <div><span class="font-semibold">⭐</span>: အထက်ပါ Conditions ၂ ခုလုံး OK (အစိမ်း/🙂 နှစ်ခုလုံး)</div>
            </div>

            <div class="text-xs text-gray-500">
                မှတ်ချက်: Real Avg/Day ကို {{ $periodDays }} ရက်အတွင်း Real Sale မှာ အခြေခံတွက်ချက်ထားပါတယ်။
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-button flat label="Close" wire:click="$set('manualModal', false)" />
            </div>
        </x-slot>
    </x-modal.card>

</div>
