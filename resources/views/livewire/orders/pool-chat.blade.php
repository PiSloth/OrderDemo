@extends('livewire.orders.layout.dashboard-layout')

@section('content')
    <div class="pl-10 pt-10 pr-10 lg:pl-72">
        {{-- @dd($chatspool); --}}
        <blockquote class="p-4 my-4 border-s-4 border-gray-300 bg-gray-50 dark:border-gray-500 dark:bg-gray-800">
            <p class="text-xl italic font-medium leading-relaxed text-gray-900 dark:text-white">
                "i Meeting ၏ ရည်ရွယ်ချက်သည် Decision Making Conversation များကို ဆွေးနွေးကြရန်ဖြစ်သည်။
                ၁၈ နာရီအတွင်း Decision မပြတ်ပါက Alarm ပြပြီး Approver,Purchaser, Order တာဝန်ခံ အသီးသီးတို့၏
                လုပ်ငန်းစွမ်းဆောင်ရည်ကို ထိခိုက်နိုင်ပါသည်။"
            </p>
        </blockquote>
        @foreach ($chatspool as $chatpool)
            @php
                $differentTime = $chatpool->created_at->diffForHumans();
            @endphp

            <div
                class="px-5 py-4 my-10 {{ $chatpool->created_at->diffInHours($currentTime) >= 18 ? 'bg-red-200' : 'bg-white' }} border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                <div>
                    {{-- i Meeting Title --}}
                    <div class="mb-2 text-black flex items-center justify-between dark:text-gray-200">
                        <p>{{ $chatpool->title }}</p>
                        <p class="text-sm">{{ $differentTime }}</p>
                    </div>

                    <div class="flex justify-between mb-3 pb-4 border-b-2 border-gray-600">
                        <div class="flex items-center justify-center gap-2">
                            @if ($chatpool->order->status_id < $chatpool->status_id)
                                <button label="success" icon="check" positive
                                    class="bg-gray-500 text-white px-2 py-1.5 rounded text-sm cursor-not-allowed"
                                    disabled>Success, but not</button>
                            @elseif($chatpool->status_id >= $chatpool->order->status_id)
                                <button label="disabledsuccess" icon="check"
                                    wire:click="complete_pool({{ $chatpool->id }},{{ $chatpool->order->status_id }},{{ $chatpool->status_id }})"
                                    positive class="bg-emerald-700 text-white px-2 py-1.5 rounded text-sm">Success</button>
                            @else
                                <x-button.circle 2xs icon="x" outline rose
                                    onclick="$openModal('{{ $chatpool->id }}{{ $chatpool->order_id }}')" />
                            @endif

                        </div>
                        <div>

                            @can('isAuthorizedToEndMeeting')
                                <button onclick="$openModal({{ $chatpool->id }})"
                                    class="text-sm bg-red-500 text-white p-1.5 rounded">End Meeting</button>
                            @endcan

                            <a href="/order/detail?order_id={{ $chatpool->order_id }}" wire:navigate label="view"
                                class="text-sm bg-emerald-500 text-white p-1.5 rounded">See Order Detail</a>
                        </div>
                    </div>

                    <div class="mb-4 flex items-center justify-between text-sm">
                        <x-button primary label="Let's Talk" class="px-2 py-1 text-white bg-blue-500"
                            wire:click="toggle({{ $chatpool->id }})" />

                        <p class="dark:text-gray-200">တာဝန််ခံ {{ $chatpool->order->user->name }}</p>
                    </div>

                    {{-- End of to end i-Meeting --}}

                    {{-- Start End-button with a reason --}}

                    <x-modal.card title="Cancel i-Meeting" blur name="cancelMeeting"
                        wire:model="{{ $chatpool->id }}{{ $chatpool->order_id }}">
                        {{-- ! This sentence add to wire ui modal.blade --> x-on:close-modal.window="show = false" --}}
                        <div class="grid grid-cols-1">
                            <x-input wire:model='reason' label="Reason" placeholder="Write a reason to cancel" />
                        </div>

                        <x-slot name="footer">

                            <div class="flex justify-between gap-x-4">
                                <x-button primary label="Confirm"
                                    wire:click="complete_pool({{ $chatpool->id }},{{ $chatpool->order->status_id }},{{ $chatpool->status_id }})" />
                            </div>
                        </x-slot>
                    </x-modal.card>
                    {{-- start meeting noted model --}}

                    <x-modal.card title="Note" blur name="myModal" wire:model="{{ $chatpool->id }}">
                        <div class="w-full mb-5">
                            <x-select label="Select Status" placeholder="Select one status" :options="[
                                ['name' => 'Ack', 'id' => 2, 'description' => 'အသိအမှတ်ပြုသည်'],
                                ['name' => 'Approved', 'id' => 4, 'description' => 'အော်ဒါတင်ရန် ခွင့်ပြုမယ်'],
                                ['name' => 'Ordered', 'id' => 5, 'description' => 'အော်ဒါတင်လိုက်ရန်'],
                                ['name' => 'Cancel', 'id' => 8, 'description' => 'Cancel လုပ်လိုက်သည်'],
                            ]"
                                option-label="name" option-value="id" wire:model="assign_id" />
                        </div>
                        <x-textarea label="i-Meeting Note" wire:model='meetingnote' placeholder="write your annotations" />
                        <x-slot name="footer">
                            <div class="flex justify-between gap-x-4">
                                {{-- <x-button flat negative label="Delete" wire:click="delete" /> --}}

                                <div class="flex">
                                    <button class="bg-red-600 px-2 py-1.5 rounded text-white" label="Cancel"
                                        x-on:click="close">Cancel</button>
                                    <button primary class="bg-primary-600 ml-2 px-4 py-1.5 rounded text-white"
                                        label="Save" wire:click='create_meetingnote({{ $chatpool->id }})'>Save</button>
                                </div>
                            </div>
                        </x-slot>
                    </x-modal.card>

                    {{-- End end-button with a reason  --}}


                    {{-- <div id="hideMnote" class="relative hidden w-full max-w-2xl max-h-full p-4">

                    <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                        <div class="p-2 border">
                            <form wire:submit='create_meetingnote({{ $chatpool->id }})' class="flex flex-col">

                                <h5>Meeting Note {{ $chatpool->id }}</h5>


                                <label for="assignJob">Assign to</label>
                                @error('assign_id')
                                    <p class="text-sm text-red-300">Can't Empty</p>
                                @enderror
                                <select wire:model='assign_id' class="mb-4 w-60">
                                    <option value="">Select</option>
                                    <option value="2">Ack</option>
                                    <option value="4">Approve</option>
                                    <option value="5">Order</option>
                                    <option value="8">Cancel</option>
                                </select>



                                <textarea wire:model='meetingnote' class="mb-2" placeholder="Write a Note"></textarea>
                                <button type="submit" class="w-10 p-1 text-white bg-blue-500 rounded">save</button>
                                <button type="button" onclick="toggleDiv('hideMnote')"
                                    class="w-10 p-1 text-white bg-red-500 rounded">&times;</button>
                            </form>

                        </div>
                    </div>
                </div> --}}


                    {{-- Success Meeting Note Function and Note preview --}}
                    <div>
                        {{-- Assign job and note --}}
                        <div class="text-gray-500">
                            @if ($chatpool->meeting_note)
                                <p><b>Asssign to: {{ $chatpool->status->name }}<br /></b>{{ $chatpool->meeting_note }}</p>
                            @endif
                        </div>
                    </div>
                    {{-- meeting note form --}}

                    {{-- start hidden section for the comment box --}}
                    @if ($chatpool->id == $toggle_id)
                        <div class="hidden-comment">
                            <div class="w-full p-2 overflow-y-scroll border border-gray-400 rounded commentSession h-60">
                                @forelse ($chatpool->order->comments as $comment)
                                    <div class="flex p-2 mb-3 bg-blue-200 rounded sent-message w-fit">
                                        <div class="mr-2 profile">
                                            <img src="{{ asset('images/user.png') }}" alt="user-avatar" class="w-6 h-6">
                                        </div>
                                        <div class="">
                                            <div class="mb-1 text-sm">
                                                <p class="font-semibold">{{ auth()->user()->name }}</p>
                                                <p class="text-xs">
                                                    {{ $comment->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                            <p class="text-sm">
                                                {{ $comment->content }}
                                                <button wire:click='reply_to_comment({{ $comment->id }})'
                                                    class="text-xs text-blue-800 underline rounded-full">reply</button>
                                            </p>
                                        </div>
                                    </div>

                                    @foreach ($comment->replys as $reply)
                                        <div class="flex mb-1 ml-10 sent-message w-fit">
                                            <div class="mr-2 profile">
                                                <img src="{{ asset('images/user.png') }}" alt="user-avatar"
                                                    class="w-6 h-6">
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
                                                <input
                                                    class="w-full border text-sm rounded focus:ring-0 dark:bg-gray-600 dark:text-gray-200"
                                                    type="text" wire:model='reply_content'
                                                    placeholder="reply to this comment" />
                                                <button class="ml-4 bg-emerald-600 text-white px-2 py-1.5 rounded"
                                                    wire:click="create_reply({{ $comment->id }})">Reply</button>
                                            </div>
                                            @error('reply_content')
                                                <span class="text-xs text-red-500">Can't empty reply</span>
                                            @enderror
                                        </div>
                                    @endif
                                @empty
                                    <p class="my-2 text-black dark:text-gray-200">Message မရှိသေးပါ</p>
                                @endforelse
                            </div>
                            <form action="" class="relative pt-7"
                                wire:submit="create_comment({{ $chatpool->order_id }},{{ $chatpool->id }})"
                                wire:keydown.shift.enter="create_comment({{ $chatpool->order_id }},{{ $chatpool->id }})">
                                @if ($content)
                                    <i class="absolute top-0 text-sm text-slate-500 dark:text-gray-300">
                                        {{ auth()->user()->name }} is writing...
                                    </i>
                                @endif

                                <textarea wire:model.live="content" id="message" rows="4"
                                    class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder="အကြောင်းအရာရေးပါ..."></textarea>

                                {{-- Error Alert Box --}}
                                @error('content')
                                    <div class="flex items-center p-4 mt-2 text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400"
                                        role="alert">
                                        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                                        </svg>
                                        <span class="sr-only">Info</span>
                                        <div class="text-sm font-medium ms-3">
                                            အကြောင်းအရာဖြည့်စွက်ရန်လိုအပ်နေပါတယ်...
                                        </div>
                                    </div>
                                @enderror

                                <div class="flex items-center justify-between">
                                    <button type="submit"
                                        class="inline-flex items-center px-3 py-2 mt-4 text-sm font-medium text-center text-white bg-green-700 rounded-lg hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                                        Send
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                    {{-- End hidden message form --}}
                </div>
            </div>
        @endforeach
    </div>


    <script>
        function toggleDiv(id) {
            var myDiv = document.getElementById(id);
            console.log("Throunged toggle");
            myDiv.classList.toggle("hidden");
        }


        // function updateScroll(){
        // startAutoScroll();
    </script>
@endsection
