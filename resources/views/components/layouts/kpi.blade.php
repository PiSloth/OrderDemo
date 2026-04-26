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
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @yield('styles')
</head>

<body class="bg-slate-100 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <x-notifications z-index="z-50" position="bottom-right" />
    <x-dialog z-index="z-40" blur="md" align="center" />
    {{-- <x-announcement-login-modal :show="session()->has('show_login_announcement')" :announcement="config('announcements.login_popup')" /> --}}
    <x-profile-photo-reminder-modal :announcement="config('announcements.profile_photo_popup')" />

    @php
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'kpi.dashboard', 'gate' => null],
            ['label' => 'My Tasks', 'route' => 'kpi.tasks', 'gate' => null],
            ['label' => 'Audit', 'route' => 'kpi.audit', 'gate' => null],
            ['label' => 'Exclusions', 'route' => 'kpi.exclusions', 'gate' => null],
            ['label' => 'Approvals', 'route' => 'kpi.approvals', 'gate' => null],
            ['label' => 'Holidays', 'route' => 'kpi.holidays', 'gate' => 'kpiManageHolidays'],
            ['label' => 'Templates', 'route' => 'kpi.templates', 'gate' => 'kpiManageTemplates'],
            ['label' => 'Assignments', 'route' => 'kpi.assignments', 'gate' => 'kpiManageAssignments'],
            ['label' => 'Import / Export', 'route' => 'kpi.import-export', 'gate' => 'kpiManageImports'],
            ['label' => 'Leaderboard', 'route' => 'kpi.leaderboard', 'gate' => null],
        ];
    @endphp

    <div x-data="kpiLayout()" class="min-h-screen lg:flex">
        <div x-cloak x-show="mobileMenuOpen" class="fixed inset-0 z-40 bg-slate-900/40 dark:bg-slate-950/70 lg:hidden"
            @click="mobileMenuOpen = false"></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full border-r border-slate-200 bg-white transition-transform duration-200 ease-out dark:border-slate-800 dark:bg-slate-900 lg:static lg:min-h-screen lg:translate-x-0"
            :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">KPI
                        Module</p>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Employee Task Tracking</h1>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('report-dashboard') }}"
                        class="rounded-full border border-slate-200 px-3 py-1 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        Home
                    </a>
                    <button type="button"
                        class="rounded-full border border-slate-200 p-2 text-slate-600 dark:border-slate-700 dark:text-slate-300 lg:hidden"
                        @click="mobileMenuOpen = false">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <nav class="px-3 pb-5">
                <ul class="space-y-1">
                    @foreach ($navItems as $item)
                        @continue($item['gate'] && !auth()->user()->can($item['gate']))
                        @php
                            $active =
                                $item['route'] === 'kpi.dashboard'
                                    ? request()->routeIs('kpi.dashboard') || request()->routeIs('kpi.dashboard.home')
                                    : request()->routeIs($item['route']);
                        @endphp
                        <li>
                            <a href="{{ route($item['route']) }}" wire:navigate @click="mobileMenuOpen = false"
                                class="block rounded-xl px-4 py-3 text-sm font-medium {{ $active ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Current Rules</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <li>One employee per task assignment</li>
                    <li>Sequential approval</li>
                    <li>Daily reminder starts at 08:45 AM</li>
                    <li>Late approved means completed but failed</li>
                </ul>
            </div>
        </aside>

        <div class="flex-1 lg:min-w-0">
            <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between px-5 py-4">
                    <div class="flex items-center gap-3">
                        <button type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 lg:hidden"
                            @click="mobileMenuOpen = true">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4A1 1 0 013 5zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm1 4a1 1 0 100 2h12a1 1 0 100-2H4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Separate KPI workflow and scoring
                                domain</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        @include('components.layouts.parts.theme-toggle')
                        @include('components.layouts.parts.user-menu')
                    </div>
                </div>
            </header>

            <main class="px-4 py-5 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('kpiLayout', () => ({
                mobileMenuOpen: false,
            }));
        });
    </script>

    @yield('script')
    <wireui:scripts />
    @wireUiScripts
    @livewireScripts
</body>

</html>
