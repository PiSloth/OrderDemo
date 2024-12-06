<div class="relative p-2 overflow-x-auto shadow-md sm:rounded-lg">
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





    <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
        @foreach ($products as $product)
            <div class="">
                <article wire:click="initializeUpdate({{ $product->stock_id }})"
                    class="relative flex flex-col justify-end max-w-sm px-8 pt-40 pb-8 mx-auto mt-24 overflow-hidden hover:cursor-pointer isolate rounded-2xl"
                    @click="$openModal('{{ $product->id }}')">
                    <img class="absolute inset-0 object-cover w-full h-full rounded-lg"
                        src="{{ asset('storage/' . $product->image) }}" />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40"></div>
                    <h3 class="z-10 mt-3 text-xl font-bold text-white lg:text-3xl md:text-3xl">{{ $product->weight }} <i
                            class="hidden md:inline">g</i></h3>
                    <div class="z-10 overflow-hidden text-sm leading-6 text-gray-300 gap-y-1">{{ $product->detail }}
                    </div>
                    @if ($product->sale_qty > 0)
                        <div class="z-10 overflow-hidden text-sm leading-6 text-gray-300 gap-y-1">
                            <span class="px-2 text-white bg-green-600 rounded-full">{{ $product->sale_qty }}</span>
                        </div>
                    @endif
                </article>
                <x-modal wire:model='{{ $product->id }}'>
                    <x-card title="Daily Sale">
                        <div class="bg-gray-300 w-72">
                            <div>
                                <img src="{{ asset('storage/' . $product->image) }}"
                                    class="mb-6 bg-white rounded-lg " />
                            </div>
                        </div>
                        <div class="flex flex-col p-2 m-2 border rounded border-sky-100">
                            <span>{{ $product->design }}</span>
                            <span>{{ $product->detail }}</span>
                            <span>{{ $product->weight }} g</span>
                            <span>{{ $product->length }} {{ $product->uom }}</span>
                            <span> {{ $product->balance }} qty (Counter လက်ကျန်)</span>
                            <span> {{ $product->sale_qty ? $product->sale_qty : 0 }} qty (ယနေ့ အရောင်း)</span>

                        </div>
                        {{-- Sale data input   --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-input type="date" label="Sale Date" placeholder="Sale Date"
                                wire:model.live="sale_date" />
                            <x-input placeholder="input sale quantity" label="Quantity" wire:model.live='sale_qty' />
                        </div>

                        <x-slot name="footer">
                            <div class="flex justify-between">
                                <button @click="$openModal('historyModal')" class="px-2 py-1 rounded bg-slate-200"
                                    wire:click='initializeSaleHsitory({{ $product->stock_id }})'>history</button>
                                <div class="flex justify-end gap-x-4">
                                    <x-button flat label="Cancel" x-on:click="close" />
                                    <x-button primary label="save" right-icon="save" wire:click='updateSale' green />
                                </div>
                            </div>
                        </x-slot>
                    </x-card>
                </x-modal>
            </div>
        @endforeach

    </div>

    {{-- Daily Sale HISTORY MODAL --}}
    <x-modal.card title="Add this Product to PSI" blur wire:model="historyModal">
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
                <x-datetime-picker label="Start Date" placeholder="Sale Date" parse-format="DD-MM-YY HH:mm"
                    wire:model.live="sale_date" without-time=true />
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
