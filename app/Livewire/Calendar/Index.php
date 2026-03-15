<?php

namespace App\Livewire\Calendar;

use App\Models\Calendar\CalendarEvent;
use App\Models\GoogleCalendarAccount;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Calendar')]
class Index extends Component
{
    public bool $connected = false;
    public ?string $email = null;

    public ?int $editingEventId = null;

    public string $title = '';
    public ?string $description = null;
    public ?string $location = null;

    /**
     * Datetime-local input format: YYYY-MM-DDTHH:MM
     */
    public string $startsAt = '';

    /**
     * Datetime-local input format: YYYY-MM-DDTHH:MM
     */
    public string $endsAt = '';

    public bool $allDay = false;

    /**
     * @var array<int,int>
     */
    public array $attendeeUserIds = [];

    public function mount(): void
    {
        $this->syncConnectionState();
    }

    public function syncConnectionState(): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->connected = false;
            $this->email = null;
            return;
        }

        /** @var GoogleCalendarAccount|null $account */
        $account = $user->googleCalendarAccount;
        $this->connected = $account !== null;
        $this->email = $account?->email;
    }

    #[On('calendar-range-selected')]
    public function onCalendarRangeSelected(array $payload): void
    {
        if (!$this->connected) {
            session()->flash('error', 'Connect your Google account before creating events.');
            return;
        }

        $start = (string) Arr::get($payload, 'start', '');
        $end = (string) Arr::get($payload, 'end', '');
        $allDay = (bool) Arr::get($payload, 'allDay', false);

        if ($start === '' || $end === '') {
            return;
        }

        $startAt = CarbonImmutable::parse($start)->setTimezone(config('app.timezone'));
        $endAt = CarbonImmutable::parse($end)->setTimezone(config('app.timezone'));

        if ($allDay) {
            // In month view FullCalendar selects [date, date+1) for all-day.
            $startAt = $startAt->startOfDay()->addHours(9);
            $endAt = $startAt->addHour();
        } elseif ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $startAt->addHour();
        }

        $this->resetEventForm();
        $this->editingEventId = null;
        $this->allDay = $allDay;

        $this->startsAt = $startAt->format('Y-m-d\\TH:i');
        $this->endsAt = $endAt->format('Y-m-d\\TH:i');

        $this->dispatch('open-modal', 'calendar-event-modal');
    }

    #[On('calendar-event-clicked')]
    public function onCalendarEventClicked(array $payload): void
    {
        $googleEventId = (string) Arr::get($payload, 'eventId', '');
        if ($googleEventId === '') {
            return;
        }

        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        /** @var CalendarEvent|null $event */
        $event = CalendarEvent::query()
            ->where('created_by_user_id', $userId)
            ->where('google_event_id', $googleEventId)
            ->with('attendees:id')
            ->first();

        if (!$event) {
            // Not an app-owned event; leave it read-only.
            return;
        }

        $this->resetEventForm();
        $this->editingEventId = (int) $event->id;

        $this->title = (string) $event->title;
        $this->description = $event->description;
        $this->location = $event->location;
        $this->allDay = (bool) $event->all_day;

        $tz = config('app.timezone');
        $this->startsAt = CarbonImmutable::parse($event->starts_at)->setTimezone($tz)->format('Y-m-d\\TH:i');
        $this->endsAt = CarbonImmutable::parse($event->ends_at)->setTimezone($tz)->format('Y-m-d\\TH:i');
        $this->attendeeUserIds = $event->attendees->pluck('id')->map(fn($id) => (int) $id)->all();

        $this->dispatch('open-modal', 'calendar-event-modal');
    }

    public function saveEvent(GoogleCalendarService $googleCalendar): void
    {
        $user = Auth::user();
        if (!$user) {
            session()->flash('error', 'Unauthenticated.');
            return;
        }

        if (!$this->connected) {
            session()->flash('error', 'Connect your Google account before creating events.');
            return;
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'startsAt' => ['required', 'date_format:Y-m-d\\TH:i'],
            'endsAt' => ['required', 'date_format:Y-m-d\\TH:i', 'after:startsAt'],
            'attendeeUserIds' => ['array'],
            'attendeeUserIds.*' => ['integer', 'exists:users,id'],
        ]);

        $tz = config('app.timezone');
        $startsAt = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $validated['startsAt'], $tz);
        $endsAt = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $validated['endsAt'], $tz);

        $attendees = User::query()
            ->whereIn('id', $validated['attendeeUserIds'] ?? [])
            ->whereNotNull('email')
            ->get(['id', 'email']);

        $attendeeEmails = $attendees->pluck('email')->filter()->values()->all();

        try {
            if ($this->editingEventId) {
                /** @var CalendarEvent $event */
                $event = CalendarEvent::query()
                    ->where('id', $this->editingEventId)
                    ->where('created_by_user_id', $user->id)
                    ->firstOrFail();

                $event->fill([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'location' => $validated['location'] ?? null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'all_day' => (bool) $this->allDay,
                ]);
                $event->save();
                $event->attendees()->sync($attendees->pluck('id')->all());

                if ($event->google_event_id) {
                    $googleCalendar->updateEvent(
                        $user,
                        (string) ($event->google_calendar_id ?: 'primary'),
                        (string) $event->google_event_id,
                        $event->title,
                        $event->description,
                        $event->location,
                        $startsAt,
                        $endsAt,
                        $attendeeEmails,
                        true
                    );
                }

                session()->flash('success', 'Event updated.');
            } else {
                $googleEvent = $googleCalendar->createEvent(
                    $user,
                    'primary',
                    $validated['title'],
                    $validated['description'] ?? null,
                    $validated['location'] ?? null,
                    $startsAt,
                    $endsAt,
                    $attendeeEmails,
                    true
                );

                if (!$googleEvent) {
                    session()->flash('error', 'Unable to create event in Google Calendar.');
                    return;
                }

                $event = CalendarEvent::query()->create([
                    'created_by_user_id' => $user->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'location' => $validated['location'] ?? null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'all_day' => (bool) $this->allDay,
                    'google_calendar_id' => 'primary',
                    'google_event_id' => (string) $googleEvent->getId(),
                ]);

                $event->attendees()->sync($attendees->pluck('id')->all());

                session()->flash('success', 'Event created and invitations sent.');
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Google Calendar sync failed. If you recently connected, try Disconnect → Connect again to grant permissions.');
            return;
        }

        $this->dispatch('close-modal', 'calendar-event-modal');
        $this->dispatch('calendar-refetch');
    }

    public function deleteEvent(GoogleCalendarService $googleCalendar): void
    {
        $user = Auth::user();
        if (!$user) {
            session()->flash('error', 'Unauthenticated.');
            return;
        }

        if (!$this->editingEventId) {
            return;
        }

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()
            ->where('id', $this->editingEventId)
            ->where('created_by_user_id', $user->id)
            ->firstOrFail();

        try {
            if ($event->google_event_id) {
                $googleCalendar->deleteEvent(
                    $user,
                    (string) ($event->google_calendar_id ?: 'primary'),
                    (string) $event->google_event_id,
                    true
                );
            }
        } catch (\Throwable $e) {
            // Fall through and still delete locally.
        }

        $event->delete();

        session()->flash('success', 'Event deleted.');
        $this->dispatch('close-modal', 'calendar-event-modal');
        $this->dispatch('calendar-refetch');
    }

    public function resetEventForm(): void
    {
        $this->resetErrorBag();

        $this->editingEventId = null;
        $this->title = '';
        $this->description = null;
        $this->location = null;
        $this->startsAt = '';
        $this->endsAt = '';
        $this->allDay = false;
        $this->attendeeUserIds = [];
    }

    public function render()
    {
        $users = User::query()
            ->whereNotNull('email')
            ->when(Auth::id(), fn($q) => $q->where('id', '!=', Auth::id()))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.calendar.index', [
            'users' => $users,
        ]);
    }
}
