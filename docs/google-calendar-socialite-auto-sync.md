# Google Calendar Auto Sync (Socialite + Refresh Tokens)

This guide covers connecting a user’s Google account once, then automatically pushing assigned tasks/events into their **primary** Google Calendar.

## 1) Google Cloud setup

1. In Google Cloud Console, create/select a project.
2. Enable **Google Calendar API**.
3. Create **OAuth Client ID** credentials (Web application).
4. Add an **Authorized redirect URI** for the callback route:

- `/calendar/google-socialite/callback`

(Example full URL: `https://your-domain.com/calendar/google-socialite/callback`)

## 2) Laravel Socialite install

Already installed in this repo via Composer:

- `laravel/socialite`

## 3) Configure `services.php` + env

This project uses:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`

Add them to your `.env`:

```env
GOOGLE_CLIENT_ID=your-google-oauth-client-id
GOOGLE_CLIENT_SECRET=your-google-oauth-client-secret
```

Configured in:

- `config/services.php` (`services.google.client_id/client_secret`)

Note: this implementation overrides the redirect URL at runtime using `redirectUrl(route('calendar.socialite.callback'))`, so you do **not** need to change `GOOGLE_REDIRECT_URI` for the Socialite flow.

If you change `.env`, run:

```bash
php artisan config:clear
```

## 4) Request the correct scope + refresh token

The Socialite connect uses:

- Scope: `https://www.googleapis.com/auth/calendar.events`
- Params: `access_type=offline` and `prompt=consent`

Implemented in:

- `app/Http/Controllers/Calendar/GoogleSocialiteAuthController.php`

Important behavior from Google:
- A refresh token is typically only returned the **first** time a user consents.
- If you don’t get one, disconnect and reconnect, or remove the app from the user’s Google Account and try again.

## 5) Database schema changes (users table)

This repo adds these columns:

- `users.google_token` (TEXT, nullable)
- `users.google_refresh_token` (TEXT, nullable)
- `users.google_token_expires_at` (DATETIME, nullable)

Migration:

- `database/migrations/2026_03_14_150000_add_google_calendar_tokens_to_users_table.php`

## 6) Pushing events with a Laravel Job

Job:

- `app/Jobs/PushGoogleCalendarEvent.php`

What it does:

- Checks if token is missing/expired (`google_token_expires_at`).
- Uses `google_refresh_token` to fetch a new access token.
- Uses `google/apiclient` to insert an event into the user’s **primary** calendar.
- Uses `sendUpdates=all` so Google sends calendar notifications to attendees.
- If access is revoked (`invalid_grant` / unauthorized), clears the tokens so the UI shows disconnected.

### Dispatch example

Call this wherever your “assignment” happens:

```php
use App\Jobs\PushGoogleCalendarEvent;
use Carbon\CarbonImmutable;

$startsAt = CarbonImmutable::parse('2026-03-20 10:00', config('app.timezone'));
$endsAt = $startsAt->addHour();

PushGoogleCalendarEvent::dispatch(
    userId: $user->id,
    title: 'Assigned Task: Inventory Check',
    description: 'Please complete the inventory check for Branch A.',
    location: 'Branch A',
    startsAtRfc3339: $startsAt->toRfc3339String(),
    endsAtRfc3339: $endsAt->toRfc3339String(),
    attendeeEmails: ['someone@company.com'],
    sendUpdates: true,
);
```

## 7) User-facing “category” page

A new Calendar category/page is available:

- Route: `/calendar/auto-sync`
- Livewire: `app/Livewire/Calendar/AutoSync.php`
- View: `resources/views/livewire/calendar/auto-sync.blade.php`

Sidebar link was added in:

- `resources/views/components/layouts/parts/aside.blade.php`
