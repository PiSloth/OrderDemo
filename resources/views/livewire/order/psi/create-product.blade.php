<div>
    <div class="block p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-900 dark:border-gray-700">
        <h2 class="mb-5 text-2xl font-bold text-center dark:text-gray-200">PSI အတွက် Product သတ်မှတ်ပါ</h2>
        <div wire:loading class="absolute px-2 text-sm bg-blue-800 rounded text-slate-50">Searching. . . .</div>
        <div>
            <form class="p-2" wire:submit='create_order'>
                <div class="grid grid-cols-1 mb-5 md:grid-cols-3 md:space-x-4">


                    <div class="mt-3 priority-selection md:mt-0">
                        <label for="category"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Category</label>
                        <select id="category"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            wire:model.live='category_id'>
                            <option value="" selected>Select a Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="text-sm text-red-400">that's required</span>
                        @enderror
                    </div>

                    <div class="priority-selection">
                        <x-select label="Quality" wire:model.live="quality_id" placeholder="quality" :async-data="route('qualities.index')"
                            option-label="name" option-value="id" />
                        @error('quality_id')
                            <span class="text-sm text-red-400">that's required</span>
                        @enderror
                    </div>

                    <div class="mt-3 priority-selection md:mt-0">

                        <x-select label="Design" wire:model.live="design_id" placeholder="Choose a desing"
                            :async-data="route('designs.index')" option-label="name" option-value="id" />
                        @error('design_id')
                            <span class="text-sm text-red-400">that's required</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 mb-5 md:grid-cols-3 md:space-x-4">
                    <x-select label="ပုံသဏ္ဍာန်" wire:model.live="shape_id" placeholder="Select Shpae" :async-data="route('psi.shapes')"
                        option-label="name" option-value="id">
                        <x-slot name="afterOptions" class="flex justify-center p-2"
                            x-show="displayOptions.length === 0">
                            <x-button x-on:click='close' wire:click='createShape' primary flat full>
                                <span x-html="`<b>${search}</b> ကို အသစ်ဖန်တီးမယ်`"></span>
                            </x-button>
                        </x-slot>
                    </x-select>


                    <div>
                        <label for="length" class="block text-sm/6 font-medium text-gray-900">အတိုင်းအတာ</label>
                        <div class="relative mt-2 rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">~</span>
                            </div>
                            <x-input wire:model='length' type="integer" name="length" id="length"
                                class="block w-full rounded-md border-0 py-1.5 pl-7 pr-20 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm/6"
                                placeholder="0.00" />
                            <div class="absolute inset-y-0 right-0 flex items-center">
                                <label for="uom" class="sr-only">uom</label>
                                <select id="uom" name="uom" wire:model='uom_id'
                                    class="h-full rounded-md border-0 bg-transparent py-0 pl-2 pr-7 text-gray-500 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                    <option>Select a uom</option>
                                    @foreach ($uoms as $uom)
                                        <option value={{ $uom->id }}>{{ $uom->name }} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>


                    <div class="mt-3 priority-selection md:mt-0">
                        <label for="technique"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ပြုလုပ်
                            အမျိုးအစား</label>
                        <select id="technique"
                            class="uppercase bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            wire:model.live='manufacture_technique_id'>
                            <option value="" selected>Select a technique</option>
                            @foreach ($techniques as $technique)
                                <option value="{{ $technique->id }}">{{ $technique->name }}</option>
                            @endforeach
                        </select>
                        @error('technique_id')
                            <span class="text-sm text-red-400">that's required</span>
                        @enderror
                    </div>
                </div>

                <div class="grid
                            grid-cols-1 mb-5 md:grid-cols-3 md:space-x-4">
                    <x-input label="Weight" wire:model='weight' placeholder="gram" />


                </div>
                {{-- Photo upload --}}
                <div class="my-2">
                    <div wire:loading wire:target='productImg'>
                        <span class="text-green-700">uploading . . . .</span>
                    </div>
                    <input wire:model="productImg" id="image" accept="image/jpeg,image/jpg"
                        class="my-2 text-gray-700 border border-gray-500 rounded dark:text-gray-200" type="file" />
                    @error('productImg')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @if ($productImg)
                        <div class="w-36 h-36">
                            <img src="{{ $productImg->temporaryUrl() }}" />
                        </div>
                    @endif
                </div>
                <button type="button" wire:click='createProduct'
                    class=" first-letter:text-white bg-gradient-to-r mt-5 from-red-400 via-red-500 to-red-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 shadow-lg shadow-red-500/50 dark:shadow-lg dark:shadow-red-800/80 font-medium rounded-lg px-5 py-2.5 text-center me-2 mb-2 flex items-center justify-center">
                    <img src="{{ asset('images/note.png') }}" alt="Note icon" class="w-6 h-6 mr-2">Create
                </button>
            </form>
        </div>
    </div>
</div>
