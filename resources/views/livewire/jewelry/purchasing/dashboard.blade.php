<div x-data="{ summaryMode: 'category', bootcampIndex: 0, bootcampSlides: @js($dailyBootcampHistory ?? []) }" class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Jewelry Purchasing Dashboard</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Purchase status, registration status, and daily
                metrics.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="flex items-center gap-2">
                <div class="text-xs font-medium text-slate-500 dark:text-slate-300">Branch</div>
                <select wire:model.live="branchId"
                    class="h-9 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                    <option value="">All</option>
                    @foreach ($branches ?? [] as $b)
                        <option value="{{ (int) ($b['id'] ?? 0) }}">{{ (string) ($b['name'] ?? '') }}</option>
                    @endforeach
                </select>
            </div>

            <a wire:navigate href="{{ route('jewelry.groups.index') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <x-icon name="clipboard-list" class="w-4 h-4 mr-2" />
                Groups
            </a>
        </div>
    </div>

    @php
        $rankEmoji = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
        $bootcampSlides = $dailyBootcampHistory ?? [];
    @endphp
    <div
        class="rounded-lg border border-slate-200 bg-gradient-to-r from-yellow-50 to-white p-5 dark:border-slate-700 dark:from-yellow-900/10 dark:to-slate-800">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Daily
                    Prize
                    Bootcamp</div>
                <div class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">Champions 🏆</div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-sm font-medium text-slate-600 dark:text-slate-200">Motivation: win by speed + accuracy
                </div>

                @if (!empty($bootcampSlides))
                    <div class="flex items-center gap-2">
                        <button type="button" @click="bootcampIndex = Math.max(0, bootcampIndex - 1)"
                            class="px-3 py-1.5 text-xs font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                            :class="bootcampIndex === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                            :disabled="bootcampIndex === 0">
                            Prev
                        </button>
                        <button type="button"
                            @click="bootcampIndex = Math.min((bootcampSlides?.length ?? 1) - 1, bootcampIndex + 1)"
                            class="px-3 py-1.5 text-xs font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                            :class="bootcampIndex >= (bootcampSlides?.length ?? 1) - 1 ? 'opacity-50 cursor-not-allowed' : ''"
                            :disabled="bootcampIndex >= (bootcampSlides?.length ?? 1) - 1">
                            Next
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-300"
            x-show="(bootcampSlides?.length ?? 0) > 0" x-cloak>
            <span
                x-text="(() => {
                const s = bootcampSlides?.[bootcampIndex];
                if (!s) return '';
                return s.is_today ? ('Today (' + s.date + ')') : s.date;
            })()"></span>
            <span class="mx-2">•</span>
            <span x-text="(bootcampIndex + 1) + ' / ' + (bootcampSlides?.length ?? 0)"></span>
        </div>

        <div class="mt-4 overflow-hidden" x-show="(bootcampSlides?.length ?? 0) > 0" x-cloak>
            <div class="flex transition-transform duration-300"
                :style="'transform: translateX(-' + (bootcampIndex * 100) + '%)'">
                @foreach ($bootcampSlides ?? [] as $slide)
                    @php
                        $winner = (array) ($slide['skill_winner'] ?? []);
                        $topRegs = (array) ($slide['top_registrars'] ?? []);

                        $gradeValue = (int) ($winner['grade_value'] ?? 0);
                        $gradeLabel = (string) ($winner['grade_label'] ?? '');

                        $gradeClasses = 'bg-slate-100 text-slate-900 dark:bg-slate-700 dark:text-slate-100';
                        if ($gradeValue === 1) {
                            $gradeClasses = 'bg-yellow-200 text-slate-900 dark:bg-yellow-300 dark:text-slate-900';
                        } elseif ($gradeValue === 2) {
                            $gradeClasses = 'bg-green-100 text-green-900 dark:bg-green-200 dark:text-green-900';
                        } elseif ($gradeValue === 3) {
                            $gradeClasses = 'bg-red-600 text-white dark:bg-red-600 dark:text-white';
                        }
                    @endphp
                    <div class="w-full shrink-0">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div
                                class="rounded-lg border border-slate-200 bg-white/80 p-4 dark:border-slate-700 dark:bg-slate-900/20">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">Purchase Skill
                                        Grade Winner</div>
                                    @if (!empty($gradeLabel))
                                        <span
                                            class="inline-flex items-center gap-2 rounded-md px-2.5 py-1 text-sm font-semibold {{ $gradeClasses }}">
                                            <span>{{ $gradeLabel }}</span>
                                            @if ($gradeValue === 1)
                                                <span aria-label="Excellent">👑</span>
                                            @elseif ($gradeValue === 3)
                                                <span aria-label="Fighting">💪</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>

                                @if (!empty($winner))
                                    <div class="mt-3">
                                        <div class="text-2xl font-extrabold text-slate-900 dark:text-white">
                                            {{ (string) ($winner['user_name'] ?? '—') }}
                                        </div>
                                        <div class="mt-1 text-sm text-slate-600 dark:text-slate-200">
                                            Group <span
                                                class="font-semibold">{{ (string) ($winner['group_number'] ?? '') }}</span>
                                            <span class="mx-2">•</span>
                                            Time <span
                                                class="font-semibold">{{ is_null($winner['mins'] ?? null) ? '—' : ((int) $winner['mins']) . ' min' }}</span>
                                            <span class="mx-2">•</span>
                                            Items <span
                                                class="font-semibold">{{ (int) ($winner['items_count'] ?? 0) }}</span>
                                            <span class="mx-2">•</span>
                                            Registered <span
                                                class="font-semibold">{{ (int) ($winner['registered_count'] ?? 0) }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-3 text-sm text-slate-600 dark:text-slate-200">No finished purchases
                                        on this day yet.</div>
                                @endif
                            </div>

                            <div
                                class="rounded-lg border border-slate-200 bg-white/80 p-4 dark:border-slate-700 dark:bg-slate-900/20">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white">Top Registrars</div>

                                @if (!empty($topRegs))
                                    <div class="mt-3 grid gap-2">
                                        @foreach ($topRegs as $i => $row)
                                            @php
                                                $rank = (int) $i + 1;
                                            @endphp
                                            <div
                                                class="flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-800">
                                                <div class="flex items-center gap-3">
                                                    <div class="text-xl font-extrabold text-slate-900 dark:text-white">
                                                        {{ $rankEmoji[$rank] ?? '#' . $rank }}
                                                    </div>
                                                    <div>
                                                        <div class="text-base font-bold text-slate-900 dark:text-white">
                                                            {{ (string) ($row['user_name'] ?? '—') }}
                                                        </div>
                                                        <div class="text-xs text-slate-500 dark:text-slate-300">
                                                            Registered items</div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="text-2xl font-extrabold text-slate-900 dark:text-white tabular-nums">
                                                    {{ (int) ($row['registered_count'] ?? 0) }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-3 text-sm text-slate-600 dark:text-slate-200">No registrations on
                                        this day yet.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2" x-show="(bootcampSlides?.length ?? 0) === 0" x-cloak>
            <div class="rounded-lg border border-slate-200 bg-white/80 p-4 dark:border-slate-700 dark:bg-slate-900/20">
                <div class="text-sm text-slate-600 dark:text-slate-200">No daily bootcamp results yet.</div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-4 dark:bg-slate-800 dark:border-slate-700">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-semibold text-slate-900 dark:text-white">Purchase Status</div>
                <button type="button" wire:click="refreshStats"
                    class="px-3 py-1.5 text-xs font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Refresh</button>
            </div>
            <div id="jewelry-purchase-status-donut" class="w-full" wire:ignore></div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 dark:bg-slate-800 dark:border-slate-700">
            <div class="text-sm font-semibold text-slate-900 dark:text-white mb-2">Registration Status</div>
            <div id="jewelry-register-status-donut" class="w-full" wire:ignore></div>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4 dark:bg-slate-800 dark:border-slate-700">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-3">
            <div>
                <div class="text-sm font-semibold text-slate-900 dark:text-white">Register Summary</div>
                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-300">Swipe switch: Category (default) /
                    Product</div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                        Purchased groups
                        <span
                            class="inline-block rounded-full bg-white px-2 py-0.5 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
                            {{ (int) ($purchaseSummaryTotals['purchased_groups'] ?? 0) }}
                        </span>
                    </span>
                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                        Purchased batches
                        <span
                            class="inline-block rounded-full bg-white px-2 py-0.5 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
                            {{ (int) ($purchaseSummaryTotals['purchased_batches'] ?? 0) }}
                        </span>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div
                    class="relative inline-flex rounded-full border border-slate-300 bg-slate-100 p-1 dark:border-slate-600 dark:bg-slate-900">
                    <span
                        class="absolute top-1 bottom-1 w-1/2 rounded-full bg-white shadow-sm transition-all duration-200 dark:bg-slate-700"
                        :class="summaryMode === 'category' ? 'left-1' : 'left-1/2'"></span>
                    <button type="button" @click="summaryMode = 'category'"
                        class="relative z-10 px-3 py-1 text-xs font-semibold"
                        :class="summaryMode === 'category' ? 'text-slate-900 dark:text-white' :
                            'text-slate-600 dark:text-slate-300'">
                        Category
                    </button>
                    <button type="button" @click="summaryMode = 'product'"
                        class="relative z-10 px-3 py-1 text-xs font-semibold"
                        :class="summaryMode === 'product' ? 'text-slate-900 dark:text-white' :
                            'text-slate-600 dark:text-slate-300'">
                        Product
                    </button>
                </div>

                <div x-show="summaryMode === 'product'" x-cloak class="flex items-center gap-2">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-300">Category</div>
                    <select wire:model.live="summaryCategoryId"
                        class="h-9 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">All</option>
                        @foreach ($categories ?? [] as $c)
                            <option value="{{ (int) ($c['id'] ?? 0) }}">{{ (string) ($c['name'] ?? '') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div x-show="summaryMode === 'category'" x-cloak>
            @php
                $catRows = $categoryRegisterSummary ?? [];
                $gtCount = 0;
                $gtPurchasedGroups = 0;
                $gtPurchasedBatches = 0;
                $gtPurchased = 0;
                $gtReg = 0;
                $gtGram = 0.0;
                foreach ($catRows as $r) {
                    $gtCount += (int) ($r['total_count'] ?? 0);
                    $gtPurchasedGroups += (int) ($r['purchased_groups_count'] ?? 0);
                    $gtPurchasedBatches += (int) ($r['purchased_batches_count'] ?? 0);
                    $gtPurchased += (int) ($r['purchased_count'] ?? 0);
                    $gtReg += (int) ($r['registered_count'] ?? 0);
                    $gtGram += (float) ($r['total_gram'] ?? 0);
                }
            @endphp
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 dark:text-slate-300">
                            <th class="py-2 pr-4">Category</th>
                            <th class="py-2 pr-4">Total Count</th>
                            <th class="py-2 pr-4">Total Gram</th>
                            <th class="py-2 pr-4">Purchased Groups</th>
                            <th class="py-2 pr-4">Purchased Batches</th>
                            <th class="py-2 pr-4">Purchased Items</th>
                            <th class="py-2 pr-4">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($catRows as $row)
                            @php
                                $total = (int) ($row['total_count'] ?? 0);
                                $pGroups = (int) ($row['purchased_groups_count'] ?? 0);
                                $pBatches = (int) ($row['purchased_batches_count'] ?? 0);
                                $purchased = (int) ($row['purchased_count'] ?? 0);
                                $reg = (int) ($row['registered_count'] ?? 0);
                                $done = $total > 0 && $reg >= $total;
                            @endphp
                            <tr class="{{ $done ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                <td class="py-2 pr-4 text-slate-900 dark:text-white">
                                    {{ (string) ($row['category_name'] ?? '') }}
                                </td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $total }}</td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">
                                    {{ number_format((float) ($row['total_gram'] ?? 0), 3) }}
                                </td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $pGroups }}</td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $pBatches }}</td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $purchased }}</td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $reg }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-3 text-slate-500 dark:text-slate-300">No items found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr
                            class="border-t border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/30">
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">Grand Total</td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">{{ $gtCount }}
                            </td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">
                                {{ number_format($gtGram, 3) }}</td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">
                                {{ $gtPurchasedGroups }}</td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">
                                {{ $gtPurchasedBatches }}</td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">{{ $gtPurchased }}
                            </td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">{{ $gtReg }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div x-show="summaryMode === 'product'" x-cloak>
            @php
                $prodRows = $productRegisterSummary ?? ($itemRegisterSummary ?? []);
                $gtCount = 0;
                $gtPurchased = 0;
                $gtReg = 0;
                $gtGram = 0.0;
                foreach ($prodRows as $r) {
                    $gtCount += (int) ($r['total_count'] ?? 0);
                    $gtPurchased += (int) ($r['purchased_count'] ?? 0);
                    $gtReg += (int) ($r['registered_count'] ?? 0);
                    $gtGram += (float) ($r['total_gram'] ?? 0);
                }
            @endphp
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 dark:text-slate-300">
                            <th class="py-2 pr-4">Item Name</th>
                            <th class="py-2 pr-4">Total Count</th>
                            <th class="py-2 pr-4">Total Gram</th>
                            <th class="py-2 pr-4">Purchased</th>
                            <th class="py-2 pr-4">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($prodRows as $row)
                            @php
                                $total = (int) ($row['total_count'] ?? 0);
                                $purchased = (int) ($row['purchased_count'] ?? 0);
                                $reg = (int) ($row['registered_count'] ?? 0);
                                $done = $total > 0 && $reg >= $total;
                            @endphp
                            <tr class="{{ $done ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                <td class="py-2 pr-4 text-slate-900 dark:text-white">
                                    {{ (string) ($row['product_name'] ?? '') }}
                                </td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $total }}</td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">
                                    {{ number_format((float) ($row['total_gram'] ?? 0), 3) }}
                                </td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $purchased }}</td>
                                <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $reg }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-3 text-slate-500 dark:text-slate-300">No items found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr
                            class="border-t border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/30">
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">Grand Total</td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">{{ $gtCount }}
                            </td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">
                                {{ number_format($gtGram, 3) }}</td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">{{ $gtPurchased }}
                            </td>
                            <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">{{ $gtReg }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-4 dark:bg-slate-800 dark:border-slate-700">
            <div class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Today</div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Finished Groups</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ (int) ($today['finished_groups'] ?? 0) }}</div>
                </div>
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Finished Items</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ (int) ($today['finished_items'] ?? 0) }}</div>
                </div>
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Groups Created</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ (int) ($today['groups_created'] ?? 0) }}</div>
                </div>
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Purchase Completion Rate</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        @if (!is_null($today['purchase_completion_rate'] ?? null))
                            {{ $today['purchase_completion_rate'] }}%
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700 sm:col-span-2">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Registered Items Today</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ (int) ($today['registered_items'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 dark:bg-slate-800 dark:border-slate-700">
            <div class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Predictive
                ({{ (int) ($predictive['window_days'] ?? 0) }}-day avg)</div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Avg Purchase Items / day</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ $predictive['avg_purchase_items_per_day'] ?? 0 }}</div>
                </div>
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Remaining Purchase Items</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ (int) ($predictive['remaining_purchase_items'] ?? 0) }}</div>
                </div>
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Days to Clear Purchase</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ $predictive['days_to_clear_purchase'] ?? '—' }}</div>
                </div>
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Avg Register Items / day</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ $predictive['avg_register_items_per_day'] ?? 0 }}</div>
                </div>
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Remaining Register Items</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ (int) ($predictive['remaining_register_items'] ?? 0) }}</div>
                </div>
                <div class="p-3 rounded border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-500 dark:text-slate-300">Days to Clear Register</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ $predictive['days_to_clear_register'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            function renderDonut(elId, payload) {
                if (typeof window.ApexCharts === 'undefined') return;
                const el = document.getElementById(elId);
                if (!el) return;

                const labels = (payload && payload.labels) ? payload.labels : [];
                const series = (payload && payload.series) ? payload.series : [];

                const options = {
                    chart: {
                        type: 'donut',
                        height: 320
                    },
                    labels: labels,
                    series: series,
                    legend: {
                        position: 'bottom'
                    },
                    dataLabels: {
                        enabled: true
                    },
                };

                if (el.__apexchart) {
                    el.__apexchart.updateOptions({
                        labels
                    }, false, true);
                    el.__apexchart.updateSeries(series, true);
                    return;
                }

                const chart = new ApexCharts(el, options);
                el.__apexchart = chart;
                chart.render();
            }

            const initialPurchase = @json($purchaseStatusChart);
            const initialRegister = @json($registerStatusChart);

            function renderAll() {
                renderDonut('jewelry-purchase-status-donut', initialPurchase);
                renderDonut('jewelry-register-status-donut', initialRegister);
            }

            document.addEventListener('DOMContentLoaded', renderAll);
            renderAll();

            document.addEventListener('livewire:init', function() {
                if (!window.Livewire || typeof window.Livewire.on !== 'function') return;

                window.Livewire.on('jewelry-purchase-status-chart-updated', function(payload) {
                    renderDonut('jewelry-purchase-status-donut', payload && payload.chart ? payload
                        .chart : payload);
                });
                window.Livewire.on('jewelry-register-status-chart-updated', function(payload) {
                    renderDonut('jewelry-register-status-donut', payload && payload.chart ? payload
                        .chart : payload);
                });
            });

            document.addEventListener('livewire:navigated', renderAll);
        })();
    </script>
</div>
