<div class="h-screen">

<div id="date-range-picker" date-rangepicker class="flex items-center">
    <div class="relative">
      <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
           <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
          </svg>
      </div>
      <input id="datepicker-range-start" name="start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date start">
    </div>
    <span class="mx-4 text-gray-500">to</span>
    <div class="relative">
      <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
           <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
          </svg>
      </div>
      <input id="datepicker-range-end" name="end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date end">
  </div>
  </div>

    <div class="grid grid-cols-3 md:grid-cols-2 sm:grid-cols-1 gap-4">
        <div class="border rounded shadow-lg p-4 ">
            <div>
                <x-button href="{{ route('order-branch-report') }}" class="h-12 w-full" outline teal icon="chart-pie"
                    wire:navigate>Branch
                    Report Detail</x-button>
                <form class="max-w-sm mx-auto my-3 border-1 border-gray-100">
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
                    <a href="{{ route('order-branch-report', ['branch' => $item->id, 'status' => $status_id]) }}"
                        wire:navigate
                        class="font-bold py-2 bg-gradient-to-r from-green-500 to-emerald-800 text-white grid w-full grid-cols-2 gap-2 mb-2 uppercase">
                        <div class="text-center">{{ $item->name }}</div>
                        <div class="text-center">{{ $item->total }}</div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- //With Design --}}
        <div class="p-4 border shadow-xl rounde">
            <div>
                <x-button href="{{ route('order-branch-report') }}" class="h-12 w-full" outline pink icon="chart-pie"
                    wire:navigate>All
                    Branch Report</x-button>
                <form class="max-w-sm mx-auto my-3 border-1 border-gray-100">
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
                    <a href="{{ route('order-branch-report', ['branch' => $item->id, 'priority' => $priority_id]) }}"
                        wire:navigate
                        class="hover:font-bold hover:text-teal-900 hover:bg-pink-50 text-slate-500 py-2 border shadow-md grid w-full grid-cols-2 gap-2 mb-2 uppercase">
                        <div class="text-center">{{ $item->name }}</div>
                        <div class="text-center">{{ $item->total }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-900 uppercase dark:text-gray-400">
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
                    <th scope="col" class="px-6 py-3">
                        HO
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Branch 1
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Branch 2
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Branch 3
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Branch 4
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Branch 5
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Branch 6
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr class="bg-white dark:bg-gray-800">
                        <td class="px-6 py-4">
                            {{ $loop->index }}
                        </td>
                        <th class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $product->design->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $product->weight }}<i> g</i>
                        </td>
                        <td class="px-6 py-4">
                            {{ $product->ho }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $product->b1 }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $product->b2 }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $product->b3 }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $product->b4 }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $product->b5 }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $product->b6 }}
                        </td>

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

</div>
