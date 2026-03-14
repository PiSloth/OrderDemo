<div x-data="{ importOpen: false, updateOpen: false, externalOpen: false, exportOpen: false, categoryOpen: false, mappingOpen: false, qualityOpen: false }" x-on:jewelry-import-success.window="importOpen = false"
    x-on:jewelry-update-success.window="updateOpen = false" x-on:jewelry-external-success.window="externalOpen = false"
    class="space-y-6">
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

            <div x-data="{ actionsOpen: false }" class="relative">
                <button type="button" @click="actionsOpen = !actionsOpen"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700"
                    aria-haspopup="true" :aria-expanded="actionsOpen.toString()">
                    Actions
                    <x-icon name="chevron-down" class="w-4 h-4 ml-2" />
                </button>

                <div x-show="actionsOpen" x-cloak @click.away="actionsOpen = false"
                    class="absolute right-0 z-20 mt-2 w-64 overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                    <div class="py-1">
                        <a href="{{ route('jewelry.template') }}"
                            class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700"
                            @click="actionsOpen = false">
                            <x-icon name="download" class="w-4 h-4 mr-2" />
                            Items Template
                        </a>
                        <a href="{{ route('jewelry.template_external_mapping') }}"
                            class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700"
                            @click="actionsOpen = false">
                            <x-icon name="download" class="w-4 h-4 mr-2" />
                            External Template
                        </a>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700"></div>

                    <div class="py-1">
                        <button type="button" @click="importOpen = true; actionsOpen = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="upload" class="w-4 h-4 mr-2" />
                            Import Excel
                        </button>
                        <button type="button" @click="updateOpen = true; actionsOpen = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="refresh" class="w-4 h-4 mr-2" />
                            Update by Barcode
                        </button>
                        <button type="button" @click="externalOpen = true; actionsOpen = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="refresh" class="w-4 h-4 mr-2" />
                            Update External / Lot
                        </button>
                        <button type="button" @click="exportOpen = true; actionsOpen = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="download" class="w-4 h-4 mr-2" />
                            Export Items
                        </button>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700"></div>

                    <div class="py-1">
                        <button type="button" @click="categoryOpen = true; actionsOpen = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="collection" class="w-4 h-4 mr-2" />
                            Categories
                        </button>
                        <button type="button" @click="mappingOpen = true; actionsOpen = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="link" class="w-4 h-4 mr-2" />
                            Map Category
                        </button>
                        <button type="button" @click="qualityOpen = true; actionsOpen = false"
                            class="flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="clipboard-list" class="w-4 h-4 mr-2" />
                            Quality List
                        </button>
                        <a wire:navigate href="{{ route('jewelry.dashboard') }}"
                            class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700"
                            @click="actionsOpen = false">
                            <x-icon name="chart-pie" class="w-4 h-4 mr-2" />
                            Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quality List Modal -->
    <div x-show="qualityOpen" x-cloak class="fixed inset-0 z-50" x-data="{ copied: '' }">
        <div class="absolute inset-0 bg-black/50" @click="qualityOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Quality Names</div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Copy exact DB quality values for
                            mapping{{ !is_null($branchId) ? ' (in this branch)' : '' }}.</div>
                    </div>
                    <button type="button" @click="qualityOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close quality modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 space-y-3">
                    <div class="text-xs text-slate-500 dark:text-slate-300">
                        Tip: Use these values in the import file “Quality” column (or map codes like <span
                            class="font-medium">999</span> → <span class="font-medium">999 24 K</span> in the importer
                        mapping array).
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="button"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                            @click="navigator.clipboard.writeText(@js(implode("\n", array_map(fn($v) => (string) $v, $qualityNames ?? [])))).then(() => copied = 'ALL').catch(() => { try { window.prompt('Copy all qualities:', @js(implode("\n", array_map(fn($v) => (string) $v, $qualityNames ?? [])))); } catch (e) {} })">
                            <x-icon name="clipboard" class="w-4 h-4 mr-1" />
                            Copy All
                        </button>
                    </div>

                    <div class="overflow-hidden border rounded-md border-slate-200 dark:border-slate-700">
                        <div class="max-h-80 overflow-y-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-900/40">
                                    <tr class="text-left text-slate-500 dark:text-slate-300">
                                        <th class="px-4 py-2">Quality</th>
                                        <th class="px-4 py-2 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    @forelse(($qualityNames ?? []) as $q)
                                        <tr>
                                            <td class="px-4 py-2 text-slate-900 dark:text-white">
                                                {{ (string) $q }}
                                            </td>
                                            <td class="px-4 py-2 text-right">
                                                <button type="button"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                                                    @click="navigator.clipboard.writeText(@js((string) $q)).then(() => copied = @js((string) $q)).catch(() => { try { window.prompt('Copy quality:', @js((string) $q)); } catch (e) {} })">
                                                    <x-icon name="clipboard" class="w-4 h-4 mr-1" />
                                                    Copy
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2"
                                                class="px-4 py-6 text-sm text-slate-500 dark:text-slate-300">No quality
                                                values found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div x-show="copied !== ''" class="text-xs text-slate-500 dark:text-slate-300">
                        Copied: <span class="font-medium" x-text="copied === 'ALL' ? 'all qualities' : copied"></span>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="qualityOpen = false"
                            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <div class="text-xs font-medium text-slate-500 dark:text-slate-300">Search</div>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Product name, PO ref, barcode…"
            class="h-9 w-full max-w-md rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" />
    </div>

    @if (session('success'))
        <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (!empty($importErrors ?? []))
        <div class="p-3 text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded">
            <div class="font-medium">Import warnings</div>
            <ul class="mt-2 space-y-1 list-disc list-inside max-h-60 overflow-y-auto pr-2">
                @foreach ($importErrors as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Category Modal -->
    <div x-show="categoryOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="categoryOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Product Categories</div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Create, edit, delete categories.</div>
                    </div>
                    <button type="button" @click="categoryOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close category modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="max-h-[80vh] overflow-y-auto p-4 space-y-4">
                    <form wire:submit.prevent="{{ $editing_category_id ? 'updateCategory' : 'createCategory' }}"
                        class="grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Category
                                Name</label>
                            <input type="text" wire:model.live="category_name"
                                class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" />
                            @error('category_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-1 flex items-end justify-end gap-2">
                            @if ($editing_category_id)
                                <button type="button" wire:click="cancelEditCategory"
                                    class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                            @endif
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                                {{ $editing_category_id ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-500 dark:text-slate-300">
                                    <th class="py-2 pr-4">Name</th>
                                    <th class="py-2 pr-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                @forelse(($categories ?? []) as $cat)
                                    <tr>
                                        <td class="py-2 pr-4 text-slate-900 dark:text-white">
                                            {{ (string) ($cat->name ?? '') }}
                                        </td>
                                        <td class="py-2 pr-4 text-right">
                                            <button type="button"
                                                wire:click="editCategory({{ (int) ($cat->id ?? 0) }})"
                                                class="px-3 py-1.5 text-xs font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Edit</button>
                                            <button type="button"
                                                wire:click="deleteCategory({{ (int) ($cat->id ?? 0) }})"
                                                class="ml-2 px-3 py-1.5 text-xs font-medium border rounded-md border-red-200 text-red-700 hover:bg-red-50 dark:border-red-800/60 dark:text-red-300 dark:hover:bg-red-900/20">Delete</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-3 text-slate-500 dark:text-slate-300">No
                                            categories
                                            yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product -> Category Mapping Modal -->
    <div x-show="mappingOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="mappingOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Map Item Name → Category
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Sets category for all items with the
                            selected product name{{ !is_null($branchId) ? ' (in this branch)' : '' }}.</div>
                    </div>
                    <button type="button" @click="mappingOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close mapping modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 space-y-4">
                    <form wire:submit.prevent="saveProductCategoryMapping" class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Item
                                Name</label>
                            <input type="text" list="product-name-options" wire:model.live="mapping_product_name"
                                placeholder="Type to search item name…"
                                class="mt-1 block h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" />
                            <datalist id="product-name-options">
                                @foreach ($productNames ?? [] as $pn)
                                    <option value="{{ (string) $pn }}"></option>
                                @endforeach
                            </datalist>
                            @error('mapping_product_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-200">Category</label>
                            <select wire:model.live="mapping_category_id"
                                class="mt-1 h-10 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                                <option value="">Select category</option>
                                @foreach ($categories ?? [] as $cat)
                                    <option value="{{ (int) ($cat->id ?? 0) }}">{{ (string) ($cat->name ?? '') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('mapping_category_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2 flex items-center justify-end gap-2">
                            <button type="button" @click="mappingOpen = false"
                                class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                                Save Mapping
                            </button>
                        </div>
                    </form>

                    <div class="text-xs text-slate-500 dark:text-slate-300">
                        Note: Only existing items are updated. New imports will need mapping again if item names change.
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        Required columns: <span class="font-medium">Branch ID</span>, <span
                            class="font-medium">Product
                            Name</span>, <span class="font-medium">Quality</span>,
                        <span class="font-medium">Total Weight</span>, <span
                            class="font-medium">ပန်းထိမ်အလျော့တွက်</span>,
                        <span class="font-medium">ပန်းထိမ် လက်ခ</span>, <span class="font-medium">ကျောက်ချိန်</span>.
                        Optional: <span class="font-medium">Barcode</span>, <span class="font-medium">Gold
                            Weight</span>,
                        <span class="font-medium">ကျောက်ဖိုး</span>, <span class="font-medium">အမြတ်အလျော့</span>,
                        <span class="font-medium">အမြတ်လက်ခ</span>.
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">Limits: max 12 unique batch IDs
                            and
                            120 total items per group. If exceeded, the system will auto-create new group(s) and
                            continue importing.</div>
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

    <!-- Update Modal (by barcode) -->
    <div x-show="updateOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="updateOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Update Existing Items</div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Updates items by matching
                            <span
                                class="font-medium">Barcode</span>{{ !is_null($branchId) ? ' (in this branch)' : '' }}.
                        </div>
                    </div>
                    <button type="button" @click="updateOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close update modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <form wire:submit.prevent="updateExistingByBarcode" class="p-4 space-y-4">
                    <div class="text-sm text-slate-600 dark:text-slate-200">
                        Required column: <span class="font-medium">Barcode</span>.
                        Optional update columns (if provided in the file): <span class="font-medium">Product
                            Name</span>,
                        <span class="font-medium">Quality</span>, <span class="font-medium">Gold Weight</span>,
                        <span class="font-medium">Total Weight</span>, <span
                            class="font-medium">ပန်းထိမ်အလျော့တွက်</span>,
                        <span class="font-medium">ပန်းထိမ် လက်ခ</span>, <span class="font-medium">ကျောက်ချိန်</span>,
                        <span class="font-medium">ကျောက်ဖိုး</span>, <span class="font-medium">အမြတ်အလျော့</span>,
                        <span class="font-medium">အမြတ်လက်ခ</span>.
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">If a barcode is not found, it will
                            be listed in the warnings panel.</div>
                    </div>

                    <div>
                        <input type="file" wire:model="updateFile" accept=".xlsx,.csv,.ods"
                            class="block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:text-slate-200 dark:file:bg-slate-700 dark:file:text-slate-100" />
                        @error('updateFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div style="display: none" wire:loading.flex wire:target="updateFile"
                            class="mt-2 text-sm text-slate-500">Uploading…</div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="updateOpen = false"
                            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700"
                            @disabled(!$updateFile) wire:loading.attr="disabled"
                            wire:target="updateFile,updateExistingByBarcode">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Modal (external_id + lot/serial by match) -->
    <div x-show="externalOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="externalOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Update External ID / Lot
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Matches existing items by weights +
                            <span class="font-medium">PO Ref</span> + stone price
                            rule{{ !is_null($branchId) ? ' (in this branch)' : '' }}.
                        </div>
                    </div>
                    <button type="button" @click="externalOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close external update modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <form wire:submit.prevent="updateExternalByMatch" class="p-4 space-y-4">
                    <div class="text-sm text-slate-600 dark:text-slate-200">
                        Required columns: <span class="font-medium">External ID</span>, <span class="font-medium">Lot
                            serial</span>,
                        <span class="font-medium">Purchase Order</span>, <span class="font-medium">Quality</span>,
                        <span class="font-medium">Total Weight</span>, <span class="font-medium">Kyauk Weight</span>,
                        <span class="font-medium">Gold smith detuction</span>, <span
                            class="font-medium">Labor-fee</span>,
                        <span class="font-medium">ကျောက်ဖိုး</span>.
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">Stone price rule: the file should
                            contain the
                            <span class="font-medium">half value</span> (the UI value); the system matches by comparing
                            (file value × 2) to the stored stone price.
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('jewelry.template_external_mapping') }}"
                                class="inline-flex items-center text-sm font-medium text-primary-600 hover:underline">
                                <x-icon name="download" class="w-4 h-4 mr-1" />
                                Download template
                            </a>
                        </div>
                    </div>

                    <div>
                        <input type="file" wire:model="externalFile" accept=".xlsx,.csv,.ods"
                            class="block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:text-slate-200 dark:file:bg-slate-700 dark:file:text-slate-100" />
                        @error('externalFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div style="display: none" wire:loading.flex wire:target="externalFile"
                            class="mt-2 text-sm text-slate-500">Uploading…</div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="externalOpen = false"
                            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700"
                            @disabled(!$externalFile) wire:loading.attr="disabled"
                            wire:target="externalFile,updateExternalByMatch">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Export Modal (filter + export) -->
    <div x-show="exportOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="exportOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-4xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Export Items</div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Filter by PO Ref / Barcode / Product
                            name, then export.</div>
                    </div>
                    <button type="button" @click="exportOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close export modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 space-y-4">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">PO Ref
                                (multi-select)</label>
                            <input type="text" wire:model.live.debounce.250ms="exportPoRefSearch"
                                placeholder="Search PO Ref…"
                                class="mt-1 block h-9 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" />
                            <select multiple wire:model.live="exportPoRefs" size="7"
                                class="mt-2 block w-full rounded-md border border-slate-300 bg-white p-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                                @foreach ($exportPoRefOptions ?? [] as $po)
                                    <option value="{{ (string) $po }}">{{ (string) $po }}</option>
                                @endforeach
                            </select>

                            @if (!empty($exportPoRefs ?? []))
                                <div
                                    class="mt-2 flex flex-wrap gap-2 rounded-md border border-slate-200 p-2 dark:border-slate-700 dark:bg-slate-900">
                                    @foreach ($exportPoRefs ?? [] as $po)
                                        <span
                                            class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-800 dark:bg-slate-700 dark:text-slate-100">
                                            <span class="break-all">{{ (string) $po }}</span>
                                            <button type="button"
                                                wire:click="removeExportPoRef(@js((string) $po))"
                                                class="text-slate-600 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white"
                                                aria-label="Remove">
                                                ×
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Barcode
                                (multi-select)</label>
                            <input type="text" wire:model.live.debounce.250ms="exportBarcodeSearch"
                                placeholder="Search barcode…"
                                class="mt-1 block h-9 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" />
                            <select multiple wire:model.live="exportBarcodes" size="7"
                                class="mt-2 block w-full rounded-md border border-slate-300 bg-white p-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                                @foreach ($exportBarcodeOptions ?? [] as $bc)
                                    <option value="{{ (string) $bc }}">{{ (string) $bc }}</option>
                                @endforeach
                            </select>

                            @if (!empty($exportBarcodes ?? []))
                                <div
                                    class="mt-2 flex flex-wrap gap-2 rounded-md border border-slate-200 p-2 dark:border-slate-700 dark:bg-slate-900">
                                    @foreach ($exportBarcodes ?? [] as $bc)
                                        <span
                                            class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-800 dark:bg-slate-700 dark:text-slate-100">
                                            <span class="break-all">{{ (string) $bc }}</span>
                                            <button type="button"
                                                wire:click="removeExportBarcode(@js((string) $bc))"
                                                class="text-slate-600 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white"
                                                aria-label="Remove">
                                                ×
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Product name
                                (multi-select)</label>
                            <input type="text" wire:model.live.debounce.250ms="exportProductNameSearch"
                                placeholder="Search product name…"
                                class="mt-1 block h-9 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" />
                            <select multiple wire:model.live="exportProductNames" size="7"
                                class="mt-2 block w-full rounded-md border border-slate-300 bg-white p-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                                @foreach ($exportProductNameOptions ?? [] as $pn)
                                    <option value="{{ (string) $pn }}">{{ (string) $pn }}</option>
                                @endforeach
                            </select>

                            @if (!empty($exportProductNames ?? []))
                                <div
                                    class="mt-2 flex flex-wrap gap-2 rounded-md border border-slate-200 p-2 dark:border-slate-700 dark:bg-slate-900">
                                    @foreach ($exportProductNames ?? [] as $pn)
                                        <span
                                            class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-800 dark:bg-slate-700 dark:text-slate-100">
                                            <span class="break-all">{{ (string) $pn }}</span>
                                            <button type="button"
                                                wire:click="removeExportProductName(@js((string) $pn))"
                                                class="text-slate-600 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white"
                                                aria-label="Remove">
                                                ×
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-300">
                        Tip: Use Ctrl/Shift to multi-select. Export includes all matched items. Preview shows up to 200
                        matches.
                    </div>

                    <div class="overflow-hidden border rounded-md border-slate-200 dark:border-slate-700">
                        <div class="max-h-[50vh] overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-900/40 sticky top-0 z-10">
                                    <tr class="text-left text-slate-500 dark:text-slate-300">
                                        <th class="px-4 py-2">Product</th>
                                        <th class="px-4 py-2">Barcode</th>
                                        <th class="px-4 py-2">PO Ref</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    @forelse(($exportPreviewItems ?? []) as $it)
                                        <tr wire:key="export-preview-item-{{ (int) ($it->id ?? 0) }}">
                                            <td class="px-4 py-2 text-slate-900 dark:text-white">
                                                {{ (string) ($it->product_name ?? '') }}
                                            </td>
                                            <td class="px-4 py-2 text-slate-700 dark:text-slate-200">
                                                {{ (string) ($it->barcode ?? '') }}
                                            </td>
                                            <td class="px-4 py-2 text-slate-700 dark:text-slate-200">
                                                {{ (string) ($it->po_reference ?? '') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-slate-500 dark:text-slate-300">
                                                Select at least one filter (PO Ref / Barcode / Product name) to load a
                                                preview list.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if (!empty($exportPreviewHasMore))
                        <div class="text-xs text-slate-500 dark:text-slate-300">
                            Showing first 200 matched items. Narrow filters to see fewer.
                        </div>
                    @endif

                    @error('exportFilters')
                        <div class="text-sm text-red-600">{{ $message }}</div>
                    @enderror

                    <div class="flex items-center justify-between gap-2">
                        <button type="button" wire:click="resetExport"
                            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Reset</button>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="exportOpen = false"
                                class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Close</button>
                            <button type="button" wire:click="exportFilteredItems"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                                <x-icon name="download" class="w-4 h-4 mr-2" />
                                Export
                            </button>
                        </div>
                    </div>
                </div>
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
                                @php
                                    $purchaserName = (string) ($g->purchaseBy?->name ?? '—');
                                    $skillGrade = strtolower((string) ($g->skillGradeLabel() ?? ''));

                                    $skillIcon = null;
                                    if ($skillGrade === 'excellent') {
                                        $skillIcon = '👑';
                                    } elseif ($skillGrade === 'good') {
                                        $skillIcon = '⭐';
                                    } elseif ($skillGrade === 'fighting') {
                                        $skillIcon = '💪';
                                    }
                                @endphp

                                <span class="inline-flex items-center gap-1.5">
                                    <span>{{ $purchaserName }}</span>
                                    @if (!is_null($skillIcon))
                                        <span class="text-base leading-none"
                                            title="Skill grade: {{ $g->skillGradeLabel() }}"
                                            aria-label="Skill grade: {{ $g->skillGradeLabel() }}">
                                            {{ $skillIcon }}
                                        </span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                @php
                                    $status = (string) ($g->purchase_status ?? '');
                                    $label = $status !== '' ? ucwords(str_replace('_', ' ', $status)) : '—';

                                    $badgeClass = match ($status) {
                                        'done'
                                            => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
                                        'processing'
                                            => 'bg-amber-100 text-amber-900 dark:bg-amber-900/20 dark:text-amber-200',
                                        'not_started'
                                            => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                                        default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                                    };
                                @endphp

                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                {{ (int) ($g->items_count ?? 0) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                @php
                                    $itemsCount = (int) ($g->items_count ?? 0);
                                    $registeredCount = (int) ($g->registered_items_count ?? 0);
                                    $allRegistered = $itemsCount > 0 && $registeredCount === $itemsCount;
                                    $purchaseStatus = (string) ($g->purchase_status ?? '');
                                @endphp

                                @if ($purchaseStatus !== 'done')
                                    <span
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">Not
                                        Start</span>
                                @elseif ($itemsCount === 0)
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
