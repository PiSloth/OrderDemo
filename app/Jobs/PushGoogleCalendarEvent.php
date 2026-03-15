<?php

namespace App\Jobs;

use App\Models\User;
use Carbon\CarbonImmutable;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushGoogleCalendarEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $title,
        public ?string $description,
        public ?string $location,
        public string $startsAtRfc3339,
        public string $endsAtRfc3339,
        /** @var array<int,string> */
        public array $attendeeEmails = [],
        public bool $sendUpdates = true,
    ) {}

    public function handle(): void
    {
        /** @var User|null $user */
        $user = User::query()->find($this->userId);
        if (!$user) {
            return;
        }

        if (empty($user->google_refresh_token)) {
            // Not connected for auto-sync.
            return;
        }

        $client = $this->makeGoogleClient();

        $client->setAccessToken([
            'access_token' => (string) ($user->google_token ?? ''),
            'refresh_token' => (string) $user->google_refresh_token,
        ]);

        // Refresh if expired (or missing token).
        if (empty($user->google_token) || ($user->google_token_expires_at && now()->greaterThanOrEqualTo($user->google_token_expires_at))) {
            $this->refreshAccessToken($client, $user);
        }

        $service = new GoogleCalendar($client);

        $event = new GoogleCalendarEvent([
            'summary' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'start' => [
                'dateTime' => $this->startsAtRfc3339,
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $this->endsAtRfc3339,
                'timeZone' => config('app.timezone'),
            ],
        ]);

        $emails = array_values(array_filter(array_map('trim', $this->attendeeEmails), fn($e) => $e !== ''));
        if (!empty($emails)) {
            $event->setAttendees(array_map(fn($email) => ['email' => $email], $emails));
        }

        try {
            $service->events->insert('primary', $event, [
                'sendUpdates' => $this->sendUpdates ? 'all' : 'none',
            ]);
        } catch (\Throwable $e) {
            // If the user revoked access, Google commonly responds with invalid_grant on refresh or 401 on API.
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'invalid_grant') || str_contains($message, 'unauthorized') || str_contains($message, 'invalid credentials')) {
                $user->forceFill([
                    'google_token' => null,
                    'google_refresh_token' => null,
                    'google_token_expires_at' => null,
                ])->save();

                Log::info('Google Calendar access revoked; cleared tokens', ['user_id' => $user->id]);

                return;
            }

            Log::warning('Failed to push Google Calendar event', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function makeGoogleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setApplicationName(config('app.name'));
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));

        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes([
            GoogleCalendar::CALENDAR_EVENTS,
        ]);

        return $client;
    }

    private function refreshAccessToken(GoogleClient $client, User $user): void
    {
        try {
            $newToken = $client->fetchAccessTokenWithRefreshToken((string) $user->google_refresh_token);

            if (!is_array($newToken) || empty($newToken['access_token'])) {
                return;
            }

            $expiresAt = null;
            if (!empty($newToken['expires_in'])) {
                $expiresAt = CarbonImmutable::now()->addSeconds((int) $newToken['expires_in']);
            }

            $user->forceFill([
                'google_token' => (string) $newToken['access_token'],
                'google_token_expires_at' => $expiresAt,
                // Keep refresh token unless Google rotates it.
                'google_refresh_token' => !empty($newToken['refresh_token']) ? (string) $newToken['refresh_token'] : $user->google_refresh_token,
            ])->save();

            $client->setAccessToken([
                'access_token' => (string) $user->google_token,
                'refresh_token' => (string) $user->google_refresh_token,
            ]);
        } catch (\Throwable $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'invalid_grant')) {
                $user->forceFill([
                    'google_token' => null,
                    'google_refresh_token' => null,
                    'google_token_expires_at' => null,
                ])->save();

                Log::info('Google refresh token invalid_grant; cleared tokens', ['user_id' => $user->id]);

                return;
            }

            throw $e;
        }
    }
}
