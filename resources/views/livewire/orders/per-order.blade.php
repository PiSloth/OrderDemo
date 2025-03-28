{{-- @extends('livewire.orders.layout.dashboard-layout')

@section('content') --}}
<div>

    <div class="p-2 rounded-lg dark:bg-gray-800 dark:text-gray-100">

        <p class="mb-5 text-2xl font-bold text-red-800">{{ $order->status->name }}</p>


        <div class="">


            <div class="lg:ml-10">
                <div class="flex flex-wrap gap-4">
                    <div class="">
                        <span> {{ $order->grade->name }}</span>
                        <div class="mt-2">
                            <div class="w-48 h-40 overflow-hidden rounded">
                                @if (count($order->images) > 0)

                                    @foreach ($order->images as $image)
                                        <img x-on:click="$openModal('imageModal')"
                                            class="object-cover w-full mb-4 rounded-lg hover:cursor-zoom-in h-96 md:h-auto md:w-48"
                                            src="{{ asset('storage/' . $image->orderimg) }}" alt="">
                                    @endforeach
                                    <x-modal wire:model='imageModal'>

                                        <img src="{{ asset('storage/' . $image->orderimg) }}"
                                            class="mb-6 bg-white rounded-lg">

                                    </x-modal>
                                @else
                                    @if ($productImg)
                                        <div class="w-36 h-36">
                                            <img src="{{ $productImg->temporaryUrl() }}" />
                                        </div>
                                    @else
                                        <span class="text-red-300"><svg xmlns="http://www.w3.org/2000/svg"
                                                class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            "No photo found"</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        {{-- product image ရှိလျှင်/ ဆိုလိုသည်မှာ Tempoary Photo အတွက်သာပေါ်မည်။  --}}
                        @if ($productImg)
                            <x-button class="my-2" icon="save" green squared
                                wire:click='savePhoto({{ $order->id }},{{ $order->user_id }})' label="Save Photo" />
                            <x-button icon='x' red wire:click='clearPhoto' />
                        @endif
                        <div class="text-teal-500">{{ $order->detail }}</div>

                    </div>
                    {{-- Product info --}}
                    <div class="border-r-2 text-wrap border-r-indigo-950 ">
                        <h3 class="flex gap-2 font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Product
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            <span>Quality:</span>
                            <span>{{ $order->quality->name }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <span>Design: </span>
                            <span>{{ $order->design->name }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <span>Gram: </span>
                            <span>{{ $order->weight }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <span>Size: </span>
                            <span>{{ $order->size }}</span>
                        </div>
                    </div>

                    {{-- Sale info  --}}
                    <div class="border-r-2 border-gray-800 text-wrap">
                        <h3 class="flex gap-2 font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                            Sale
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            <span>Sell/month: </span>
                            <span>{{ $order->sell_rate }}</span>
                        </div>
                    </div>

                    {{-- Stock info --}}
                    <div class="text-wrap">
                        <h3 class="flex gap-2 font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z" />
                                <path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z" />
                                <path d="M17 5c0 1.657-3.134 3-7 3S3 6.657 3 5s3.134-3 7-3 7 1.343 7 3z" />
                            </svg>
                            Stock Balance
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <span>Counter: </span>
                            <span>{{ $order->counterstock }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <span>Inventory: </span>
                            <span>{{ $order->instockqty }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <span>Req Qty: </span>
                            <span>{{ $order->qty }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- check photo  --}}
        @if (count($order->images) == 0 && !$productImg && $order->status_id <= 2)
            {{-- Photo upload --}}
            <div class="my-2">
                <div wire:loading wire:target='productImg'>
                    <span class="text-green-700">uploading . . . .</span>
                </div>
                <input class="block text-red-500 bg-gray-100 rounded" wire:model="productImg" id="image"
                    accept="image/jpeg,image/jpg"
                    class="my-2 text-gray-700 border border-gray-500 rounded dark:text-gray-200" type="file" />
                @error('productImg')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Note  --}}
        <figure class="max-w-screen-md mx-auto text-center">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-400 dark:text-gray-600" aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 14">
                <path
                    d="M6 0H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3H2a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Zm10 0h-4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3h-1a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Z" />
            </svg>
            <blockquote>
                <p class="text-2xl italic font-medium text-gray-900 dark:text-white">
                    "{{ $order->note }}"</p>
            </blockquote>
            <figcaption class="flex items-center justify-center mt-6 space-x-3 rtl:space-x-reverse">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z"
                        clip-rule="evenodd" />
                </svg>
                <div class="flex items-center divide-x-2 divide-gray-500 rtl:divide-x-reverse dark:divide-gray-700">
                    <cite class="font-medium text-gray-900 pe-3 dark:text-white">{{ $order->user->name }}</cite>
                    <cite class="text-sm text-gray-500 ps-3 dark:text-gray-400">{{ $order->user->branch->name }}</cite>
                </div>
            </figcaption>
            <span>{{ $order->created_at->diffForHumans() }}</span>
            <span>( {{ \Carbon\Carbon::parse($order->created_at)->format('j M y') }} )</span>
        </figure>
    </div>

    {{-- message button --}}
    <div class="flex gap-2 mt-4 mb-2 ">
        <div>
            <x-badge sky class="absolute w-3 h-4 -ml-1" rounded primary label="{{ $comments }}" />
            <x-button flat positive icon="chat-alt-2" label="Comments" @click="$openModal('commentModal')"></x-button>
        </div>
        <x-button flat info icon="clock" label="History" @click="$openModal('historyModal')"></x-button>
        @if ($order->status_id !== 8 && $order->status_id !== 7 && Gate::allows('isCanceller'))
            <x-button flat negative icon="x" label="Cancel" @click="$openModal('cancelOrderModal')"></x-button>
        @endif
    </div>

    {{-- Assinged Supplier to Order --}}
    @if ($order->status_id >= 4)
        @if ($order->approvedOrder)
            <div class="">
                <div class="p-4 bg-white rounded w-96">

                    <h2>Order တင်ရန် ခွင့်ပြုထားသော Supplier</h2>
                    <div class="flex flex-col px-8 py-5 ">
                        <div class="grid grid-cols-2">
                            <p class="font-medium">Name </p>
                            <span>: {{ $order->approvedOrder->supplierProduct->supplier->name }}</span>
                        </div>
                        @if ($order->quality->name == '18K')
                            <div class="grid grid-cols-2">
                                <p class="font-medium">1 Gram Price </p>
                                <span>: {{ $order->approvedOrder->youktwat }} </span>
                            </div>
                        @else
                            <div class="grid grid-cols-2">
                                <p class="font-medium">အလျော့တွက် </p>
                                <span>: {{ $order->approvedOrder->youktwat_in_kpy }} </span>
                            </div>
                        @endif
                        <div class="grid grid-cols-2">
                            <p class="font-medium">လက်ခ </p>
                            <span>: {{ $order->approvedOrder->laukkha }}</span>
                        </div>
                        <div class="grid grid-cols-2">
                            <p class="font-medium">Order တင်ရမည့် ရက်စွဲ</p>
                            <span>: {{ $order->approvedOrder->to_order_date }}</span>
                        </div>
                        <div class="grid grid-cols-2">
                            <p class="font-medium">မှတ်ချက်</p>
                            <span>: {{ $order->approvedOrder->approve_note }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
    {{-- End Assinged Supplier to Order --}}

    {{-- Start input data section --}}

    @if ($invUser && $order->status_id == 2)
        <div
            class="w-full px-2 py-4 mx-auto mb-5 text-sm bg-white border border-gray-200 rounded-lg shadow md:flex-row dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-10">

                <div class="w-56">
                    <label for="inhand" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">In
                        Hand</label>
                    <input wire:model='instockqty' type="number" id="inhand"
                        placeholder="{{ $order->instockqty }}"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="0">
                    @error('instockqty')
                        <span class="text-sm text-red-400">Pls fill the instock Qty</span>
                    @enderror
                </div>
            </div>
        </div>
    @endif

    {{-- add supplier data  --}}

    @if (Gate::allows('isSupplierDataCreator') && ($order->status_id == 3 || $order->status_id == 2))
        <div
            class="w-full px-2 py-4 mx-auto mb-5 text-sm bg-white border border-gray-200 rounded-lg shadow md:flex-row dark:border-gray-700 dark:bg-gray-800">
            {{-- supplier data add button --}}
            <div class="flex items-end">
                <div class="flex gap-2">
                    <button wire:click="supplierToggle" class="bg-indigo-600 px-2 py-1.5 text-white rounded">Supplier
                        Product </button>
                    <button wire:click="previousToggle" class="bg-cyan-600 px-2 py-1.5 text-white rounded">Show
                        Previous
                        Supplier Product
                    </button>
                </div>
            </div>
            {{-- End supplier data add button --}}

            {{-- Add new Product Data and Edit data input sections --}}

            <div class="mt-4 {{ $supplierProductToggle ? '' : 'hidden' }}">
                <div class="grid grid-cols-2 gap-6 lg:grid-cols-4 lg:gap-8">
                    <div>
                        <label class="font-medium dark:text-gray-100">Choose a supplier</label>
                        <x-select wire:model.live="supplier_id" placeholder="Choose a supplier" :async-data="route('suppliers')"
                            option-label="name" option-value="id" />
                    </div>

                    <div>
                        <label class="font-medium dark:text-gray-100">Choose a quality</label>
                        <x-select wire:model.live="quality_id" placeholder="Choose a quality" :async-data="route('qualities.index')"
                            option-label="name" option-value="id" />
                    </div>
                    <div>
                        <label class="font-medium dark:text-gray-100">Choose a design</label>
                        <x-select wire:model.live="design_id" placeholder="Choose a desing" :async-data="route('designs.index')"
                            option-label="name" option-value="id" />
                    </div>
                    <div>
                        <label class="font-medium dark:text-gray-100">Detail</label>
                        <x-input type="text" wire:model='detail' placeholder="Enter detail" />
                    </div>
                    <div>
                        <label class="font-medium dark:text-gray-100">Color</label>
                        <x-input type="text" wire:model='color' placeholder="Enter color" />
                    </div>
                    <div class="relative">
                        <label class="font-medium dark:text-gray-100">Weight</label>
                        <x-input type='number' step=0.01 wire:model='weight' id="weightInSupplier"
                            placeholder='gram' />
                        <div id="weightInnerText" class="absolute text-teal-600" wire:ignore></div>
                        <x-input id="gramToMmUnitSupplier" type="text" wire:model.live.debounce='weight_in_kpy'
                            disabled class="absolute hidden text-sm text-blue-900 ring-white dark:text-blue-300" />
                    </div>
                    <div>
                        <label class="font-medium dark:text-gray-100">လက်ခ</label>
                        <x-input type="number" wire:model='laukkha' placeholder="Enter လက်ခ" />
                    </div>
                    <div class="relative">
                        <label class="font-medium dark:text-gray-100">အလျော့တွက်</label>
                        <x-input type='number' step=0.01 wire:model='youktwat' id="youktwatInSupplier"
                            placeholder='gram' />
                        <div id="youktwatInnerText" class="absolute text-teal-600" wire:ignore></div>
                        <x-input hidden id="gramToMmUnitYouktwatSupplier" type="text" wire:model='youktwat_in_kpy'
                            class="absolute hidden text-sm text-blue-900 dark:text-blue-300" />
                    </div>
                    <div>
                        <label class="font-medium dark:text-gray-100">Product Remark</label>
                        <x-input type="text" wire:model='product_remark' />
                    </div>
                    <div>
                        <label class="font-medium dark:text-gray-100">ပစ္စည်းရောက်ရန် အနည်းဆုံး ကြာချိန်</label>
                        <x-input type="date" wire:model='min_ar_date' />
                    </div>
                    <div>
                        <label class="font-medium dark:text-gray-100">ပစ္စည်းရောက်ရန် အများဆုံး ကြာချိန်</label>
                        <x-input type="date" wire:model='max_ar_date' />
                    </div>
                    <div>
                        <label class="font-medium dark:text-gray-100">အထွေထွေ မှတ်ချက် </label>
                        <x-input type="text" wire:model='remark' />
                    </div>
                </div>

                @if ($editSupplierProductMode)
                    <div class="flex gap-2">
                        <button wire:click='updateSupplierProduct'
                            class="bg-teal-700 text-gray-100 mt-5 px-2 py-1.5 rounded">Update Supplier
                            Product</button>
                        <button wire:click='cancelEditSupplierProduct'
                            class="bg-red-700 text-gray-100 mt-5 px-2 py-1.5 rounded">Cancel</button>
                    </div>
                @else
                    <button wire:click='createSupplierProduct'
                        class="bg-amber-700 text-gray-100 mt-5 px-2 py-1.5 rounded">Add Supplier
                        Product</button>
                @endif
            </div>
            <hr class="my-4 border border-gray-200 dark:border-gray-400">
        </div>
    @endif
    {{-- End Purchaser Request input with supplier Data   --}}

    {{-- start to show relevant requested orders --}}
    @if ($order->status_id >= 2 && $order->status_id <= 3 && Gate::allows('isQuotationViewer'))
        <div>
            <h1 class="text-2xl font-medium">ယခု order အတွက် Quotation Form များ</h1>

            <div class="grid items-center justify-start grid-cols-1 gap-10 py-5 md:grid-cols-2 lg:grid-cols-4">

                @forelse ($order->requestedOrder as $requested)
                    <div
                        class="
                        @if ($requested->supplier_product_id == $selected_approved_supplier) bg-green-400 text-white
                        @elseif($requested->supplierProduct->is_reject)
                            bg-red-100
                        @else
                            bg-teal-100 @endif rounded w-80 h-[30rem]">
                        <h2 class="pt-4 text-xl font-bold text-center underline">
                            {{ $requested->supplierProduct->supplier->name }}</h2>
                        <div class="flex justify-center gap-2">
                            @can('isPurchaser')
                                <x-button outline red wire:click="removeRequestedOrder({{ $requested->id }})"
                                    label="Remove" />
                                <x-button outline sky
                                    wire:click="editSupplierProduct({{ $requested->supplier_product_id }})"
                                    label="edit" />
                            @endcan

                            @can('isSupplierDataApprover')
                                <x-button outline pink
                                    wire:click="rejectSupplierProduct({{ $requested->supplier_product_id }})"
                                    x-on:click="$openModal('rejectSupplierDataModal')" label="reject" />
                                <x-button outline teal
                                    wire:click="selectedSupplier({{ $requested->supplier_product_id }})"
                                    label="Select" />
                            @endcan
                        </div>

                        {{-- supplier data detial  --}}
                        <div class="flex flex-col px-8 py-5 ">
                            <div class="grid grid-cols-2">
                                <p class="font-medium">Quality/Design: </p>
                                <p>{{ $requested->supplierProduct->quality->name }} /
                                    {{ $requested->supplierProduct->design->name }} </p>
                            </div>
                            <div class="grid grid-cols-2">
                                <p class="font-medium">Detail: </p>
                                <p>{{ $requested->supplierProduct->detail }}</p>
                            </div>
                            <div class="grid grid-cols-2">
                                <p class="font-medium">Color: </p>
                                <p>{{ $requested->supplierProduct->color }}</p>
                            </div>

                            @if ($requested->supplierProduct->quality->name == '18K')
                                <div class="grid grid-cols-2">
                                    <p class="font-medium">Weight: </p>
                                    <p>{{ $requested->supplierProduct->weight }} g</p>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <p class="font-medium">အလျော့တွက်: </p>
                                    <p>{{ $requested->supplierProduct->youktwat }}</p>
                                </div>
                            @else
                                <div class="grid grid-cols-2">
                                    <p class="font-medium">Weight: </p>
                                    <p>{{ $requested->supplierProduct->weight_in_kpy }} </p>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <p class="font-medium">အလျော့တွက်: </p>
                                    <p>{{ $requested->supplierProduct->youktwat_in_kpy }} </p>
                                </div>
                            @endif

                            <div class="grid grid-cols-2">
                                <p class="font-medium">လက်ခ: </p>
                                <p>{{ $requested->supplierProduct->laukkha }} ကျပ်</p>
                            </div>

                            <div class="grid grid-cols-2 overflow-auto text-wrap">
                                <p class="font-medium">Product Remark: </p>
                                <p>{{ $requested->supplierProduct->product_remark }}</p>
                            </div>

                            <div class="grid grid-cols-2 overflow-auto text-wrap">
                                <p class="font-medium">အနည်းဆုံး ရောက်ရှိမည့်ရက်: </p>
                                <p>{{ $requested->supplierProduct->min_ar_date }}</p>
                            </div>

                            <div class="grid grid-cols-2 overflow-auto text-wrap">
                                <p class="font-medium">အများဆုံး ရောက်ရှိမည့်ရက်: </p>
                                <p>{{ $requested->supplierProduct->max_ar_date }}</p>
                            </div>
                            <div class="grid grid-cols-2 overflow-auto text-wrap">
                                <p class="font-medium"> Remark: </p>
                                <p>{{ $requested->supplierProduct->remark }}</p>
                            </div>
                            @if ($requested->supplierProduct->is_reject)
                                <div class="grid grid-cols-2 overflow-auto text-red-400 text-wrap">
                                    <p class="font-medium"> Reject Because: </p>
                                    <p>{{ $requested->supplierProduct->reject_note }}</p>
                                </div>
                            @endif

                        </div>
                    </div>
                @empty
                    <span>Nothing to show</span>
                @endforelse
            </div>
        </div>
    @endif
    {{-- end show relevant requested orders --}}

    {{-- Show Previous Supplier Product Data   --}}
    @if ($previousSupplierToggle)
        <div class="grid items-center justify-start grid-cols-1 gap-10 py-5 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($supplierdatas as $item)
                <div class="bg-gray-100 rounded w-80 h-96 ">
                    <h2 class="pt-4 text-xl font-bold text-center underline">{{ $item->supplier->name }}</h2>
                    <div class="flex justify-center">
                        <x-button outline sky wire:click="addRequestedOrder({{ $item->id }})" label="Add" />
                    </div>
                    <div class="flex flex-col px-8 py-5 ">
                        <div class="grid grid-cols-2">
                            <p class="font-medium">Quality/Design: </p>
                            <p>{{ $item->quality->name }}/ {{ $item->design->name }}</p>
                        </div>
                        <div class="grid grid-cols-2">
                            <p class="font-medium">Detail: </p>
                            <p>{{ $item->detail }}</p>
                        </div>
                        <div class="grid grid-cols-2">
                            <p class="font-medium">Color : </p>
                            <p>{{ $item->color }}</p>
                        </div>

                        @if ($item->quality->name == '18K')
                            <div class="grid grid-cols-2">
                                <p class="font-medium">Weight: </p>
                                <p>{{ $item->weight }} g</p>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <p class="font-medium">အလျော့တွက်: </p>
                                <p>{{ $item->youktwat }}</p>
                            </div>
                        @else
                            <div class="grid grid-cols-2">
                                <p class="font-medium">Weight: </p>
                                <p>{{ $item->weight_in_kpy }} g</p>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <p class="font-medium">အလျော့တွက်: </p>
                                <p>{{ $item->youktwat_in_kpy }} g</p>
                            </div>
                        @endif

                        <div class="grid grid-cols-2">
                            <p class="font-medium">လက်ခ: </p>
                            <p>{{ $item->laukkha }} ကျပ်</p>
                        </div>

                        <div class="grid grid-cols-2">
                            <p class="font-medium">Product Remark: </p>
                            <p>{{ $item->product_remark }}</p>
                        </div>

                        <div class="grid grid-cols-2">
                            <p class="font-medium">Remark: </p>
                            <p>{{ $item->remark }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="w-64 mt-2 mb-4 ml-10 mr-10 lg:ml-72">
            <x-input placeholder="Type detail design" wire:model.live.debounce="supplier_data_serarch"
                type="search" />
        </div>
    @endif
    {{-- End Showing Previous Supplier Product Data   --}}

    {{-- approve info  --}}
    @if (Gate::allows('isOrderApprover') && $order->status_id == 3)
        <div class="p-4 mt-2">
            <h2 class="text-xl font-bold">Approve Data</h2>
            <div class="mt-2 w-52">
                <label for="editqty"
                    class="block mb-2 text-sm font-medium text-gray-900 text-blue-700 dark:text-white">Edit ? Order
                    Qty</label>
                <input wire:model='editqty' type="number" id="days" placeholder="{{ $order->qty }}"
                    class="bg-red-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="0">
            </div>
            @if ($selectedApprovedSupplier)
                {{ $selectedApprovedSupplier->supplier->name }}
                <div>
                    Supplier အမှတ်
                    {{ $selected_approved_supplier }}
                </div>
            @else
                <span class="font-bold text-red-500">Please Select a supplier to order!</span>
            @endif
            <div class="flex gap-2 mt-1">
                <x-input type="text" wire:model='approved_note' label="Approved Note" />
                <x-input type="date" wire:model='to_order_date' label="To Order at" />
            </div>
        </div>
    @endif
    {{-- end approve info  --}}

    @if ($order->status_id >= 5 && Gate::allows('isPurchaser'))
        <div class="flex items-center justify-between gap-10 mt-4 {{ $invUser ? '' : 'hidden' }}">
            <div class="w-full">
                <label for="aqty"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Arrivals-Qty</label>
                <input type="number" id="aqty" wire:model='arqty'
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="{{ $order->arqty }}" required>
                @error('arqty')
                    <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="cqty"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Close-Qty</label>
                <input type="number" id="cqty" wire:model='closeqty'
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="{{ $order->closeqty }}">
                @error('closeqty')
                    <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif

    {{-- End input data section --}}

    {{-- Start Action Buttons --}}
    <hr class="my-2 border-gray-400">
    <div class="w-full px-2 py-4 mx-auto mt-2 mb-3 text-sm bg-white md:flex-row dark:border-gray-700 dark:bg-gray-800">
        @if (!$chatPool || $chatPool->completed)
            {{-- --}}
            <x-button label="i-Meeting" onclick="$openModal('iMeetingModal')"
                class="text-black border-2 border-cyan-500 items-center justify-center p-0.5 mb-2 me-2 px-5 py-2.5 transition-all ease-in duration-75 dark:bg-gray-900 rounded-md  bg-gradient-to-br hover:from-cyan-500 hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800" />
            @error('i_title')
                <span class="text-sm text-red-400">
                    Add "title" to create an i Meeting
                </span>
            @enderror
        @endif
        @if ($chatPool && !$chatPool->completed)
            <button
                class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <a href="{{ route('chat') }}"
                    class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    See in i-Meeting
                </a>
            </button>
        @endif



        {{-- Start input data section --}}
        @if ($invUser && $order->status_id == 1)
            <button wire:click="acked({{ $order->id }})"
                class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span
                    class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Ack
                </span>
            </button>
        @endif

        @if (Gate::allows('isPurchaser') && $order->status_id == 2)
            <button wire:click="updateInstockqty({{ $order->id }})"
                class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span
                    class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Update Inventory Stock
                </span>
            </button>

            <button wire:click="requested({{ $order->id }})"
                class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span
                    class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Request
                </span>
            </button>
        @endif

        @if (Gate::allows('isOrderApprover') && $order->status_id == 3)
            <button wire:click="approved({{ $order->id }},{{ $order->qty }})"
                class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span
                    class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Approve
                </span>
            </button>
        @endif

        @if (Gate::allows('isOrderMaker') && $order->status_id == 4)
            <button wire:click="ordered({{ $order->id }})"
                class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span
                    class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Order
                </span>
            </button>
        @endif

        @if ($invUser && $order->status_id == 5)
            <button wire:click="arrived({{ $order->id }})"
                class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span
                    class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Arrived
                </span>
            </button>
        @endif

        @if ($invUser && $order->status_id == 6)
            <button wire:click="closed({{ $order->id }})"
                class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span
                    class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Close
                </span>
            </button>
        @endif

    </div>
    {{-- End Action Buttons --}}

    {{-- i Meeting Create Modal --}}
    <x-modal.card title="Create i-Meeting" wire:model='iMeetingModal' name="iMeeting">
        <x-input wire:model='i_title' label="Meeting Title" />

        <x-slot name="footer">
            <button wire:click="create_pool({{ $order->id }})"
                class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span
                    class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Create i-Meeting
                </span>
            </button>
        </x-slot>
    </x-modal.card>

    {{-- Cancel Order Modal  --}}
    <x-modal.card title="Cancel Order" wire:model='cancelOrderModal' name="cancelOrder">
        <div>
            <x-input label="Reason" class="w-full" wire:model='cancel_reason' placeholder="Write a reason" />
        </div>
        <x-slot name="footer">
            <x-button wire:click="cancel_order({{ $order->id }})" label="confirm"></x-button>
        </x-slot>
    </x-modal.card>
    {{-- End Cancel Order Modal  --}}

    {{-- History Modal  --}}
    <x-modal.card title="History" wire:model='historyModal' name='history'>
        <div class="px-2 pb-2">
            <ul>

                @foreach ($order->histories as $history)
                    <li class="cursor-pointer ">
                        <details>
                            <summary class="text-gray-400 hover:text-gray-900">{{ $history->status->name }}</summary>
                            <ul class="ml-4">
                                <li>
                                    <span class="flex">
                                        <x-icon name="user" class="w-5 h-5" />
                                        {{ $history->user->name }}
                                    </span>
                                </li>
                                <li class="ml-2 text-xs text-slate-300">
                                    {{ $history->updated_at }}
                                </li>
                                <li class="ml-2">
                                    <span class="text-red-700">{{ $history->content }}</span>
                                </li>
                            </ul>
                        </details>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-modal.card>
    {{-- End Order History --}}

    <x-modal.card title="Comments" wire:model='commentModal' name="comments">
        <div class="w-full p-2 overflow-y-scroll border border-gray-400 rounded commentSession h-80">
            @forelse ($order->comments as $comment)
                <div>
                    <div class="flex p-2 mb-3 bg-blue-200 rounded sent-message w-fit">
                        <div class="mr-2 profile">
                            <img src="{{ asset('images/user.png') }}" alt="user-avatar" class="w-6 h-6">
                        </div>
                        <div class="">
                            <div class="mb-1 text-sm">
                                <p class="font-semibold">{{ $comment->user->name }}</p>
                                <p class="text-xs">
                                    {{ $comment->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <p class="text-sm">{{ $comment->content }}
                                <button wire:click='reply_to_comment({{ $comment->id }})'
                                    class="text-xs text-blue-800 underline rounded-full">reply</button>
                            </p>
                        </div>
                    </div>
                    @foreach ($comment->replys as $reply)
                        <div class="flex mb-1 ml-10 sent-message w-fit">
                            <div class="mr-2 profile">
                                <img src="{{ asset('images/user.png') }}" alt="user-avatar" class="w-6 h-6">
                            </div>
                            <div class="mb-1 text-sm">
                                <p class="font-semibold">{{ $reply->user->name }}<span
                                        class="text-xs italic text-slate-500">
                                        {{ $reply->created_at->diffForHumans() }}
                                    </span></p>

                                <p>{{ $reply->content }}</p>
                            </div>
                        </div>
                    @endforeach
                    @if ($comment->id == $reply_toggle)
                        <div x-data x-transition.duration.500ms class="flex flex-col px-10 mb-2">
                            <div class="flex">
                                <input
                                    class="w-full text-sm border rounded focus:ring-0 dark:bg-gray-600 dark:text-gray-200"
                                    type="text" wire:model='reply_content' placeholder="reply to this comment" />
                                <button class="ml-4 bg-emerald-600 text-white px-2 py-1.5 rounded"
                                    wire:click="create_reply({{ $comment->id }})">Reply</button>
                            </div>
                            @error('reply_content')
                                <span class="text-xs text-red-500">Can't empty reply</span>
                            @enderror
                        </div>
                    @endif
                </div>
            @empty
                <p>No comment</p>
            @endforelse
        </div>
        <x-slot name="footer">
            <form>
                <textarea class="w-full mb-2 rounded bg-slate-100" title="Create a comment" wire:model='content'
                    placeholder="Type a comment"></textarea>
                <x-button wire:click.prevent="create_comment({{ $order->id }})">send</x-button>
            </form>
        </x-slot>
    </x-modal.card>

    {{-- Reject Supplier Data Modal  --}}
    <x-modal.card title="Reject Supplier Data" wire:model='rejectSupplierDataModal' name="rejectSupplierData">
        <div>
            <x-input label="Reason" class="w-full" wire:model='reject_note' placeholder="Write a reason for this" />
        </div>
        <x-slot name="footer">
            <x-button wire:click="rejectSupplierData" label="confirm"></x-button>
        </x-slot>
    </x-modal.card>
    {{-- End Supplier Data Modal  --}}
</div>




{{-- <script>
        console.log("Hello");

        function mmUnitCalc(gramWeight) {
            let kyat = gramWeight * (1 / 16.606);
            kyat.toFixed(2)
            let answerKyat = Math.floor(kyat);
            console.log(Math.floor(kyat));

            let pae = (kyat - answerKyat) * 16;
            let answerPae = Math.floor(pae);

            let yawe = (pae - answerPae) * 8;
            let answerYawe = yawe.toFixed(2);
            if (answerKyat > 0) {
                return `${answerKyat} ကျပ် ${answerPae} ပဲ ${answerYawe} ရွေး`;
            } else if (answerPae > 0) {
                return ` ${answerPae} ပဲ ${answerYawe} ရွေး`;
            } else {
                return `${answerYawe} ရွေး`;
            }
        }

        function gramToKpy() {
            let gram = document.getElementById("weightInGram").value;
            // console.log(gram);
            let answer = mmUnitCalc(gram);
            document.getElementById("weight").innerHTML = answer;
        }
        gramToKpy()
    </script> --}}
{{-- </div> --}}
{{-- @endsection --}}
