<div>
    <div class="w-1/2 mx-auto mb-4">
        <x-datetime-picker wire:model.live.debounce="report_types_date_filter" without-time='true' label="Date"
            placeholder="Now" />
    </div>
    {{-- <h1 class="text-xl">ပစ္စည်းမပြတ်စေရန် စစ်ဆေးပါ</h1> --}}
    {{-- <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Type
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
            @foreach ($monthlyAllReportTypes as $type => $data)

                @php
                    $rowspan = count($data);
                @endphp
                @foreach ($data as $name => $result)
                    <tr class="mt-4 border-b-2 border-gray-400 odd:bg-white even:bg-gray-100">
                        @if ($loop->first)
                            <th scope="row" class="px-6 py-4 text-teal-500 font-lg dark:text-white"
                                rowspan="{{ $rowspan }}">

                                {{ ucfirst($type) }}
                            </th>
                        @endif
                        <td class="px-3 py-2 md:px-6 md:py-4">{{ $name }}</td>
                        <td class="px-3 py-2 md:px-6 md:py-4">{{ $result[0] }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table> --}}

    <div>Report at - {{ \Carbon\Carbon::parse($report_types_date_filter)->format('M, Y') }}</div>
    @if ($monthlyAllReportTypes)
        <table class="w-full mt-2 text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-2 py-1">Type Name</th>
                    @foreach (array_keys($monthlyAllReportTypes['ရွှေ (weight / g)']) as $branchName)
                        <th scope="col" class="px-2 py-1">{{ $branchName }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($monthlyAllReportTypes as $typeName => $branchData)
                    <tr class="odd:bg-white even:bg-gray-100">
                        <td class=" md:px-4 md:py-2">{{ $typeName }}</td>
                        @foreach ($branchData as $values)
                            <td class=" md:px-4 md:py-2">{{ $values[0] ?? 0 }}</td> {{-- Display value or 0 if empty --}}
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="p-2 mt-4 text-red-300 rounded-full bg-gray-50">No data found yet</div>
    @endif

</div>
