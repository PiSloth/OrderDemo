<div>


    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
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
                @foreach ($products as $branchName => $products)
                    @php $rowspan = count($products); @endphp
                    @foreach ($products as $productName => $details)
                        <tr class="border-b odd:bg-white even:bg-gray-50">
                            @if ($loop->first)
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white"
                                    rowspan="{{ $rowspan }}">
                                    {{ ucfirst($branchName) }}
                                </th>
                            @endif
                            <td class="px-6 py-4">{{ $productName }}</td>
                            <td class="px-6 py-4">{{ $details['weight'] }}</td>
                            <td class="px-6 py-4">{{ $details['total_sale'] }}</td>
                        </tr>
                    @endforeach
                @endforeach

            </tbody>
        </table>
    </div>

</div>
