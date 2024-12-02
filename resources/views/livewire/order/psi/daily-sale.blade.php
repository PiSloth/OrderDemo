<div>
    <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Product
                </th>
                <th scope="col" class="px-6 py-3">
                    ပုံသဏ္ဍာန်
                </th>
                <th scope="col" class="px-6 py-3">
                    လက်ကျန်
                </th>
                <th scope="col" class="px-6 py-3 sr-only">
                    Action
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr
                    class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">

                    <td class="p-4">
                        <img class="w-16 max-w-full max-h-full cursor-pointer md:w-32"
                            src="{{ asset('storage/' . $product->image) }}"
                            @click="$openModal('{{ $product->id }}')" />
                        <x-modal wire:model='{{ $product->id }}'>
                            <img src="{{ asset('storage/' . $product->image) }}" class="mb-6 bg-white rounded-lg">
                        </x-modal>
                    </td>
                    <td class="px-6 py-4">
                        {{ $product->detail }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $product->balance }}
                    </td>
                    <td class="px-6 py-4">
                        <x-button label="update" @click="$openModal('saleModal')"
                            wire:click='initializeUpdate({{ $product->id }}, {{ $product->stock_id }})' />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Sale  --}}
    <x-modal wire:model="saleModal">
        <x-card title="Daily Sale">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-input type="date" label="Date" wire:model.live='sale_date' placeholder="Sale Date" />
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
