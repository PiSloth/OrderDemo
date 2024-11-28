<div>
    {{-- @dd($product) --}}
    <div class="flex px-1 py-2 mb-2 text-sm bg-yellow-100 rounded">
        <a href="{{ route('mainboard') }}" wire:navigate class="underline hover:text-blue-500">Product Dashboard</a>
        <x-icon name="arrow-narrow-right" class="w-5 h-5" solid />
        <a href="{{ route('focus', ['brch' => $branch_id, 'prod' => $product_id]) }}" wire:navigate
            class="underline hover:text-blue-500">Focus</a>
        <x-icon name="arrow-narrow-right" class="w-5 h-5" solid />
        <a href="{{ route('order_detail', ['prod' => $product_id, 'brch' => $branch_id]) }}" wire:navigate
            class="underline hover:text-blue-500">Order</a>
        <x-icon name="arrow-narrow-right" class="w-5 h-5" solid />
        <a href="#" class="font-bold">Detial</a>
    </div>
    <span class="uppercase">{{ $order->user->branch->name }}</span>
    <div class="flex flex-wrap gap-4 mt-4">
        <img src="{{ asset('storage/' . $order->branchPsiProduct->psiProduct->productPhoto->image) }}"
            class="w-48 max-w-full max-h-full rounded md:w-42" />
        <div>
            <div class="p-2 mb-4 border-2 border-gray-600 border-dotted rounded">
                <div class="grid grid-cols-2 gap-3">
                    <span class="font-bold">Category</span>
                    <span>{{ $order->branchPsiProduct->psiProduct->category->name }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <span class="font-bold">Design</span>
                    <span>{{ $order->branchPsiProduct->psiProduct->design->name }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <span class="font-bold">Length</span>
                    <span>{{ $order->branchPsiProduct->psiProduct->length }}
                        <i>{{ $order->branchPsiProduct->psiProduct->uom->name }}</i></span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <span class="font-bold">Weight</span>
                    <span>{{ $order->branchPsiProduct->psiProduct->weight }} <i>g</i></span>
                </div>
            </div>
            <span class="mt-2 font-semibold">{{ $order->branchPsiProduct->psiProduct->shape->name }}</span>
        </div>
    </div>

    <div class="relative mb-8 overflow-x-auto shadow-md sm:rounded-lg">
        <h1 class="text-xl">Order မှာယူထားသောစာရင်း</h1>
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Supplier name
                    </th>
                    <td scope="col" class="px-6 py-4">
                        Order Qty
                    </td>
                    <td scope="col" class="px-6 py-4">
                        Arrivals Qty
                    </td>
                    <td scope="col" class="px-6 py-4">
                        QC Passed
                    </td>
                    <td scope="col" class="px-6 py-4">
                        Error Qty
                    </td>
                    <td scope="col" class="px-6 py-4">
                        Transfered
                    </td>

                    <th scope="col" class="px-6 py-3">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $order->psiPrice->supplier->name }}
                    </th>
                    <td class="px-6 py-4">

                        {{ $order->order_qty }}

                    </td>
                    <td class="px-6 py-4">
                        @if ($order->arrival_qty !== null)
                            {{ $order->arrival_qty }}
                        @else
                            <x-button @click="$openModal('arrivalModal')" label="Arrival" blue />
                        @endif
                        {{-- <x-button @click="$openModal('arrivalModal')" label="Arrival" blue /> --}}
                    </td>

                    <td class="px-6 py-4">
                        @if ($order->qc_passed_qty !== null)
                            {{ $order->qc_passed_qty }}
                        @else
                            @if ($order->arrival_qty !== null)
                                <x-input type="number" wire:model.live='qc_passed_qty'
                                    placeholder="input & hit '\Enter'" wire:keyup.enter='updateQC' />
                            @endif
                        @endif
                    </td>

                    <td class="px-6 py-4">
                        @if ($order->error_qty !== null)
                            {{ $order->error_qty }}
                        @else
                            @if ($order->qc_passed_qty !== null)
                                <x-input type="number" wire:model.live='error_qty' placeholder="input & hit '\Enter'"
                                    wire:keyup.enter='updateError' />
                            @endif
                        @endif
                    </td>

                    <td class="px-6 py-4">
                        @if ($order->transfer_qty !== null)
                            {{ $order->transfer_qty }}
                        @else
                            @if ($order->error_qty !== null)
                                <x-input type="number" wire:model.live='transfer_qty'
                                    placeholder="input & hit '\Enter'" wire:keyup.enter='updateTransfer' />
                            @endif
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <x-modal.card title="Arrival" blur wire:model="arrivalModal">
        <x-input type="number" label="Arrival Qty" wire:model.live='arrival_qty' placeholder="input number" />

        @if ($skip == false)
            <x-input type="date" label="Photo Shooting Schedule" wire:model.live='schedule_date'
                placeholder="Data here" />
        @else
            <x-textarea label="remark" placeholder="အဘယ့်ကြောင့် ပုံရိုက်ရန် မလိုအပ်သည်ကို မှတ်ချက်ပြုပါ"
                wire:model.live='skip_note' />
        @endif

        <div class="my-3">
            <x-checkbox class="text-teal-500 bg-gray-200" id="left-label" left-label="Skip" wire:click="skipFun" />
        </div>
        <x-slot name="footer">
            <div class="flex justify-between gap-x-4">
                <x-button flat negative label="Delete" x-on:click="close" />

                <div class="flex">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="Save" wire:click="updateArrival" />
                </div>
            </div>
        </x-slot>
    </x-modal.card>

</div>
