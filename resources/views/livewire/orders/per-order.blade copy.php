<div>
    <div class="p-2 rounded bg-slate-900">
        {{-- Order Detail block  --}}
        <div class="p-2 rounded shadow-inner bg-slate-200 shadow-cyan-800">
            <div class="flex gap-2">
                {{ $order->grade->name }}
                <div class="flex text-white">
                    <span class="p-1 bg-fuchsia-400 rounded-s-full">{{ $order->category->name }}</span>
                    <span class="p-1 bg-fuchsia-500/95">{{ $order->quality->name }}</span>
                    <span class="p-1 bg-fuchsia-600/80 rounded-e-full">{{ $order->design->name }}</span>
                </div>
            </div>
            <div class="flex flex-wrap gap-1">
                {{-- Branch Input --}}
                <div class="flex gap-1">
                    <i>Quantity</i>
                    <i>{{ $order->status->name }}</i>
                    {{-- <span>{{ $order->qty }}</span> --}}
                    <form action="" class="flex flex-col" wire:submit='update_ordQty({{ $order->id }})'>
                        <div>
                            <input class="w-16 py-0 rounded" wire:model='update_qty' placeholder="{{ $order->qty }}"/>
                        </div>
                        @error('update_qty')
                            <span class="text-sm text-red-600">Enter edit value</span>
                            @enderror
                            @if(session('updatedQty'))
                                <span class="text-sm text-green-700">{{ session('updatedQty') }}</span>
                        @endif
                    </form>
                </div>
                <div>
                    <i>Counter Stock</i>
                    <span>{{ $order->counterstock }}</span>
                    <i>Sell/Month</i>
                    <span>{{ $order->sell_rate }}<br/></span>
                    <span>{{ $order->note }}</span>
                </div>
                {{-- End Branch Input --}}
                @if ($order->status_id > 1)
                    <div class="flex gap-1">
                        {{-- <span>{{ $order->qty }}</span> --}}
                        <form action="" class="flex flex-col" wire:submit='update_invData({{ $order->id }})'>
                            <div>
                                <label for="instock">Inv/Stock</label>
                                <input id="instock" class="w-16 py-0 rounded" wire:model='instock_qty' placeholder="{{ $order->instockqty }}"/>
                                <label for="time">estimate/Time?</label>
                                <input id="time" class="w-16 py-0 rounded" wire:model='estimate_time' placeholder="{{ $order->estimatetime }}"/>
                                <button class="hidden">update</button>
                            </div>
                            @error('instock_qty')
                                <span class="text-sm text-red-600">empty instock qty</span>
                            @enderror
                            @error('estimate_time')
                                <span class="text-sm text-red-600">empty estimate time</span>
                            @enderror
                            @if(session('updatedInv'))
                                    <span class="text-sm text-green-700">{{ session('updatedInv') }}</span>
                            @endif
                        </form>
                    </div>
                @endif
            </div>
        </div>
        {{-- End Order Detail --}}
        {{-- * Action Buttons --}}
        <div class="flex">
            {{-- Ackbutton --}}
        <div class="p-2 m-4 border rounded">
            <button wire:click="acked({{ $order->id }})" class="px-4 text-white bg-red-300 rounded">Ack</button>
            <div class="text-green-300">
                @if (session('ackedSuccess'))
                    {{ session('ackedSuccess') }}
                @endif
            </div>
        </div>
            {{-- End Ack button --}}

            {{-- Request button --}}
        <div class="p-2 m-4 border rounded">
            <button wire:click="requested({{ $order->id }})" class="px-4 text-white bg-red-300 rounded">Request</button>
            <div class="text-green-300">
                @if (session('requestedSuccess'))
                    {{ session('requestedSuccess') }}
                @endif
            </div>
        </div>
        {{-- End Action Buttons --}}
        </div>

        {{-- Quick Chat Meeting create --}}
        <div>
            <form action="" wire:submit="create_pool({{ $order->id }})">
                <button class="p-2 bg-blue-700 rounded text-slate-50">create a meeting for this</button>
                <span class="text-red-500">
                    @if(session()->has('msgByPool'))
                    {{ session('msgByPool') }}
                    @endif
                </span>
            </form>
        </div>
        {{-- End Quick Chat --}}

        {{-- Comment Loop Section Start --}}
        <div class="p-2 mt-4 bg-slate-800 ">
            <div class="mb-3 text-slate-50">
                @forelse ($order->comments as $comment)
                {{-- comment and his reply field  --}}
                <div class="px-2 py-4 mb-3 border rounded-md">
                    <div class="flex gap-5">
                        <a href="#comment-{{ $comment->id }}"><li class="text-yellow-500">{{ $comment->user->name }}</li></a>
                        <button wire:click="reply_to_comment({{ $comment->id }})">reply
                        @foreach ($comment->replys as $reply)
                            @if ($loop->first)
                            <span class="text-sm text-blue-600">{{ $loop->count }}</span>
                            @endif
                        @endforeach
                        </button>
                        <div class="text-sm text-slate-400">{{ $comment->created_at->diffForHumans() }}</div>
                    </div>
                    {{ $comment->content }}

                    {{-- Comment's reply loop  --}}
                    @if($reply_toggle == $comment->id)
                    <div class="ml-5">
                        @foreach ($comment->replys as $reply)
                            <div class="p-2 mb-2 border rounded border-red-500/40 bg-slate-900">
                                <li class="text-sm text-yellow-700">{{ $reply->user->name }}</li>
                                <i class="text-sm">{{ $reply->content }}</i>
                            </div>
                        @endforeach
                    </div>
                    {{--* reply form --}}
                            <div class="rounded ps-10 min-w-min">
                                <form class="flex" wire:submit="create_reply({{ $comment->id }})">
                                    <input wire:model="reply_content" class="w-full rounded-full bg-slate-900 text-slate-50">
                                    <button class="px-2 py-0 bg-blue-800 rounded text-slate-200 hover:bg-blue-900 hover:text-white">reply</button>
                                </form>
                            </div>
                    {{--* end reply form  --}}
                    @endif
                </div>
            {{--End comment and his reply field  --}}
                @empty
                    <i>not yet comment</i>
                @endforelse
            </div>
        {{-- Loop Comment Section  --}}

            {{-- Write New Comment --}}
            <div class="mt-5">
                <form class="w-full" action="" wire:submit="create_comment({{ $order->id }})">
                    <textarea class="w-full" wire:model="content"></textarea>
                    <button class="p-1 text-white bg-blue-400 rounded">Comment</button>
                </form>
            </div>
            {{-- End Writting Comment --}}
        </div>
    </div>
</div>
