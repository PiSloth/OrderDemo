    <div class="ml-10 mr-10 mt-2">
        <x-button class="mb-4" black label="Back" href="{{ route('order-report') }}" icon="arrow-left" wire:navigate />
        <article class="bg-green-100 p-2 rounded mb-4">
            <h1 class="text-yellow-500 font-bold">သတိပေးချက်</h1>
            <p>အဆင့်တစ်ခုနှင့် တစ်ခုကြား ကြာချိန်ကို ၂၄ နာရီသတ်မှတ်ထားသည်။ ပိုမိုကြာမြင့်အောင်ထားသော Order တို့သည်
                အနီရောင်ပြောင်းလဲဖော်ပြသွားမည်။ သက်ဆိုင်ရာ အဆင့်အလိုက် အဓိက တာဝန်ရှိသူတွင် တာဝန်အပြည့်အဝရှိပါသည်။</p>
        </article>
        {{-- <h2 class="text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">Order Reports
        </h2> --}}
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

                {{-- <select id="Duration" wire:model.live="durationFilter"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-200">
                    <option selected value="0">Filter by duration</option>
                    <option value="1">1 month ago</option>
                    <option value="2">2 months agi</option>
                    <option value="3">3 months ago</option>
                    <option value="4">4 months ago</option>
                    <option value="6">6 months ago</option>
                    <option value="8">8 months ago</option>
                </select> --}}
            </div>
        </div>
        {{-- end filter section   --}}

        {{-- disign filter  --}}
        <div class="my-4 bg-red-300 p-4 w-1/2">
            <x-input class="h-12 !text-xl !text-gray-500" icon='search' type="search" wire:model.live="detailFilter"
                placeholder="Search with detail design" />
        </div>
        {{-- End Design filter --}}

        {{-- filter warning  --}}
        {{-- <a class="flex items-center p-4 mb-4 text-sm text-yellow-800 border border-yellow-300 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300 dark:border-yellow-800"
            role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                fill="currentColor" viewBox="0 0 20 20">
                <path
                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
            </svg>
            <span class="sr-only">Info</span>
            <div>
                @if ($detailFilter)
                    {{ $detailFilter }} ဒီဇိုင်း
                @endif
                @if ($designFilter)
                    ({{ $designFilter }})
                @endif
                <span>
                    and sale grade with A/ branch 1 requested to order by priority and then this item currently state is
                    add / This result is between st and en
                </span>
            </div>
        </a> --}}
        {{-- end filter warning --}}

        <div class="bg-slate-300 mt-20 mb-4">
            @foreach ($orderGroup as $branchName => $statusGroup)
                {{-- start one branch information  --}}
                <section class="bg-white py-1 my-8 antialiased rounded-lg dark:bg-gray-900 md:py-1 dark:text-gray-200">
                    <div class="mx-auto max-w-screen-4xl px-4 2xl:px-0">
                        <div class="mx-auto uppercase">
                            <h1 class="my-4 font-bold text-2xl">{{ $branchName }} </h1>
                            {{-- new table design --}}
                            <div class="relative overflow-x-auto max-w-screen-kg shadow-md sm:rounded-lg h-auto">
                                @foreach ($statusGroup as $statusName => $relatedData)
                                    <h2 class="font-bold mt-12 text-red-400">{{ $statusName }} </h2>
                                    <table
                                        class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                        <thead
                                            class="text-xs text-gray-700 uppercase bg-violet-50 dark:bg-gray-700 dark:text-gray-400">
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
                                                    Order Date
                                                </th>
                                                <th scope="col" class="px-6 py-3 sr-only">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($relatedData as $item)
                                                <tr
                                                    class=" border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 {{ \Carbon\Carbon::now()->diffInHours($item->updated_at) > 24 ? 'bg-red-200' : 'bg-white' }}">

                                                    <th scope="row"
                                                        class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                        {{ $item->detail }}
                                                    </th>
                                                    <td class="px-6 py-4">
                                                        {{ $item->priority->name }}
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        {{ $item->quality->name }}
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        {{ $item->design->name }}
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        {{ $item->weight }}
                                                    </td>

                                                    <td class="px-6 py-4">
                                                        {{ $item->updated_at->format('d-M-y') }}<i
                                                            class="text-xs text-blue-400">
                                                            ({{ $item->updated_at->diffForHumans() }})</i>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        {{ $item->created_at->format('d-M-y') }}
                                                    </td>
                                                    <td class="flex items-center px-6 py-4">
                                                        @can('isPurchaser')
                                                            @if ($item->status_id == 1)
                                                                <x-button green label="Ack" xs wire:click="ack({{ $item->id }})" />
                                                            @endif
                                                        @endcan
                                                        <x-button href="/order/detail?order_id={{ $item->id }}"
                                                            flat teal icon="information-circle"
                                                            wire:navigate></x-button>
                                                        <x-button icon="chat" teal flat
                                                            onclick="$openModal('commentModal')"
                                                            wire:click="order({{ $item->id }})"><span
                                                                class="text-blue-500">{{ count($item->comments) < 1 ? '' : count($item->comments) }}</span></x-button>
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

        {{-- Comment Modal  --}}
        <x-modal.card title="Comments" wire:model='commentModal' name="comments">
            <div class="w-full p-2 overflow-y-scroll border border-gray-400 rounded commentSession h-80">
                @forelse ($comments as $comment)
                    <div>
                        <div class="flex p-2 mb-3 bg-blue-200 rounded sent-message w-fit">
                            <div class="mr-2 profile">
                                <img src="{{ asset('images/user.png') }}" alt="user-avatar" class="w-6 h-6">
                            </div>
                            <div class="">
                                <div class="mb-1 text-sm">
                                    <p class="font-semibold">{{ $comment->user->name }}</p>
                                    <p class="text-xs">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <p class="text-sm">{{ $comment->content }}
                                    <button wire:click='replyComment({{ $comment->id }})'
                                        class="text-xs text-blue-800 underline rounded-full">reply</button>
                                </p>
                            </div>
                        </div>
                        @foreach ($comment->replys as $reply)
                            <div class="flex mb-1 ml-10 sent-message w-fit">
                                <div class="mr-2 profile">
                                    <img src="{{ asset('images/user.png') }}" alt="user-avatar" class="w-6 h-6">
                                </div>
                                <div class="mb-1 text-sm">
                                    <p class="font-semibold">{{ $reply->user->name }}<span
                                            class="text-xs italic text-slate-500">
                                            {{ $reply->created_at->diffForHumans() }}
                                        </span></p>

                                    <p>{{ $reply->content }}</p>
                                </div>
                            </div>
                        @endforeach
                        @if ($comment->id == $reply_toggle)
                            <div x-data x-transition.duration.500ms class="flex flex-col px-10 mb-2">
                                <div class="flex">
                                    <x-input
                                        class="w-full border text-sm rounded focus:ring-0 dark:bg-gray-600 dark:text-gray-200"
                                        type="text" wire:model='reply' placeholder="reply to this comment" />
                                    <div>
                                        <button class="ml-4 bg-emerald-600 text-white px-2 py-1.5 rounded"
                                            wire:click="createReply({{ $comment->id }})">Reply</button>
                                    </div>
                                </div>
                                @error('reply_content')
                                    <span class="text-xs text-red-500">Can't empty reply</span>
                                @enderror
                            </div>
                        @endif
                    </div>
                @empty
                    <p>No comment</p>
                @endforelse
            </div>
            <x-slot name="footer">
                <form>
                    <x-textarea class="w-full mb-2 rounded bg-slate-100" title="Create a comment"
                        wire:model='comment' placeholder="Type a comment"></x-textarea>
                    <x-button wire:click.prevent="createComment()">send</x-button>
                </form>
            </x-slot>
        </x-modal.card>


    </div>
