<?php

namespace App\Livewire\Calendar;

use App\Models\Calendar\CalendarEvent;
use App\Models\Calendar\CalendarEventGoogleCopy;
use App\Models\CalendarNotification;
use App\Models\GoogleCalendarAccount;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
    public ?int $reminderMinutes = 30;
    public string $inviteeSearch = '';

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
        $this->connected = !empty($user->google_refresh_token) || !empty($user->google_token);
        $this->email = $account?->email ?: $user->email;
    }

    #[On('calendar-range-selected')]
    public function onCalendarRangeSelected(string $start = '', string $end = '', bool $allDay = false): void
    {
        if (!$this->connected) {
            session()->flash('error', 'Connect your Google account before creating meetings.');
            return;
        }

        if ($start === '' || $end === '') {
            return;
        }

        $startAt = CarbonImmutable::parse($start)->setTimezone(config('app.timezone'));
        $endAt = CarbonImmutable::parse($end)->setTimezone(config('app.timezone'));

        $this->resetEventForm();
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
    public function onCalendarEventClicked(string $eventId = ''): void
    {
        $googleEventId = $eventId;
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

        $this->fillFormFromEvent($event);
        $this->dispatch('open-modal', 'calendar-event-modal');
    }

    public function openOwnedEventModal(int $eventId): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        /** @var CalendarEvent|null $event */
        $event = CalendarEvent::query()
            ->where('id', $eventId)
            ->where('created_by_user_id', $userId)
            ->with('attendees:id')
            ->first();

        if (!$event) {
            return;
        }

        $this->fillFormFromEvent($event);
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
            // Keep current values if conversion fails.
        }
    }

    public function openCreateEventModal(): void
    {
        if (!$this->connected) {
            session()->flash('error', 'Connect your Google account before creating meetings.');
            return;
        }

        $this->resetEventForm();

        $start = now(config('app.timezone'))->addHour()->startOfHour();
        $end = $start->addHour();

        $this->startsAt = $start->format('Y-m-d\\TH:i');
        $this->endsAt = $end->format('Y-m-d\\TH:i');

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
            session()->flash('error', 'Connect your Google account before creating meetings.');
            return;
        }

        $validated = $this->validate($this->rules());
        [$startsAt, $endsAt] = $this->resolveEventRange(
            (string) $validated['startsAt'],
            (string) $validated['endsAt']
        );

        $attendees = $this->eligibleInviteeQuery()
            ->whereIn('id', $validated['attendeeUserIds'] ?? [])
            ->get(['id', 'name', 'email', 'google_token', 'google_refresh_token', 'google_token_expires_at']);

        $syncStep = 'start';

        try {
            if ($this->editingEventId) {
                $syncStep = 'load_local_event';
                /** @var CalendarEvent $event */
                $event = CalendarEvent::query()
                    ->where('id', $this->editingEventId)
                    ->where('created_by_user_id', $user->id)
                    ->firstOrFail();

                $syncStep = 'save_local_event';
                $event->fill([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'location' => $validated['location'] ?? null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'all_day' => (bool) $this->allDay,
                    'reminder_minutes' => $validated['reminderMinutes'] ?? null,
                ]);
                $event->save();
                $event->attendees()->sync($attendees->pluck('id')->all());

                if ($event->google_event_id) {
                    $syncStep = 'update_creator_google_event';
                    $googleCalendar->updateEvent(
                        $user,
                        (string) ($event->google_calendar_id ?: 'primary'),
                        (string) $event->google_event_id,
                        $event->title,
                        $event->description,
                        $event->location,
                        $startsAt,
                        $endsAt,
                        [],
                        false,
                        (bool) $event->all_day,
                        $event->reminder_minutes
                    );
                }

                $syncStep = 'sync_invited_user_google_events';
                $this->syncInvitedUserCalendars($event, $attendees, $googleCalendar);
                $syncStep = 'create_local_notifications';
                $this->createCalendarNotifications($event, $attendees, true);

                session()->flash('success', 'Meeting updated for all invited users.');
            } else {
                $syncStep = 'create_creator_google_event';
                $googleEvent = $googleCalendar->createEvent(
                    $user,
                    'primary',
                    $validated['title'],
                    $validated['description'] ?? null,
                    $validated['location'] ?? null,
                    $startsAt,
                    $endsAt,
                    [],
                    false,
                    (bool) $this->allDay,
                    $validated['reminderMinutes'] ?? null
                );

                if (!$googleEvent) {
                    $reference = $this->logMeetingSyncFailure(
                        new \RuntimeException('Google calendar service returned no event for the creator.'),
                        $user,
                        $attendees,
                        $syncStep
                    );
                    session()->flash('error', "Meeting sync failed [{$reference}] at {$syncStep}: creator calendar service returned no event.");
                    return;
                }

                $syncStep = 'create_local_event';
                $event = CalendarEvent::query()->create([
                    'created_by_user_id' => $user->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'location' => $validated['location'] ?? null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'all_day' => (bool) $this->allDay,
                    'reminder_minutes' => $validated['reminderMinutes'] ?? null,
                    'google_calendar_id' => 'primary',
                    'google_event_id' => (string) $googleEvent->getId(),
                ]);

                $event->attendees()->sync($attendees->pluck('id')->all());
                $syncStep = 'sync_invited_user_google_events';
                $this->syncInvitedUserCalendars($event, $attendees, $googleCalendar);
                $syncStep = 'create_local_notifications';
                $this->createCalendarNotifications($event, $attendees, false);

                session()->flash('success', 'Meeting created and pushed to invited users.');
            }
        } catch (\Throwable $e) {
            $reference = $this->logMeetingSyncFailure($e, $user, $attendees, $syncStep);
            $message = Str::limit(trim(preg_replace('/\s+/', ' ', $e->getMessage())), 220);
            session()->flash('error', "Meeting sync failed [{$reference}] at {$syncStep}: {$message}");
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
            ->with('googleCopies.user')
            ->firstOrFail();

        try {
            if ($event->google_event_id) {
                $googleCalendar->deleteEvent(
                    $user,
                    (string) ($event->google_calendar_id ?: 'primary'),
                    (string) $event->google_event_id,
                    false
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Failed deleting creator Google event', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($event->googleCopies as $copy) {
            try {
                if ($copy->user) {
                    $googleCalendar->deleteManagedUserEvent(
                        $copy->user,
                        (string) $copy->google_calendar_id,
                        (string) $copy->google_event_id
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Failed deleting invited user Google event copy', [
                    'event_id' => $event->id,
                    'copy_user_id' => $copy->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $event->delete();

        session()->flash('success', 'Meeting deleted from website and synced calendars.');
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
        $this->reminderMinutes = 30;
        $this->inviteeSearch = '';
        $this->attendeeUserIds = [];
    }

    public function addInvitee(int $userId): void
    {
        if (in_array($userId, $this->attendeeUserIds, true)) {
            return;
        }

        $this->attendeeUserIds[] = $userId;
        $this->inviteeSearch = '';
    }

    public function removeInvitee(int $userId): void
    {
        $this->attendeeUserIds = array_values(array_filter(
            $this->attendeeUserIds,
            fn (int $id): bool => $id !== $userId
        ));
    }

    public function clearInvitees(): void
    {
        $this->attendeeUserIds = [];
    }

    public function render()
    {
        $selectedInvitees = $this->eligibleInviteeQuery()
            ->whereIn('id', $this->attendeeUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $users = $this->eligibleInviteeQuery()
            ->when(Auth::id(), fn (Builder $query) => $query->where('id', '!=', Auth::id()))
            ->when(!empty($this->attendeeUserIds), fn (Builder $query) => $query->whereNotIn('id', $this->attendeeUserIds))
            ->when($this->inviteeSearch !== '', function (Builder $query): void {
                $search = '%' . trim($this->inviteeSearch) . '%';
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'email']);

        $todayStart = now(config('app.timezone'))->startOfDay();
        $todayEnd = $todayStart->endOfDay();

        $todayMeetings = CalendarEvent::query()
            ->with(['createdBy:id,name', 'attendees:id,name'])
            ->where(function (Builder $query): void {
                $userId = Auth::id();
                $query->where('created_by_user_id', $userId)
                    ->orWhereHas('attendees', fn (Builder $attendeeQuery) => $attendeeQuery->where('users.id', $userId));
            })
            ->where(function (Builder $query) use ($todayStart, $todayEnd): void {
                $query->whereBetween('starts_at', [$todayStart, $todayEnd])
                    ->orWhere(function (Builder $nested) use ($todayStart): void {
                        $nested->where('starts_at', '<', $todayStart)
                            ->where('ends_at', '>=', $todayStart);
                    });
            })
            ->orderBy('starts_at')
            ->get();

        return view('livewire.calendar.index', [
            'users' => $users,
            'selectedInvitees' => $selectedInvitees,
            'todayMeetings' => $todayMeetings,
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
            'reminderMinutes' => ['nullable', 'integer', 'min:0', 'max:40320'],
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

    protected function fillFormFromEvent(CalendarEvent $event): void
    {
        $this->resetEventForm();
        $this->editingEventId = (int) $event->id;
        $this->title = (string) $event->title;
        $this->description = $event->description;
        $this->location = $event->location;
        $this->allDay = (bool) $event->all_day;
        $this->reminderMinutes = $event->reminder_minutes;

        $tz = config('app.timezone');
        $start = CarbonImmutable::parse($event->starts_at)->setTimezone($tz);
        $end = CarbonImmutable::parse($event->ends_at)->setTimezone($tz);

        $this->startsAt = $this->allDay ? $start->format('Y-m-d') : $start->format('Y-m-d\\TH:i');
        $this->endsAt = $this->allDay ? $end->format('Y-m-d') : $end->format('Y-m-d\\TH:i');
        $this->inviteeSearch = '';
        $this->attendeeUserIds = $event->attendees->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    protected function eligibleInviteeQuery(): Builder
    {
        return User::query()
            ->whereNotNull('email')
            ->where(function (Builder $query): void {
                $query->whereNotNull('google_refresh_token')
                    ->orWhereNotNull('google_token')
                    ->orWhereHas('googleCalendarAccount', function (Builder $accountQuery): void {
                        $accountQuery->where(function (Builder $nested): void {
                            $nested->whereNotNull('refresh_token')
                                ->orWhereNotNull('access_token');
                        });
                    });
            });
    }

    protected function syncInvitedUserCalendars(
        CalendarEvent $event,
        Collection $attendees,
        GoogleCalendarService $googleCalendar
    ): void {
        $existingCopies = $event->googleCopies()->get()->keyBy('user_id');
        $keepUserIds = [];

        foreach ($attendees as $attendee) {
            if ((int) $attendee->id === (int) $event->created_by_user_id) {
                continue;
            }

            $keepUserIds[] = (int) $attendee->id;
            /** @var CalendarEventGoogleCopy|null $copy */
            $copy = $existingCopies->get((int) $attendee->id);

            try {
                if ($copy) {
                    $googleCalendar->updateManagedUserEvent(
                        $attendee,
                        (string) $copy->google_calendar_id,
                        (string) $copy->google_event_id,
                        $event->title,
                        $event->description,
                        $event->location,
                        $event->starts_at,
                        $event->ends_at,
                        (bool) $event->all_day,
                        $event->reminder_minutes
                    );
                    continue;
                }

                $googleEvent = $googleCalendar->createManagedUserEvent(
                    $attendee,
                    $event->title,
                    $event->description,
                    $event->location,
                    $event->starts_at,
                    $event->ends_at,
                    (bool) $event->all_day,
                    $event->reminder_minutes
                );

                if (!$googleEvent) {
                    continue;
                }

                $event->googleCopies()->updateOrCreate(
                    ['user_id' => $attendee->id],
                    [
                        'google_calendar_id' => 'primary',
                        'google_event_id' => (string) $googleEvent->getId(),
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Failed syncing invited user meeting copy', [
                    'calendar_event_id' => $event->id,
                    'invitee_user_id' => $attendee->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($existingCopies as $userId => $copy) {
            if (in_array((int) $userId, $keepUserIds, true)) {
                continue;
            }

            try {
                if ($copy->user) {
                    $googleCalendar->deleteManagedUserEvent(
                        $copy->user,
                        (string) $copy->google_calendar_id,
                        (string) $copy->google_event_id
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Failed deleting removed invitee meeting copy', [
                    'calendar_event_id' => $event->id,
                    'invitee_user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }

            $copy->delete();
        }
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
                'title' => $isUpdate ? 'Meeting updated' : 'New meeting',
                'message' => $isUpdate
                    ? "{$actor->name} updated \"{$event->title}\" for {$dateLabel}."
                    : "{$actor->name} invited you to \"{$event->title}\" on {$dateLabel}.",
                'data' => [
                    'event_id' => $event->id,
                    'google_event_id' => $event->google_event_id,
                    'title' => $event->title,
                    'starts_at' => $event->starts_at?->toISOString(),
                    'all_day' => (bool) $event->all_day,
                    'reminder_minutes' => $event->reminder_minutes,
                ],
            ]);
        }
    }

    protected function logMeetingSyncFailure(
        \Throwable $exception,
        User $user,
        Collection $attendees,
        string $step
    ): string {
        $reference = strtoupper(Str::random(8));

        Log::error('Calendar meeting sync failed', [
            'reference' => $reference,
            'step' => $step,
            'user_id' => $user->id,
            'editing_event_id' => $this->editingEventId,
            'title' => $this->title,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'all_day' => $this->allDay,
            'reminder_minutes' => $this->reminderMinutes,
            'attendee_user_ids' => $this->attendeeUserIds,
            'resolved_attendee_ids' => $attendees->pluck('id')->all(),
            'exception_class' => $exception::class,
            'error' => $exception->getMessage(),
        ]);

        return $reference;
    }
}
