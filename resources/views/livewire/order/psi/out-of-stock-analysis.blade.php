<div class="bg-white dark:bg-gray-900 p-2 rounded">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">ပစ္စည်းမပြတ်စေရန် စစ်ဆေးပါ</h1>

    <div class="mb-4">
        <div class="max-w-sm">
            <x-select label="Branches" placeholder="Select branches" multiselect searchable :options="$branchOptions"
                option-label="name" option-value="id" wire:model.live="selectedBranchIds" />
        </div>

        <!-- Dropdown menu -->
        <div id="dropdownSearch" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-800">

            <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200"
                aria-labelledby="dropdownSearchButton">

                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b1" type="checkbox" value="" wire:model.live='br1'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b1"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-200">Branch
                            1</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b2" type="checkbox" value="" wire:model.live='br2'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b2"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-200">Branch
                            2</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b3" type="checkbox" value="" wire:model.live='br3'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b3"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-200">Branch
                            3</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b4" type="checkbox" value="" wire:model.live='br4'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b4"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-200">Branch
                            4</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b5" type="checkbox" value="" wire:model.live='br5'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b5"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-200">Brnach
                            5</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b6" type="checkbox" value="" wire:model.live='br6'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b6"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-200">Branch
                            6</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="online" type="checkbox" value="" wire:model.live='online_sale'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="online"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-200">Online
                            Sale</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="ho" type="checkbox" value="" wire:model.live='ho'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="ho"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-200">HO</label>
                    </div>
                </li>
            </ul>

        </div>

    </div>

    <!-- Legend -->
    <div class="mt-2 mb-3 flex flex-wrap gap-3 text-xs text-gray-600 dark:text-gray-300">
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500"></span> Critical
            (today)</span>
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500"></span> Urgent
            (&lt; 3 days)</span>
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-500"></span> Soon (3
            days)</span>
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Safe
            (&gt; 3 days)</span>
    </div>

    <div class="relative mt-2 overflow-auto max-h-[70vh] border rounded-lg border-gray-200 dark:border-gray-700">
        <table class="min-w-full table-auto text-sm text-left text-gray-700 dark:text-gray-200">
            <thead
                class="text-xs uppercase bg-gray-50/80 backdrop-blur supports-backdrop-blur:backdrop-blur-sm dark:bg-gray-800/80 sticky top-0 z-10 text-gray-700 dark:text-gray-300">
                <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">Product</th>
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">Branch</th>
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">Focus</th>
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">Real Sale</th>
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">Balance</th>
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">Remaining to Sale</th>
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">နောက်ဆုံးပို့ရမည့်ရက်</th>
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">ကွာဟနေသော ရက်</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($analysis as $productName => $products)
                    {{-- @dd($products) --}}
                    @php
                        $rowspan = count($products);

                    @endphp
                    @foreach ($products as $branchName => $details)
                        {{-- @dd($products) --}}
                        @php
                            $focus = $details['focus'];
                            $balance = $details['balance'];
                            // $balance = 0;
                            $avg_sale = ceil($details['avg_sale']);
                            $remainingToSale = floor($balance / ($avg_sale > 0 ? $avg_sale : 1));
                            if ($balance == 0) {
                                $dueDate = \Carbon\Carbon::now()->format('M j, y');
                            } else {
                                $dueDate = \Carbon\Carbon::now()->addDays($remainingToSale)->format('M j, y');
                            }
                            $diffWithDueDate = \Carbon\Carbon::now()->diffInDays($dueDate);
                            // Determine severity styles (classic, dark/light compatible)
                            $rowAccent = '';
                            $badgeClass = '';
                            if ($diffWithDueDate === 0) {
                                $rowAccent = 'border-l-4 border-l-red-500';
                                $badgeClass = 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200';
                            } elseif ($diffWithDueDate < 3) {
                                $rowAccent = 'border-l-4 border-l-amber-500';
                                $badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
                            } elseif ($diffWithDueDate === 3) {
                                $rowAccent = 'border-l-4 border-l-blue-500';
                                $badgeClass = 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200';
                            } else {
                                $rowAccent = 'border-l-4 border-l-emerald-500';
                                $badgeClass =
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
                            }
                            $rowHover = 'hover:bg-gray-50 dark:hover:bg-gray-700/40';
                        @endphp
                        <tr class="mt-0 bg-white dark:bg-gray-900 {{ $rowAccent }} {{ $rowHover }}">
                            @if ($loop->first)
                                <th scope="row"
                                    class="align-top px-4 md:px-6 py-4 text-teal-600 dark:text-teal-300 font-medium bg-white dark:bg-gray-900"
                                    rowspan="{{ $rowspan }}">
                                    <div class="flex flex-col items-start gap-2">
                                        <img class="w-28 md:w-36 mb-1 border rounded-md object-cover"
                                            src="{{ asset('/storage/' . $images[$productName]) }}"
                                            alt="product_photo" />
                                        <span class="text-sm md:text-base">{{ ucfirst($productName) }}</span>
                                    </div>
                                </th>
                            @endif
                            <td class="px-4 md:px-6 py-3 text-gray-900 dark:text-gray-100">{{ ucfirst($branchName) }}
                            </td>
                            <td class="px-4 md:px-6 py-3 text-gray-900 dark:text-gray-100">{{ $focus }}</td>
                            <td class="px-4 md:px-6 py-3 tabular-nums text-gray-900 dark:text-gray-100">
                                {{ $avg_sale }}</td>
                            <td
                                class="px-4 md:px-6 py-3 tabular-nums {{ $balance == 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-900 dark:text-gray-100' }}">
                                {{ $balance }}</td>
                            <td class="px-4 md:px-6 py-3 tabular-nums text-gray-900 dark:text-gray-100">
                                {{ $remainingToSale }}</td>
                            <td class="px-4 md:px-6 py-3 text-gray-900 dark:text-gray-100">{{ $dueDate }}</td>
                            <td class="px-4 md:px-6 py-3">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                    {{ $diffWithDueDate }} days
                                </span>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Product totals row --}}
                    @php
                        $total = $totals[$productName] ?? null;
                        if ($total) {
                            $tSale = (int) ($total['total_sale'] ?? 0);
                            $tFocus = (int) ($total['total_focus'] ?? 0);
                            $tStock = (int) ($total['total_stock'] ?? 0);
                            $tAvg = max(1, $tSale); // avoid div by 0
                            $tRemaining = (int) floor($tStock / $tAvg);
                            $tDueCarbon =
                                $tStock === 0 ? \Carbon\Carbon::now() : \Carbon\Carbon::now()->addDays($tRemaining);
                            $tDueStr = $tDueCarbon->format('M j, y');
                            $tDiff = \Carbon\Carbon::now()->diffInDays($tDueCarbon);
                            if ($tDiff === 0) {
                                $tBadge = 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200';
                            } elseif ($tDiff < 3) {
                                $tBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
                            } elseif ($tDiff === 3) {
                                $tBadge = 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200';
                            } else {
                                $tBadge =
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
                            }
                        }
                    @endphp
                    @if (!empty($total))
                        <tr class="bg-gray-50/60 dark:bg-gray-800/40">
                            <td colspan="2"
                                class="px-4 md:px-6 py-3 text-xs uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                Total</td>
                            <td class="px-4 md:px-6 py-3 tabular-nums text-gray-900 dark:text-gray-100">
                                {{ $tFocus }}</td>
                            <td class="px-4 md:px-6 py-3 tabular-nums text-gray-900 dark:text-gray-100">
                                {{ $tSale }}</td>
                            <td class="px-4 md:px-6 py-3 tabular-nums text-gray-900 dark:text-gray-100">
                                {{ $tStock }}</td>
                            <td class="px-4 md:px-6 py-3 tabular-nums text-gray-900 dark:text-gray-100">
                                {{ $tRemaining }}</td>
                            <td class="px-4 md:px-6 py-3 text-gray-900 dark:text-gray-100">{{ $tDueStr }}</td>
                            <td class="px-4 md:px-6 py-3"><span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tBadge }}">{{ $tDiff }}
                                    days</span></td>
                        </tr>
                    @endif
                @endforeach
                @if (empty($analysis) || count($analysis) === 0)
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No data to
                            display.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
