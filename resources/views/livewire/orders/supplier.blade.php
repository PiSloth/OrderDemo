@extends('livewire.orders.layout.dashboard-layout')

@section('content')
    <div class="pt-10 pl-10 pr-10 text-sm lg:pl-72 ">

        {{-- supplier add modal --}}
        <div class="flex items-center justify-between">
            <h1 class="mb-4 text-3xl font-bold dark:text-gray-100">Supplier</h1>
            <button class="bg-indigo-400 text-white px-2 py-1.5 rounded focus:ring-4 focus:ring-indigo-300"
                x-on:click="$openModal('addSupplierModal')">Add New Supplier</button>
        </div>
        {{-- end supplier add modal --}}

        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4 lg:gap-8">
            <div>
                <label class="font-medium dark:text-gray-100">Choose a supplier</label>
                <x-select wire:model.live="supplier_id" placeholder="Choose a supplier" :async-data="route('suppliers')" option-label="name"
                    option-value="id" />
            </div>

            <div>
                <label class="font-medium dark:text-gray-100">Choose a quality</label>
                <x-select wire:model.live="quality_id" placeholder="Choose a quality" :async-data="route('qualities.index')" option-label="name"
                    option-value="id" />
            </div>
            <div>
                <label class="font-medium dark:text-gray-100">Choose a design</label>
                <x-select wire:model.live="design_id" placeholder="Choose a desing" :async-data="route('designs.index')" option-label="name"
                    option-value="id" />
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
                <x-input type='number' step=0.01 wire:model='weight' id="weightInSupplier" placeholder='gram' />
                <div id="weightInnerText" class="text-teal-600 absolute" wire:ignore></div>
                <x-input id="gramToMmUnitSupplier" type="text" wire:model.live.debounce='weight_in_kpy' disabled
                    class="absolute hidden text-sm text-blue-900 ring-white dark:text-blue-300" />
            </div>
            <div>
                <label class="font-medium dark:text-gray-100">လက်ခ</label>
                <x-input type="number" wire:model='laukkha' placeholder="Enter လက်ခ" />
            </div>
            <div class="relative">
                <label class="font-medium dark:text-gray-100">အလျော့တွက်</label>
                <x-input type='number' step=0.01 wire:model='youktwat' id="youktwatInSupplier" placeholder='gram' />
                <div id="youktwatInnerText" class="text-teal-600 absolute" wire:ignore></div>
                <x-input hidden id="gramToMmUnitYouktwatSupplier" type="text" wire:model='youktwat_in_kpy'
                    class="absolute hidden text-sm text-blue-900 dark:text-blue-300" />
            </div>
            <div>
                <label class="font-medium dark:text-gray-100">Remark</label>
                <x-input type="text" wire:model='product_remark' />
            </div>
        </div>
        <button wire:click='createSupplierProduct' class="bg-amber-700 text-gray-100 mt-5 px-2 py-1.5 rounded">Add Supplier
            Product</button>


        {{-- supplier table start --}}
        <div class="relative overflow-x-auto rounded-lg shadow-md mt-8">
            <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                <thead class="text-xs text-gray-900 uppercase bg-amber-200">
                    <tr>
                        <th scope="col" class="px-6 py-3">
                            Supplier name
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Quality
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Design
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Detail
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Weight
                        </th>
                        <th scope="col" class="px-6 py-3">
                            အလျော့တွက်
                        </th>
                        <th scope="col" class="px-6 py-3">
                            လက်ခ
                        </th>
                        <th scope="col" class="px-6 py-3">
                            remark
                        </th>
                        <th scope="col" class="px-6 py-3">
                            <span class="sr-only">Aciton</span>
                        </th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($supplierproducts as $product)
                        <tr class="bg-white border-b dark:bg-gray-700 dark:border-gray-700">
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $product->supplier->name }}
                            </th>
                            <td class="px-6 py-4">
                                {{ $product->quality->name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $product->design->name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $product->detail }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($product->quality->name == '18K')
                                    {{ $product->weight }}g
                                @else
                                    {{ $product->weight_in_kpy }}
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                {{ $product->youktwat }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $product->laukkha }}

                            </td>
                            <td class="px-6 py-4">
                                {{ $product->product_remark }}
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    class="px-4 py-1 font-medium bg-green-600 rounded text-gray-50 dark:text-blue-500 hover:underline">edit</button>
                                <a href="#"
                                    class="px-4 py-1 font-medium bg-yellow-600 rounded text-gray-50 dark:text-blue-500 hover:underline">update</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- supplier table end --}}

        {{-- add supplier modal start --}}
        <x-modal name="addSupplierModal">
            <x-card title="Add Supplier">
                <form class="text-sm">
                    <div class="flex flex-col gap-2 mb-4">
                        <label for="suppliername" class="font-medium">Supplier Name</label>
                        <input type="text" id="suppliername" wire:model="name" placeholder="Enter supplier name"
                            class="text-sm border border-gray-200 rounded" required>
                    </div>

                    <div class="flex flex-col gap-2 mb-4">
                        <label for="address" class="font-medium">Address</label>
                        <input type="text" id="address" wire:model="address" placeholder="Enter supplier address"
                            class="text-sm border border-gray-200 rounded" required>
                    </div>

                    <div class="flex flex-col gap-2 mb-4">
                        <label for="phone" class="font-medium">Phone number</label>
                        <input type="text" id="phone" wire:model="phone"
                            placeholder="Enter supplier phone number" class="text-sm border border-gray-200 rounded"
                            required>
                    </div>

                    <div class="flex flex-col gap-2 mb-4">
                        <label for="error" class="font-medium">Error Rate</label>
                        <input type="number" id="error" wire:model="error_rate" placeholder="Enter error"
                            class="text-sm border border-gray-200 rounded">
                    </div>

                    <div class="flex flex-col gap-2 mb-4">
                        <label for="remark" class="font-medium">Remark</label>
                        <input type="text" id="remark" wire:model="remark" placeholder="Add remark"
                            class="text-sm border border-gray-200 rounded">
                    </div>

                    <x-slot name="footer" class="flex justify-end gap-x-4">
                        <button flat label="Cancel" x-on:click="close"
                            class="bg-red-400 text-white px-2 py-1.5 mr-2 rounded focus:ring-4 focus:ring-red-300">Cancel</button>

                        <button wire:click="createSupplier" type="submit"
                            class="bg-teal-400 text-white px-2 py-1.5 rounded focus:ring-4 focus:ring-teal-300">Agree</button>
                    </x-slot>
                </form>
            </x-card>
        </x-modal>
        {{-- add supplier modal end --}}

        {{-- update modal --}}
        <x-modal name="">
            <x-card title="Add Supplier">
                <form class="text-sm">
                    <div class="flex flex-col gap-2 mb-4">
                        <label for="suppliername" class="font-medium">Supplier Name</label>
                        <input disabled type="text" id="suppliername" placeholder="Enter supplier name"
                            wire:model='supplier' class="text-sm border border-gray-200 rounded" required>
                    </div>

                    <div>
                        <div class="flex flex-col gap-2 mb-4">
                            <x-select wire:model.live="quality_id" placeholder="Choose a desing" :async-data="route('qualities.index')"
                                option-label="name" option-value="id" />
                        </div>
                        <div class="flex flex-col gap-2 mb-4">
                            <x-select wire:model.live="design_id" placeholder="Choose a desing" :async-data="route('designs.index')"
                                option-label="name" option-value="id" />
                        </div>
                        <div class="flex flex-col gap-2 mb-4">
                            <x-input type="text" wire:model='detail' />
                        </div>
                        <div class="flex flex-col gap-2 mb-4">
                            <x-input type='number' step=0.01 wire:model='weight' id="weightInSupplier"
                                placeholder='အလေးချိန် / g' />
                            <div id="weightInnerText" wire:ignore></div>
                            <x-input id="gramToMmUnitSupplier" type="text" wire:model.live.debounce='weight_in_kpy'
                                disabled class="absolute hidden" />
                        </div>
                        <div class="flex flex-col gap-2 mb-4">

                            <x-input type='number' step=0.01 wire:model='youktwat' id="youktwatInSupplier"
                                placeholder='အလျော့တွက်/ g' />
                            <div id="youktwatInnerText" wire:ignore></div>
                            <x-input hidden id="gramToMmUnitYouktwatSupplier" type="text" wire:model='youktwat_in_kpy'
                                class="absolute hidden text-sm text-blue-900 dark:text-blue-300" />

                        </div>
                        <div class="flex flex-col gap-2 mb-4">
                            <x-input type="number" wire:model='laukkha' />
                        </div>
                        <div class="flex flex-col gap-2 mb-4">
                            <x-input type="text" wire:model='product_remark' />
                        </div>
                    </div>

                    <x-slot name="footer" class="flex justify-end gap-x-4">
                        <button flat label="Cancel" x-on:click="close"
                            class="bg-red-400 text-white px-2 py-1.5 mr-2 rounded focus:ring-4 focus:ring-red-300">Cancel</button>

                        <button wire:click="updateProduct" type="submit"
                            class="bg-teal-600 text-white px-2 py-1.5 rounded focus:ring-4 focus:ring-teal-300">Agree</button>
                    </x-slot>
                </form>
            </x-card>
        </x-modal>
        {{-- end update modal --}}
    </div>
@endsection
