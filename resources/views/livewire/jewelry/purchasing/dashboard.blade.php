<div class="space-y-6">
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
        <div class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Item Register Summary</div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 dark:text-slate-300">
                        <th class="py-2 pr-4">Item Name</th>
                        <th class="py-2 pr-4">Total Count</th>
                        <th class="py-2 pr-4">Total Gram</th>
                        <th class="py-2 pr-4">Registered</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse(($itemRegisterSummary ?? []) as $row)
                        @php
                            $total = (int) ($row['total_count'] ?? 0);
                            $reg = (int) ($row['registered_count'] ?? 0);
                            $done = $total > 0 && $reg >= $total;
                        @endphp
                        <tr class="{{ $done ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                            <td class="py-2 pr-4 text-slate-900 dark:text-white">
                                {{ (string) ($row['product_name'] ?? '') }}</td>
                            <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $total }}</td>
                            <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">
                                {{ number_format((float) ($row['total_gram'] ?? 0), 3) }}</td>
                            <td class="py-2 pr-4 text-slate-700 dark:text-slate-200">{{ $reg }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-3 text-slate-500 dark:text-slate-300">No items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
