<div class="">
    @if($newComment > 0)
    <a href="#" class="flex items-center p-4 mb-4 text-sm text-yellow-800 border border-yellow-300 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300 dark:border-yellow-800" role="alert">
        <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
          <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
        </svg>
        <span class="sr-only">Info</span>
        <div>
          <span class="font-medium">Unread comment</span> {{ $newComment }} ခု တွေ့ရှိထားသည်။
        </div>
    </a>
    @endif
        @foreach ($comments as $comment)
            <div class="p-2 cursor-pointer {{ $comment->isRead ? 'bg-slate-300' : 'bg-black' }} rounded mb-4"
                wire:click="readComment({{ $comment->id }}, {{ $comment->orderId }})">
                <div class=" flex">
                    <div class="relative">
                        <h1
                            class="{{ $comment->isRead ? 'text-black' : 'text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-pink-500 to-purple-500' }} ">
                            {{ $comment->content }}
                        </h1>
                        <div class="absolute inset-0 animate-pulse opacity-50 {{ $comment->isRead ? 'hidden' : '' }}">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-blue-400 via-pink-500 to-purple-500 blur-lg rounded-xl">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 {{ $comment->isRead ? 'text-slate-800' : 'text-slate-300' }}"><em class="text-lg">{{ $comment->commentBy }} </em><i class="text-sm">left a comment on your order</i> </div>
            </div>
        @endforeach

</div>
