<div>


    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Branch
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Product name
                    </th>
                    <th scope="col" class="px-6 py-3">
                        weight
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Total Sale
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $item)
                    <tr
                        class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ ucfirst($item->name) }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $item->product }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $item->weight }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $item->total_sale }}
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
