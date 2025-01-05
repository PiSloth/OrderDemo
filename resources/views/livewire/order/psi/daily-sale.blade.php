<div class="relative p-2 overflow-auto shadow-md sm:rounded-lg">
    <div class="pb-4 bg-white dark:bg-gray-900">
        <label for="table-search" class="sr-only">Search</label>
        <div class="relative mt-1">
            <div class="absolute inset-y-0 flex items-center pointer-events-none rtl:inset-r-0 start-0 ps-3">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                </svg>
            </div>
            <input type="text" id="table-search" wire:model.live='detail'
                class="block pt-2 text-sm text-gray-900 border border-gray-300 rounded-lg ps-10 w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Search for items">
        </div>
    </div>
    <div class="flex flex-col">
        <span>{{ \Carbon\Carbon::parse($sale_history_date)->format('M j') }} နေ့၏ မှတ်တမ်း</span>
        <div class="mt-2">
            <x-button label="filter" negative icon="filter" @click="$openModal('filterModal')" />
        </div>
    </div>


    <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
        @foreach ($products as $product)
            <div class="">
                <article wire:click="initializeUpdate({{ $product->stock_id }})"
                    class="relative flex flex-col justify-end max-w-sm px-8 pt-40 pb-8 mx-auto mt-24 overflow-hidden hover:cursor-pointer isolate rounded-2xl"
                    @click="$openModal('dailySaleModal')">
                    <img class="absolute inset-0 object-cover w-full h-full rounded-lg"
                        src="{{ asset('storage/' . $product->image) }}" />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40"></div>
                    <h3 class="z-10 mt-3 text-xl font-bold text-white lg:text-3xl md:text-3xl">{{ $product->weight }} <i
                            class="hidden md:inline">g</i></h3>
                    <div class="z-10 overflow-hidden text-sm leading-6 text-gray-300 gap-y-1">{{ $product->detail }}
                    </div>

                    @if ($product->sale_qty !== null)
                        <div class="z-10 overflow-hidden text-sm leading-6 text-gray-300 gap-y-1">
                            <span class="px-2 text-white bg-green-600 rounded-full">{{ $product->sale_qty }}</span>
                        </div>
                    @endif
                </article>
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $products->links() }}</div>

    <x-modal wire:model='dailySaleModal'>
        <x-card title="Daily Sale">

            <div class="mx-auto bg-gray-300 w-72">
                <div>
                    <img src="{{ asset('storage/' . $dailySale->image) }}" class="mb-6 bg-white rounded-lg " />
                </div>
            </div>
            <div class="flex flex-col p-2 m-2 border rounded border-sky-100">
                <span>{{ $dailySale->design }}</span>
                <span>{{ $dailySale->detail }}</span>
                <span>{{ $dailySale->weight }} g</span>
                <span>{{ $dailySale->length }} {{ $dailySale->uom }}</span>
                <span> {{ $dailySale->balance }} qty (Counter လက်ကျန်)</span>
                <span> {{ $dailySale->sale_qty ? $dailySale->sale_qty : 0 }} qty (ယနေ့ အရောင်း)</span>
            </div>

            {{-- Sale data input   --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- <x-datetime-picker without-time='no' type="date" label="Sale Date"
                    placeholder="Sale Date" wire:model.live="sale_date" /> --}}
                <x-input label="Date" type="date" wire:model.live='sale_date' />
                <x-input placeholder="input sale quantity" label="Quantity" wire:model.live='sale_qty' />
            </div>

            <x-slot name="footer">
                <div class="flex justify-between">
                    <button @click="$openModal('historyModal')" class="px-2 py-1 rounded bg-slate-200"
                        wire:click='initializeSaleHsitory({{ $dailySale->stock_id }})'>history</button>
                    <div class="flex justify-end gap-x-4">
                        <x-button flat label="Cancel" x-on:click="close" />
                        <x-button primary label="save" right-icon="save" wire:click='updateSale' green />
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
                <label for="branch" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Category</label>
                <select id="branch"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    wire:model.live='branch_id'>
                    <option value="" selected>Select a Branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endcan
        </div>
    </x-modal.card>

    {{-- Daily Sale HISTORY MODAL --}}
    <x-modal.card title="Sale Data input histories" blur wire:model="historyModal">
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Date
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Quantity
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Write By
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($saleHistories as $data)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $data->sale_date }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $data->qty }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->user->name }}
                        </td>
                        <td class="px-6 py-4">
                            <button @click="$openModal('editSaleRecordModal')"
                                wire:click='initializeDailySale({{ $data->id }})' class="text-blue-500">
                                Edit</button>
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
