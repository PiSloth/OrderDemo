<div>
    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Notifications</h1>
            @if($unreadCount > 0)
                <button
                    wire:click="markAllAsRead"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm"
                >
                    Mark All as Read ({{ $unreadCount }})
                </button>
            @endif
        </div>

        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        @if($notifications->count() > 0)
            <div class="space-y-4">
                @foreach($notifications as $notification)
                    <div class="border border-gray-200 rounded-lg p-4 {{ $notification->read ? 'bg-gray-50' : 'bg-blue-50 border-blue-200' }}">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h3 class="font-semibold text-gray-900">{{ $notification->title }}</h3>
                                    @if(!$notification->read)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            New
                                        </span>
                                    @endif
                                    <span class="text-sm text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                                </div>

                                <p class="text-gray-700 mb-2">{{ $notification->message }}</p>

                                @if($notification->data && isset($notification->data['comment_preview']))
                                    <div class="bg-gray-100 p-3 rounded text-sm text-gray-600 mb-3">
                                        "{{ $notification->data['comment_preview'] }}"
                                    </div>
                                @endif

                                <div class="flex items-center space-x-4">
                                    <a
                                        href="{{ route('task_comments', $notification->todo_list_id) }}#comment-{{ $notification->task_comment_id }}"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                    >
                                        View Comment
                                    </a>

                                    @if(!$notification->read)
                                        <button
                                            wire:click="markAsRead({{ $notification->id }})"
                                            class="text-green-600 hover:text-green-800 text-sm"
                                        >
                                            Mark as Read
                                        </button>
                                    @endif

                                    <button
                                        wire:click="deleteNotification({{ $notification->id }})"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                        onclick="return confirm('Are you sure you want to delete this notification?')"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.868 12.683A17.925 17.925 0 0112 21c7.962 0 12-1.21 12-2.683m-12 2.683a17.925 17.925 0 01-7.132-8.317M12 21c4.411 0 8-4.03 8-9s-3.589-9-8-9-8 4.03-8 9a9.06 9.06 0 001.832 5.683L4 21l4.868-8.317z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
                <p class="mt-1 text-sm text-gray-500">You're all caught up! New notifications will appear here.</p>
            </div>
        @endif
    </div>
</div>
