<div>
    <div>
        <div class="container p-4 mx-auto mt-8 space-y-4 sm:p-0">
            <div class="flex w-72   gap-2 ">
                <x-datetime-picker wire:model.live.debounce="start_date" without-time='true' label="Start Date" placeholder="Now"
                     />
                <x-datetime-picker wire:model.live.debounc="end_date" without-time='true' label="End Date"
                     />
            </div>
            <h2 class="font-mono">ဒီဇိုင်း အခြေပြု အလေးချိန်၊ အရေအတွက်ပြ ဇယား</h1>
                <div class="flex-1 p-4 bg-white border rounded shadow" style="height: 32rem;">
                    <livewire:livewire-column-chart key="{{ $columnChartModel->reactiveKey() }}" :column-chart-model="$columnChartModel" />
                </div>
        </div>
        {{-- Category Pie Chatr and Quality column Chart  --}}
        <div class="flex-col py-2 my-2 border border-gray-300 rounded bg-slate-200">
            <div x-data="{ weight: true }" class="m-2">
                {{-- top select box  --}}
                <button x-on:click="weight = true" x-bind:class="weight ? 'bg-cyan-500 text-white' : 'bg-slate-300'"
                    class="px-4 py-1 text-gray-500 rounded "
                    wire:click='toggle("forCategoryQuality","weight")'>Weight</button>
                <button @click="weight = false" :class="!weight ? 'bg-cyan-500 text-white' : 'bg-slate-300'"
                    class="px-4 py-1 text-gray-500 rounded" wire:click='toggle("forCategoryQuality","qty")'>Qty</button>
            </div>
            <div class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
                <div class="flex-1 p-4 bg-white border rounded shadow" style="height: 32rem;">
                    <livewire:livewire-pie-chart key="{{ $categoryPieChartModel->reactiveKey() }}" :pie-chart-model="$categoryPieChartModel" />
                </div>
                <div class="flex-1 p-4 bg-white border rounded shadow" style="height: 32rem;">
                    <livewire:livewire-line-chart key="{{ $qualineChartModel->reactiveKey() }}" :line-chart-model="$qualineChartModel" />
                </div>
            </div>
        </div>

        {{-- <div class="p-4 bg-white border rounded shadow" style="height: 32rem;">
            <livewire:livewire-area-chart key="{{ $areaChartModel->reactiveKey() }}" :area-chart-model="$areaChartModel" />
        </div> --}}

        {{-- <div class="p-4 bg-white border rounded shadow" style="height: 32rem;">
                <livewire:livewire-line-chart
                    key="{{ $multiLineChartModel->reactiveKey() }}"
                    :line-chart-model="$multiLineChartModel"
                /> --}}
        {{-- </div> --}}

        {{-- <div class="p-4 bg-white border rounded shadow" style="height: 32rem;">
            <livewire:livewire-column-chart key="{{ $multiColumnChartModel->reactiveKey() }}" :column-chart-model="$multiColumnChartModel" />
        </div> --}}
    </div>

    {{-- <div class="p-4 bg-white border rounded shadow" style="height: 32rem;">
        <livewire:livewire-radar-chart key="{{ $radarChartModel->reactiveKey() }}" :radar-chart-model="$radarChartModel" />
    </div>

    <div class="p-4 bg-white border rounded shadow" style="height: 32rem;">
        <livewire:livewire-tree-map-chart key="{{ $treeChartModel->reactiveKey() }}" :tree-map-chart-model="$treeChartModel" />
    </div> --}}
</div>
</div>
