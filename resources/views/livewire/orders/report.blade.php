    <div class="ml-10 mr-10 mt-10">
        <article class="bg-green-100 p-2 rounded mb-4">
            <h1 class="text-yellow-500 font-bold">သတိပေးချက်</h1>
            <p>အဆင့်တစ်ခုနှင့် တစ်ခုကြား ကြာချိန်ကို ၁၂ နာရီသတ်မှတ်ထားသည်။ ပိုမိုကြာမြင့်အောင်ထားသော Order တို့သည်
                အနီရောင်ပြောင်းလဲဖော်ပြသွားမည်။ သက်ဆိုင်ရာ အဆင့်အလိုက် အဓိက တာဝန်ရှိသူတွင် တာဝန်အပြည့်အဝရှိပါသည်။</p>
        </article>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">Order Reports
        </h2>
        <div class="gap-x-4 lg:flex lg:items-center lg:justify-between my-4b">
            <div
                class="mt-6 gap-4 space-y-4 sm:flex sm:items-center sm:space-y-0 lg:mt-0 lg:justify-end dark:text-gray-200">


                <select id="branches" wire:model.live="branchFilter"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-200">
                    <option selected value="0">Filter by Branch</option>

                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>

                <select id="statuses" wire:model.live="statusFilter"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-200">
                    <option selected value="0">Filter by Status</option>

                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                    @endforeach
                </select>

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
        {{-- end filter section   --}}


        <div class="bg-slate-300 my-4">
            @foreach ($orderGroup as $branchName => $statusGroup)
                {{-- start one branch information  --}}
                <section class="bg-white py-1 my-8 antialiased rounded-lg dark:bg-gray-900 md:py-1 dark:text-gray-200">
                    <div class="mx-auto max-w-screen-4xl px-4 2xl:px-0">
                        <div class="mx-auto">
                            <center class="my-4 font-bold text-2xl">{{ $branchName }} </center>
                            {{-- new table design --}}
                            <div class="relative overflow-x-auto max-w-screen-kg shadow-md sm:rounded-lg h-96">
                                @foreach( $statusGroup as $statusName => $relatedData)
                                <h2 class="font-bold my-3">{{ $statusName }} </h2>
                                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                    <thead
                                        class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">
                                                Detail
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Priority
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Quality
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Design
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Gram
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Last Update
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($relatedData as $item)
                                        <tr
                                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">

                                            <th scope="row"
                                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                {{$item->detail}}
                                            </th>
                                            <td class="px-6 py-4">
                                                {{$item->priority->name}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{$item->quality->name}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{$item->design->name}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{$item->weight}}
                                            </td>

                                            <td class="px-6 py-4">
                                                {{$item->updated_at}}
                                            </td>
                                            <td class="flex items-center px-6 py-4">
                                                <a href="/order/detail?order_id={{ $item->id }}"  wire:navigate><small
                                                    class="text-primary-400 underline ">detail</small></a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @endforeach
                            </div>
                            {{-- end new table design  --}}
                        </div>
                    </div>
                </section>
                {{-- end one branch information  --}}
            @endforeach
        </div>

    </div>
