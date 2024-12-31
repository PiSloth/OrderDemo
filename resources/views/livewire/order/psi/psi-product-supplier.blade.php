<div>
    @can('isAGM')
        <div class="w-1/4 mb-3 min-w-52">
            <label for="lead_day">Product အတွက် Lead Day သတ်မှတ်ပါ။</label>
            <x-input id="lead_day" type="number" wire:model='branch_lead_day' wire:keydown.enter='createLeadDay'
                placeholder="Branch lead day" />
            <span>initial lead day {{ $initial_lead_day }}</span>
        </div>
    @endcan
    <div class="px-1 py-2 mb-2 text-sm bg-yellow-100 rounded">
        <a href="{{ route('mainboard') }}" wire:navigate class="underline hover:text-blue-500">Product Dashboard</a> > <a
            href="{{ route('focus', ['prod' => $product_id, 'brch' => $branch_id]) }}" wire:navigate
            class="underline hover:text-blue-500">Focus</a> > <a href="#" class="font-bold">Safty Point &
            Order</a>
    </div>
    <div class="my-4 ">
        <span class="px-4 py-2 font-bold uppercase bg-yellow-200 rounded">{{ $branchName }}</span>
    </div>
    <div class="flex flex-wrap gap-4 mb-4">
        <div class="w-48 h-56 overflow-hidden rounded bg-slate-200">
            <img src="{{ asset('storage/' . $photo) }}" />
        </div>
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
        <div class="content-center w-48 h-56 text-center rounded bg-green-50">
            <x-button icon="view-grid-add" green flat label="Add another supplier"
                @click="$openModal('productSupplierModal')" />
        </div>
        <div class="relative mb-4 overflow-x-auto shadow-md sm:rounded-lg">
            <h1 class="text-xl">Reorder Point တွက်ချက်ခြင်း</h1>

            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">
                            Desc
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Qty
                        </th>
                        <th scope="col" class="px-6 py-3 sr-only">
                            Input
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            လက်ကျန် အရေအတွက်
                        </th>
                        <td class="px-6 py-4">
                            {{ $stockInfo->inventory_balance }}
                        </td>
                        <td class="px-6 py-4">
                            {{-- <x-input wire:model='stock_balance' /> --}}
                        </td>
                        <td class="px-6 py-4">
                            @if (!$stockInfo->reorderPoint)
                                {{ 'Hello' }}
                            @else
                                <x-button icon="save" @click="$openModal('adjustmentIn')" flat sky />
                                <x-button icon="save" @click="$openModal('adjustmentOut')" flat red />
                            @endif
                        </td>

                    </tr>
                    @if (!$stockInfo->reorderPoint)
                        <tr class="items-center content-center">
                            <td colspan="2">
                                <x-button class="mt-2" @click="$openModal('reorderModal')" outline pink
                                    label="Create Reorder Point" />
                            </td>
                        </tr>
                    @else
                        <tr
                            class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                အရံချန်ထားရမည့် အချိန်
                            </th>
                            <td class="px-6 py-4">
                                {{ $stockInfo->reorderPoint->safty_day }}
                            </td>
                            <td class="px-6 py-4">
                                <x-input wire:model='safty_day' placeholder="Update safty day" />
                            </td>
                            <td class="px-6 py-4">
                                <x-button wire:click='createReorderPoint' icon="save" flat sky />
                            </td>
                        </tr>
                        <tr
                            class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                အရောင်း ခန့်မှန်းအရေအတွက်
                            </th>
                            <td class="px-6 py-4">
                                {{ $lastFocus }}
                            </td>
                            <td class="px-6 py-4">
                                {{-- <x-input wire:model='safty_day' /> --}}
                            </td>
                            <td class="px-6 py-4">
                                {{-- <x-button icon="save" flat sky /> --}}
                            </td>
                        </tr>
                        <tr
                            class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                Order စတင်မှာရတော့မည့် အနည်းဆုံးအမှတ်
                            </th>
                            <td class="px-6 py-4">
                                {{ ($stockInfo->reorderPoint->safty_day + $productLeadDay) * $lastFocus }}
                            </td>
                            <td class="px-6 py-4">
                                </p>
                                Safty Day ({{ $stockInfo->reorderPoint->safty_day }} +
                                {{ $productLeadDay }}) Delivery Days * Focus {{ $lastFocus }}
                                <p>
                            </td>
                            <td class="px-6 py-4">
                                {{-- <x-button icon="save" flat sky /> --}}
                            </td>
                        </tr>
                        <tr
                            class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                Order တင်ရန် နောက်ဆုံးရက်
                            </th>
                            <td class="px-6 py-4">
                                {{ $stockInfo->reorderPoint->reorder_due_date }}
                            </td>
                            <td class="px-6 py-4">
                                Diff -
                                {{ \Carbon\Carbon::now()->diffInDays($stockInfo->reorderPoint->reorder_due_date) }}
                            </td>
                            {{-- stock status code calculation   --}}
                            @php
                                $color = $stockInfo->reorderPoint->psiStockStatus->color;
                                if ($stockInfo->reorderPoint->psiStockStatus->id >= 3) {
                                    $fontColor = 'white';
                                } else {
                                    $fontColor = 'black';
                                }
                                $rawDateDiff =
                                    \Carbon\Carbon::now()->diffInHours($stockInfo->reorderPoint->reorder_due_date) / 24;
                                $dateDiff = ceil($rawDateDiff);
                                $remainBalance = fmod($stockInfo->inventory_balance, $lastFocus);

                                // @dd($remainBalance);

                                if ($stockInfo->reorderPoint->psiStockStatus->id > 3) {
                                    $lossSale = $dateDiff * $lastFocus - $remainBalance;
                                } else {
                                    $lossSale = 0;
                                }
                                $index =
                                    $lossSale * 0.4 +
                                    $stockInfo->branchPsiProduct->psiProduct->weight * $lossSale * 0.6;
                            @endphp

                            {{-- end stock status code calculation --}}
                            <td class="grid grid-cols-1 px-6 py-4">
                                <span class="px-6 py-4 text-xl "
                                    style="background: {{ $color }}; color: {{ $fontColor }};">
                                    <strong>{{ $stockInfo->reorderPoint->psiStockStatus->name }} /</storng>
                                        Loss {{ $lossSale }} qty
                                </span>
                                Index = {{ $index }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- transfer to another branch --}}
    {{-- <div class="p-3 border border-gray-300 rounded">

        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Branch
                    </th>
                    <th scope="col" class="px-6 py-3">
                        အရေအတွက်
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($branchStock as $data)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $data->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $data->inventory_balance }}
                            <small><button class="px-2 py-1 ml-2 rounded bg-slate-200">Transfer to</button></small>
                        </td>
                        <td class="px-6 py-4">
                            <select id="branch" class="px-4 py-1 uppercase rounded">
                                <option>Select</option>
                                @foreach ($branchStock as $branch)
                                    <option value="{{ $branch->stock_id }}" class="uppercase">{{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" class="px-2 py-1 rounded bg-slate-50" placeholder="Qty" />
                        </td>
                        <td class="px-6 py-4">
                            <x-button icon="save" green />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div> --}}

    {{-- Order Histories --}}
    <div class="relative mb-8 overflow-x-auto shadow-md sm:rounded-lg">
        <h1 class="text-xl">လတ်တလော Order မှာယူထားသောစာရင်းများ</h1>
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Supplier name
                    </th>
                    <th scope="col" class="px-6 py-3">
                        အရေအတွက်
                    </th>
                    <th scope="col" class="px-6 py-3">
                        မှာယူခဲ့သော ရက်စွဲ
                    </th>
                    <th scope="col" class="px-6 py-4">
                        အခြေအနေ
                    </th>

                    <th scope="col" class="px-6 py-3">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($psiOrders as $data)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $data->psiPrice->supplier->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $data->order_qty }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->created_at }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->psiStatus->name }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="#" class="text-blue-500"></a>
                            <a href="{{ route('order_detail', ['ord' => $data->id, 'brch' => $branch_id, 'prod' => $product_id]) }}"
                                class="text-blue-500" wire:navigate>
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

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <h1 class="text-xl">Order မှာယူနိုင်သော Supplier များ</h1>

        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Supplier name
                    </th>
                    <th scope="col" class="px-6 py-3">
                        အလျော့တွက်
                    </th>
                    <th scope="col" class="px-6 py-3">
                        အလျော့တွက် မြန်မာချိန်
                    </th>
                    <th scope="col" class="px-6 py-3">
                        လက်ခ
                    </th>
                    <th scope="col" class="px-6 py-3">
                        ကြာချိန်
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($productSuppliers as $data)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $data->supplier->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $data->psiPrice->youktwat }} g
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->psiPrice->youktwat_in_kpy }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->psiPrice->laukkha }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $data->psiPrice->lead_day }} d
                        </td>
                        <td class="px-6 py-4">
                            <a href="#" wire:click='editInitialize({{ $data->psiPrice->id }})'
                                @click="$openModal('productSupplierModal')"
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                            <a href="#" wire:click='orderInitialize({{ $data->psiPrice->id }})'
                                @click="$openModal('orderModal')"
                                class="font-medium text-green-600 dark:text-green-500 hover:underline">Order</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>



    {{--
    <table class="w-full border-collapse table-auto">
        <thead>
            <tr class="bg-gray-200">
                <th class="px-4 py-2 border">Category</th>
                <th class="px-4 py-2 border">Details</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($specifications as $category => $details)
                <tr>
                    <td class="px-4 py-2 font-bold border" rowspan="{{ is_array($details) ? count($details) : 1 }}">
                        {{ $category }}</td>

                    @if (is_array($details))
                        @foreach ($details as $key => $value)
                            @if ($loop->first)
                                <td class="px-4 py-2 border">
                                    @if (is_array($value))
                                        <strong>{{ $key }}:</strong>
                                        <ul>
                                            @foreach ($value as $subKey => $subValue)
                                                <li>{{ $subKey }}: {{ $subValue }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <strong>{{ $key }}:</strong> {{ $value }}
                                    @endif
                                </td>
                            @else
                <tr>
                    <td class="px-4 py-2 border">
                        @if (is_array($value))
                            <strong>{{ $key }}:</strong>
                            <ul>
                                @foreach ($value as $subKey => $subValue)
                                    <li>{{ $subKey }}: {{ $subValue }}</li>
                                @endforeach
                            </ul>
                        @else
                            <strong>{{ $key }}:</strong> {{ $value }}
                        @endif
                    </td>
                </tr>
            @endif
            @endforeach
        @else
            <td class="px-4 py-2 border">{{ $details }}</td>
            @endif
            </tr>
            @endforeach
        </tbody>
    </table> --}}





    {{-- Product supplier modal  --}}
    <x-modal wire:model="productSupplierModal">
        <x-card title="Suppler Form">
            <form wire:submit='createProductPrice'>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="col-span-1 sm:col-span-2">
                        <x-select label="Supplier" wire:model.live="supplier_id" placeholder="Select a Supplier"
                            :async-data="route('suppliers')" option-label="name" option-value="id">
                            <x-slot name="afterOptions" class="flex justify-center p-2"
                                x-show="displayOptions.length === 0">
                                <x-button href="/addsupplier" x-on:click='close' primary flat full>
                                    <span x-html="`<b>${search}</b> add as a supplier`"></span>
                                </x-button>
                            </x-slot>
                        </x-select>
                    </div>
                    <x-input type='number' step=0.01 label="အလျော့တွက်/ Gram" wire:model.live='youktwat'
                        placeholder="gram" />

                    <x-input thpe='text' wire:model.live='youktwat_in_kpy' disabled label="Auto Convert"
                        placeholder="ကျပ်ပဲရွေး" />

                    <x-input type='number' label="လက်ခ" wire:model.live='laukkha' placeholder="ကျပ်" />
                    <x-input thpe='number' wire:model.live='lead_day' label="ကြာချိန် / ရက်"
                        placeholder="ကြာချိန်" />

                    <div class="col-span-1 rounded sm:col-span-2">
                        <x-textarea label="Product Remark" wire:model='product_remark'
                            placeholder="ပစ္စည်း၏ ထူးခြားမှု မှတ်ချက်ကို ရေးပါ။" />
                    </div>
                    <div class="col-span-1 rounded sm:col-span-2">
                        <x-textarea label="Supplier remark" wire:model='remark'
                            placeholder="ပစ္စည်းအပေါ် Supplier ၏ ထူးခြားချက်ကို ရေးပါ" />
                    </div>
                </div>

                <x-slot name="footer">
                    <div class="flex justify-end gap-x-4">
                        <x-button flat label="Cancel" x-on:click="close" />
                        <x-button primary label="save" right-icon="save" wire:click='createProductPrice'
                            type="submit" green />
                    </div>
                </x-slot>
            </form>
        </x-card>
    </x-modal>

    {{-- Product supplier modal  --}}
    <x-modal wire:model="reorderModal">
        <x-card title="Reorder Point Form">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">
                <x-input type='number' wire:model='safty_day' label="Safty Day" placeholder="day" />
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='createReorderPoint' green />
                </div>
            </x-slot>
        </x-card>
    </x-modal>

    {{-- Adjustment In  --}}
    <x-modal wire:model="adjustmentIn">
        <x-card title="Inventory Adjustment In">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">
                <x-input type='number' wire:model='adjust_qty' label="Adjustment Qty" placeholder="Quantity" />
                <x-input type='text' wire:model='adjust_remark' label="Remark" placeholder="Write a reason" />
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='adjustmentIn' green />
                </div>
            </x-slot>
        </x-card>
    </x-modal>

    {{-- Adjustment Out  --}}
    <x-modal wire:model="adjustmentOut">
        <x-card title="Inventory Adjustment Out">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">
                <x-input type='number' wire:model='adjust_qty' label="Adjustment Qty" placeholder="Quantity" />
                <x-input type='text' wire:model='adjust_remark' label="Remark" placeholder="Write a reason" />
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='adjustmentOut' green />
                </div>
            </x-slot>
        </x-card>
    </x-modal>

    {{-- Order Modal  --}}
    <x-modal wire:model="orderModal">
        <x-card title="Order Modal">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-1">
                <x-input type='number' wire:model='order_qty' label="Order Qty" placeholder="Quantity" />
                {{-- <x-input type='text' wire:model='order_remark' label="Remark" placeholder="Write a remark" /> --}}
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='createOrder' green />
                </div>
            </x-slot>
        </x-card>
    </x-modal>
</div>
