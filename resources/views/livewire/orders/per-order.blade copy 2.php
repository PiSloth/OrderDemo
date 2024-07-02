<div class="mt-10 ml-10 mr-10 lg:ml-72">

    <div class="">

        <h1 class="text-2xl font-bold dark:text-gray-200 text-center mb-2">Order Detail</h1>

        <div class="flex items-center justify-center">
            {{-- <img src="{{url('images/logo.png')}}" alt="" class="w-24 h-20 bg-white mb-6 rounded-lg"> --}}
            @if ($order->images)
            @foreach ($order->images as $image)
            <img x-on:click="$openModal('image')" class="object-cover w-full rounded-lg hover:cursor-zoom-in h-96 md:h-auto md:w-48 mb-4" src="{{ asset('storage/' . $image->orderimg) }}" alt="">
            <x-modal wire:model='image'>

                <img src="{{ asset('storage/' . $image->orderimg) }}" class="bg-white mb-6 rounded-lg">

            </x-modal>
            @endforeach
            @endif
        </div>

        {{-- Start order detail info container --}}
        <div x-data class="grid md:grid-cols-2 gap-10 bg-gray-200 py-2 dark:bg-gray-700">

            <div class="py-3 mt-2 text-sm leading-normal md:ps-16 md:py-0">
                <div class="flex items-center justify-between mb-2">
                    {{-- <h5 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">Order Information</h5> --}}
                    <p class=" px-2 py-1 rounded
                    @if ($order->priority_id == 1) bg-red-400
                        text-white
                    @elseif ($order->priority_id == 2)
                        bg-yellow-200
                        text-gray-600
                    @elseif ($order->priority_id == 3)
                        bg-green-400
                        text-white @endif
                    ">
                        {{ $order->priority->name }}
                    </p>
                    <p class="text-red-500">
                        {{ $order->status->name }}
                    </p>
                </div>

                <div class="w-full font-normal text-gray-700 dark:text-gray-200">
                    <div class="flex items-center justify-between gap-10 mb-1">
                        <p><b>Order ID</b>: {{ $order->id }}</p>
                        <p class="">{{ $order->grade->name }}</p>
                    </div>

                    <div class="grid grid-cols-3 mb-1 sm:gap-5 md:gap-20">
                        <p class="">
                            <b>Category</b>: {{ $order->category->name }}
                        </p>
                        <p>
                            <b>Weight</b>: <span id="weight"></span> <i>({{ $order->weight }} g)</i>
                            <input id="weightInGram" hidden value="{{ $order->weight }}" />
                        </p>
                        <p>
                            <b>Qty </b>: <span class="">{{ $order->qty }}</span> <i>pcs</i>
                        </p>
                    </div>

                    <div class="grid grid-cols-3 mb-1 sm:gap-5 md:gap-20">
                        <p>
                            <input hidden value="{{ $order->weight }}" id="weightInGram">

                            <b>Design</b>: {{ $order->design->name }}
                        </p>
                        <p>
                            <b>Size</b>: {{ $order->size }}
                        </p>
                        <p>
                            <b>In Inv</b>: {{ $order->instockqty }} <i>pcs</i>
                        </p>
                    </div>

                    <div class="grid grid-cols-3 mb-1 sm:gap-5 md:gap-20">
                        <p>
                            <b>Quality</b>: {{ $order->quality->name }}
                        </p>
                        <p>
                            <b>Sell Rate</b>: {{ $order->sell_rate }} <i>pcs</i>
                        </p>
                        <p>
                            <b>Estimate/Time </b>: {{ $order->estimatetime }} <i>days</i>
                        </p>
                    </div>
                </div>
                {{-- message button --}}
                <div class="flex gap-2 mt-4 mb-2 ">
                    <div>
                        <x-badge sky class="absolute w-3 h-4 -ml-1" rounded primary label="{{ $comments }}" />
                        <x-button flat positive icon="chat-alt-2" label="Comments" @click="$openModal('comments')"></x-button>
                    </div>
                    <x-button flat info icon="clock" label="History" @click="$openModal('history')"></x-button>
                    @if ($order->status_id !== 8 && $order->status_id !== 7 && $invUser)
                    <x-button flat negative icon="x" label="Cancel" @click="$openModal('cancelOrder')"></x-button>
                    @endif
                </div>
            </div>

            <div class="w-full px-2 py-4 mx-auto mb-5 text-sm dark:text-gray-200 leading-7">
                <b>Note</b>: {{ $order->note }}
            </div>

        </div>


        {{-- End order detail info container --}}
        {{-- Start input data section --}}
        @if ($invUser && $order->status_id >= 2)
        <div class="w-full px-2 py-4 mx-auto mb-5 text-sm bg-white border border-gray-200 rounded-lg shadow md:flex-row dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-10">
                <div class="w-full">
                    <label for="inhand" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">In
                        Hand</label>
                    <input wire:model='instockqty' type="number" id="inhand" placeholder="{{ $order->instockqty }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="0">
                    @error('instockqty')
                    <span class="text-sm text-red-400">Pls fill the instock Qty</span>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="days" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ကြာချိန်/Days</label>
                    <input wire:model='estimatetime' type="date" id="days" placeholder="{{ $order->estimatetime }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="0">
                    @error('estimatetime')
                    <span class="text-sm text-red-400">Pls fill estimate time</span>
                    @enderror
                </div>
            </div>
        </div>
        @endif
        @if ($invUser && $order->status_id == 3)
        <div class="mt-3">
            <div class="w-full">
                <label for="editqty" class="block mb-2 text-sm font-medium text-gray-900 text-blue-700 dark:text-white">Edit ? Order
                    Qty</label>
                <input wire:model='editqty' type="number" id="days" placeholder="{{ $order->qty }}" class="bg-red-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="0">
            </div>
        </div>
        @endif

        @if ($order->status_id >= 5)
        <div class="flex items-center justify-between gap-10 mt-4 {{ $invUser ? "" : "hidden" }}">
            <div class="w-full">
                <label for="aqty" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Arrivals-Qty</label>
                <input type="number" id="aqty" wire:model='arqty' class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="{{ $order->arqty }}" required>
                @error('arqty')
                <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="cqty" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Close-Qty</label>
                <input type="number" id="cqty" wire:model='closeqty' class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="{{ $order->closeqty }}">
                @error('closeqty')
                <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
        @endif

        {{-- End input data section --}}

        {{-- Start Action Buttons --}}
        <hr class="border-gray-400 my-2">
        <div class="w-full px-2 py-4 mx-auto mt-2 mb-5 text-sm bg-white md:flex-row dark:border-gray-700 dark:bg-gray-800">
            @if (!$chatPool || $chatPool->completed)
            {{-- --}}
            <x-button label="i-Meeting" onclick="$openModal('iMeeting')" class="text-black border-2 border-cyan-500 items-center justify-center p-0.5 mb-2 me-2 px-5 py-2.5 transition-all ease-in duration-75 dark:bg-gray-900 rounded-md  bg-gradient-to-br hover:from-cyan-500 hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800" />
            @error('i_title')
            <span class="text-sm text-red-400">
                Add "title" to create an i Meeting
            </span>
            @enderror
            @endif
            @if ($chatPool && !$chatPool->completed)
            <button class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <a href="{{ route('chat') }}" class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    See in i-Meeting
                </a>
            </button>
            @endif



            @if ($invUser && $order->status_id == 1)
            <button wire:click="acked({{ $order->id }})" class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Ack
                </span>
            </button>
            @endif

            @if ($invUser && $order->status_id == 2)
            <button wire:click="requested({{ $order->id }})" class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Request
                </span>
            </button>
            @endif

            @if ($approver && $order->status_id == 3)
            <button wire:click="approved({{ $order->id }},{{ $order->qty }})" class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Approve
                </span>
            </button>
            @endif

            @if ($invUser && $order->status_id == 4)
            <button wire:click="ordered({{ $order->id }})" class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Order
                </span>
            </button>
            @endif

            @if ($invUser && $order->status_id == 5)
            <button wire:click="arrived({{ $order->id }})" class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Arrived
                </span>
            </button>
            @endif

            @if ($invUser && $order->status_id == 6)
            <button wire:click="closed({{ $order->id }})" class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                <span class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                    Close
                </span>
            </button>
            @endif


        </div>
        {{-- End Action Buttons --}}

        {{-- i Meeting Create Modal --}}
        <x-modal.card title="Create i-Meeting" wire:model='iMeeting' name="iMeeting">
            <x-input wire:model='i_title' label="Meeting Title" />

            <x-slot name="footer">
                <button wire:click="create_pool({{ $order->id }})" class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-cyan-500 to-blue-500 group-hover:from-cyan-500 group-hover:to-blue-500 hover:text-white dark:text-white focus:ring-4 focus:outline-none focus:ring-cyan-200 dark:focus:ring-cyan-800">
                    <span class="relative px-5 py-2.5 transition-all ease-in duration-75 bg-white dark:bg-gray-900 rounded-md group-hover:bg-opacity-0">
                        Create i-Meeting
                    </span>
                </button>
            </x-slot>
        </x-modal.card>

        <x-modal.card title="Cancel Order" wire:model='cancelOrder' name="cancelOrder">
            <div>
                <x-input label="Reason" class="w-full" wire:model='cancel_reason' placeholder="Write a reason" />
            </div>
            <x-slot name="footer">
                <x-button wire:click="cancel_order({{ $order->id }})" label="confirm"></x-button>
            </x-slot>
        </x-modal.card>

        <x-modal.card title="History" wire:model='history' name='history'>
            <div class="px-2 pb-2">
                <ul>

                    @foreach ($order->histories as $history)
                    <li class="cursor-pointer ">
                        <details>
                            <summary class="text-gray-400 hover:text-gray-900">{{ $history->status->name }}</summary>
                            <ul class="ml-4">
                                <li>
                                    <span class="flex">
                                        <x-icon name="user" class="w-5 h-5" />
                                        {{ $history->user->name }}
                                    </span>
                                </li>
                                <li class="ml-2 text-xs text-slate-300">
                                    {{ $history->updated_at }}
                                </li>
                                <li class="ml-2">
                                    <span class="text-red-700">{{ $history->content }}</span>
                                </li>
                            </ul>
                        </details>
                    </li>
                    @endforeach
                </ul>
            </div>
        </x-modal.card>
        {{-- End Order History --}}

        <x-modal.card title="Comments" wire:model='comments' name="comments">
            <div class="w-full p-2 overflow-y-scroll border border-gray-400 rounded commentSession h-80">
                @forelse ($order->comments as $comment)
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
                                <button wire:click='reply_to_comment({{ $comment->id }})' class="text-xs text-blue-800 underline rounded-full">reply</button>
                            </p>
                        </div>
                    </div>
                    @foreach ($comment->replys as $reply)
                    <div class="flex mb-1 ml-10 sent-message w-fit">
                        <div class="mr-2 profile">
                            <img src="{{ asset('images/user.png') }}" alt="user-avatar" class="w-6 h-6">
                        </div>
                        <div class="mb-1 text-sm">
                            <p class="font-semibold">{{ $reply->user->name }}<span class="text-xs italic text-slate-500">
                                    {{ $reply->created_at->diffForHumans() }}
                                </span></p>

                            <p>{{ $reply->content }}</p>
                        </div>
                    </div>
                    @endforeach
                    @if ($comment->id == $reply_toggle)
                    <div x-data x-transition.duration.500ms class="flex flex-col px-10 mb-2">
                        <div class="flex">
                            <input class="w-full border text-sm rounded focus:ring-0 dark:bg-gray-600 dark:text-gray-200" type="text" wire:model='reply_content' placeholder="reply to this comment" />
                            <button class="ml-4 bg-emerald-600 text-white px-2 py-1.5 rounded" wire:click="create_reply({{ $comment->id }})">Reply</button>
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
                <form wire:model=''>
                    <textarea class="w-full mb-2 rounded bg-slate-100" title="Create a comment" wire:model='content' placeholder="Type a comment"></textarea>
                    <x-button wire:click.prevent="create_comment({{ $order->id }})">send</x-button>
                </form>
            </x-slot>
        </x-modal.card>
    </div>



    <script>
        console.log("Hello");

        function mmUnitCalc(gramWeight) {
            let kyat = gramWeight * (1 / 16.606);
            kyat.toFixed(2)
            let answerKyat = Math.floor(kyat);
            console.log(Math.floor(kyat));

            let pae = (kyat - answerKyat) * 16;
            let answerPae = Math.floor(pae);

            let yawe = (pae - answerPae) * 8;
            let answerYawe = yawe.toFixed(2);
            if (answerKyat > 0) {
                return `${answerKyat} ကျပ် ${answerPae} ပဲ ${answerYawe} ရွေး`;
            } else if (answerPae > 0) {
                return ` ${answerPae} ပဲ ${answerYawe} ရွေး`;
            } else {
                return `${answerYawe} ရွေး`;
            }
        }

        function gramToKpy() {
            let gram = document.getElementById("weightInGram").value;
            console.log(gram);
            let answer = mmUnitCalc(gram);
            document.getElementById("weight").innerHTML = answer;
        }
        gramToKpy()
    </script>
