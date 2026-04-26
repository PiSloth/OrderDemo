<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    <script>
        (() => {
            const storedTheme = localStorage.getItem('color-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const darkMode = storedTheme === 'dark' || (!storedTheme && prefersDark);

            document.documentElement.classList.toggle('dark', darkMode);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
    @stack('styles')
</head>

<body class="antialiased bg-gray-100 dark:bg-gray-800">
    <x-notifications z-index="z-50" position="top-right" />
    <x-dialog z-index="z-40" blur="md" align="center" />
    {{-- <x-announcement-login-modal :show="session()->has('show_login_announcement')" :announcement="config('announcements.login_popup')" /> --}}
    <x-profile-photo-reminder-modal :announcement="config('announcements.profile_photo_popup')" />

    <nav class="border-b border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 justify-between">
                <div class="flex">
                    <div class="flex shrink-0 items-center">
                        <a href="{{ route('report-dashboard') }}">
                            <svg class="mr-2 h-8 w-8 hover:cursor-pointer" viewBox="0 0 100 100" aria-hidden="true">
                                <path
                                    d="M10 48L44 12a9 9 0 0 1 12 0l34 36a4 4 0 0 1-3 7H74v26a8 8 0 0 1-8 8H56V67H44v22H34a8 8 0 0 1-8-8V55H13a4 4 0 0 1-3-7Z"
                                    fill="#36A6D8" />
                                <path
                                    d="M10 48L44 12a9 9 0 0 1 12 0l34 36a4 4 0 0 1-3 7H74v26a8 8 0 0 1-8 8H56V67H44v22H34a8 8 0 0 1-8-8V55H13a4 4 0 0 1-3-7Z"
                                    fill="none" stroke="#1F1F25" stroke-width="2.2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Operation Dashboard</h1>
                    </div>

                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('operation.titles') }}" wire:navigate
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium {{ request()->routeIs('operation.titles') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white' }}">
                            Titles
                        </a>
                        <a href="{{ route('operation.daily-notes') }}"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium {{ request()->routeIs('operation.daily-notes') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white' }}">
                            Daily Notes
                        </a>

                    </div>
                </div>

                <div class="flex items-center">
                    <div class="ml-3 flex items-center space-x-4">
                        @include('components.layouts.parts.theme-toggle')
                        @include('components.layouts.parts.user-menu')
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="w-full px-4 py-4 sm:px-5 lg:px-6 text-gray-900 dark:text-gray-100">
        {{ $slot }}
    </main>

    @yield('script')
    @stack('scripts')
    <wireui:scripts />
    @wireUiScripts
    @livewireScripts
</body>

</html>
