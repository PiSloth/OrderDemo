<?php

namespace App\Services;

use App\Models\GoogleCalendarAccount;
use App\Models\User;
use Carbon\CarbonImmutable;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Oauth2 as GoogleOauth2;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    public const DEFAULT_SCOPES = [
        GoogleCalendar::CALENDAR_EVENTS,
        'https://www.googleapis.com/auth/userinfo.email',
        'openid',
    ];

    public function makeClient(): GoogleClient
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect');

        $client = new GoogleClient();
        $client->setApplicationName(config('app.name'));
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes(self::DEFAULT_SCOPES);

        return $client;
    }

    public function getAuthUrl(string $state): string
    {
        $client = $this->makeClient();
        $client->setState($state);

        return $client->createAuthUrl();
    }

    /**
     * @return array<string,mixed>
     */
    public function fetchTokenWithAuthCode(string $code): array
    {
        $client = $this->makeClient();

        return $client->fetchAccessTokenWithAuthCode($code);
    }

    public function storeTokenForUser(User $user, array $token): GoogleCalendarAccount
    {
        $client = $this->makeClient();
        $client->setAccessToken($token);

        $oauth2 = new GoogleOauth2($client);
        $me = $oauth2->userinfo->get();

        $expiresAt = null;
        if (!empty($token['expires_in'])) {
            $expiresAt = CarbonImmutable::now()->addSeconds((int) $token['expires_in']);
        }

        /** @var GoogleCalendarAccount|null $existing */
        $existing = GoogleCalendarAccount::query()->where('user_id', $user->id)->first();

        $refreshToken = (string) (($token['refresh_token'] ?? '') ?: ($existing?->refresh_token));
        if ($refreshToken !== '') {
            $token['refresh_token'] = $refreshToken;
        }

        $tokenJson = json_encode($token);

        return GoogleCalendarAccount::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'google_user_id' => $me->getId() ?: ($existing?->google_user_id),
                'email' => $me->getEmail() ?: ($existing?->email),
                'token_json' => $tokenJson === false ? null : $tokenJson,
                'access_token' => (string) ($token['access_token'] ?? ''),
                'refresh_token' => $refreshToken,
                'token_expires_at' => $expiresAt,
                'scopes' => (string) (($token['scope'] ?? '') ?: ($existing?->scopes ?? '')),
            ]
        );
    }

    public function getAuthorizedClientForUser(User $user): ?GoogleClient
    {
        /** @var GoogleCalendarAccount|null $account */
        $account = GoogleCalendarAccount::query()->where('user_id', $user->id)->first();
        if (!$account) {
            return null;
        }

        $client = $this->makeClient();

        $token = null;
        if (!empty($account->token_json)) {
            $decoded = json_decode((string) $account->token_json, true);
            if (is_array($decoded)) {
                $token = $decoded;
            }
        }

        $token = $token ?: [
            'access_token' => $account->access_token,
            'refresh_token' => $account->refresh_token,
        ];

        if (!empty($account->refresh_token)) {
            $token['refresh_token'] = (string) $account->refresh_token;
        }

        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            try {
                $refreshToken = (string) ($account->refresh_token ?? '');
                if ($refreshToken === '') {
                    return $client;
                }

                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

                if (!isset($newToken['access_token'])) {
                    return $client;
                }

                $account->access_token = (string) $newToken['access_token'];
                if (!empty($newToken['refresh_token'])) {
                    $account->refresh_token = (string) $newToken['refresh_token'];
                }
                if (!empty($newToken['expires_in'])) {
                    $account->token_expires_at = CarbonImmutable::now()->addSeconds((int) $newToken['expires_in']);
                }
                if (!empty($newToken['scope'])) {
                    $account->scopes = (string) $newToken['scope'];
                }

                $mergedToken = $client->getAccessToken();
                if (is_array($mergedToken)) {
                    if (!empty($account->refresh_token)) {
                        $mergedToken['refresh_token'] = (string) $account->refresh_token;
                    }
                    $mergedJson = json_encode($mergedToken);
                    $account->token_json = $mergedJson === false ? null : $mergedJson;
                }

                $account->save();
            } catch (\Throwable $e) {
                Log::warning('Google Calendar token refresh failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $client;
    }

    public function getCalendarServiceForUser(User $user): ?GoogleCalendar
    {
        $client = $this->getAuthorizedClientForUser($user);
        if (!$client) {
            return null;
        }

        return new GoogleCalendar($client);
    }

    /**
     * @param array<int,string> $attendeeEmails
     */
    public function createEvent(
        User $user,
        string $calendarId,
        string $title,
        ?string $description,
        ?string $location,
        \DateTimeInterface $startsAt,
        \DateTimeInterface $endsAt,
        array $attendeeEmails = [],
        bool $sendUpdates = true
    ): ?GoogleCalendarEvent {
        $service = $this->getCalendarServiceForUser($user);
        if (!$service) {
            return null;
        }

        $event = new GoogleCalendarEvent([
            'summary' => $title,
            'description' => $description,
            'location' => $location,
            'start' => [
                'dateTime' => CarbonImmutable::instance($startsAt)->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => CarbonImmutable::instance($endsAt)->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ],
        ]);

        $attendeeEmails = array_values(array_filter(array_map('trim', $attendeeEmails), fn($e) => $e !== ''));
        if (!empty($attendeeEmails)) {
            $event->setAttendees(array_map(fn($email) => ['email' => $email], $attendeeEmails));
        }

        return $service->events->insert($calendarId, $event, [
            'sendUpdates' => $sendUpdates ? 'all' : 'none',
        ]);
    }

    /**
     * @param array<int,string> $attendeeEmails
     */
    public function updateEvent(
        User $user,
        string $calendarId,
        string $eventId,
        string $title,
        ?string $description,
        ?string $location,
        \DateTimeInterface $startsAt,
        \DateTimeInterface $endsAt,
        array $attendeeEmails = [],
        bool $sendUpdates = true
    ): ?GoogleCalendarEvent {
        $service = $this->getCalendarServiceForUser($user);
        if (!$service) {
            return null;
        }

        $event = $service->events->get($calendarId, $eventId);
        $event->setSummary($title);
        $event->setDescription($description);
        $event->setLocation($location);

        $event->setStart([
            'dateTime' => CarbonImmutable::instance($startsAt)->toRfc3339String(),
            'timeZone' => config('app.timezone'),
        ]);
        $event->setEnd([
            'dateTime' => CarbonImmutable::instance($endsAt)->toRfc3339String(),
            'timeZone' => config('app.timezone'),
        ]);

        $attendeeEmails = array_values(array_filter(array_map('trim', $attendeeEmails), fn($e) => $e !== ''));
        $event->setAttendees(array_map(fn($email) => ['email' => $email], $attendeeEmails));

        return $service->events->update($calendarId, $eventId, $event, [
            'sendUpdates' => $sendUpdates ? 'all' : 'none',
        ]);
    }

    public function deleteEvent(User $user, string $calendarId, string $eventId, bool $sendUpdates = true): bool
    {
        $service = $this->getCalendarServiceForUser($user);
        if (!$service) {
            return false;
        }

        $service->events->delete($calendarId, $eventId, [
            'sendUpdates' => $sendUpdates ? 'all' : 'none',
        ]);

        return true;
    }
}
