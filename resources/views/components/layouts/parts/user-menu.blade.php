<div class="relative"
    x-data="{
        open: false,
        name: @js(Auth::user()?->name),
        photoUrl: @js(Auth::user()?->profile_photo_url ?? asset('images/admin-icon.png'))
    }"
    x-on:profile-updated.window="
        if ($event.detail.name) name = $event.detail.name;
        if ($event.detail.photoUrl) photoUrl = $event.detail.photoUrl;
    "
    @click.outside="open = false"
    @keydown.escape.window="open = false">
    <button type="button"
        class="inline-flex items-center gap-3 rounded-full border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-slate-700"
        @click="open = !open" :aria-expanded="open.toString()">
        <img src="{{ Auth::user()?->profile_photo_url ?? asset('images/admin-icon.png') }}" :src="photoUrl"
            alt="{{ Auth::user()?->name ?? 'User' }}"
            class="h-8 w-8 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700">
        <span class="hidden sm:inline" x-text="name"></span>
        <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd"
                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                clip-rule="evenodd" />
        </svg>
    </button>

    <div x-cloak x-show="open" x-transition.origin.top.right
        class="absolute right-0 z-50 mt-2 w-44 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
        <a href="{{ route('profile') }}" wire:navigate @click="open = false"
            class="block px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
            Profile
        </a>
        <form method="GET" action="{{ route('doLogout') }}">
            @csrf
            <button type="submit"
                class="block w-full px-4 py-3 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
                Logout
            </button>
        </form>
    </div>
</div>
