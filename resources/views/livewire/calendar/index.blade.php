@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.css">
    @endpush
@endonce

<div class="space-y-4">
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
            <div class="mb-3 text-sm text-slate-500 dark:text-slate-300">
                Select a date/time range to create an event. Click an event created here to edit it.
            </div>
            <div wire:ignore class="min-h-[650px]" data-google-calendar
                data-events-url="{{ route('calendar.google.events') }}"></div>
        @endif
    </div>

    <x-layouts.modal name="calendar-event-modal" maxWidth="2xl" focusable>
        <form wire:submit.prevent="saveEvent" class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ $editingEventId ? 'Edit Event' : 'Create Event' }}
                </h2>
                @if ($editingEventId)
                    <x-danger-button type="button" wire:click="deleteEvent">
                        Delete
                    </x-danger-button>
                @endif
            </div>

            <div>
                <x-input-label for="calendar_title" value="Title" />
                <x-text-input wire:model.defer="title" id="calendar_title" type="text" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="calendar_starts_at" value="Starts" />
                    <x-text-input wire:model.defer="startsAt" id="calendar_starts_at" type="datetime-local"
                        class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('startsAt')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="calendar_ends_at" value="Ends" />
                    <x-text-input wire:model.defer="endsAt" id="calendar_ends_at" type="datetime-local"
                        class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('endsAt')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="calendar_location" value="Location (optional)" />
                <x-text-input wire:model.defer="location" id="calendar_location" type="text"
                    class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('location')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="calendar_description" value="Description (optional)" />
                <textarea wire:model.defer="description" id="calendar_description" rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="calendar_attendees" value="Invite People" />
                <select wire:model.defer="attendeeUserIds" id="calendar_attendees" multiple
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hold Ctrl/⌘ to select multiple.</div>
                <x-input-error :messages="$errors->get('attendeeUserIds')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>
                <x-primary-button>
                    Save
                </x-primary-button>
            </div>
        </form>
    </x-layouts.modal>
</div>
