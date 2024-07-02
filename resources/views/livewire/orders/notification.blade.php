<div>
    @foreach ($notis as $noti)
    <div class="p-2 mb-2 bg-blue-300 rounded">
        <a href="http://localhost:8000/order/detail?order_id={{ $noti->comment->order_id }}&prestro={{ $noti->comment->id }}" wire:navigate>
            <div class="flex justify-between">
                @if ($noti->user->id == $noti->comment->user->id)
                <div>
                    <span>{{ $noti->user->name }} <i>replyed to</i></span>
                    <span>his own <i>comment</i></span>
                    {{-- <span><i>where</i>Order_No{{ $noti->comment->order_id }}</span> --}}
                </div>
                @else
                <div>
                    <span>{{ $noti->user->name }} <i>replyed to</i></span>
                    <span>{{ $noti->comment->user->name }}'s <i>comment</i></span>
                    {{-- <span><i>where</i>Order_No{{ $noti->comment->order_id }}</span> --}}
                </div>

                @endif
                <span>{{ $noti->created_at->diffForHumans() }}</span>
            </div>
        </a>
    </div>
    @endforeach
</div>
