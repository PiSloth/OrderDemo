<div>
    <div class="flex flex-wrap gap-4">
        <x-button href="{{ route('oos') }}" label="OoS" negative icon="view-grid-add" wire:navigate />
        <x-button href="{{ route('psi_product') }}" label="new PSI Product" green icon="view-grid-add" wire:navigate />
        <x-button href="{{ route('psi-report') }}" label="report" teal icon="view-grid-add" wire:navigate />
        <x-button href="{{ route('stock-update') }}" label="Stock" positive icon="truck" wire:navigate />

        {{-- Marketing --}}
        @can('isMarketing')
            <x-dropdown align='left'>
                <x-slot name="trigger">
                    <x-button label="Digital Marketing" primary />
                </x-slot>
                <x-dropdown.header label="Actions">
                    <x-dropdown.item href="{{ route('shooting') }}" wire:navigate>ဓာတ်ပုံရိုက်ရန်
                        @if ($jobs4Dm > 0)
                            <x-badge.circle negative label="{{ $jobs4Dm }}" />
                        @endif
                    </x-dropdown.item>
                </x-dropdown.header>
            </x-dropdown>
        @endcan

        @can('isBranchSupervisor')
            <x-dropdown align='left'>
                <x-slot name="trigger">
                    <x-button label="Branch Operation" sky />
                </x-slot>
                <x-dropdown.header label="Actions">
                    <x-dropdown.item href="{{ route('orders', ['stus' => 8]) }}" wire:navigate>Receiving
                        @if ($jobs4Br > 0)
                            <x-badge.circle negative label="{{ $jobs4Br }}" />
                        @endif
                    </x-dropdown.item>
                </x-dropdown.header>
            </x-dropdown>
        @endcan

        @can('isInventory')
            <x-dropdown align='left'>
                <x-slot name="trigger">
                    <x-button label="Inventory" sky />
                </x-slot>
                <x-dropdown.header label="Actions">
                    <x-dropdown.item href="{{ route('orders', ['stus' => 5]) }}" wire:navigate>To Register
                    </x-dropdown.item>
                    <x-dropdown.item href="{{ route('orders', ['stus' => 6]) }}" wire:navigate>Processing in Registeration
                    </x-dropdown.item>
                </x-dropdown.header>
            </x-dropdown>
        @endcan

        <x-button href="{{ route('daily_sale') }}" label="Daily Sale" wire:navigate />
    </div>
    {{-- End of Action button  --}}
    {{-- Tempoary off --}}
    <div class="hidden my-4">
        <div class="flex gap-2 text-blue-400 text-md flex-warp opacity-80">
            @foreach ($selectedTag as $data)
                <div class="flex gap-1 cursor-pointer group" wire:click="removeTag({{ $data['key'] }})">
                    #
                    <span class="group-hover:text-red-700">
                        {{ $data['name'] }}
                    </span>
                    {{-- <x-icon name="x"
                        class="hidden w-4 h-4 mt-1 text-red-400 border-gray-300 rounded group-hover:block hover:border hover:text-red-800" /> --}}
                </div>
            @endforeach
        </div>
    </div>
    <div class="hidden">
        <div class="w-48">
            <x-select wire:model.live="filter_hashtag_id" placeholder="#hash-tag" :async-data="route('hashtag')" option-label="name"
                option-value="id">
                <x-slot name="afterOptions" class="flex justify-center p-2" x-show="displayOptions.length === 0">
                    <x-button x-on:click='close' primary flat full>
                        <span x-html="`<b>${search}</b> No found`"></span>
                    </x-button>
                </x-slot>
            </x-select>
        </div>
        <div>
            <x-button id="save" icon="save" wire:click='selectTag' />
        </div>
    </div>
    {{-- ! tempoary off Hash Tag Filter  --}}

    {{-- Showing Chart --}}
    <div class="py-4">
        {{-- <x-button icon="chart-bar" solid label="chart" /> --}}
        {{-- chart sample --}}
        @php
            $total_branch_sale = 0;
            foreach ($branch_sales as $sale) {
                $total_branch_sale += $sale->total;
            }
        @endphp

        {{-- Chart Tempoary off --}}
        {{-- <div class="w-full max-w-sm p-4 bg-white rounded-lg shadow dark:bg-gray-800 md:p-6">
            <div class="flex justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div
                        class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-lg dark:bg-gray-700 me-3">
                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 19">
                            <path
                                d="M14.5 0A3.987 3.987 0 0 0 11 2.1a4.977 4.977 0 0 1 3.9 5.858A3.989 3.989 0 0 0 14.5 0ZM9 13h2a4 4 0 0 1 4 4v2H5v-2a4 4 0 0 1 4-4Z" />
                            <path
                                d="M5 19h10v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2ZM5 7a5.008 5.008 0 0 1 4-4.9 3.988 3.988 0 1 0-3.9 5.859A4.974 4.974 0 0 1 5 7Zm5 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm5-1h-.424a5.016 5.016 0 0 1-1.942 2.232A6.007 6.007 0 0 1 17 17h2a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5ZM5.424 9H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h2a6.007 6.007 0 0 1 4.366-5.768A5.016 5.016 0 0 1 5.424 9Z" />
                        </svg>
                    </div>
                    <div>
                        <h5 class="pb-1 text-2xl font-bold leading-none text-gray-900 dark:text-white">
                            {{ $total_branch_sale }}</h5>
                        <p class="text-sm font-normal text-gray-500 dark:text-gray-400">Total Sales over branches</p>
                    </div>
                </div>
                <div class="hidden">
                    <span
                        class="bg-green-100 text-green-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-green-900 dark:text-green-300">
                        <svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 10 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13V1m0 0L1 5m4-4 4 4" />
                        </svg>
                        42.5%
                    </span>
                </div>
            </div>

            {{-- <div class="grid grid-cols-2">
                <dl class="flex items-center">
                    <dt class="text-sm font-normal text-gray-500 dark:text-gray-400 me-1"></dt>
                    <dd class="text-sm font-semibold text-gray-900 dark:text-white"></dd>
                </dl>
                <dl class="flex items-center justify-end">
                    <dt class="text-sm font-normal text-gray-500 dark:text-gray-400 me-1">Conversion rate:</dt>
                    <dd class="text-sm font-semibold text-gray-900 dark:text-white">1.2%</dd>
                </dl>
            </div> --

        <div id="column-chart"></div>
        <div class="grid items-center justify-between grid-cols-1 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between pt-5">
                <!-- Button -
                <button id="dropdownDefaultButton" data-dropdown-toggle="lastDaysdropdown"
                    data-dropdown-placement="bottom"
                    class="inline-flex items-center text-sm font-medium text-center text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                    type="button">
                    Last 7 days
                    <svg class="w-2.5 m-2.5 ms-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 4 4 4-4" />
                    </svg>
                </button>
                <!-- Dropdown menu
                <div id="lastDaysdropdown"
                    class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700">
                    <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownDefaultButton">
                        <li>
                            <a href="#" wire:click='durationFilter(1)'
                                class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Yesterday</a>
                        </li>
                        <li>
                            <a href="#" wire:click='durationFilter(0)'
                                class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Today</a>
                        </li>
                        <li>
                            <a href="#" wire:click='durationFilter(7)'
                                class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last
                                7 days</a>
                        </li>
                        <li>
                            <a href="#" wire:click='durationFilter(30)'
                                class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last
                                30 days</a>
                        </li>
                        <li>
                            <a href="#" wire:click='durationFilter(90)'
                                class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last
                                90 days</a>
                        </li>
                    </ul>
                </div>
                {{-- <a href="#"
                        class="inline-flex items-center px-3 py-2 text-sm font-semibold text-blue-600 uppercase rounded-lg hover:text-blue-700 dark:hover:text-blue-500 hover:bg-gray-100 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700">
                        Leads Report
                        <svg class="w-2.5 h-2.5 ms-1.5 rtl:rotate-180" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="m1 9 4-4-4-4" />
                        </svg>
                    </a>
            </div>
        </div>
    </div> --}}

    </div>

    {{-- Sticky Table  --}}
    <div class="container mx-auto my-10 overflow-x-auto">
        <div class="my-3 font-bold text-blue-500">Branch အလိုက် Signature Product များထားရှိခြင်းပြ ဇယား</div>

        <div>
            {{-- Shape filter --}}
            <div class="pb-4 m-4 bg-white dark:bg-gray-900">
                <label for="table-search" class="sr-only">Search</label>
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 flex items-center pointer-events-none rtl:inset-r-0 start-0 ps-3">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live="shape_detail" id="table-search"
                        class="block pt-2 text-sm text-gray-900 border border-gray-300 rounded-lg ps-10 w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Search for items">
                </div>
            </div>
            <table id="pageproducts" class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400 ">
                <thead class="sticky top-0 bg-white">
                    <tr>
                        <th scope="col" class="px-16 py-3">
                            <span class="sr-only">Image</span>
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Product
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Weight/g
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Size
                        </th>
                        @foreach ($branches as $branch)
                            <th scope="col" class="px-6 py-3">
                                {{ $branch->name }}
                            </th>
                        @endforeach
                        {{-- <th scope="col" class="px-6 py-3">
                            Action
                        </th> --}}
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr
                            class="bg-white border-b odd:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="p-4">
                                <img wire:click='initializeProductId({{ $product->id }})'
                                    class="w-32 max-w-full max-h-full md:w-32 cursor-help"
                                    src="{{ asset('storage/' . $product->image) }}"
                                    @click="$openModal('productSummaryModal')" />
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                {{ $product->shape }}
                            </td>
                            <td class="px-6 py-4">
                                <span>{{ $product->weight }}</span>
                            </td>

                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                <div class="flex items-center">
                                    {{ $product->length }} {{ $product->uom }}
                                </div>
                            </td>

                            @foreach ($branches as $branch)
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    @if ($product->{'index' . $branch->id} > 0)
                                        <a href="#"
                                            class="flex flex-col items-center content-center gap-1 px-2 py-1 hover:rounded hover:bg-gray-100"
                                            wire:click='propsToLink({{ $product->id }},{{ $branch->id }})'>
                                            @if ($product->{'status' . $branch->id})
                                                <div class="w-6 h-6 rounded-full"
                                                    style="background: {{ $product->{'color' . $branch->id} }}">
                                                </div>
                                                <span
                                                    class="text-xs rounded ">{{ $product->{'status' . $branch->id} }}
                                                </span>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-400"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                                </svg>
                                            @endif

                                        </a>
                                    @else
                                        <button
                                            wire:click="setBranchPsiProduct({{ $product->id }},{{ $branch->id }})"
                                            @click="$openModal('psiProduct')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                                {{-- @dd($product->{'index' . $branch->id}) --}}
                            @endforeach
                        </tr>
                    @endforeach

                </tbody>
            </table>
            <div class="p-4">{{ $products->onEachSide(1)->links(data: ['scrollTo' => '#pageproducts']) }}</div>
            {{-- <div class="flex-1 overflow-x-auto overflow-y-auto">
                <table class="w-full table-fixed">
                </table>
            </div> --}}
        </div>
    </div>



    <x-modal.card title="{{ $productSummary['detail'] }}" wire:model='productSummaryModal'>
        {{-- <div class="flex gap-2 text-blue-400 text-md flex-warp opacity-80">
            @foreach ($tags as $data)
                <div class="flex gap-1 cursor-pointer group">#<span
                        class="group-hover:text-blue-700">{{ $data->hashtag->name }}</span> <x-icon name="x"
                        class="hidden w-4 h-4 mt-1 text-red-400 border-gray-300 rounded group-hover:block hover:border hover:text-red-800" />
                </div>
            @endforeach
        </div> --}}
        <div class="container mx-auto my-2 overflow-x-auto">
            <div style="display: none" class="grid grid-cols-2 gap-2 my-2 bg-white dark:bg-gray-900">
                <x-select wire:model.live="hashtag_id" placeholder="#hash-tag" :async-data="route('hashtag')" option-label="name"
                    option-value="id">
                    <x-slot name="afterOptions" class="flex justify-center p-2" x-show="displayOptions.length === 0">
                        <x-button x-on:click='close' wire:click='createTag' primary flat full>
                            <span x-html="`<b>${search}</b> ကို အသစ်ဖန်တီးမယ်`"></span>
                        </x-button>
                    </x-slot>
                </x-select>
                <div>
                    <x-button id="save" icon="save" wire:click='addTagToProduct' />
                </div>
            </div>

            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400 ">
                <thead>
                    <tr class="p-1 border border-gray-300">
                        <th scope="col" class="px-6 py-3">
                            Branch
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Focus
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Avg Sale
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Balance
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Remaining to Sale
                        </th>
                        <th scope="col" class="px-6 py-3">
                            To Order Due Date
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productSummary['branches'] as $data)
                        <tr
                            class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-3 uppercase">
                                {{ $data['branch_name'] }}
                            </td>
                            @php
                                $avg_sale = $data['avg_sales'] > 0 ? $data['avg_sales'] : 1;
                                $remaining_days = $data['balance'] / $avg_sale;
                            @endphp
                            <td class="px-6 py-4">{{ $data['latest_focus_qty'] }}</td>
                            <td class="px-6 py-4">{{ (int) $data['avg_sales'] }}</td>
                            <td class="px-6 py-4">{{ $data['balance'] }}</td>
                            <td class="px-6 py-4">{{ (int) $remaining_days }} <small>days</small></td>

                            <td class="px-6 py-4">
                                {{ \Carbon\Carbon::parse($data['due_date'])->format('(D) d-M-Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <article
                class="relative flex flex-col justify-end px-8 pt-4 pb-8 mx-auto mt-4 overflow-hidden h-96 hover:cursor-pointer isolate rounded-2xl">
                <img class="absolute inset-0 object-cover w-full h-full max-w-xl rounded-lg"
                    src="{{ asset('storage/' . $productSummary['image']) }}" alt="product photo" />
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40">
                </div>
                <h3 class="z-10 mt-3 text-xl font-bold text-white lg:text-3xl md:text-3xl">
                    {{ $productSummary['weight'] }} <i class="hidden md:inline">g</i></h3>
                <div class="z-10 overflow-hidden text-sm leading-6 text-gray-300 gap-y-1">
                    {{ $productSummary['detail'] }}
                </div>
            </article>
        </div>
    </x-modal.card>



    <x-modal.card title="Add this Product to PSI" blur wire:model="psiProduct">
        <x-input label="Remark" wire:model.live='remark' placeholder="Here remark please" />
        <x-slot name="footer">
            <div class="flex justify-between gap-x-4">
                <x-button flat negative label="Delete" wire:click="cancle" />

                <div class="flex">
                    <x-button flat label="Cancel" wire:click='cancle' x-on:click="close" />
                    <x-button primary label="Save" wire:click="createBranchPsiProduct" />
                </div>
            </div>
        </x-slot>
    </x-modal.card>




    {{-- Modal trigger form js  --}}
    <x-modal wire:model="defaultModal" blur>
        <x-card title="Choose a function">

            <ol class="">
                <li class="hover:text-gray-500 hover:underline">
                    <a href="{{ route('focus', ['brch' => $branchId, 'prod' => $productId]) }}" wire:navigate>Product
                        Focus</a>
                </li>
                <li class="hover:text-gray-500 hover:underline">
                    <a href="{{ route('focus', ['brch' => $branchId, 'prod' => $productId]) }}" wire:navigate>Daily
                        Sale</a>
                </li>
                <li>
                    <a class="hover:text-gray-500 hover:underline"
                        href="{{ route('price', ['prod' => $productId, 'bch' => $branchId]) }}" class="flex"
                        wire:navigate><span>Order</span>
                    </a>
                    @if ($orderCount)
                        {{-- <x-icon name="arrow-narrow-right" class="w-5 h-5" solid /> --}}
                        <x-button flat blue right-icon="arrow-narrow-right" @click="$openModal('orderModal')"
                            class="underline">
                            Order found
                            {{ $orderCount }}</x-button>
                    @endif
                </li>
                {{-- <li>
                    <a href="{{ route('oos', ['bch' => $branchId, 'prod' => $productId]) }}" wire:navigate>OOS
                        Analysis</a>
                </li> --}}
            </ol>

            <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                </div>
            </x-slot>
        </x-card>
    </x-modal>

    {{-- ORDER HISTORY MODAL --}}
    <x-modal.card title="Add this Product to PSI" blur wire:model="orderModal">
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
                            <a href="{{ route('order_detail', ['ord' => $data->id, 'brch' => $branchId, 'prod' => $productId]) }}"
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
    </x-modal.card>

    {{-- <x-modal.card title="Product Summary" wire:model='porductSummaryModal'>
        <table>
            <thead>
                <tr class="p-1 border border-gray-300">
                    <th scope="col" class="px-6 py-3">
                        Branch
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Focus
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Avg Sale
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Balance
                    </th>
                    <th scope="col" class="px-6 py-3">
                        To Order Due Date
                    </th>
                </tr>
            </thead>
            <tbody class="p-1 border border-gray-400">

                @foreach ($productSummary as $data)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">

                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $data['branch_name'] }}</th>
                        <td class="px-6 py-4">{{ $data['latest_focus_qty'] }}</td>
                        <td class="px-6 py-4">{{ (int) $data['avg_sales'] }}</td>
                        <td class="px-6 py-4">{{ $data['balance'] }}</td>
                        <td class="px-6 py-4">
                            {{ \Carbon\Carbon::parse($data['due_date'])->format('(D) d-M-Y') }}
                        </td>

                    </tr>
            </tbody>
        </table>
        @endforeach --}}

    {{-- </x-modal.card> --}}
</div>
@section('script')
    <script>
        const saleData = JSON.parse('{!! addslashes($sales) !!}');

        const data = Object.entries(saleData).map(([branch, total]) => ({
            x: branch,
            y: parseFloat(total)
        }));

        console.log(data);

        const options = {
            colors: ["#1A56DB", "#FDBA8C"],
            series: [{
                    name: "Sales",
                    color: "#1A56DB",
                    data: data,
                },

            ],
            chart: {
                type: "bar",
                height: "320px",
                fontFamily: "Inter, sans-serif",
                toolbar: {
                    show: false,
                },
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: "70%",
                    borderRadiusApplication: "end",
                    borderRadius: 8,
                },
            },
            tooltip: {
                shared: true,
                intersect: false,
                style: {
                    fontFamily: "Inter, sans-serif",
                },
            },
            states: {
                hover: {
                    filter: {
                        type: "darken",
                        value: 1,
                    },
                },
            },
            stroke: {
                show: true,
                width: 0,
                colors: ["transparent"],
            },
            grid: {
                show: false,
                strokeDashArray: 4,
                padding: {
                    left: 2,
                    right: 2,
                    top: -14
                },
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                show: false,
            },
            xaxis: {
                floating: false,
                labels: {
                    show: true,
                    style: {
                        fontFamily: "Inter, sans-serif",
                        cssClass: 'text-xs font-normal fill-gray-500 dark:fill-gray-400'
                    }
                },
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                },
            },
            yaxis: {
                show: false,
            },
            fill: {
                opacity: 1,
            },
        }

        if (document.getElementById("column-chart") && typeof ApexCharts !== 'undefined') {
            const chart = new ApexCharts(document.getElementById("column-chart"), options);
            chart.render();
        }
    </script>
@endsection
