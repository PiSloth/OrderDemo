<div>
    <div class="flex gap-3 mb-4 flex-warp">

    </div>





    <div class="flex flex-wrap gap-4">
        <x-button href="{{ route('psi_product') }}" label="new PSI Product" green icon="view-grid-add" wire:navigate />

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

        {{-- Tempoary off --}}
        <x-button href="{{ route('daily_sale') }}" label="Daily Sale" wire:navigate />
    </div>
    {{-- End of Action button  --}}

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
            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400 ">
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
                                <img class="w-32 max-w-full max-h-full md:w-32 cursor-help"
                                    src="{{ asset('storage/' . $product->image) }}"
                                    wire:click='initializeProductId({{ $product->id }})'
                                    @click="$openModal('{{ $product->id }}')" />
                                <x-modal.card title="{{ $product->shape }}" wire:model='{{ $product->id }}'>
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
                                            @endforeach

                                        </tbody>
                                    </table>
                                    <div>
                                        <article
                                            class="relative flex flex-col justify-end px-8 pt-4 pb-8 mx-auto mt-4 overflow-hidden h-96 hover:cursor-pointer isolate rounded-2xl">
                                            <img class="absolute inset-0 object-cover w-full h-full max-w-xl rounded-lg"
                                                src="{{ asset('storage/' . $product->image) }}" />
                                            <div
                                                class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40">
                                            </div>
                                            <h3 class="z-10 mt-3 text-xl font-bold text-white lg:text-3xl md:text-3xl">
                                                {{ $product->weight }} <i class="hidden md:inline">g</i></h3>
                                            <div class="z-10 overflow-hidden text-sm leading-6 text-gray-300 gap-y-1">
                                                {{ $product->detail }}
                                            </div>
                                        </article>
                                    </div>
                                    {{-- <img src="{{ asset('storage/' . $product->image) }}"
                                        class="mb-6 bg-white rounded-lg"> --}}
                                </x-modal.card>
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
            <div>{{ $products->links() }}</div>
            {{-- <div class="flex-1 overflow-x-auto overflow-y-auto">
                <table class="w-full table-fixed">
                </table>
            </div> --}}
        </div>
    </div>






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


    <x-modal.card title="Summary of Product" blur wire:model="productSummaryModal">
        @foreach ($productSummary as $data)
            <div class="flex">
                <div>
                    <div class="p-2 mb-4 border-2 border-gray-600 border-dotted rounded">
                        <div class="grid grid-cols-2 gap-3">
                            <span class="font-bold">Category</span>
                            {{-- <span>{{ $product->category->name }}</span> --}}
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <span class="font-bold">Design</span>
                            {{-- <span>{{ $product->design->name }}</span> --}}
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <span class="font-bold">Length</span>
                            {{-- <span>{{ $product->length }} <i>{{ $product->uom->name }}</i></span> --}}
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <span class="font-bold">Weight</span>
                            {{-- <span>{{ $product->weight }} <i>g</i></span> --}}
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <span class="font-bold">နည်းပညာ</span>
                            {{-- <span>{{ $product->manufactureTechnique->name }}</span> --}}
                        </div>
                    </div>
                    {{-- <span class="mt-2 font-semibold">{{ $product->shape->name }}</span> --}}
                </div>
            </div>
        @endforeach
        <x-slot name="footer">
            <div class="flex justify-between gap-x-4">
                <x-button flat negative label="Delete" wire:click="cancle" />

                {{-- <div class="flex">
                    <x-button flat label="Cancel" wire:click='cancle' x-on:click="close" />
                    <x-button primary label="Save" wire:click="createBranchPsiProduct" />
                </div> --}}
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
</div>
