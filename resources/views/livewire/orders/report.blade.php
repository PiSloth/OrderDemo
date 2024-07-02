@extends('livewire.orders.layout.dashboard-layout')

@section('content')
    <div class="ml-10 mr-10 lg:ml-72 mt-10">
        <article class="bg-green-100 p-2 rounded mb-4">
            <h1 class="text-yellow-500 font-bold">သတိပေးချက်</h1>
            <p>အဆင့်တစ်ခုနှင့် တစ်ခုကြား ကြာချိန်ကို ၁၂ နာရီသတ်မှတ်ထားသည်။ ပိုမိုကြာမြင့်အောင်ထားသော Order တို့သည် အနီရောင်ပြောင်းလဲဖော်ပြသွားမည်။ သက်ဆိုင်ရာ အဆင့်အလိုက် အဓိက တာဝန်ရှိသူတွင် တာဝန်အပြည့်အဝရှိပါသည်။</p>
        </article>
        {{-- <h1 class="text-3xl font-bold text-center underline mb-4 dark:text-gray-200 mt-10">Order Report</h1> --}}

        <section class="bg-white py-8 antialiased rounded-lg dark:bg-gray-900 md:py-16 dark:text-gray-200">
            <div class="mx-auto max-w-screen-xl px-4 2xl:px-0">
                <div class="mx-auto">
                    <div class="gap-x-4 lg:flex lg:items-center lg:justify-between">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">Order Reports</h2>

                        <div
                            class="mt-6 gap-4 space-y-4 sm:flex sm:items-center sm:space-y-0 lg:mt-0 lg:justify-end dark:text-gray-200">

                            <select id="grades" wire:model.live="gradeFilter"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-200">
                                <option selected value="0">Filter by Grade</option>

                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>

                            <select id="priorities" wire:model.live="priorityFilter"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-200">
                                <option selected value="0">Filter by Priority</option>

                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                @endforeach
                            </select>

                            <select id="designs" wire:model.live="designFilter"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-200">
                                <option selected value="0">Filter by design</option>

                                @foreach ($designs as $design)
                                    <option value="{{ $design->id }}">{{ $design->name }}</option>
                                @endforeach
                            </select>


                            <select id="Duration" wire:model.live="durationFilter"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-200">
                                <option selected value="0">Filter by duration</option>


                                <option value="1">1 month ago</option>
                                <option value="2">2 months agi</option>
                                <option value="3">3 months ago</option>
                                <option value="4">4 months ago</option>
                                <option value="6">6 months ago</option>
                                <option value="8">8 months ago</option>

                            </select>

                        </div>
                    </div>

                    <div class="mt-2 flow-root sm:mt-8">
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">

                            @foreach ($orderGroup as $branchName => $statusGroup)
                                <div class="py-6">
                                    <div class="col-span-2 content-center sm:col-span-4 lg:col-span-1">
                                        <a href="#"
                                            class="text-base font-extrabold text-gray-900 hover:underline underline dark:text-gray-100">{{ ucfirst($branchName) }}</a>
                                    </div>

                                    @foreach ($statusGroup as $statusName => $dataGroup)
                                        <div class="mb-10">
                                            <div class="my-2 text-end">
                                                <span
                                                    class="inline-flex items-center rounded bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                                                    {{ $statusName }}
                                                </span>
                                            </div>

                                            <table class="w-full text-sm text-left rtl:text-right">
                                                <thead
                                                    class="text-xs text-gray-700 uppercase bg-amber-200 dark:bg-amber-400">
                                                    <tr>

                                                        <th scope="col" class="px-6 py-3">
                                                            Quality
                                                        </th>
                                                        <th scope="col" class="px-6 py-3">
                                                            Priority
                                                        </th>
                                                        <th scope="col" class="px-6 py-3">
                                                            Detail
                                                        </th>
                                                        <th scope="col" class="px-6 py-3">
                                                            Design
                                                        </th>
                                                        <th scope="col" class="px-6 py-3">Gram</th>
                                                        <th scope="col" class="px-6 py-3">Quantity</th>
                                                        <th scope="col" class="px-6 py-3">Last Update</th>
                                                        <th scope="col" class="px-6 py-3"></th>
                                                    </tr>
                                                </thead>
                                                @foreach ($dataGroup as $data)
                                                    <tbody class="dark:bg-gray-800 {{  $data->updated_at->diffInHours($currentTime) >= 12 ? 'bg-red-400 text-white' : 'bg-white' }}">
                                                        <tr class="border-b border-gray-500">

                                                            <td class="px-6 py-3">{{ $data->quality->name }}</td>
                                                            <td class="px-6 py-3">{{ $data->priority->name }}</td>
                                                            <td class="px-6 py-3">{{ $data->detail }}</td>
                                                            <td class="px-6 py-3">{{ $data->design->name }}</td>
                                                            <td class="px-6 py-3">{{ $data->weight }} g</td>
                                                            <td class="px-6 py-3">{{ $data->qty }}</td>
                                                            <td class="px-6 py-3">
                                                                {{ $data->updated_at->diffForHumans() }}
                                                            </td>
                                                            <td class="px-6 py-3 text-sm">
                                                                <div class="flex gap-1">
                                                                    <a href="/order/detail?order_id={{ $data->id }}"><small
                                                                        class="text-primary-400 underline">detil</small></a>
                                                                    @if($data->instockqty == 0)
                                                                    <small class='text-red-500'>inv turn</small>
                                                                    @endif
                                                                </div>
                                                                    
                                                                
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                @endforeach
                                            </table>
                                        </div>
                                    @endforeach
                            @endforeach


                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
