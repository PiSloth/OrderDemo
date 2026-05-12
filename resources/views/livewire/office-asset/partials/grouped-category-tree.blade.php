<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Category Summary</h3>
    </div>

    <div class="p-4 space-y-3">
        @forelse($groupedAssets as $category)
            @php $categoryCollapsed = in_array($category['key'], $collapsedCategories, true); @endphp
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                <button type="button" wire:click="toggleCategoryCollapse('{{ $category['key'] }}')"
                    class="w-full px-4 py-3 flex items-center justify-between text-left bg-gray-50 dark:bg-gray-700/40 rounded-t-lg">
                    <div>
                        <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $category['name'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Total Balance: {{ number_format($category['total_balance']) }} | Total Cost:
                            {{ number_format($category['total_cost'], 2) }}
                        </div>
                    </div>
                    <x-icon name="{{ $categoryCollapsed ? 'chevron-right' : 'chevron-down' }}" class="w-4 h-4 text-gray-500" />
                </button>

                @unless($categoryCollapsed)
                    <div class="p-3 space-y-2">
                        @foreach ($category['branches'] as $branch)
                            @php $branchCollapsed = in_array($branch['key'], $collapsedBranches, true); @endphp
                            <div class="border border-gray-200 dark:border-gray-700 rounded">
                                <button type="button" wire:click="toggleBranchCollapse('{{ $branch['key'] }}')"
                                    class="w-full px-3 py-2 flex items-center justify-between text-left bg-white dark:bg-gray-800 rounded-t">
                                    <div>
                                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $branch['name'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Total Balance: {{ number_format($branch['total_balance']) }} | Total Cost:
                                            {{ number_format($branch['total_cost'], 2) }}
                                        </div>
                                    </div>
                                    <x-icon name="{{ $branchCollapsed ? 'chevron-right' : 'chevron-down' }}"
                                        class="w-4 h-4 text-gray-500" />
                                </button>

                                @unless($branchCollapsed)
                                    <div class="px-3 pb-3 space-y-2">
                                        @foreach ($branch['items'] as $item)
                                            @php $itemCollapsed = in_array($item['key'], $collapsedItems, true); @endphp
                                            <div class="border border-gray-100 dark:border-gray-700 rounded">
                                                <button type="button" wire:click="toggleItemCollapse('{{ $item['key'] }}')"
                                                    class="w-full px-3 py-2 flex items-center justify-between text-left bg-gray-50/60 dark:bg-gray-700/20">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                                            {{ $item['name'] }}
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            Total Balance: {{ number_format($item['total_balance']) }} | Total Cost:
                                                            {{ number_format($item['total_cost'], 2) }}
                                                        </div>
                                                    </div>
                                                    <x-icon name="{{ $itemCollapsed ? 'chevron-right' : 'chevron-down' }}"
                                                        class="w-4 h-4 text-gray-500" />
                                                </button>

                                                @unless($itemCollapsed)
                                                    <div class="overflow-auto">
                                                        <table class="min-w-full text-sm">
                                                            <thead class="bg-white dark:bg-gray-800 border-y border-gray-200 dark:border-gray-700">
                                                                <tr>
                                                                    <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Department</th>
                                                                    <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Batch</th>
                                                                    <th class="px-3 py-2 text-right text-xs text-gray-500 uppercase">Cost</th>
                                                                    <th class="px-3 py-2 text-right text-xs text-gray-500 uppercase">Balance</th>
                                                                    <th class="px-3 py-2 text-right text-xs text-gray-500 uppercase">Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                @foreach ($item['assets'] as $asset)
                                                                    <tr>
                                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                                                            {{ $asset->department->name ?? '-' }}</td>
                                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                                                            {{ $asset->batch->name ?? '-' }}</td>
                                                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                                                            {{ $asset->cost === null ? '-' : number_format($asset->cost, 2) }}</td>
                                                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                                                            {{ number_format($asset->balance) }}</td>
                                                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                                                            {{ number_format(((float) ($asset->cost ?? 0)) * ((int) ($asset->balance ?? 0)), 2) }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endunless
                                            </div>
                                        @endforeach
                                    </div>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                @endunless
            </div>
        @empty
            <div class="text-sm text-gray-500 dark:text-gray-400">No assets found.</div>
        @endforelse
    </div>
</div>
