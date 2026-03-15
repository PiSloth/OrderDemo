<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class GoogleCalendarAuthController extends Controller
{
    public function connect(Request $request, GoogleCalendarService $googleCalendar): RedirectResponse
    {
        $state = (string) Str::uuid();
        Session::put('google_calendar_oauth_state', $state);

        return redirect()->away($googleCalendar->getAuthUrl($state));
    }

    public function callback(Request $request, GoogleCalendarService $googleCalendar): RedirectResponse
    {
        $expectedState = (string) Session::pull('google_calendar_oauth_state', '');
        $state = (string) $request->query('state', '');

        if ($expectedState === '' || $state !== $expectedState) {
            return redirect()
                ->route('calendar.index')
                ->with('error', 'Google Calendar connection failed (invalid state).');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()
                ->route('calendar.index')
                ->with('error', 'Google Calendar connection failed (missing code).');
        }

        $token = $googleCalendar->fetchTokenWithAuthCode($code);
        if (isset($token['error'])) {
            return redirect()
                ->route('calendar.index')
                ->with('error', 'Google Calendar connection failed: ' . (string) ($token['error_description'] ?? $token['error']));
        }

        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $googleCalendar->storeTokenForUser($user, $token);

        return redirect()
            ->route('calendar.index')
            ->with('success', 'Google Calendar connected.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $user->googleCalendarAccount()?->delete();
        }

        return redirect()
            ->route('calendar.index')
            ->with('success', 'Google Calendar disconnected.');
    }
}
