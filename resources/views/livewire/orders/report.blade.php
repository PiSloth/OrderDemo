<div class="">

    {{-- <div id="date-range-picker" date-rangepicker class="flex items-center">
        <div class="relative">
            <div class="absolute inset-y-0 flex items-center pointer-events-none start-0 ps-3">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                </svg>
            </div>
            <input id="datepicker-range-start" name="start" type="text"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Select date start">
        </div>
        <span class="mx-4 text-gray-500">to</span>
        <div class="relative">
            <div class="absolute inset-y-0 flex items-center pointer-events-none start-0 ps-3">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                </svg>
            </div>
            <input id="datepicker-range-end" name="end" type="text"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Select date end">
        </div>
    </div> --}}
    <div
        class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 mb-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white/60 dark:bg-gray-800/60 backdrop-blur">
        <x-datetime-picker label="Start Date" placeholder="Start Date" parse-format="YYYY-MM-DD HH:mm"
            wire:model.live="startDate" without-time=true />
        <x-datetime-picker label="End Date" placeholder="End Date" parse-format="YYYY-MM-DD HH:mm" wire:model.live="endDate"
            without-time=true />
    </div>

    <div class="grid gap-4 md:grid-cols-2 sm:grid-cols-1">
        <div class="p-4 border rounded-lg shadow-sm bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
            <div>
                <x-button href="{{ route('order-branch-report') }}" class="w-full h-12" outline teal icon="chart-pie"
                    wire:navigate>Branch
                    Report Detail</x-button>
                <form class="max-w-sm mx-auto my-3 border-gray-100 border-1">
                    <label for="underline_select" class="sr-only">Underline select</label>
                    <select id="underline_select" wire:model.live="status_id"
                        class="block py-2.5 px-0 w-full text-sm text-gray-500 bg-transparent border-0 border-b-2 border-gray-200 appearance-none dark:text-gray-400 dark:border-gray-700 focus:outline-none focus:ring-0 focus:border-gray-200 peer">
                        {{-- <option selected>Choose a country</option> --}}
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </form>
                @foreach ($branches as $item)
                    <a href="{{ route('order-branch-report', ['branch' => $item->id, 'status' => $status_id, 'st' => $startDate, 'en' => $endDate]) }}"
                        wire:navigate
                        class="grid w-full grid-cols-2 gap-2 py-2 mb-2 uppercase border border-gray-200 dark:border-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-700/40 text-slate-600 dark:text-slate-300">
                        <div class="text-center">{{ $item->name }}</div>
                        <div class="text-center">{{ $item->total }}</div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- //With Design --}}
        <div class="p-4 border rounded-lg shadow-sm bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
            <div>
                <x-button href="{{ route('order-branch-report') }}" class="w-full h-12" outline pink icon="chart-pie"
                    wire:navigate>All
                    Branch Report</x-button>
                <form class="max-w-sm mx-auto my-3 border-gray-100 border-1">
                    <label for="underline_select" class="sr-only">Underline select</label>
                    <select id="underline_select" wire:model.live="priority_id"
                        class="block py-2.5 px-0 w-full text-sm text-gray-500 bg-transparent border-0 border-b-2 border-gray-200 appearance-none dark:text-gray-400 dark:border-gray-700 focus:outline-none focus:ring-0 focus:border-gray-200 peer">
                        {{-- <option selected>Choose a country</option> --}}
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                        @endforeach
                    </select>
                </form>
                @foreach ($prioritiesData as $item)
                    <a href="{{ route('order-branch-report', ['branch' => $item->id, 'priority' => $priority_id, 'st' => $startDate, 'en' => $endDate]) }}"
                        wire:navigate
                        class="grid w-full grid-cols-2 gap-2 py-2 mb-2 uppercase border border-gray-200 dark:border-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-700/40 text-slate-600 dark:text-slate-300">
                        <div class="text-center">{{ $item->name }}</div>
                        <div class="text-center">{{ $item->total }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Averages --}}
    <section class="my-6">
        <div
            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white/60 dark:bg-gray-800/60 backdrop-blur p-4 sm:p-6 shadow-sm">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-100">Process တစ်ခုနှင့် တစ်ခုကြား
                ပျှမ်းမျှကြာချိန် (Days)</h2>
            @foreach ($average as $data)
                <ul class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                    <li
                        class="flex items-center justify-between py-2 px-2 rounded-md hover:bg-gray-50/70 dark:hover:bg-gray-700/30">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Total Order</span>
                        <span
                            class="text-2xl sm:text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $data->TotalCount }}
                            <i class="text-sm font-normal not-italic text-gray-500 dark:text-gray-400">pcs</i></span>
                    </li>
                    <li
                        class="flex items-center justify-between py-2 px-2 rounded-md hover:bg-gray-50/70 dark:hover:bg-gray-700/30">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Add to Ack</span>
                        <span
                            class="text-2xl sm:text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $data->AvgAddedToAcked }}</span>
                    </li>
                    <li
                        class="flex items-center justify-between py-2 px-2 rounded-md hover:bg-gray-50/70 dark:hover:bg-gray-700/30">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Ack to Request</span>
                        <span
                            class="text-2xl sm:text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $data->AvgAckedToRequest }}</span>
                    </li>
                    <li
                        class="flex items-center justify-between py-2 px-2 rounded-md hover:bg-gray-50/70 dark:hover:bg-gray-700/30">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Request to Approve</span>
                        <span
                            class="text-2xl sm:text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $data->AvgRequestToApprove }}</span>
                    </li>
                    <li
                        class="flex items-center justify-between py-2 px-2 rounded-md hover:bg-gray-50/70 dark:hover:bg-gray-700/30">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Approve to Order <i
                                class="text-xs font-normal not-italic text-gray-500 dark:text-gray-400">(Supplier)</i></span>
                        <span
                            class="text-2xl sm:text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $data->AvgApproveToOrdered }}</span>
                    </li>
                    <li
                        class="flex items-center justify-between py-2 px-2 rounded-md hover:bg-gray-50/70 dark:hover:bg-gray-700/30">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Supplier to STT</span>
                        <span
                            class="text-2xl sm:text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $data->AvgOrderedToArrived }}</span>
                    </li>
                    <li
                        class="flex items-center justify-between py-2 px-2 rounded-md hover:bg-gray-50/70 dark:hover:bg-gray-700/30">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Arrived to Delivered</span>
                        <span
                            class="text-2xl sm:text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $data->AvgDeliveredToSuccess }}</span>
                    </li>
                </ul>
            @endforeach
        </div>
    </section>

    {{-- //Design with gram  --}}
    <div class="relative mt-4 overflow-auto max-h-[70vh] rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full table-auto text-sm text-left text-gray-700 dark:text-gray-200 rtl:text-right">
            <thead
                class="text-xs uppercase bg-gray-50/80 backdrop-blur supports-backdrop-blur:backdrop-blur-sm dark:bg-gray-800/80 sticky top-0 z-10 text-gray-700 dark:text-gray-300">
                <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">
                        No
                    </th>
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">
                        Design
                    </th>
                    <th scope="col" class="px-4 md:px-6 py-3 font-semibold">
                        Weight
                    </th>

                    @foreach ($thBranches as $branch)
                        <th scope="col" class="px-4 md:px-6 py-3 font-semibold">
                            {{ $branch->name }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($products as $product)
                    <tr class="bg-white odd:bg-gray-50 dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 md:px-6 py-3">
                            {{ $loop->index + 1 }}
                        </td>
                        <th class="px-4 md:px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-100">
                            {{ $product->design->name }}
                        </th>
                        <td class="px-4 md:px-6 py-3">
                            {{ $product->weight }}<i> g</i>
                        </td>
                        @foreach ($thBranches as $branch)
                            <td class="px-4 md:px-6 py-3 text-center tabular-nums">
                                {{ $product->{'index' . $branch->id} > 0 ? $product->{'index' . $branch->id} : '-' }}
                            </td>
                        @endforeach

                    </tr>
                @endforeach
                {{-- <tr class="bg-white dark:bg-gray-800">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        Microsoft Surface Pro
                    </th>
                    <td class="px-6 py-4">
                        White
                    </td>
                    <td class="px-6 py-4">
                        Laptop PC
                    </td>
                    <td class="px-6 py-4">
                        $1999
                    </td>
                </tr>
                <tr class="bg-white dark:bg-gray-800">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        Magic Mouse 2
                    </th>
                    <td class="px-6 py-4">
                        Black
                    </td>
                    <td class="px-6 py-4">
                        Accessories
                    </td>
                    <td class="px-6 py-4">
                        $99
                    </td>
                </tr> --}}
            </tbody>
        </table>
    </div>


    {{-- <div class="relative h-64 overflow-auto">
        <table class="min-w-full text-sm text-left">
          <thead class="sticky top-0 bg-gray-200">
            <tr>
              <th class="px-6 py-3">Column 1</th>
              <th class="px-6 py-3">Column 2</th>
              <th class="px-6 py-3">Column 3</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="px-6 py-4 border">Row 1 Data 1</td>
              <td class="px-6 py-4 border">Row 1 Data 2</td>
              <td class="px-6 py-4 border">Row 1 Data 3</td>
            </tr>
            <tr>
              <td class="px-6 py-4 border">Row 2 Data 1</td>
              <td class="px-6 py-4 border">Row 2 Data 2</td>
              <td class="px-6 py-4 border">Row 2 Data 3</td>
            </tr>
            <!-- Add more rows as needed -->
          </tbody>
        </table>
      </div> --}}

    {{-- inline block
      <span class="block border">Hello</span>
      <span>World</span>

    <div class="h-96 "></div>
    <div class="h-96 "></div>
    <div class="h-96 "></div> --}}

</div>
