<div class="relative p-2 overflow-visible sm:rounded-lg">
    <div class="pb-3 bg-white dark:bg-gray-800">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="w-full sm:w-auto">
                <label for="table-search" class="sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 flex items-center pointer-events-none rtl:inset-r-0 start-0 ps-3">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <input type="text" id="table-search" wire:model.live='detail'
                        class="block w-full sm:w-80 pt-2 text-sm text-gray-900 border border-gray-300 rounded-lg ps-10 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Search for items">
                </div>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span
                    class="text-sm text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($sale_history_date)->format('M j') }}
                    နေ့၏ မှတ်တမ်း</span>
                <x-button label="Filter" icon="filter" @click="$openModal('filterModal')" class="whitespace-nowrap" />
            </div>
        </div>
    </div>


    <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
        @foreach ($products as $product)
            <div>
                <article wire:click="initializeUpdate({{ $product->stock_id }})" @click="$openModal('dailySaleModal')"
                    class="group relative flex h-64 md:h-72 flex-col justify-end overflow-hidden rounded-2xl bg-gray-900/80 hover:cursor-pointer ring-1 ring-gray-200 dark:ring-gray-700">
                    <img class="absolute inset-0 h-full w-full object-cover transition-transform duration-200 group-hover:scale-[1.02]"
                        src="{{ asset('storage/' . $product->image) }}" alt="product image" />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/50"></div>
                    @if ($product->sale_qty !== null)
                        <span
                            class="absolute right-2 top-2 z-10 rounded-full bg-emerald-600 px-2 py-0.5 text-xs font-medium text-white">{{ $product->sale_qty }}</span>
                    @endif
                    <div class="z-10 p-4">
                        <h3 class="text-xl md:text-2xl font-semibold text-white">{{ $product->weight }} <i
                                class="hidden md:inline">g</i></h3>
                        <p class="mt-1 text-sm text-gray-300 max-h-10 overflow-hidden">{{ $product->detail }}</p>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $products->links() }}</div>

    <x-modal wire:model='dailySaleModal'>
        <x-card title="Daily Sale">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="flex items-start justify-center">
                    <div class="w-full max-w-sm rounded-lg bg-white p-2 dark:bg-gray-800">
                        <img src="{{ asset('storage/' . $dailySale->image) }}" alt="selected product"
                            class="w-full rounded-md object-contain" />
                    </div>
                </div>
                <div>
                    <div class="flex flex-col gap-1 p-2 rounded border border-gray-200 dark:border-gray-700">
                        <span class="text-gray-900 dark:text-gray-100">{{ $dailySale->design }}</span>
                        <span class="text-gray-700 dark:text-gray-300">{{ $dailySale->detail }}</span>
                        <span class="text-gray-700 dark:text-gray-300">{{ $dailySale->weight }} g •
                            {{ $dailySale->length }} {{ $dailySale->uom }}</span>
                        <div class="mt-1 flex gap-2 text-sm">
                            <span
                                class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">Counter
                                {{ $dailySale->balance }} qty</span>
                            <span
                                class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">Today
                                {{ $dailySale->sale_qty ? $dailySale->sale_qty : 0 }} qty</span>
                        </div>
                    </div>

                    {{-- Sale data input   --}}
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-input label="Date" type="date" wire:model.live='sale_date' />
                        <x-input placeholder="input sale quantity" label="Quantity" type="number" min="0"
                            wire:model.live='sale_qty' />
                    </div>
                </div>
            </div>

            <x-slot name="footer">
                <div class="flex w-full items-center justify-between">
                    <x-button flat label="History" icon="clock" @click="$openModal('historyModal')"
                        wire:click='initializeSaleHsitory({{ $dailySale->stock_id }})' />
                    <div class="flex justify-end gap-x-3">
                        <x-button flat label="Cancel" x-on:click="close" />
                        <x-button primary label="Save" right-icon="save" wire:click='updateSale'
                            wire:loading.attr="disabled" />
                    </div>
                </div>
            </x-slot>
        </x-card>
    </x-modal>

    <x-modal.card title="Filter" wire:model="filterModal">
        <div>
            <x-datetime-picker wire:model.live.debounce="sale_history_date" without-time='true' label="Date"
                placeholder="Now" />

            @can('isSuperAdmin')
                <label for="branch" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Branch</label>
                <select id="branch"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    wire:model.live='branch_id'>
                    <option value="" selected>Select a Branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                    @endforeach
                </select>
            @endcan
        </div>
    </x-modal.card>

    {{-- Daily Sale HISTORY MODAL --}}
    <x-modal.card title="Sale Data input histories" blur wire:model="historyModal">
        <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                <thead
                    class="sticky top-0 z-10 text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Date</th>
                        <th scope="col" class="px-6 py-3">Quantity</th>
                        <th scope="col" class="px-6 py-3">Write By</th>
                        <th scope="col" class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($saleHistories as $data)
                        <tr
                            class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $data->sale_date }}</th>
                            <td class="px-6 py-4">{{ $data->qty }}</td>
                            <td class="px-6 py-4">{{ $data->user->name }}</td>
                            <td class="px-6 py-4">
                                <button @click="$openModal('editSaleRecordModal')"
                                    wire:click='initializeDailySale({{ $data->id }})'
                                    class="text-blue-500">Edit</button>
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
        </div>
    </x-modal.card>

    {{-- Sale  --}}
    <x-modal wire:model="editSaleRecordModal">
        <x-card title="Daily Sale">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2">
                {{-- <x-datetime-picker label="Start Date" placeholder="Sale Date" parse-format="DD-MM-YY HH:mm"
                    wire:model.live="sale_date" without-time=true /> --}}
                <x-input label="Date" placeholder="pick a date" type="date" wire:model.live='sale_date' />
                <x-input placeholder="input sale quantity" label="Quantity" wire:model.live='sale_qty' />
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='updateSale' green />
                </div>
            </x-slot>
        </x-card>
    </x-modal>
</div>
