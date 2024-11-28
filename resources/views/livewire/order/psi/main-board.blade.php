<div>
    <div class="flex gap-4">
        <x-button href="{{ route('psi_product') }}" label="new PSI Product" green icon="view-grid-add" wire:navigate />
        @can('isMarketing')
            <x-button href="{{ route('shooting') }}" teal wire:navigate>Jobs for
                Digital_M
                <x-badge rounded negative> {{ $jobs4Dm > 0 ? $jobs4Dm : '' }}</x-badge>
            </x-button>
        @endcan

        @can('isBranchSupervisor')
            <x-button href="{{ route('orders', ['stus' => 8]) }}" sky wire:navigate>Jobs for
                Branch
                <x-badge rounded negative>{{ $jobs4Br > 0 ? $jobs4Br : '' }}</x-badge>
            </x-button>
        @endcan

        @can('isInventory')
            <x-button href="{{ route('orders', ['stus' => 5]) }}" sky wire:navigate>
                To Register
                <x-badge rounded negative>i</x-badge>
            </x-button>

            <x-button href="{{ route('orders', ['stus' => 6]) }}" sky wire:navigate>
                Processing in Registeration
                <x-badge rounded negative>i</x-badge>
            </x-button>
        @endcan
    </div>

    <div class="relative mt-4 overflow-x-auto shadow-md sm:rounded-lg">
        <div class="my-3 font-bold text-blue-500">Branch အလိုက် Signature Product များထားရှိခြင်းပြ ဇယား</div>
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
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
                        <th>
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
                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="p-4">
                            <img src="{{ asset('storage/' . $product->image) }}"
                                @click="$openModal('{{ $product->id }}')"
                                class="w-16 max-w-full max-h-full md:w-32 cursor-help " />
                            <x-modal wire:model='{{ $product->id }}'>

                                <img src="{{ asset('storage/' . $product->image) }}" class="mb-6 bg-white rounded-lg">

                            </x-modal>
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
                                                style="background: {{ $product->{'color' . $branch->id} }}"></div>
                                            <span class="text-xs rounded ">{{ $product->{'status' . $branch->id} }}
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
                                    <button wire:click="setBranchPsiProduct({{ $product->id }},{{ $branch->id }})"
                                        @click="$openModal('psiProduct')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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



    {{-- Modal trigger form js  --}}
    <x-modal wire:model="defaultModal" blur>
        <x-card title="Choose a function">

            <ol class="">
                <li>
                    <a href="{{ route('focus', ['brch' => $branchId, 'prod' => $productId]) }}" wire:navigate>Product
                        Focus</a>
                </li>
                <li>
                    <a href="{{ route('focus', ['brch' => $branchId, 'prod' => $productId]) }}" wire:navigate>Daily
                        Sale</a>
                </li>
                <li>
                    <a href="{{ route('price', ['prod' => $productId, 'bch' => $branchId]) }}" class="flex"
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
