<div>
    <form wire:submit.prevent="save" class="p-4">
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-4">
                <div class="flex flex-col items-center p-4 border rounded-lg">
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="object-cover w-48 h-48 mb-4 rounded-full">
                    @elseif (isset($product) && $product->productPhoto)
                        <img src="{{ asset('storage/' . $product->productPhoto->image) }}"
                            class="object-cover w-48 h-48 mb-4 rounded-full">
                    @else
                        <div class="w-48 h-48 mb-4 bg-gray-200 rounded-full"></div>
                    @endif
                    <x-button label="Change Photo" onclick="document.getElementById('photo-upload').click()" />
                    <input type="file" id="photo-upload" wire:model="photo" class="hidden">
                    @error('photo')
                        <span class="text-red-500 error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-span-12 md:col-span-8">
                <div class="p-4 border rounded-lg">
                    <h3 class="mb-4 text-lg font-semibold">Product Information</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-select label="Category" placeholder="Select one category" wire:model.defer="category_id"
                            :options="$categories" option-label="name" option-value="id" />
                        <x-select label="Quality" placeholder="Select one quality" wire:model.defer="quality_id"
                            :options="$qualities" option-label="name" option-value="id" />
                        <x-select label="Design" placeholder="Select one design" wire:model.defer="design_id"
                            :options="$designs" option-label="name" option-value="id" />
                        <x-input wire:model.defer="weight" label="Weight" placeholder="Enter weight" />
                        <x-select label="Shape" placeholder="Select one shape" wire:model.defer="shape_id"
                            :options="$shapes" option-label="name" option-value="id" />
                        <x-select label="UOM" placeholder="Select one UOM" wire:model.defer="uom_id"
                            :options="$uoms" option-label="name" option-value="id" />
                        <x-select label="Manufacture Technique" placeholder="Select one technique"
                            wire:model.defer="manufacture_technique_id" :options="$manufactureTechniques" option-label="name"
                            option-value="id" />
                        <x-input wire:model.defer="length" label="Length" placeholder="Enter length" />
                        <x-input wire:model.defer="remark" label="remark" placeholder="Enter remark" />
                        <div class="flex items-center">
                            <x-toggle wire:model.defer="is_suspended" label="Is Suspended" />
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <x-button type="submit" label="Save" primary />
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
