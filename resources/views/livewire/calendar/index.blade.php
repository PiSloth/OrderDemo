@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.css">
    @endpush
@endonce

<div class="space-y-6" data-calendar-notification-feed data-check-url="{{ url('/api/calendar-notifications/check') }}">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Meeting Calendar</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">
                Create meetings here and push them into every invited user Google Calendar.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($connected)
                <button type="button" wire:click="openCreateEventModal"
                    class="inline-flex items-center rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                    Add meeting
                </button>
                <div class="text-sm text-slate-600 dark:text-slate-200">
                    Connected{{ $email ? ' as ' . $email : '' }}
                </div>
                <button type="button" data-enable-calendar-notifications
                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-100">
                    Enable notifications
                </button>
                <form method="POST" action="{{ route('calendar.google.disconnect') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        Disconnect
                    </button>
                </form>
            @else
                <a href="{{ route('calendar.google.connect') }}"
                    class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                    Connect Google Calendar
                </a>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1.6fr,1fr]">
        <div class="rounded-md border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
            @if (!$connected)
                <div class="space-y-3 text-sm text-slate-500 dark:text-slate-300">
                    <p>Connect your Google account to create meetings from this page.</p>
                    <p>Only users with Google calendar access are shown in the invitee list.</p>
                </div>
            @else
                <div class="mb-3 flex flex-col gap-1 text-sm text-slate-500 dark:text-slate-300">
                    <span>Select a range on the calendar or use the Add meeting button.</span>
                    <span>Invited users receive a local notification and get the meeting inserted into their own Google Calendars.</span>
                </div>
                <div wire:ignore class="min-h-[680px]" data-google-calendar
                    data-events-url="{{ route('calendar.google.events') }}"></div>
            @endif
        </div>

        <div class="rounded-md border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Today Meetings</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-300">Sorted by start time.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                    {{ $todayMeetings->count() }} items
                </span>
            </div>

            <div class="space-y-3">
                @forelse ($todayMeetings as $meeting)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">
                                        {{ $meeting->all_day ? 'All day' : $meeting->starts_at->timezone(config('app.timezone'))->format('g:i A') . ' - ' . $meeting->ends_at->timezone(config('app.timezone'))->format('g:i A') }}
                                    </span>
                                    @if ($meeting->reminder_minutes !== null)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Reminder {{ $meeting->reminder_minutes }} min before
                                        </span>
                                    @endif
                                </div>

                                <h3 class="mt-3 text-base font-semibold text-slate-900">
                                    {{ $meeting->title }}
                                </h3>

                                @if ($meeting->description)
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        {{ $meeting->description }}
                                    </p>
                                @endif

                                <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500">
                                    <span>Host: {{ $meeting->createdBy?->name ?? 'Unknown' }}</span>
                                    @if ($meeting->location)
                                        <span>Location: {{ $meeting->location }}</span>
                                    @endif
                                </div>

                                @if ($meeting->attendees->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($meeting->attendees as $attendee)
                                            <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">
                                                {{ $attendee->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if ((int) $meeting->created_by_user_id === (int) auth()->id())
                                <button type="button" wire:click="openOwnedEventModal({{ $meeting->id }})"
                                    class="inline-flex items-center rounded-full border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Edit
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">
                        No meetings scheduled for today.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <x-layouts.modal name="calendar-event-modal" maxWidth="2xl" focusable>
        <form wire:submit.prevent="saveEvent" class="overflow-hidden rounded-2xl bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div class="flex items-center gap-3">
                    <button type="button" x-on:click="$dispatch('close')"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-900">
                        <span class="text-xl leading-none">&times;</span>
                    </button>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            {{ $editingEventId ? 'Edit meeting' : 'Add meeting' }}
                        </h2>
                        <p class="text-sm text-slate-500">Google-style meeting form with multi-user calendar push.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if ($editingEventId)
                        <button type="button" wire:click="deleteEvent"
                            class="inline-flex items-center rounded-full border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                            Delete
                        </button>
                    @endif
                    <button type="submit"
                        class="inline-flex items-center rounded-full bg-sky-600 px-5 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                        Save
                    </button>
                </div>
            </div>

            <div class="space-y-6 px-6 py-6">
                <div class="pl-12">
                    <input wire:model.defer="title" id="calendar_title" type="text" placeholder="Add meeting title"
                        class="w-full border-0 border-b border-slate-300 px-0 pb-3 text-3xl font-normal text-slate-900 placeholder:text-slate-400 focus:border-sky-500 focus:ring-0" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div class="grid gap-5 md:grid-cols-[32px,1fr] md:items-start">
                    <div class="pt-3 text-center text-slate-400">
                        <i class="fa-regular fa-clock text-base"></i>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-4 flex flex-wrap items-center gap-4">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input wire:model.live="allDay" type="checkbox"
                                    class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                All day
                            </label>
                            <span class="text-sm text-slate-500">{{ config('app.timezone') }}</span>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="calendar_starts_at" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Start
                                </label>
                                <input wire:model.defer="startsAt" id="calendar_starts_at"
                                    type="{{ $allDay ? 'date' : 'datetime-local' }}"
                                    class="block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                                <x-input-error :messages="$errors->get('startsAt')" class="mt-2" />
                            </div>
                            <div>
                                <label for="calendar_ends_at" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    End
                                </label>
                                <input wire:model.defer="endsAt" id="calendar_ends_at"
                                    type="{{ $allDay ? 'date' : 'datetime-local' }}"
                                    class="block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                                <x-input-error :messages="$errors->get('endsAt')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-[32px,1fr] md:items-start">
                    <div class="pt-3 text-center text-slate-400">
                        <i class="fa-solid fa-bell text-base"></i>
                    </div>
                    <div>
                        <label for="calendar_reminder" class="mb-2 block text-sm font-medium text-slate-700">
                            Reminder time
                        </label>
                        <div class="flex items-center gap-3">
                            <input wire:model.defer="reminderMinutes" id="calendar_reminder" type="number" min="0" max="40320"
                                class="block w-40 rounded-xl border-slate-300 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                            <span class="text-sm text-slate-500">minutes before the meeting</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">This reminder is pushed into the creator and invited user Google Calendars.</p>
                        <x-input-error :messages="$errors->get('reminderMinutes')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-[32px,1fr] md:items-start">
                    <div class="pt-3 text-center text-slate-400">
                        <i class="fa-solid fa-user-group text-base"></i>
                    </div>
                    <div>
                        <label for="calendar_attendees" class="mb-2 block text-sm font-medium text-slate-700">Invite people with Google calendar access</label>
                        <select wire:model.defer="attendeeUserIds" id="calendar_attendees" multiple
                            class="block min-h-[180px] w-full rounded-2xl border-slate-300 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Only users with Google token access are shown. Hold Ctrl or Command to select multiple.</p>
                        <x-input-error :messages="$errors->get('attendeeUserIds')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-[32px,1fr] md:items-start">
                    <div class="pt-3 text-center text-slate-400">
                        <i class="fa-solid fa-location-dot text-base"></i>
                    </div>
                    <div>
                        <input wire:model.defer="location" id="calendar_location" type="text" placeholder="Add location"
                            class="block w-full rounded-2xl border-slate-300 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                        <x-input-error :messages="$errors->get('location')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-[32px,1fr] md:items-start">
                    <div class="pt-3 text-center text-slate-400">
                        <i class="fa-regular fa-note-sticky text-base"></i>
                    </div>
                    <div>
                        <textarea wire:model.defer="description" id="calendar_description" rows="5" placeholder="Add meeting details"
                            class="block w-full rounded-2xl border-slate-300 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>
            </div>
        </form>
    </x-layouts.modal>
</div>
