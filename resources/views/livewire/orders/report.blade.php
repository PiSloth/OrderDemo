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
    <div class="grid grid-cols-4 gap-4 p-4 mb-4 bg-blue-100 rounded sm:grid-cols-2">
        <x-datetime-picker label="Start Date" placeholder="Start Date" parse-format="YYYY-MM-DD HH:mm"
            wire:model.live="startDate" without-time=true />
        <x-datetime-picker label="End Date" placeholder="End Date" parse-format="YYYY-MM-DD HH:mm" wire:model.live="endDate"
            without-time=true />
    </div>


    <div class="grid grid-cols-3 gap-4 md:grid-cols-2 sm:grid-cols-1 bg-leamon-50">
        <div class="p-4 border rounded shadow-lg ">
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
                        class="grid w-full grid-cols-2 gap-2 py-2 mb-2 uppercase border shadow-sm hover:font-bold hover:text-teal-900 hover:bg-pink-50 text-slate-500">
                        <div class="text-center">{{ $item->name }}</div>
                        <div class="text-center">{{ $item->total }}</div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- //With Design --}}
        <div class="p-4 border rounded shadow-xl">
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
                        class="grid w-full grid-cols-2 gap-2 py-2 mb-2 uppercase border shadow-sm hover:font-bold hover:text-teal-900 hover:bg-pink-50 text-slate-500">
                        <div class="text-center">{{ $item->name }}</div>
                        <div class="text-center">{{ $item->total }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Averages --}}

    <div class="grid gap-4 p-2 my-4 sm:grid-cols-1 md:grid-cols-2">
        <ul class="">
            <span class="text-2xl font-bold">Process တစ်ခုနှင့် တစ်ခုကြား ပျှမ်းမျှကြာချိန် (Days)</span>
            @foreach ($average as $data)
                <li class="grid grid-cols-2 mt-4 mb-2 border-b cursor-pointer hover:bg-slate-100">
                    <span> Total Order </span>
                    <span class="text-3xl">{{ $data->TotalCount }} <i>pcs</i></span>
                </li>
                <li class="grid grid-cols-2 mb-2 border-b cursor-pointer hover:bg-slate-100">
                    <span>Add to Ack</span>
                    <span class="text-3xl ">{{ $data->AvgAddedToAcked }}</span>

                </li>
                <li class="grid grid-cols-2 mb-2 border-b cursor-pointer hover:bg-slate-100">
                    <span>Ack to Request </span>
                    <span class="text-3xl ">{{ $data->AvgAckedToRequest }}</span>
                </li>
                <li class="grid grid-cols-2 mb-2 border-b cursor-pointer hover:bg-slate-100">
                    <span> Request to Approve </span>
                    <span class="text-3xl ">{{ $data->AvgRequestToApprove }}</span>
                </li>
                <li class="grid grid-cols-2 mb-2 border-b cursor-pointer hover:bg-slate-100">
                    <span>Approve to Order<i>(Supplier)</i></span>
                    <span class="text-3xl ">{{ $data->AvgApproveToOrdered }}</span>
                </li>
                <li class="grid grid-cols-2 mb-2 border-b cursor-pointer hover:bg-slate-100">
                    <span>Supplier to STT </span>
                    <span class="text-3xl ">{{ $data->AvgOrderedToArrived }}</span>
                </li>
                <li class="grid grid-cols-2 mb-2 border-b cursor-pointer hover:bg-slate-100">
                    <span>Arrivered to Delivered </span>
                    <span class="text-3xl "> {{ $data->AvgDeliveredToSuccess }}</span>
                </li>
            @endforeach

        </ul>

        {{-- all comments by user name with count  --}}
        {{-- <ul class="">
            <span class="text-2xl font-bold">မဖတ်ရသေးသော Comments များ </span>
            @foreach ($allUserComments as $item)
                <li class="grid grid-cols-2 mt-4 mb-2 border-b cursor-pointer hover:bg-slate-100">
                    <span> {{ $item[0] }} </span>
                    <span class="text-3xl">{{ $item[1] }} <i></i></span>
                </li>
            @endforeach
        </ul> --}}

    </div>

    {{-- //Design with gram  --}}
    <div class="relative my-3 overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 table-auto rtl:text-right dark:text-gray-400">
            <thead class="sticky top-0 text-xs text-gray-900 uppercase dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        No
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Design
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Weight
                    </th>

                    @foreach ($thBranches as $branch)
                        <th scope="col" class="px-6 py-3">
                            {{ $branch->name }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr
                        class="bg-white odd:bg-gray-50 hover:bg-black hover:text-white hover:font-bold dark:bg-gray-800">
                        <td class="px-6 py-4">
                            {{ $loop->index + 1 }}
                        </td>
                        <th class="px-6 py-4 font-medium whitespace-nowrap dark:text-white">
                            {{ $product->design->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $product->weight }}<i> g</i>
                        </td>
                        @foreach ($thBranches as $branch)
                            <td class="px-6 py-4">
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
