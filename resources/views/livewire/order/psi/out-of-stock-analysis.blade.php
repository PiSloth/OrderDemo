<div>
    <div class="mb-4">
        <button id="dropdownSearchButton" data-dropdown-toggle="dropdownSearch"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            type="button">Dropdown filter <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="m1 1 4 4 4-4" />
            </svg></button>
        <div class="p-4 rounded">
            @foreach ($selectedBranch as $b)
                <span class="text-blue-400 cursor-pointer group hover:text-red-400"
                    wire:click='removeFilter("{{ $b }}")'> <span class="group-hover:hidden">#</span>
                    <span class="hidden group-hover:inline" x-transition>x</span> {{ ucfirst($b) }}</span>
            @endforeach
        </div>

        <!-- Dropdown menu -->
        <div id="dropdownSearch" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">

            <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200"
                aria-labelledby="dropdownSearchButton">

                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b1" type="checkbox" value="" wire:model.live='br1'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b1"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-300">Branch
                            1</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b2" type="checkbox" value="" wire:model.live='br2'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b2"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-300">Branch
                            2</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b3" type="checkbox" value="" wire:model.live='br3'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b3"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-300">Branch
                            3</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b4" type="checkbox" value="" wire:model.live='br4'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b4"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-300">Branch
                            4</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b5" type="checkbox" value="" wire:model.live='br5'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b5"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-300">Brnach
                            5</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="b6" type="checkbox" value="" wire:model.live='br6'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="b6"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-300">Branch
                            6</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="online" type="checkbox" value="" wire:model.live='online_sale'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="online"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-300">Online
                            Sale</label>
                    </div>
                </li>
                <li>
                    <div class="flex items-center p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input id="ho" type="checkbox" value="" wire:model.live='ho'
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="ho"
                            class="w-full text-sm font-medium text-gray-900 rounded ms-2 dark:text-gray-300">HO</label>
                    </div>
                </li>
            </ul>
            {{-- <a href="#"
                class="flex items-center p-3 text-sm font-medium text-red-600 border-t border-gray-200 rounded-b-lg bg-gray-50 dark:border-gray-600 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-red-500 hover:underline">
                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 20 18">
                    <path
                        d="M6.5 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM8 10H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Zm11-3h-6a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2Z" />
                </svg>
                Delete user
            </a>  --}}
        </div>

    </div>
    <h1 class="text-xl">ပစ္စည်းမပြတ်စေရန် စစ်ဆေးပါ</h1>
    <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Product name
                </th>
                <td scope="col" class="px-6 py-4">
                    Branches
                </td>
                <td scope="col" class="px-6 py-4">
                    Focus
                </td>
                <td scope="col" class="px-6 py-4">
                    Real Sale
                </td>
                <td scope="col" class="px-6 py-4">
                    Balance
                </td>
                <td scope="col" class="px-6 py-4">
                    Remaining to Sale
                </td>
                <td scope="col" class="px-6 py-4">
                    နောက်ဆုံးပို့ရမည့်ရက်
                </td>
                <td scope="col" class="px-6 py-3">
                    ကွာဟနေသော ရက်
                </td>
            </tr>
        </thead>
        <tbody>
            @foreach ($analysis as $productName => $products)
                {{-- @dd($products) --}}
                @php
                    $rowspan = count($products);

                @endphp
                @foreach ($products as $branchName => $details)
                    {{-- @dd($products) --}}
                    @php
                        $focus = $details['focus'];
                        $balance = $details['balance'];
                        // $balance = 0;
                        $avg_sale = ceil($details['avg_sale']);
                        $remainingToSale = floor($balance / ($avg_sale > 0 ? $avg_sale : 1));
                        if ($balance == 0) {
                            $dueDate = \Carbon\Carbon::now()->format('M j, y');
                        } else {
                            $dueDate = \Carbon\Carbon::now()->addDays($remainingToSale)->format('M j, y');
                        }
                        $diffWithDueDate = \Carbon\Carbon::now()->diffInDays($dueDate);
                        $color = '';
                        $backgroundColor = '';

                        switch (true) {
                            case $diffWithDueDate == 0:
                                $color = 'white';
                                $backgroundColor = 'red';
                                break;
                            case $diffWithDueDate > 3:
                                $color = '#1E3A8A';
                                $backgroundColor = '#F5F5F5';
                                break;
                            case $diffWithDueDate == 3:
                                $color = 'white';
                                $backgroundColor = 'blue';
                                break;
                            case $diffWithDueDate < 3:
                                $color = 'green';
                                $backgroundColor = 'yellow';
                                break;
                            default:
                                break;
                        }
                    @endphp
                    <tr class="mt-4 border-b-2 border-gray-400 odd:bg-white even:bg-gray-100">
                        @if ($loop->first)
                            <th scope="row" class="px-6 py-4 text-teal-500 font-lg dark:text-white"
                                rowspan="{{ $rowspan }}">
                                <img class="w-48 mb-2 border rounded-full md:rounded-lg"
                                    src="{{ asset('/storage/' . $images[$productName]) }}" alt="product_photo" />
                                {{ ucfirst($productName) }}
                            </th>
                        @endif
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-3 py-2 md:px-6 md:py-4">{{ $branchName }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-3 py-2 md:px-6 md:py-4">{{ $focus }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-3 py-2 md:px-6 md:py-4">{{ $avg_sale }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-3 py-2 md:px-6 md:py-4">{{ $balance }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-3 py-2 md:px-6 md:py-4">{{ $remainingToSale }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-3 py-2 md:px-6 md:py-4">{{ $dueDate }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-3 py-2 md:px-6 md:py-4">{{ $diffWithDueDate }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
