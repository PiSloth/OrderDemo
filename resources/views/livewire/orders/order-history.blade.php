<div>



    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full p-2 text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <div class="grid grid-cols-1 gap-4 p-4 mb-4 bg-blue-100 rounded sm:grid-cols-2">
                <x-datetime-picker label="Start Date" placeholder="Start Date" parse-format="YYYY-MM-DD HH:mm"
                    wire:model.live="start_date" without-time=true />
                <x-datetime-picker label="End Date" placeholder="End Date" parse-format="YYYY-MM-DD HH:mm"
                    wire:model.live="end_date" without-time=true />
            </div>
            <x-button class="mb-2" wire:click='export'>Exprot</x-button>

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
                            {{ $order->arrival_qty ? $order->arrival_qty : 0 }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="#"
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div>{{ $orders->links() }}</div>
    </div>

</div>
