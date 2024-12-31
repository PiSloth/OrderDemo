<div>
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
                @php $rowspan = count($products); @endphp
                @foreach ($products as $branchName => $details)
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
                                $color = 'white';
                                $backgroundColor = 'green';
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
                    <tr class="mt-4 border-b-2 border-teal-900 odd:bg-white even:bg-gray-100">
                        @if ($loop->first)
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white"
                                rowspan="{{ $rowspan }}">
                                {{ ucfirst($productName) }}
                            </th>
                        @endif
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-6 py-4">{{ $branchName }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-6 py-4">{{ $focus }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-6 py-4">{{ $avg_sale }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-6 py-4">{{ $balance }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-6 py-4">{{ $remainingToSale }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-6 py-4">{{ $dueDate }}</td>
                        <td style="color: {{ $color }}; background-color: {{ $backgroundColor }}"
                            class="px-6 py-4">{{ $diffWithDueDate }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
