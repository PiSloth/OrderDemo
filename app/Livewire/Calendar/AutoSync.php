<?php

namespace App\Livewire\Calendar;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Http\Request;
use GoogleCalendarService;

#[Layout('components.layouts.app')]
#[Title('Calendar Auto Sync')]
class AutoSync extends Component
{
    public bool $connected = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->connected = $user && !empty($user->google_refresh_token);
    }

    public function render()
    {
        return view('livewire.calendar.auto-sync');
    }

    public function store(Request $request, GoogleCalendarService $googleCalendar): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $calendarId = 'primary';
        $title = $request->input('title');
        $description = $request->input('description');
        $location = $request->input('location');
        $startsAt = new \DateTime($request->input('start'));
        $endsAt = new \DateTime($request->input('end'));
        $attendees = $request->input('attendees', []);

        $event = $googleCalendar->createEvent(
            $user,
            $calendarId,
            $title,
            $description,
            $location,
            $startsAt,
            $endsAt,
            $attendees
        );

        if (!$event) {
            return response()->json(['message' => 'Failed to create event'], 500);
        }

        return response()->json(['message' => 'Event created', 'event' => $event->getId()]);
    }
}
