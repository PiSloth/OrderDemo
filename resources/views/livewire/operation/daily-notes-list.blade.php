<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">


    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Operations</p>
                    <span
                        class="rounded-full bg-amber-300 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-900">
                        {{ $isSelectedToday ? 'Today' : 'Backdate' }}
                    </span>
                </div>
                <h1 class="mt-2 text-3xl font-semibold">Daily operation notes</h1>
                <p class="mt-3 max-w-3xl text-sm text-slate-200">
                    {{ $userLocationName }} team notes for {{ $todayLabel }}. Open a topic, add your note, and chat in
                    real-time.
                </p>
            </div>
            <div class="rounded-2xl border border-slate-700 bg-slate-800 px-4 py-3 text-sm text-slate-200">
                Scope: {{ auth()->user()->department?->name ?? 'No department' }} /
                {{ auth()->user()->branch?->name ?? 'No branch' }}
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="$set('activeTab', 'opened')"
                class="inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium {{ $activeTab === 'opened' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
                <span>Opened notes</span>
                <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ $openedBadgeCount }}</span>
            </button>

            <button type="button" wire:click="$set('activeTab', 'finished')"
                class="inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium {{ $activeTab === 'finished' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
                <span>Finished notes</span>
                <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ $finishedNotes->count() }}</span>
            </button>

            @if ($showRecentTab)
                <button type="button" wire:click="$set('activeTab', 'recent')"
                    class="inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium {{ $activeTab === 'recent' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">
                    <span>Updated within 1 hour</span>
                    <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ $recentNotes->count() }}</span>
                </button>
            @endif
            </div>

            <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-100 p-1">
                <button type="button" wire:click="$set('viewMode', 'card')"
                    class="rounded-xl px-3 py-2 text-sm font-medium {{ $viewMode === 'card' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600' }}">
                    Card view
                </button>
                <button type="button" wire:click="$set('viewMode', 'table')"
                    class="rounded-xl px-3 py-2 text-sm font-medium {{ $viewMode === 'table' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600' }}">
                    Table view
                </button>
            </div>
        </div>
    </section>

    <div class="w-full">
        <div class="grid gap-3 md:grid-cols-2">
            <input type="search" wire:model.live="search" placeholder="Search notes..."
                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-500 focus:ring-slate-500">
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="selectedDate" max="{{ now()->toDateString() }}"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-500 focus:ring-slate-500">
                <button type="button" wire:click="$set('selectedDate', '{{ now()->toDateString() }}')"
                    class="rounded-2xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                    Today
                </button>
            </div>
        </div>
        @error('selectedDate')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($viewMode === 'card' && $activeTab === 'opened')
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($openedCards as $card)
                <button type="button" wire:click="openTitle({{ $card['title']->id }})"
                    class="rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Topic</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ $card['title']->name }}</h2>
                        </div>
                        @if ($card['has_no_messages'])
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                No chat yet
                            </span>
                        @elseif ($card['unread_message_count'] > 0)
                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700">
                                {{ $card['unread_message_count'] }} unread
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p>{{ $card['note']?->note ?: 'Tap to create or continue today\'s note.' }}</p>
                        <div class="flex items-center justify-between gap-3">
                            <span>{{ $card['message_count'] }} messages</span>
                            <span>{{ $card['note'] ? 'Opened' : 'Ready to start' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 pt-1">
                            <span class="text-xs text-slate-500">Noted by today</span>
                            <div class="flex -space-x-2">
                                @forelse ($card['noted_users'] as $notedUser)
                                    <img src="{{ $notedUser['photo'] }}" alt="{{ $notedUser['name'] }}"
                                        title="{{ $notedUser['name'] }}"
                                        class="h-7 w-7 rounded-full border-2 border-white object-cover">
                                @empty
                                    <span class="text-xs text-slate-500">No one yet</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </button>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                    No open titles for today.
                </div>
            @endforelse
        </section>
    @endif

    @if ($viewMode === 'card' && $activeTab === 'finished')
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($finishedNotes as $noteItem)
                @php
                    $notedUsers = collect([$noteItem->creator])
                        ->merge($noteItem->messages->pluck('user'))
                        ->filter()
                        ->unique('id')
                        ->take(5)
                        ->values();
                @endphp
                <button type="button" wire:click="openTitle({{ $noteItem->title_id }})"
                    class="rounded-3xl border border-emerald-200 bg-white p-5 text-left shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-emerald-600">Finished</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ $noteItem->title->name }}</h2>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                            {{ $noteItem->messages_count }} messages
                        </span>
                    </div>

                    <p class="mt-4 text-sm text-slate-600">{{ $noteItem->note ?: 'No summary saved.' }}</p>
                    <p class="mt-3 text-xs text-slate-500">
                        Completed {{ optional($noteItem->completed_at)->diffForHumans() }}
                    </p>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-500">Noted by today</span>
                        <div class="flex -space-x-2">
                            @forelse ($notedUsers as $notedUser)
                                <img src="{{ $notedUser->profile_photo_url }}" alt="{{ $notedUser->name }}"
                                    title="{{ $notedUser->name }}"
                                    class="h-7 w-7 rounded-full border-2 border-white object-cover">
                            @empty
                                <span class="text-xs text-slate-500">No one yet</span>
                            @endforelse
                        </div>
                    </div>
                </button>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                    No finished notes yet.
                </div>
            @endforelse
        </section>
    @endif

    @if ($viewMode === 'card' && $activeTab === 'recent' && $showRecentTab)
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($recentNotes as $noteItem)
                @php
                    $notedUsers = collect([$noteItem->creator])
                        ->merge($noteItem->messages->pluck('user'))
                        ->filter()
                        ->unique('id')
                        ->take(5)
                        ->values();
                @endphp
                <button type="button" wire:click="openTitle({{ $noteItem->title_id }})"
                    class="rounded-3xl border border-sky-200 bg-white p-5 text-left shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-sky-600">Recent update</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ $noteItem->title->name }}</h2>
                        </div>
                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-700">
                            {{ $noteItem->updated_at->diffForHumans() }}
                        </span>
                    </div>
                    <p class="mt-4 text-sm text-slate-600">{{ $noteItem->note ?: 'Chat updated recently.' }}</p>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-500">Noted by today</span>
                        <div class="flex -space-x-2">
                            @forelse ($notedUsers as $notedUser)
                                <img src="{{ $notedUser->profile_photo_url }}" alt="{{ $notedUser->name }}"
                                    title="{{ $notedUser->name }}"
                                    class="h-7 w-7 rounded-full border-2 border-white object-cover">
                            @empty
                                <span class="text-xs text-slate-500">No one yet</span>
                            @endforelse
                        </div>
                    </div>
                </button>
            @endforeach
        </section>
    @endif

    @if ($viewMode === 'table')
        <section class="space-y-4">
            @forelse ($tableGroups as $group)
                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $group['title']->name }}</h3>
                                <p class="text-sm text-slate-600">Remark: {{ $group['remark'] ?: '-' }}</p>
                            </div>
                            <button type="button" wire:click="acknowledgeTitle({{ $group['title_id'] }})"
                                @disabled(!$group['has_unacknowledged'])
                                class="inline-flex items-center rounded-2xl border px-3 py-2 text-xs font-medium transition {{ $group['has_unacknowledged'] ? 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' : 'cursor-not-allowed border-slate-200 text-slate-400' }}">
                                {{ $group['has_unacknowledged'] ? 'Acknowledge' : 'Acknowledged' }}
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">Note</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">Created By</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">Branch</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($group['rows'] as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <p>{{ $row['note'] ?: '-' }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Checked by:
                                                {{ $row['ack_users']->isNotEmpty() ? $row['ack_users']->join(', ') : 'No one yet' }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <div class="flex items-center gap-2">
                                                @if ($row['created_by_photo'])
                                                    <img src="{{ $row['created_by_photo'] }}" alt="{{ $row['created_by'] ?: 'User' }}"
                                                        class="h-7 w-7 rounded-full border border-slate-200 object-cover">
                                                @endif
                                                <span>{{ $row['created_by'] ?: '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ $row['branch_name'] ?: '-' }}</td>
                                        <td class="px-4 py-3">
                                            <button type="button" wire:click="openMessageModal({{ $row['title_id'] }})"
                                                class="inline-flex items-center rounded-2xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                                Message
                                                @if (($row['unread_message_count'] ?? 0) > 0)
                                                    <span class="ml-2 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700">
                                                        {{ $row['unread_message_count'] }}
                                                    </span>
                                                @endif
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                    No notes available for this tab.
                </div>
            @endforelse
        </section>
    @endif

    @if ($showNoteModal && $activeNote)
        <div class="fixed inset-0 z-40 overflow-y-auto bg-slate-950/60 p-4">
            <div class="mx-auto max-w-5xl rounded-[2rem] bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Daily note</p>
                            <h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ $activeNote->title->name }}</h2>
                            <p class="mt-2 text-sm text-slate-600">
                                {{ $activeNote->branch->name }} / {{ $activeNote->department->name }} /
                                {{ $activeNote->location->name }}
                                on {{ $activeNote->date->format('Y-m-d') }}
                            </p>
                        </div>

                        <button type="button" wire:click="closeModal"
                            class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                            Close
                        </button>
                    </div>
                </div>

                <div class="grid gap-6 px-5 py-5 sm:px-6 lg:grid-cols-[320px,minmax(0,1fr)]">
                    <aside class="space-y-4">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex justify-between h-6">
                                <label class="text-sm font-medium text-slate-700">Summary note</label>
                                <x-button rounded teal wire:click="editNote" icon="pencil" />
                            </div>
                            <x-textarea type="text" wire:model="note"
                                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700"
                                placeholder="Short summary for today" />
                            @error('note')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                            <div>
                                <small class="text-xs text-slate-500">
                                    Created: {{ $created_date ? $created_date->format('M-d H') : 'Not set' }}
                                    | Updated: {{ $updated_date ? $updated_date->format('M-d H') : 'Not set' }}
                                </small>
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">Quick insert</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button type="button" wire:click="openQuickInput('number')"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m-7 5h8m-9 5h10M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z" />
                                        </svg>
                                        Calculator
                                    </button>
                                    <button type="button" wire:click="openQuickInput('datetime')"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10m-13 9h16a1 1 0 001-1V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a1 1 0 001 1z" />
                                        </svg>
                                        Date & Time
                                    </button>
                                </div>

                                @if ($quickInputMode === 'number')
                                    <div class="mt-3 flex items-center gap-2">
                                        <input type="number" wire:model.defer="quickNumber" step="any"
                                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700"
                                            placeholder="Enter number">
                                        <button type="button" wire:click="appendQuickNumber"
                                            class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white">
                                            Add
                                        </button>
                                    </div>
                                    @error('quickNumber')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                @endif

                                @if ($quickInputMode === 'datetime')
                                    <div class="mt-3 flex items-center gap-2">
                                        <input type="datetime-local" wire:model.defer="quickDateTime"
                                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700">
                                        <button type="button" wire:click="appendQuickDateTime"
                                            class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white">
                                            Add
                                        </button>
                                    </div>
                                    @error('quickDateTime')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            @if (!$note || $edit_mode)
                                <div class="flex justify-between gap-3">
                                    <button type="button" wire:click="saveNote"
                                        class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">
                                        Save
                                    </button>
                                    <button type="button" wire:click="saveAndNext"
                                        class="inline-flex items-center justify-center rounded-2xl border bg-green-700  px-4 py-3 text-sm font-medium text-white">
                                        Save & Next
                                    </button>
                                </div>
                            @endif
                            {{-- @if (!$activeNote->completed_at)
                                <button type="button" wire:click="markFinished"
                                    class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-700">
                                    Mark finished
                                </button>
                            @endif --}}
                        </div>
                    </aside>

                    <div class="min-h-[32rem]">
                        <livewire:operation.note-chat :note-id="$activeNoteId" :key="'operation-note-chat-' . $activeNoteId" />
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showMessageModal && $messageNoteId)
        <div class="fixed inset-0 z-40 overflow-y-auto bg-slate-950/60 p-4">
            <div class="mx-auto max-w-4xl rounded-[2rem] bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Message</p>
                            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Add note message</h2>
                        </div>
                        <button type="button" wire:click="closeMessageModal"
                            class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                            Close
                        </button>
                    </div>
                </div>

                <div class="px-5 py-5 sm:px-6">
                    <div class="min-h-[28rem]">
                        <livewire:operation.note-chat :note-id="$messageNoteId" :key="'operation-note-chat-message-' . $messageNoteId" />
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
