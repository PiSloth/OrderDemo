<div class="space-y-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Calendar Auto Sync</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Connect once, then the system can push assigned
                tasks/events to your Google Calendar.</p>
        </div>

        <div class="flex items-center gap-2">
            @if ($connected)
                <div class="text-sm text-slate-600 dark:text-slate-200">Connected</div>
                <form method="POST" action="{{ route('calendar.socialite.disconnect') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        Disconnect
                    </button>
                </form>
            @else
                <a href="{{ route('calendar.socialite.connect') }}"
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
</div>
