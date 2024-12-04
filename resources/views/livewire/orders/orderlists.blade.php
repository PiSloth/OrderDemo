<div>
    <div class="flex justify-between mb-4">

        {{-- <div class="self-end">
            <x-button outline sky label="Filter" icon="filter" onclick="$openModal('filter')"></x-button>
        </div> --}}

        <div class="flex gap-2 ">
            <x-datetime-picker wire:model.live.debounce="start_date" without-time='true' label="Start Date"
                placeholder="Now" />
            <x-datetime-picker wire:model.live.debounc="end_date" without-time='true' label="End Date" />
        </div>
    </div>
    <div>
        <x-modal.card title="Filter" name="filter" wire:model='filter'>

            <div class="grid gap-5 mb-5 md:grid-cols-5">
                <div>
                    <label for="priority"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Priority</label>
                    <select id="branch" wire:model.live='priority'
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="">All</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="branch"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Branch</label>
                    <select id="branch" wire:model.live='branch'
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="">All</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
                    <select id="status" wire:model.live='status'
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="">All</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="category"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Category</label>
                    <select id="category" wire:model.live='category'
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="" selected>All</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-select label="Design" wire:model.live="design_id" placeholder="Choose a design" :async-data="route('designs.index')"
                        option-label="name" option-value="id" />
                </div>
                <div>
                    <label for="weight"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Weight</label>
                    <input type="number" name="" id="weight" wire:model.live='weight'
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                </div>
            </div>
        </x-modal.card>
    </div>
    {{-- Filter block for order list  --}}



    {{-- Start Order Table section  --}}
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-200 uppercase bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        ID
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Category
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Design
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Weight
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Qty
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Priority
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="mb-5 bg-white border-b dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                        wire:key="{{ $order->id }}">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $order->id }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $order->category->name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->design->name }}
                        </td>
                        <td class="px-6 py-4 ">
                            {{ $order->status->name }}
                        </td>
                        <td class="px-6 py-4" x-data="{ kyat: '{{ $order->weight }}' }">
                            <span x-text="kyat.toFix(2) "></span>
                            <button
                                onclick="mmUnitCalcReturn({{ $order->weight }},{{ $order->id }})">{{ $order->weight }}</button>
                            <div id="weightId{{ $order->id }}" class="absolute text-sm"></div>
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->qty }}
                        </td>
                        <td
                            class="px-6 py-4
                    @if ($order->priority_id == 1) bg-red-400
                        text-white @endif
                    @if ($order->priority_id == 2) bg-yellow-200
                        text-gray-600 @endif
                    @if ($order->priority_id == 3) bg-green-400
                        text-white @endif">
                            {{ $order->priority->name }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="/order/detail?order_id={{ $order->id }}"
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                                wire:navigate>View</a>
                        </td>
                    </tr>
                @empty
                    <p>Empty record</p>
            </tbody>
            @endforelse
        </table>
    </div>
    {{-- End Order Table --}}



    {{-- <div class="my-4">{{ $orders->withQueryString()->links() }}</div> --}}
</div>


<script>
    //toggle function for hidden block
    function toggleDiv() {
        var myDiv = document.getElementById("myDiv");
        console.log("Throunged toggle");
        myDiv.classList.toggle("hidden");
    }

    //Gram to Kyat,Pae,Yawe Calculator Function
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

    //return innerhtml by his orderid
    function mmUnitCalcReturn(gramWeight, orderId) {
        let answer = mmUnitCalc(gramWeight);
        console.log("weightId" + orderId)
        document.getElementById("weightId" + orderId).innerHTML = answer;
    }
</script>
