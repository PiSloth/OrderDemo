<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the React login view (shared with the Welcome stage).
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Welcome', [
            'initialShowLogin' => true,
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($credentials['email'], $request);
        $this->ensureIsNotRateLimited($throttleKey, $request);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if ($user?->suspended) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('Your account is suspended.'),
            ]);
        }

        if (! Auth::attempt(
            array_merge($credentials, ['suspended' => false]),
            $request->boolean('remember')
        )) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        Session::regenerate();
        session()->flash('show_login_announcement', true);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Ensure the authentication request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotRateLimited(string $throttleKey, Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(string $email, Request $request): string
    {
        return Str::transliterate(Str::lower($email) . '|' . $request->ip());
    }
}
