<?php

namespace App\Livewire\Calendar;

use App\Models\Calendar\CalendarEvent;
use App\Models\CalendarNotification;
use App\Models\GoogleCalendarAccount;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Collection;
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
     * Uses either YYYY-MM-DD or YYYY-MM-DDTHH:MM depending on allDay.
     */
    public string $startsAt = '';

    /**
     * Uses either YYYY-MM-DD or YYYY-MM-DDTHH:MM depending on allDay.
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

        $this->resetEventForm();
        $this->editingEventId = null;
        $this->allDay = $allDay;

        if ($allDay) {
            $this->startsAt = $startAt->format('Y-m-d');
            $this->endsAt = $startAt->format('Y-m-d');
        } else {
            if ($endAt->lessThanOrEqualTo($startAt)) {
                $endAt = $startAt->addHour();
            }

            $this->startsAt = $startAt->format('Y-m-d\\TH:i');
            $this->endsAt = $endAt->format('Y-m-d\\TH:i');
        }

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
            return;
        }

        $this->resetEventForm();
        $this->editingEventId = (int) $event->id;
        $this->title = (string) $event->title;
        $this->description = $event->description;
        $this->location = $event->location;
        $this->allDay = (bool) $event->all_day;

        $tz = config('app.timezone');
        $start = CarbonImmutable::parse($event->starts_at)->setTimezone($tz);
        $end = CarbonImmutable::parse($event->ends_at)->setTimezone($tz);

        $this->startsAt = $this->allDay ? $start->format('Y-m-d') : $start->format('Y-m-d\\TH:i');
        $this->endsAt = $this->allDay ? $end->format('Y-m-d') : $end->format('Y-m-d\\TH:i');
        $this->attendeeUserIds = $event->attendees->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->dispatch('open-modal', 'calendar-event-modal');
    }

    public function updatedAllDay(bool $value): void
    {
        if ($this->startsAt === '' || $this->endsAt === '') {
            return;
        }

        try {
            $tz = config('app.timezone');
            $start = CarbonImmutable::parse($this->startsAt, $tz);
            $end = CarbonImmutable::parse($this->endsAt, $tz);

            if ($value) {
                $this->startsAt = $start->format('Y-m-d');
                $this->endsAt = $end->format('Y-m-d');
                return;
            }

            $this->startsAt = $start->startOfDay()->addHours(9)->format('Y-m-d\\TH:i');
            $this->endsAt = $end->startOfDay()->addHours(10)->format('Y-m-d\\TH:i');
        } catch (\Throwable $e) {
            // Leave existing values as-is if conversion fails.
        }
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

        $validated = $this->validate($this->rules());
        [$startsAt, $endsAt] = $this->resolveEventRange(
            (string) $validated['startsAt'],
            (string) $validated['endsAt']
        );

        $attendees = User::query()
            ->whereIn('id', $validated['attendeeUserIds'] ?? [])
            ->whereNotNull('email')
            ->get(['id', 'name', 'email']);

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

                $this->createCalendarNotifications($event, $attendees, true);
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
                $this->createCalendarNotifications($event, $attendees, false);

                session()->flash('success', 'Event created and invitations sent.');
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Google Calendar sync failed. If you recently connected, try Disconnect -> Connect again to grant permissions.');
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
            // Delete locally even if Google deletion fails.
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
            ->when(Auth::id(), fn ($q) => $q->where('id', '!=', Auth::id()))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.calendar.index', [
            'users' => $users,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'startsAt' => ['required', 'date'],
            'endsAt' => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    try {
                        [$startsAt, $endsAt] = $this->resolveEventRange(
                            (string) $this->startsAt,
                            (string) $this->endsAt
                        );

                        if ((!$this->allDay && $endsAt->lessThanOrEqualTo($startsAt))
                            || ($this->allDay && $endsAt->lessThan($startsAt))) {
                            $fail('The end must be after the start.');
                        }
                    } catch (\Throwable $e) {
                        $fail('Invalid date range.');
                    }
                },
            ],
            'attendeeUserIds' => ['array'],
            'attendeeUserIds.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveEventRange(string $startValue, string $endValue): array
    {
        $tz = config('app.timezone');

        if ($this->allDay) {
            return [
                CarbonImmutable::parse($startValue, $tz)->startOfDay(),
                CarbonImmutable::parse($endValue, $tz)->endOfDay(),
            ];
        }

        return [
            CarbonImmutable::parse($startValue, $tz),
            CarbonImmutable::parse($endValue, $tz),
        ];
    }

    protected function createCalendarNotifications(CalendarEvent $event, Collection $attendees, bool $isUpdate): void
    {
        $actor = Auth::user();
        if (!$actor) {
            return;
        }

        $dateLabel = $event->all_day
            ? CarbonImmutable::parse($event->starts_at)->format('M j, Y')
            : CarbonImmutable::parse($event->starts_at)->format('M j, Y g:i A');

        foreach ($attendees as $attendee) {
            if ((int) $attendee->id === (int) $actor->id) {
                continue;
            }

            CalendarNotification::query()->create([
                'calendar_event_id' => $event->id,
                'user_id' => $attendee->id,
                'actor_user_id' => $actor->id,
                'type' => $isUpdate ? 'update' : 'invite',
                'title' => $isUpdate ? 'Calendar event updated' : 'New calendar invite',
                'message' => $isUpdate
                    ? "{$actor->name} updated \"{$event->title}\" for {$dateLabel}."
                    : "{$actor->name} invited you to \"{$event->title}\" on {$dateLabel}.",
                'data' => [
                    'event_id' => $event->id,
                    'google_event_id' => $event->google_event_id,
                    'title' => $event->title,
                    'starts_at' => $event->starts_at?->toISOString(),
                    'all_day' => (bool) $event->all_day,
                ],
            ]);
        }
    }
}
