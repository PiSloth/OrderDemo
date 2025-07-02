<div>



    <div class="relative  shadow-md sm:rounded-lg">
        <table class="w-full p-2 text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <div class="w-40 mb-3">
                <select id="branches" wire:model.live="branch_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-200">
                    <option selected value="0">Filter by Branch</option>

                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 mb-4 bg-blue-100 rounded sm:grid-cols-2">
                <x-datetime-picker label="Start Date" placeholder="Start Date" parse-format="YYYY-MM-DD HH:mm"
                    wire:model.live="start_date" without-time=true />
                <x-datetime-picker label="End Date" placeholder="End Date" parse-format="YYYY-MM-DD HH:mm"
                    wire:model.live="end_date" without-time=true />
            </div>

            <x-button green right-icon="download" class="m-2" wire:click='export'>Exprot</x-button>

            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Order တင်သည့်ရက်
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Branch
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Shop {{-- Category --}}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Product {{-- Design --}}
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Design
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Size
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Quantity
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Weight
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Arrival Qty
                    </th>
                    <th scope="col" class="px-6 py-3 sr-only">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr
                        class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ date_format($order->created_at, 'F j,Y') }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $order->branch->name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->category->name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->design->name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->detail }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->size }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->qty }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->weight }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->status->name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $order->arqty ? $order->arqty : 0 }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="/order/detail?order_id={{ $order->id }}" wire:navigate
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline">view</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-3">{{ $orders->links() }}</div>
    </div>

</div>
