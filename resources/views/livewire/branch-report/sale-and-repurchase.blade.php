<div x-data="{ open: true, summary: true }">
    <x-button label="Add Report" @click="$openModal('addReportModal')" />
    <x-button label="Export" @click="$openModal('exportModal')" />
    @can('isAGM')
        <x-dropdown align='left'>
            <x-slot name="trigger">
                <x-button icon="cog" label="Configure" sky />
            </x-slot>
            <x-dropdown.header label="Actions">
                <x-dropdown.item @click="$openModal('addReportTypeModal')">New Report Type
                </x-dropdown.item>
            </x-dropdown.header>
        </x-dropdown>
    @endcan
    <div class="mt-4">
        <div class="flex flex-wrap w-2/4 gap-4">
            <input type="date" id="date" wire:model.live='report_date'
                class="bg-gray-50 border w-44  border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block  p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
            <label for="date" class="text-xl text-gray-400">Report Date ရွေးချယ်ပါ</label>
        </div>

        <label class="inline-flex items-center mt-2 cursor-pointer">
            <input x-model="open" type="checkbox" value="" class="sr-only peer">
            <div
                class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
            </div>
            <span class="text-sm font-medium text-gray-900 ms-3 dark:text-gray-300">နေ့စဉ် ဆိုင်ခွဲများ report</span>
        </label>

    </div>

    <div class="flex flex-wrap gap-2 my-4" x-show="open" x-transition>
        <div>
            <div class="p-2 border-2 border-red-700 rounded-lg">
                <x-card title="Daily Summary">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col" class="px-6 py-3">Sale</th>
                                <th scope="col" class="px-6 py-3">Repurchase</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daily_spirit as $data)
                                <tr class="text-lg text-gray-900">
                                    <td class="px-6 py-4">{{ $data->total_sale ?? 0 }}</td>
                                    <td class="px-6 py-4">{{ $data->total_repurchase ?? 0 }}</td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        {{-- @dd($daily_branch_reports) --}}
        @forelse ($daily_branch_reports as $report)
            <div>
                <x-card title="{{ $report['key'] }}">
                    <table>
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Number</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['data'] as $item)
                                <tr>
                                    <td class="px-4 py-2">{{ $item['name'] }}</td>
                                    <td class="px-4 py-2">{{ $item['quantity'] }}</td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </x-card>
            </div>

        @empty
            <div>
                <x-card title="info!">
                    <span class="font-bold">Date :
                        {{ \Carbon\Carbon::parse($report_date)->format('M j, Y') }}</span><br />
                    <center class="text-lg">There's nothing to show data.</center><br />
                    <span>Please, contact <i>branches</i> to add daily reports.</span>
                </x-card>
            </div>
        @endforelse
    </div>
    <hr />
    <div class="mt-4">
        <div class="flex flex-wrap w-2/4 gap-4 my-8">
            {{-- Filter to show interval --}}
            <select id="dateInterval" wire:model.live='duration_filter'
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500  p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="0">Today</option>
                <option value="1">Yesterday</option>
                <option value="7">7 days ago</option>
                <option selected value="30">30 days ago</option>
                <option value="60">60 days ago</option>
                <option value="90">90 days ago</option>
            </select>
            <label for="dateInterval" class="text-xl text-gray-400">Report Date ရွေးချယ်ပါ</label>
        </div>

        <label class="inline-flex items-center mt-2 cursor-pointer">
            <input x-model="summary" type="checkbox" value="" class="sr-only peer">
            <div
                class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
            </div>
            <span class="text-sm font-medium text-gray-900 ms-3 dark:text-gray-300">Reports အမျိုးအစားအလိုက်
                အကျဉ်းချုပ်</span>
        </label>

    </div>


    {{-- Summarize table --}}
    <div class="flex flex-wrap gap-2" x-show="summary" x-transition>
        <div>
            <div class="p-2 border-2 border-red-700 rounded-lg">
                <x-card title="Daily Summary">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col" class="px-6 py-3">Sale</th>
                                <th scope="col" class="px-6 py-3">Repurchase</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($impSummaryTotalGram as $data)
                                <tr class="text-lg text-gray-900">
                                    <td class="px-6 py-4">{{ $data->total_sale ?? 0 }}</td>
                                    <td class="px-6 py-4">{{ $data->total_repurchase ?? 0 }}</td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        @foreach ($impSummaryData as $type => $data)
            <div class="p-2 border border-teal-400 rounded-lg">
                <x-card title="{{ $type }}" class="border-2">
                    <table class="text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    Branch
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Quantity
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $item)
                                <tr
                                    class="border-b odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 dark:border-gray-700">
                                    <th scope="row"
                                        class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $item['name'] }}
                                    </th>
                                    <td class="px-6 py-4">
                                        {{ $item['total'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-card>
            </div>
        @endforeach
    </div>

    <div class="w-full p-4 my-8 bg-white rounded-lg shadow dark:bg-gray-800 md:p-6">
        <div class="flex justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-lg dark:bg-gray-700 me-3">
                    {{-- <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 19">
                        <path
                            d="M14.5 0A3.987 3.987 0 0 0 11 2.1a4.977 4.977 0 0 1 3.9 5.858A3.989 3.989 0 0 0 14.5 0ZM9 13h2a4 4 0 0 1 4 4v2H5v-2a4 4 0 0 1 4-4Z" />
                        <path
                            d="M5 19h10v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2ZM5 7a5.008 5.008 0 0 1 4-4.9 3.988 3.988 0 1 0-3.9 5.859A4.974 4.974 0 0 1 5 7Zm5 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm5-1h-.424a5.016 5.016 0 0 1-1.942 2.232A6.007 6.007 0 0 1 17 17h2a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5ZM5.424 9H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h2a6.007 6.007 0 0 1 4.366-5.768A5.016 5.016 0 0 1 5.424 9Z" />
                    </svg> --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm9 4a1 1 0 10-2 0v6a1 1 0 102 0V7zm-3 2a1 1 0 10-2 0v4a1 1 0 102 0V9zm-3 3a1 1 0 10-2 0v1a1 1 0 102 0v-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <h5 class="pb-1 text-2xl font-bold leading-none text-gray-900 dark:text-white">
                        {{ 'Last 30 Days' }}</h5>
                    <p class="text-sm font-normal text-gray-500 dark:text-gray-400">Limited showing data</p>
                </div>
            </div>
            <div class="hidden">
                <span
                    class="bg-green-100 text-green-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-green-900 dark:text-green-300">
                    <svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 10 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 13V1m0 0L1 5m4-4 4 4" />
                    </svg>
                    42.5%
                </span>
            </div>
        </div>

        {{-- <div class="grid grid-cols-2">
            <dl class="flex items-center">
                <dt class="text-sm font-normal text-gray-500 dark:text-gray-400 me-1"></dt>
                <dd class="text-sm font-semibold text-gray-900 dark:text-white"></dd>
            </dl>
            <dl class="flex items-center justify-end">
                <dt class="text-sm font-normal text-gray-500 dark:text-gray-400 me-1">Conversion rate:</dt>
                <dd class="text-sm font-semibold text-gray-900 dark:text-white">1.2%</dd>
            </dl>
        </div> --}}

        <div class="overflow-x-scroll" id="column-chart"></div>
        <div class="grid items-center justify-between grid-cols-1 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between pt-5">
                <!-- Button -->
                <select wire:model.live='duration_filter'>
                    <option value="0">Today</option>
                    <option value="1">yesterday</option>
                    <option value="7">7 days</option>
                    <option value="30">30 days</option>
                    <option value="60">60 days</option>
                    <option value="90">90 days</option>
                </select>
                {{-- <a href="#"
                    class="inline-flex items-center px-3 py-2 text-sm font-semibold text-blue-600 uppercase rounded-lg hover:text-blue-700 dark:hover:text-blue-500 hover:bg-gray-100 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700">
                    Leads Report
                    <svg class="w-2.5 h-2.5 ms-1.5 rtl:rotate-180" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="m1 9 4-4-4-4" />
                    </svg>
                </a> --}}
            </div>
        </div>
    </div>

    {{-- overall report types chart --}}
    <div class="w-full p-4 bg-white rounded-lg shadow dark:bg-gray-800 md:p-6">
        <div class="flex justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-lg dark:bg-gray-700 me-3">
                    <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 19">
                        <path
                            d="M14.5 0A3.987 3.987 0 0 0 11 2.1a4.977 4.977 0 0 1 3.9 5.858A3.989 3.989 0 0 0 14.5 0ZM9 13h2a4 4 0 0 1 4 4v2H5v-2a4 4 0 0 1 4-4Z" />
                        <path
                            d="M5 19h10v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2ZM5 7a5.008 5.008 0 0 1 4-4.9 3.988 3.988 0 1 0-3.9 5.859A4.974 4.974 0 0 1 5 7Zm5 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm5-1h-.424a5.016 5.016 0 0 1-1.942 2.232A6.007 6.007 0 0 1 17 17h2a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5ZM5.424 9H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h2a6.007 6.007 0 0 1 4.366-5.768A5.016 5.016 0 0 1 5.424 9Z" />
                    </svg>
                </div>
                <div>
                    <h5 class="pb-1 text-2xl font-bold leading-none text-gray-900 dark:text-white">
                        __{{ '100' }}</h5>
                    <p class="text-sm font-normal text-gray-500 dark:text-gray-400">Overall reports of report types</p>
                </div>
            </div>
            <div class="hidden">
                <span
                    class="bg-green-100 text-green-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-green-900 dark:text-green-300">
                    <svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 10 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 13V1m0 0L1 5m4-4 4 4" />
                    </svg>
                    42.5%
                </span>
            </div>
        </div>
        <div id="data-series-chart"></div>
    </div>

    {{-- Each branch daily index --}}
    <div class="w-full p-4 mt-8 bg-white rounded-lg shadow dark:bg-gray-800 md:p-6">
        <div class="flex justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-lg dark:bg-gray-700 me-3">
                    <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 19">
                        <path
                            d="M14.5 0A3.987 3.987 0 0 0 11 2.1a4.977 4.977 0 0 1 3.9 5.858A3.989 3.989 0 0 0 14.5 0ZM9 13h2a4 4 0 0 1 4 4v2H5v-2a4 4 0 0 1 4-4Z" />
                        <path
                            d="M5 19h10v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2ZM5 7a5.008 5.008 0 0 1 4-4.9 3.988 3.988 0 1 0-3.9 5.859A4.974 4.974 0 0 1 5 7Zm5 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm5-1h-.424a5.016 5.016 0 0 1-1.942 2.232A6.007 6.007 0 0 1 17 17h2a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5ZM5.424 9H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h2a6.007 6.007 0 0 1 4.366-5.768A5.016 5.016 0 0 1 5.424 9Z" />
                    </svg>
                </div>
                <div>
                    <h5 class="pb-1 text-2xl font-bold leading-none text-gray-900 dark:text-white">
                        {{-- {{ $branchOverIndex }} --}}
                    </h5>
                    <p class="text-sm font-normal text-gray-500 dark:text-gray-400">Total of indexs</p>
                </div>
            </div>
            <div>
                @if ($index_score >= 0)
                    <span
                        class="bg-green-100 text-green-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-green-900 dark:text-green-300">
                        <svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 10 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M5 13V1m0 0L1 5m4-4 4 4" />
                        </svg>
                        {{ $index_score }}
                    </span>
                @else
                    <span
                        class="bg-red-100 text-red-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-green-900 dark:text-green-300">
                        <svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 10 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M5 1v12m0 0l4-4m-4 4l-4-4" />
                        </svg>
                        {{ $index_score }}
                    </span>
                @endif
            </div>
        </div>
        <div id="each_branch_index_data-series-chart"></div>
    </div>

    {{-- Create a Report --}}
    <x-modal.card title="New Report" wire:model='addReportModal'>
        <div>
            <input type="date" wire:model.live='report_date' />
            @can('isAGM')
                <select wire:model.live='branch_id'>
                    <option value="" selected disabled>Select</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}"> {{ $branch->name }}</option>
                    @endforeach
                </select>
            @endcan

            <hr />
            @if ($entry_modal !== null)
                <table>
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Number</th>
                            <th class="px-4 py-2 sr-only">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daily_entries as $entry)
                            <tr>
                                <td class="px-4 py-2">{{ $entry->dailyReport->name }}</td>
                                <td class="px-4 py-2">{{ $entry->number }}</td>

                                @if ($edit_id == $entry->id)
                                    <td class="flex gap-2 px-4 py-2">
                                        <x-input type='number' step=0.01 wire:model.live='update_number'
                                            placeholder="number" wire:keydown.enter="update({{ $entry->id }})" />
                                        <div class="hidden lg:block xl:block">
                                            <kbd
                                                class="px-2 py-1.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">Enter</kbd>
                                        </div>
                                        <a href="#" class="text-blue-300 underline"
                                            wire:click="update({{ $entry->id }})">{{ __('Update') }}</a>
                                    </td>
                                @else
                                    <td class="px-4 py-2">
                                        <a href="#"
                                            wire:click='edit({{ $entry->id }})'>{{ __('Edit') }}</a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <button wire:click='crateNewRecord'
                    class="px-4 py-2 mt-4 text-white bg-gray-900 rounded-lg hover:bg-gray-950 hover:shadow-lg"><x-icon
                        name="check" solid class="inline w-4 h-4 mr-2" />{{ __('GENERATE') }}</button>
            @endif
        </div>
    </x-modal.card>

    {{-- Create a report type --}}
    <x-modal.card title="New Report Type" wire:model='addReportTypeModal'>
        <form wire:submit="createReportType">
            <x-input label="Name" wire:model='name' />
            <x-input label="Description" wire:model='description' />
            <hr />
            <button class="px-4 py-2 mt-4 text-white bg-gray-900 rounded-lg hover:bg-gray-950 hover:shadow-lg"><x-icon
                    name="check" solid class="inline w-4 h-4 mr-2" />{{ __('SAVE') }}</button>
        </form>
        <table class="w-full text-sm text-left text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-2">Name</th>
                    <th scope="col" class="px-4 py-2">Desc</th>
                    <th scope="col" class="px-4 py-2 sr-only">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($types as $type)
                    <tr>
                        <td class="px-4 py-2">{{ $type->name }}</td>
                        <td class="px-4 py-2">{{ $type->description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-modal.card>

    {{-- Export data --}}
    <x-modal.card title="Export data" wire:model='exportModal'>
        <select wire:model='branch_id' class="ml-4 bg-gray-100 border rounded-lg border-gray-50">
            <option value="" selected disabled>Select</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}"> {{ ucfirst($branch->name) }}</option>
            @endforeach
        </select>
        <div class="grid grid-cols-1 gap-4 p-4 mb-4 rounded sm:grid-cols-2">
            <x-datetime-picker label="Start Date" placeholder="Start Date" parse-format="YYYY-MM-DD HH:mm"
                wire:model="start_date" without-time=true />
            <x-datetime-picker label="End Date" placeholder="End Date" parse-format="YYYY-MM-DD HH:mm"
                wire:model="end_date" without-time=true />
        </div>
        <x-button green right-icon="download" class="m-2" wire:click='export'>Exprot</x-button>
    </x-modal.card>
</div>

@section('script')
    <script>
        const reports = JSON.parse('{!! addslashes($daily_reports) !!}');

        const series = Object.entries(reports).map(([name, data]) => ({
            name: name,
            data: data.map(({
                x,
                y
            }) => ({
                x,
                y
            })),
        }));

        // console.log(series);

        const options = {
            colors: ["#1A56DB",
                "#FDBA8C",
                "#81B622",
                "#0077B6",
                "#FFAEBC",
                "#A0E7E5",
                "#B4F8C8"
            ],
            series: series,

            chart: {
                type: "bar",
                height: "320px",
                fontFamily: "Inter, sans-serif",
                toolbar: {
                    show: false,
                },
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: "70%",
                    borderRadiusApplication: "end",
                    borderRadius: 8,
                },
            },
            tooltip: {
                shared: true,
                intersect: false,
                style: {
                    fontFamily: "Inter, sans-serif",
                },
            },
            states: {
                hover: {
                    filter: {
                        type: "darken",
                        value: 1,
                    },
                },
            },
            stroke: {
                show: true,
                width: 0,
                colors: ["transparent"],
            },
            grid: {
                show: false,
                strokeDashArray: 4,
                padding: {
                    left: 2,
                    right: 2,
                    top: -14
                },
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                show: false,
            },
            xaxis: {
                floating: true,
                labels: {
                    show: true,
                    rotate: -45,
                    style: {
                        fontSize: '12px',
                        fontFamily: "Inter, sans-serif",
                        cssClass: 'text-xs font-normal fill-gray-500 dark:fill-gray-400'
                    }
                },
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                },
            },
            yaxis: {
                show: false,
            },
            fill: {
                opacity: 1,
            },
        }

        if (document.getElementById("column-chart") && typeof ApexCharts !== 'undefined') {
            const chart = new ApexCharts(document.getElementById("column-chart"), options);
            chart.render();
        }


        Livewire.on('closeModal', (name) => {
            $closeModal(name);
        });

        Livewire.on('openModal', (name) => {
            $openModal(name);
        });


        // data series chart

        const all_reports = JSON.parse('{!! addslashes($all_reports) !!}');
        const all_reports_categories = JSON.parse('{!! addslashes($categories) !!}')

        const pureReports = Object.values(all_reports)

        const options3 = {
            // add data series via arrays, learn more here: https://apexcharts.com/docs/series/
            series: pureReports,

            chart: {
                height: "100%",
                maxWidth: "100%",
                type: "area",
                fontFamily: "Inter, sans-serif",
                dropShadow: {
                    enabled: false,
                },
                toolbar: {
                    show: false,
                },
            },
            tooltip: {
                enabled: true,
                x: {
                    show: false,
                },
            },
            legend: {
                show: false
            },
            fill: {
                type: "gradient",
                gradient: {
                    opacityFrom: 0.55,
                    opacityTo: 0,
                    shade: "#1C64F2",
                    gradientToColors: ["#1C64F2"],
                },
            },
            dataLabels: {
                enabled: false,
            },
            stroke: {
                width: 6,
            },
            grid: {
                show: false,
                strokeDashArray: 4,
                padding: {
                    left: 2,
                    right: 2,
                    top: 0
                },
            },
            xaxis: {
                categories: all_reports_categories,
                labels: {
                    show: false,
                },
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                },
            },
            yaxis: {
                show: false,
                labels: {
                    formatter: function(value) {
                        return '$' + value;
                    }
                }
            },
        }

        if (document.getElementById("data-series-chart") && typeof ApexCharts !== 'undefined') {
            const chart = new ApexCharts(document.getElementById("data-series-chart"), options3);
            chart.render();
        }


        // each_branch_index_data-series

        const index_data = JSON.parse('{!! addslashes($index_data) !!}');
        const pureIndexData = Object.values(index_data);
        const indexDate = JSON.parse('{!! addslashes($index_date) !!}')


        const options_index = {
            // add data series via arrays, learn more here: https://apexcharts.com/docs/series/
            series: pureIndexData,

            chart: {
                height: "100%",
                maxWidth: "100%",
                type: "area",
                fontFamily: "Inter, sans-serif",
                dropShadow: {
                    enabled: false,
                },
                toolbar: {
                    show: false,
                },
            },
            tooltip: {
                enabled: true,
                x: {
                    show: false,
                },
            },
            legend: {
                show: false
            },
            fill: {
                type: "gradient",
                gradient: {
                    opacityFrom: 0.55,
                    opacityTo: 0,
                    shade: "#1C64F2",
                    gradientToColors: ["#1C64F2"],
                },
            },
            dataLabels: {
                enabled: false,
            },
            stroke: {
                width: 6,
            },
            grid: {
                show: false,
                strokeDashArray: 4,
                padding: {
                    left: 2,
                    right: 2,
                    top: 0
                },
            },
            xaxis: {
                categories: indexDate,
                labels: {
                    show: false,
                },
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                },
            },
            yaxis: {
                show: false,
                labels: {
                    formatter: function(value) {
                        return '$' + value;
                    }
                }
            },
        }

        if (document.getElementById("each_branch_index_data-series-chart") && typeof ApexCharts !== 'undefined') {
            const chart = new ApexCharts(document.getElementById("each_branch_index_data-series-chart"), options_index);
            chart.render();
        }
    </script>
@endsection
