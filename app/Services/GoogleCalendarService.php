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
        $client = new GoogleClient();
        $client->setApplicationName(config('app.name'));
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

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

        $account = GoogleCalendarAccount::query()->updateOrCreate(
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

        $user->forceFill([
            'google_token' => (string) ($token['access_token'] ?? $user->google_token),
            'google_refresh_token' => $refreshToken !== '' ? $refreshToken : $user->google_refresh_token,
            'google_token_expires_at' => $expiresAt,
        ])->save();

        return $account;
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

    public function getManagedCalendarServiceForUser(User $user): ?GoogleCalendar
    {
        $service = $this->getAutoSyncCalendarServiceForUser($user);
        if ($service) {
            return $service;
        }

        return $this->getCalendarServiceForUser($user);
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
        bool $sendUpdates = true,
        bool $allDay = false,
        ?int $reminderMinutes = null
    ): ?GoogleCalendarEvent {
        $service = $this->getManagedCalendarServiceForUser($user);
        if (!$service) {
            return null;
        }

        $event = $this->buildEventPayload(
            $title,
            $description,
            $location,
            $startsAt,
            $endsAt,
            $attendeeEmails,
            $allDay,
            $reminderMinutes
        );

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
        bool $sendUpdates = true,
        bool $allDay = false,
        ?int $reminderMinutes = null
    ): ?GoogleCalendarEvent {
        $service = $this->getManagedCalendarServiceForUser($user);
        if (!$service) {
            return null;
        }

        $event = $service->events->get($calendarId, $eventId);
        $this->hydrateEvent(
            $event,
            $title,
            $description,
            $location,
            $startsAt,
            $endsAt,
            $attendeeEmails,
            $allDay,
            $reminderMinutes
        );

        return $service->events->update($calendarId, $eventId, $event, [
            'sendUpdates' => $sendUpdates ? 'all' : 'none',
        ]);
    }

    public function deleteEvent(User $user, string $calendarId, string $eventId, bool $sendUpdates = true): bool
    {
        $service = $this->getManagedCalendarServiceForUser($user);
        if (!$service) {
            return false;
        }

        $service->events->delete($calendarId, $eventId, [
            'sendUpdates' => $sendUpdates ? 'all' : 'none',
        ]);

        return true;
    }

    public function createManagedUserEvent(
        User $user,
        string $title,
        ?string $description,
        ?string $location,
        \DateTimeInterface $startsAt,
        \DateTimeInterface $endsAt,
        bool $allDay = false,
        ?int $reminderMinutes = null
    ): ?GoogleCalendarEvent {
        $service = $this->getManagedCalendarServiceForUser($user);
        if (!$service) {
            return null;
        }

        $event = $this->buildEventPayload(
            $title,
            $description,
            $location,
            $startsAt,
            $endsAt,
            [],
            $allDay,
            $reminderMinutes
        );

        return $service->events->insert('primary', $event, [
            'sendUpdates' => 'none',
        ]);
    }

    public function updateManagedUserEvent(
        User $user,
        string $calendarId,
        string $eventId,
        string $title,
        ?string $description,
        ?string $location,
        \DateTimeInterface $startsAt,
        \DateTimeInterface $endsAt,
        bool $allDay = false,
        ?int $reminderMinutes = null
    ): ?GoogleCalendarEvent {
        $service = $this->getManagedCalendarServiceForUser($user);
        if (!$service) {
            return null;
        }

        $event = $service->events->get($calendarId, $eventId);
        $this->hydrateEvent(
            $event,
            $title,
            $description,
            $location,
            $startsAt,
            $endsAt,
            [],
            $allDay,
            $reminderMinutes
        );

        return $service->events->update($calendarId, $eventId, $event, [
            'sendUpdates' => 'none',
        ]);
    }

    public function deleteManagedUserEvent(User $user, string $calendarId, string $eventId): bool
    {
        $service = $this->getManagedCalendarServiceForUser($user);
        if (!$service) {
            return false;
        }

        $service->events->delete($calendarId, $eventId, [
            'sendUpdates' => 'none',
        ]);

        return true;
    }

    private function getAutoSyncAuthorizedClientForUser(User $user): ?GoogleClient
    {
        if (empty($user->google_token) && empty($user->google_refresh_token)) {
            return null;
        }

        $client = $this->makeClient();
        $client->setAccessToken([
            'access_token' => (string) ($user->google_token ?? ''),
            'refresh_token' => (string) ($user->google_refresh_token ?? ''),
        ]);

        if (empty($user->google_token)
            || ($user->google_token_expires_at && now()->greaterThanOrEqualTo($user->google_token_expires_at))) {
            $this->refreshAutoSyncUserToken($client, $user);
        }

        return $client;
    }

    private function getAutoSyncCalendarServiceForUser(User $user): ?GoogleCalendar
    {
        $client = $this->getAutoSyncAuthorizedClientForUser($user);
        if (!$client) {
            return null;
        }

        return new GoogleCalendar($client);
    }

    private function refreshAutoSyncUserToken(GoogleClient $client, User $user): void
    {
        $refreshToken = (string) ($user->google_refresh_token ?? '');
        if ($refreshToken === '') {
            return;
        }

        try {
            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
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
                'google_refresh_token' => !empty($newToken['refresh_token'])
                    ? (string) $newToken['refresh_token']
                    : $user->google_refresh_token,
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

                Log::info('Google refresh token invalid_grant; cleared user tokens', [
                    'user_id' => $user->id,
                ]);

                return;
            }

            throw $e;
        }
    }

    /**
     * @param array<int,string> $attendeeEmails
     */
    private function buildEventPayload(
        string $title,
        ?string $description,
        ?string $location,
        \DateTimeInterface $startsAt,
        \DateTimeInterface $endsAt,
        array $attendeeEmails,
        bool $allDay,
        ?int $reminderMinutes
    ): GoogleCalendarEvent {
        $event = new GoogleCalendarEvent();

        $this->hydrateEvent(
            $event,
            $title,
            $description,
            $location,
            $startsAt,
            $endsAt,
            $attendeeEmails,
            $allDay,
            $reminderMinutes
        );

        return $event;
    }

    /**
     * @param array<int,string> $attendeeEmails
     */
    private function hydrateEvent(
        GoogleCalendarEvent $event,
        string $title,
        ?string $description,
        ?string $location,
        \DateTimeInterface $startsAt,
        \DateTimeInterface $endsAt,
        array $attendeeEmails,
        bool $allDay,
        ?int $reminderMinutes
    ): void {
        $event->setSummary($title);
        $event->setDescription($description);
        $event->setLocation($location);

        if ($allDay) {
            $start = CarbonImmutable::instance($startsAt)->startOfDay();
            $end = CarbonImmutable::instance($endsAt)->startOfDay()->addDay();

            $event->setStart([
                'date' => $start->format('Y-m-d'),
            ]);
            $event->setEnd([
                'date' => $end->format('Y-m-d'),
            ]);
        } else {
            $event->setStart([
                'dateTime' => CarbonImmutable::instance($startsAt)->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]);
            $event->setEnd([
                'dateTime' => CarbonImmutable::instance($endsAt)->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]);
        }

        $attendeeEmails = array_values(array_filter(array_map('trim', $attendeeEmails), fn ($email) => $email !== ''));
        $event->setAttendees(array_map(fn ($email) => ['email' => $email], $attendeeEmails));

        if ($reminderMinutes !== null) {
            $event->setReminders([
                'useDefault' => false,
                'overrides' => [
                    [
                        'method' => 'popup',
                        'minutes' => max(0, (int) $reminderMinutes),
                    ],
                ],
            ]);
        }
    }
}
