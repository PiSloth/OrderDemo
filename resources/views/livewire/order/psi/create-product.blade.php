<div>
    <div class="block p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-900 dark:border-gray-700">
        <h2 class="mb-5 text-2xl font-bold text-center dark:text-gray-200">PSI အတွက် Product သတ်မှတ်ပါ</h2>
        {{-- <div wire:loading class="absolute px-2 text-sm bg-blue-800 rounded text-slate-50">Searching. . . .</div> --}}
        <div>
            <form class="p-2" wire:submit.prevent="createProduct">
                <div class="flex flex-col gap-4 md:flex-row">
                    {{-- Image Upload Section --}}
                    <div class="w-full md:w-1/3">
                        <div class="flex flex-col items-center">
                            <div wire:loading wire:target="productImg">
                                {{-- <span class="text-green-700">uploading . . . .</span> --}}
                            </div>
                            @if ($productImg)
                                <img src="{{ $productImg->temporaryUrl() }}"
                                    class="object-cover w-full h-auto mb-4 rounded-lg">
                            @else
                                <div class="w-full h-48 mb-4 bg-gray-200 rounded-lg"></div>
                            @endif
                            <input type="file" wire:model="productImg" id="image" accept="image/jpeg,image/jpg"
                                class="my-2 text-gray-700 border border-gray-500 rounded dark:text-gray-200">
                            @error('productImg')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Product Details Section --}}
                    <div class="w-full md:w-2/3">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-select label="Category" wire:model.live="category_id" placeholder="Select a Category"
                                :options="$categories" option-label="name" option-value="id" />
                            <x-select label="Quality" wire:model.live="quality_id" placeholder="quality"
                                :async-data="route('qualities.index')" option-label="name" option-value="id" />
                            <x-select label="Design" wire:model.live="design_id" placeholder="Choose a design"
                                :async-data="route('designs.index')" option-label="name" option-value="id" />
                            <x-select label="ပုံသဏ္ဍာန်" wire:model.live="shape_id" placeholder="Select Shape"
                                :async-data="route('psi.shapes')" option-label="name" option-value="id">
                                <x-slot name="afterOptions" class="flex justify-center p-2"
                                    x-show="displayOptions.length === 0">
                                    <x-button x-on:click='close' wire:click='createShape' primary flat full>
                                        <span x-html="`<b>${search}</b> ကို အသစ်ဖန်တီးမယ်`"></span>
                                    </x-button>
                                </x-slot>
                            </x-select>
                            <div>
                                <label for="length"
                                    class="block font-medium text-gray-900 text-sm/6">အတိုင်းအတာ</label>
                                <div class="relative mt-2 rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">~</span>
                                    </div>
                                    <x-input wire:model='length' type="number" step="any" name="length"
                                        id="length"
                                        class="block w-full rounded-md border-0 py-1.5 pl-7 pr-20 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm/6"
                                        placeholder="0.00" />
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <label for="uom" class="sr-only">uom</label>
                                        <select id="uom" name="uom" wire:model='uom_id'
                                            class="h-full py-0 pl-2 text-gray-500 bg-transparent border-0 rounded-md pr-7 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                            <option>Select a uom</option>
                                            @foreach ($uoms as $uom)
                                                <option value={{ $uom->id }}>{{ $uom->name }} </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <x-select label="ပြုလုပ် အမျိုးအစား" wire:model.live='manufacture_technique_id'
                                placeholder="Select a technique" :options="$techniques" option-label="name"
                                option-value="id" />
                            <x-input type="number" step="any" label="Weight" wire:model='weight'
                                placeholder="gram" />
                            <div class="sm:col-span-2">
                                <x-input label="Remark" wire:model="remark" placeholder="Enter optional remark" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" wire:loading.attr="disabled"
                        class="first-letter:text-white bg-gradient-to-r from-red-400 via-red-500 to-red-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 shadow-lg shadow-red-500/50 dark:shadow-lg dark:shadow-red-800/80 font-medium rounded-lg px-5 py-2.5 text-center me-2 mb-2 flex items-center justify-center">
                        <img src="{{ asset('images/note.png') }}" alt="Note icon" class="w-6 h-6 mr-2">Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
