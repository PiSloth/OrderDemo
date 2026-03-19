<div class="space-y-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Calendar Auto Sync</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Connect once, then the system can push assigned
                tasks/events to your Google Calendar.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('calendar.index') }}"
                class="inline-flex items-center rounded-md border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-100">
                Open Event Calendar
            </a>
            @if ($connected)
                <div class="text-sm text-slate-600 dark:text-slate-200">Connected</div>
                <form method="POST" action="{{ route('calendar.socialite.disconnect') }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="calendar.auto-sync">
                    <button type="submit"
                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        Disconnect
                    </button>
                </form>
            @else
                <a href="{{ route('calendar.socialite.connect', ['redirect_to' => 'calendar.auto-sync']) }}"
                    class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                    Connect Google Calendar
                </a>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div
        class="rounded-md border border-slate-200 bg-white p-4 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 space-y-2">
        <div class="rounded-lg border border-sky-100 bg-sky-50 px-3 py-3 text-sky-800">
            Manual event creation is on the <a href="{{ route('calendar.index') }}" class="font-semibold underline">Calendar</a> page.
            This Auto Sync page is only for background Google token connection.
        </div>
        <div class="font-medium">How it works</div>
        <div>
            When a user is assigned a task/event, dispatch the job
            <span class="font-mono">\App\Jobs\PushGoogleCalendarEvent</span>
            with the user id and event details.
        </div>
        <div class="text-slate-500 dark:text-slate-300">
            Google redirect URI to register: <span class="font-mono">{{ route('calendar.socialite.callback') }}</span>
        </div>
        <div class="text-slate-500 dark:text-slate-300">
            Note: Google only returns a refresh token the first time you consent. If you don't get one, disconnect and
            reconnect, or remove the app from your Google Account and try again.
        </div>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Connected Users</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Users who currently have Google Calendar sync tokens.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                {{ $connectedUsers->count() }} users
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Token Expiry</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($connectedUsers as $connectedUser)
                        <tr>
                            <td class="px-4 py-3 text-slate-800">{{ $connectedUser->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $connectedUser->email }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $connectedUser->google_token_expires_at ? $connectedUser->google_token_expires_at->timezone(config('app.timezone'))->format('Y-m-d g:i A') : 'No expiry recorded' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $connectedUser->updated_at?->timezone(config('app.timezone'))->format('Y-m-d g:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                No users are connected to Google Calendar yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
