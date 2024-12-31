<div>
    {{-- @dd($product) --}}
    <div class="px-1 py-2 mb-2 text-sm bg-yellow-100 rounded">
        <a href="{{ route('mainboard') }}" wire:navigate class="underline hover:text-blue-500">Product Dashboard</a> > <a
            href="#" class="font-bold">Focus</a>
    </div>
    <div class="mt-4 "><span class="px-4 py-2 font-bold uppercase bg-yellow-200 rounded">{{ $branchName }}</span></div>
    <div class="flex flex-wrap gap-4 mt-4">
        <img src="{{ asset('storage/' . $product->productPhoto->image) }}"
            class="w-48 max-w-full max-h-full rounded md:w-42" />
        <div>
            <div class="p-2 mb-4 border-2 border-gray-600 border-dotted rounded">
                <div class="grid grid-cols-2 gap-3">
                    <span class="font-bold">Category</span>
                    <span>{{ $product->category->name }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <span class="font-bold">Design</span>
                    <span>{{ $product->design->name }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <span class="font-bold">Length</span>
                    <span>{{ $product->length }} <i>{{ $product->uom->name }}</i></span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <span class="font-bold">Weight</span>
                    <span>{{ $product->weight }} <i>g</i></span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <span class="font-bold">နည်းပညာ</span>
                    <span>{{ $product->manufactureTechnique->name }}</span>
                </div>
            </div>
            <span class="mt-2 font-semibold">{{ $product->shape->name }}</span>
        </div>
    </div>
    {{-- lead time container --}}
    <div class="my-8">
        <strong>Order မှာရန်ကြာမြင့်ချိန် </strong>
        <span
            class="text-red-500">{{ $productLeadDay->leadDay > 0 ? ceil($productLeadDay->leadDay) : 'Not found any' }}
            ရက်အတွင်း</span> ရောင်းရနိုင်ချေရှိသော <span class="underline">တစ်ရက်၏ </span>ရောင်းအားခန့်မှန်းချက်ကို
        တွက်ချက်ထည့်သွင်းပါ။
        <a href="{{ route('price', ['prod' => $product_id, 'bch' => $branch_id]) }}" wire:navigate>
            <small class="italic text-blue-500 underline">Goto Order</small>
        </a>
    </div>
    <hr />


    <div class="w-full mt-4 md:w-1/2">
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead>
                <tr>
                    <th scope="col">Year</th>
                    <th scope="col">Month</th>
                    <th scope="col">Focus</th>
                    <th scope="col">Result</th>
                    <th scope="col">Diff</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pastResult as $data)
                    @if ($data['month'])
                        <tr>
                            <td>{{ $data['year'] }}</td>
                            <td>{{ $data['month'] }}</td>
                            <td>{{ $data['avgFocus'] }}</td>
                            <td>{{ $data['avgRealSale'] }}</td>
                            <td>{{ $data['avgRealSale'] - $data['avgFocus'] }}</td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td>-</td>
                    <td>-</td>
                    <td><x-button xs @click="$openModal('focusModal')" icon="plus">Foucs</x-button></td>
                    <td><x-button xs @click="$openModal('saleModal')" icon="plus">Sale</x-button></td>
                    <td>-</td>
                </tr>
            </tbody>
        </table>
    </div>





    {{-- <div class="grid w-full grid-cols-2 gap-2 mt-8 md:w-1/2">
        <x-input type="date" label="Date" wire:model.live='sale_date' placeholder="Sale Date" />
        <x-input placeholder="input sale quantity" label="Quantity" wire:model='sale_quantity' />
        <x-button blue wire:click='saleUpdate' icon="save">save</x-button>
    </div> --}}

    {{-- Foucs HISTORY MODAL --}}
    <x-modal.card title="Focus History" blur wire:model="focusModal">
        <div class="container mx-auto my-10 overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">
                            Created at
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Quantity
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Created by
                        </th>

                        <th scope="col" class="px-6 py-3">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($foucsHistories as $data)
                        <tr
                            class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ date_format($data->created_at, 'd M, Y') }}
                            </th>
                            <td class="px-6 py-4">
                                {{ $data->qty }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $data->user->name }}
                            </td>

                            <td class="px-6 py-4">
                                <a href="#" class="text-blue-500" wire:navigate>
                                    Detail</a>
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
        <x-slot name="footer">
            <div class="flex justify-between gap-x-4">
                <x-button outline sky label="Fous သတ်မှတ်ခြင်း" x-on:click="$openModal('compareFocusModal')" />
                {{-- <div class="flex">
                    <x-input placeholder="per/day" label="Sale Focus/ day" wire:model.live='focus_quantity' />
                    <x-button class="mt-6 ms-4" icon="save" label="save" outline green wire:click='focusSave' />
                </div> --}}
                {{-- <x-button primary label="save" right-icon="save" wire:click='createOrder' green /> --}}
            </div>
        </x-slot>
    </x-modal.card>

    {{-- Compare with other gram --}}
    <x-modal.card title="' {{ $product->shape->name }} ' အတွက် အခြား အရောင်းခန့်မှန်းချက်များ" blur
        wire:model="compareFocusModal">
        <div class="container mx-auto my-10 overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">
                            Product
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Gram
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Foucs Qty
                        </th>

                        <th scope="col" class="px-6 py-3 sr-only">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row" rowspan="{{ count($compareFocus) }}"
                            class="px-6 py-4 font-medium text-gray-900 border border-gray-100 dark:border-slate-50 whitespace-nowrap dark:text-white">
                            {{ $product->shape->name }}
                        </th>
                        @php
                            $totalFocus = 0;
                        @endphp
                        @forelse ($compareFocus as $data)
                            <td class="px-6 py-4 border border-gray-100">
                                {{ $data->weight }} <i>g</i>
                            </td>
                            <td class="px-6 py-4 border border-gray-100">
                                {{ $data->latest_focus_qty }}
                            </td>

                            <td class="px-6 py-4 border border-gray-100">
                                <a href="#" class="text-blue-500" wire:navigate>
                                    Detail</a>
                            </td>
                    </tr>
                    @php
                        $totalFocus += $data->latest_focus_qty;
                    @endphp
                @empty
                    <tr>
                        <td colspan="4">
                            <center>There's no records yet</center>
                        </td>
                    </tr>
                    @endforelse
                    <tr
                        class="border border-gray-100 odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <td scope="col" colspan="2"
                            class="px-6 py-4 font-medium text-right text-gray-900 whitespace-nowrap dark:text-white">
                            <span>{{ $branchName }} အတွက် စုစုပေါင်းခန့်မှန်းထားသော ရောင်းအား</span>
                        </td>
                        <td class="px-6 py-4 border border-gray-100">{{ $totalFocus }} <small>pcs per Day</small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between gap-x-4">
                <x-button flat label="Cancel" x-on:click="close" />
                <div class="flex">
                    <x-input placeholder="per/day" label="Focus for {{ $product->weight }} g"
                        wire:model.live='focus_quantity' />
                    <x-button class="mt-6 ms-4" icon="save" label="save" outline green
                        wire:click='focusSave' />
                </div>
                {{-- <x-button primary label="save" right-icon="save" wire:click='createOrder' green /> --}}
            </div>
        </x-slot>
    </x-modal.card>

    {{-- Sale  --}}
    <x-modal wire:model="saleModal">
        <x-card title="Daily Sale">
            {{-- <div class="grid grid-cols-2 gap-4 sm:grid-cols-1">
                <x-input type="date" label="Date" wire:model.live='sale_date' placeholder="Sale Date" />
                <x-input placeholder="input sale quantity" label="Quantity" wire:model='sale_qty' />
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='saleUpdate' green />
                </div>
            </x-slot> --}}
        </x-card>
    </x-modal>
</div>
