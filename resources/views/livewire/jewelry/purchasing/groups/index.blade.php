<div x-data="{ importOpen: false }" x-on:jewelry-import-success.window="importOpen = false" class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Jewelry Groups</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Vouchers for purchasing and registering.</p>
        </div>

        <div class="flex items-center gap-2">
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

            <a href="{{ route('jewelry.template') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <x-icon name="download" class="w-4 h-4 mr-2" />
                Template
            </a>

            <button type="button" @click="importOpen = true"
                class="inline-flex items-center px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                <x-icon name="upload" class="w-4 h-4 mr-2" />
                Import Excel
            </button>

            <a wire:navigate href="{{ route('jewelry.dashboard') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                <x-icon name="chart-pie" class="w-4 h-4 mr-2" />
                Dashboard
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (!empty($importErrors ?? []))
        <div class="p-3 text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded">
            <div class="font-medium">Import warnings</div>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                @foreach ($importErrors as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Import Modal (auto-create group) -->
    <div x-show="importOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="importOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Import Excel (Create Group)
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">This will automatically create a new
                            voucher and import the file into it.</div>
                    </div>
                    <button type="button" @click="importOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close import modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <form wire:submit.prevent="importNewGroup" class="p-4 space-y-4">
                    <div class="text-sm text-slate-600 dark:text-slate-200">
                        Required columns: <span class="font-medium">Branch ID</span>, <span class="font-medium">Product
                            Name</span>, <span class="font-medium">Quality</span>,
                        <span class="font-medium">Total Weight</span>, <span class="font-medium">L Gram</span>, <span
                            class="font-medium">L MMK</span>, <span class="font-medium">Kyauk Gram</span>.
                        Optional: <span class="font-medium">Barcode</span>, <span class="font-medium">Batch
                            Number</span>.
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">Limits: max 12 unique batch IDs and
                            120 total items per group.</div>
                    </div>

                    <div>
                        <input type="file" wire:model="importFile" accept=".xlsx,.csv,.ods"
                            class="block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:text-slate-200 dark:file:bg-slate-700 dark:file:text-slate-100" />
                        @error('importFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div style="display: none" wire:loading.flex wire:target="importFile"
                            class="mt-2 text-sm text-slate-500">Uploading…</div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="importOpen = false"
                            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700"
                            @disabled(!$importFile) wire:loading.attr="disabled"
                            wire:target="importFile,importNewGroup">
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- <div class="rounded-lg border border-slate-200 bg-white p-4 dark:bg-slate-800 dark:border-slate-700">
        <div class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Create Group</div>

        <form wire:submit.prevent="create" class="grid gap-3 sm:grid-cols-3">
            <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Group Number</label>
                <input type="text" wire:model.live="new_number"
                    class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                @error('new_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">PO Reference</label>
                <input type="text" wire:model.live="new_po_reference"
                    class="block w-full mt-1 border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                @error('new_po_reference')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-3 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                    <x-icon name="plus" class="w-4 h-4 mr-2" />
                    Create
                </button>
            </div>
        </form>
    </div> --}}

    <div class="overflow-hidden bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Number</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Purchaser</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Status</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300">
                            Items</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Register Status</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Started</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Finished</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            PO Ref</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                    @forelse ($groups as $g)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 cursor-pointer"
                            wire:key="group-{{ $g->id }}"
                            onclick="window.location='{{ route('jewelry.groups.show', ['group' => $g->id]) }}'">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
                                <a wire:navigate href="{{ route('jewelry.groups.show', ['group' => $g->id]) }}"
                                    class="font-medium hover:underline">
                                    {{ $g->number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                {{ $g->purchaseBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $g->purchase_status }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                {{ (int) ($g->items_count ?? 0) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                @php
                                    $itemsCount = (int) ($g->items_count ?? 0);
                                    $registeredCount = (int) ($g->registered_items_count ?? 0);
                                    $allRegistered = $itemsCount > 0 && $registeredCount === $itemsCount;
                                @endphp

                                @if ($itemsCount === 0)
                                    <span
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">No
                                        items</span>
                                @elseif ($allRegistered)
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/20 dark:text-green-200">Registered</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900 dark:bg-amber-900/20 dark:text-amber-200">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                {{ $g->started_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                {{ $g->finished_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                {{ $g->po_reference ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-sm text-slate-500 dark:text-slate-300">No groups
                                yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $groups->links() }}
        </div>
    </div>
</div>
