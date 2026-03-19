<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleCalendarEventsController extends Controller
{
    public function index(Request $request, GoogleCalendarService $googleCalendar): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $service = $googleCalendar->getManagedCalendarServiceForUser($user);
        if (!$service) {
            return response()->json(['message' => 'Google Calendar not connected'], 409);
        }

        $start = $request->query('start');
        $end = $request->query('end');

        $startAt = $start ? CarbonImmutable::parse((string) $start) : CarbonImmutable::now()->startOfMonth();
        $endAt = $end ? CarbonImmutable::parse((string) $end) : CarbonImmutable::now()->endOfMonth();

        $events = $service->events->listEvents('primary', [
            'timeMin' => $startAt->toRfc3339String(),
            'timeMax' => $endAt->toRfc3339String(),
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'maxResults' => 2500,
        ]);

        $mapped = [];
        foreach ($events->getItems() as $event) {
            $start = $event->getStart();
            $end = $event->getEnd();

            $startDateTime = $start?->getDateTime();
            $startDate = $start?->getDate();

            $endDateTime = $end?->getDateTime();
            $endDate = $end?->getDate();

            $allDay = $startDateTime === null && $startDate !== null;

            $mapped[] = [
                'id' => (string) $event->getId(),
                'title' => (string) ($event->getSummary() ?: '(No title)'),
                'start' => (string) ($startDateTime ?: $startDate ?: ''),
                'end' => (string) ($endDateTime ?: $endDate ?: ''),
                'allDay' => $allDay,
            ];
        }

        return response()->json($mapped);
    }
}
