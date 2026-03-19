@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.css">
    @endpush
@endonce

<div class="space-y-4" data-calendar-notification-feed data-check-url="{{ url('/api/calendar-notifications/check') }}">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Calendar</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Google Calendar integration.</p>
        </div>

        <div class="flex items-center gap-2">
            @if ($connected)
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

    <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
        @if (!$connected)
            <div class="text-sm text-slate-500 dark:text-slate-300">
                Connect your Google account to view events.
            </div>
        @else
            <div class="mb-3 flex flex-col gap-1 text-sm text-slate-500 dark:text-slate-300">
                <span>Select a date/time range to create an event. Click an event created here to edit it.</span>
                <span>Invited users receive both Google guest invites and in-app browser notifications.</span>
            </div>
            <div wire:ignore class="min-h-[650px]" data-google-calendar
                data-events-url="{{ route('calendar.google.events') }}"></div>
        @endif
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
                            {{ $editingEventId ? 'Edit event' : 'Add event' }}
                        </h2>
                        <p class="text-sm text-slate-500">Styled after the Google Calendar event editor.</p>
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
                    <input wire:model.defer="title" id="calendar_title" type="text" placeholder="Add title"
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
                        <i class="fa-solid fa-user-group text-base"></i>
                    </div>
                    <div>
                        <label for="calendar_attendees" class="mb-2 block text-sm font-medium text-slate-700">Guests</label>
                        <select wire:model.defer="attendeeUserIds" id="calendar_attendees" multiple
                            class="block min-h-[160px] w-full rounded-2xl border-slate-300 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Hold Ctrl or Command to select multiple people. Google invitations are sent to these email addresses.</p>
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
                        <textarea wire:model.defer="description" id="calendar_description" rows="5" placeholder="Add description"
                            class="block w-full rounded-2xl border-slate-300 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>
            </div>
        </form>
    </x-layouts.modal>
</div>
