<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleSocialiteAuthController extends Controller
{
    /**
     * @var array<int,string>
     */
    private const ALLOWED_REDIRECT_ROUTES = [
        'calendar.index',
        'calendar.auto-sync',
    ];

    /**
     * Redirect the user to Google's consent screen.
     */
    public function connect(Request $request): RedirectResponse
    {
        $redirectRoute = $this->resolveRedirectRoute($request->query('redirect_to'));
        $request->session()->put('calendar_socialite_redirect_to', $redirectRoute);

        Log::info('Google OAuth connect attempted. Config: client_id=' . (config('services.google.client_id') ? 'present' : 'missing') . ', client_secret=' . (config('services.google.client_secret') ? 'present' : 'missing'));

        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', 'Google OAuth is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env, then run: php artisan config:clear');
        }

        $callbackUrl = route('calendar.socialite.callback');

        $redirect = Socialite::driver('google')
            ->redirectUrl($callbackUrl)
            ->scopes([
                'https://www.googleapis.com/auth/calendar.events',
                'https://www.googleapis.com/auth/userinfo.email',
                'openid',
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();

        Log::info('Google Socialite connect redirect', [
            'user_id' => $request->user()?->id,
            'callback' => $callbackUrl,
            'target' => method_exists($redirect, 'getTargetUrl') ? $redirect->getTargetUrl() : null,
        ]);

        return $redirect;
    }

    /**
     * Handle the OAuth callback and store tokens on the user.
     */
    public function callback(Request $request): RedirectResponse
    {
        $redirectRoute = $this->resolveRedirectRoute($request->session()->pull('calendar_socialite_redirect_to'));
        $user = $request->user();
        if (!$user) {
            return redirect()->route('welcome');
        }

        try {
            $callbackUrl = route('calendar.socialite.callback');

            $googleUser = Socialite::driver('google')
                ->redirectUrl($callbackUrl)
                ->user();

            $refreshToken = $googleUser->refreshToken ?: $user->google_refresh_token;
            $expiresAt = null;
            if (!empty($googleUser->expiresIn)) {
                $expiresAt = now()->addSeconds((int) $googleUser->expiresIn);
            }

            $user->forceFill([
                'google_token' => $googleUser->token,
                'google_refresh_token' => $refreshToken,
                'google_token_expires_at' => $expiresAt,
            ])->save();

            if (empty($refreshToken)) {
                return redirect()
                    ->route($redirectRoute)
                    ->with('error', 'Connected, but Google did not return a refresh token. Try Disconnect -> Connect again, and make sure Google prompts for consent (or remove the app from your Google Account and reconnect).');
            }

            return redirect()
                ->route($redirectRoute)
                ->with('success', 'Google Calendar connected.');
        } catch (\Throwable $e) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', 'Google connection failed. Please try again.');
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('welcome');
        }

        $user->forceFill([
            'google_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
        ])->save();

        return redirect()
            ->route($this->resolveRedirectRoute($request->input('redirect_to')))
            ->with('success', 'Google Calendar disconnected.');
    }

    private function resolveRedirectRoute(mixed $value): string
    {
        $route = is_string($value) ? $value : 'calendar.auto-sync';

        return in_array($route, self::ALLOWED_REDIRECT_ROUTES, true)
            ? $route
            : 'calendar.auto-sync';
    }
}
