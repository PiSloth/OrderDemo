<div>
    <div class="w-48 mb-4">
        @can('isSuperAdmin')
            <label for="branch" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Branch Name</label>
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

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Product name
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Photo
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Design
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Stock Balance
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $item)
                    <tr x-data="{ editable: false, value: {{ $item->psiStock->inventory_balance }}, }"
                        class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            <x-badge positive>{{ $loop->index + 1 }}</x-badge>
                            {{ $item->psiProduct->shape->name }} / {{ $item->psiProduct->weight }} g /
                            {{ $item->psiProduct->length }} {{ $item->psiProduct->uom->name }}
                        </th>
                        <td>
                            <img class="w-12 rounded-full cursor-pointer md:w-16 md:rounded-lg"
                                wire:click='selectedImage({{ $item->psiProduct->productPhoto->id }})'
                                src="{{ asset('/storage/' . $item->psiProduct->productPhoto->image) }}" </td>
                        <td class="px-6 py-4">
                            {{ $item->psiProduct->quality->name }} / {{ $item->psiProduct->design->name }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="w-24">
                                <template x-if="!editable" class="flex gap-1">
                                    {{-- <x-icon name="pencil" solid class="w-4 h-4" /> --}}
                                    <x-button @click="editable = true" rightIcon="pencil"
                                        label="{{ $item->psiStock->inventory_balance }}" />{{-- <span></span> --}}
                                </template>
                                <template x-if="editable">

                                    <input type="number" required
                                        class="w-20 transition-all duration-300 rounded-lg focus:outline-none focus:ring-0 focus:bg-gray-50 focus:border-blue-500"
                                        x-model="value"
                                        @keydown.enter="editable = false;$wire.updateValue({{ $item->psiStock->id }},value)"
                                        x-init="$nextTick(() => $el.focus())" @blur="editable = false" />
                                </template>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            {{-- <span style="display: none" class="text-sm text-red-400" x-show="editable">enter
                                ခေါက်ပါ</span> --}}
                            <x-button class="ml-1" icon="clock"
                                wire:click='transactionHistory({{ $item->psiStock->id }})' />
                        </td>
                    </tr>
                    <tr class="border-b dark:border-gray-700">
                        <td colspan="4" class="px-6 py-2 text-slates-400">
                            {{ $item->psiProduct->remark }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-modal wire:model='imageModal'>
        <img src="{{ asset('/storage/' . $image) }}" alt="product image" />
    </x-modal>
    {{-- Daily Sale HISTORY MODAL --}}
    <x-modal.card title="Stock update histories" blur wire:model="historyModal">
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Date
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Qty
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Remark
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Written By
                    </th>
                    <th scope="col" class="px-6 py-3 sr-only">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($transactions as $data)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ \Carbon\Carbon::parse($data->created_at)->format('M j,y') }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $data->qty }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->remark ?? '' }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->user->name ?? '' }}
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
</div>
<script>
    Livewire.on('openModal', (name) => {
        $openModal(name);
    })
</script>
