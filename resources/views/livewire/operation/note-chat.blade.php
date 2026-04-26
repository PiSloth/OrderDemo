<div
    wire:poll.15s="loadMessages"
    x-data="{
        channel: null,
        init() {
            const scrollToBottom = () => {
                const box = this.$refs.messages;
                if (box) {
                    box.scrollTop = box.scrollHeight;
                }
            };

            const subscribe = () => {
                if (!window.Echo) {
                    return;
                }

                this.channel = window.Echo.private(`note.{{ $noteId }}`)
                    .listen('.NoteMessageSent', (event) => {
                        $wire.handleBroadcast(event);
                    });
            };

            subscribe();
            setTimeout(scrollToBottom, 100);
            window.addEventListener('note-chat-scroll', scrollToBottom);
        }
    }"
    class="flex h-full flex-col rounded-3xl border border-slate-200 bg-white"
>
    <div class="border-b border-slate-200 px-4 py-4">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-lg font-semibold text-slate-900">{{ $dailyNote->title->name }}</h3>
            @if ($dailyNote->date->isToday())
                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">Today</span>
            @endif
            @if ($dailyNote->completed_at)
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">Finished</span>
            @endif
        </div>
        <p class="mt-2 text-sm text-slate-500">
            {{ $dailyNote->location->name }} / {{ $dailyNote->department->name }} / {{ $dailyNote->branch->name }}
        </p>
    </div>

    <div x-ref="messages" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-4 py-4">
        @forelse ($messages as $chatMessage)
            <div class="flex {{ $chatMessage['is_mine'] ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[85%] rounded-3xl px-4 py-3 shadow-sm {{ $chatMessage['is_mine'] ? 'bg-slate-900 text-white' : 'bg-white text-slate-900' }}">
                    <div class="mb-2 flex items-center gap-2 text-xs {{ $chatMessage['is_mine'] ? 'text-slate-300' : 'text-slate-500' }}">
                        <span class="font-semibold">{{ $chatMessage['user_name'] }}</span>
                        <span>{{ $chatMessage['time_label'] }}</span>
                    </div>

                    @if ($chatMessage['message'])
                        <p class="whitespace-pre-wrap text-sm">{{ $chatMessage['message'] }}</p>
                    @endif

                    @if ($chatMessage['image'])
                        <a href="{{ $chatMessage['image'] }}" target="_blank" class="mt-3 block">
                            <img src="{{ $chatMessage['image'] }}" alt="Note image"
                                class="max-h-72 w-full rounded-2xl object-cover">
                        </a>
                    @endif

                    @if ($chatMessage['is_mine'] && !empty($chatMessage['seen_by']))
                        <p class="mt-2 text-xs {{ $chatMessage['is_mine'] ? 'text-slate-300' : 'text-slate-500' }}">
                            Seen by {{ implode(', ', $chatMessage['seen_by']) }}
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                No messages yet. Start the conversation for this note.
            </div>
        @endforelse
    </div>

    <div class="border-t border-slate-200 bg-white px-4 py-4">
        @if ($dailyNote->completed_at)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                This note is finished. Chat is now read-only.
            </div>
        @else
            <div class="space-y-3">
                <div class="flex flex-wrap gap-3">
                    <input id="note-camera-photo-{{ $noteId }}" type="file" wire:model="cameraPhoto" accept="image/*" capture="environment" class="hidden">
                    <input id="note-gallery-photo-{{ $noteId }}" type="file" wire:model="galleryPhotos" accept="image/*" multiple class="hidden">

                    <label for="note-camera-photo-{{ $noteId }}"
                        class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">
                        Use camera
                    </label>

                    <label for="note-gallery-photo-{{ $noteId }}"
                        class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700">
                        Upload images
                    </label>
                </div>

                @if ($queuedImages !== [])
                    <div class="flex gap-3 overflow-x-auto pb-1">
                        @foreach ($queuedImages as $index => $queuedImage)
                            <div class="relative shrink-0">
                                @if (method_exists($queuedImage, 'temporaryUrl'))
                                    <img src="{{ $queuedImage->temporaryUrl() }}" alt="Queued note image"
                                        class="h-24 w-24 rounded-2xl object-cover">
                                @endif
                                <button type="button" wire:click="removeQueuedImage({{ $index }})"
                                    class="absolute -right-2 -top-2 rounded-full bg-rose-600 px-2 py-1 text-xs font-medium text-white">
                                    x
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-end gap-3">
                    <textarea wire:model.defer="message" rows="2"
                        class="block min-h-[56px] flex-1 rounded-3xl border border-slate-300 px-4 py-3 text-sm text-slate-700"
                        placeholder="Write a message..."></textarea>
                    <button type="button" wire:click="sendMessage"
                        class="inline-flex h-14 items-center justify-center rounded-3xl bg-slate-900 px-5 text-sm font-medium text-white">
                        Send
                    </button>
                </div>

                @error('message')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
                @error('queuedImages.*')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>
</div>
