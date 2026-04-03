@props([
    'show' => false,
    'announcement' => [],
])

@php
    $isEnabled = (bool) ($announcement['enabled'] ?? false);
    $shouldShow = $isEnabled && $show;
@endphp

@if ($isEnabled)
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="{ open: @js($shouldShow) }" x-cloak x-show="open" x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-[70] overflow-y-auto" aria-labelledby="login-announcement-title" role="dialog"
        aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-8">
            <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm" x-on:click="open = false"></div>

            <div
                class="relative w-full max-w-3xl overflow-hidden rounded-[2rem] border border-white/20 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="grid lg:grid-cols-[1.15fr_0.85fr]">
                    <div
                        class="relative overflow-hidden bg-gradient-to-br from-amber-400 via-rose-500 to-fuchsia-600 px-6 py-7 text-white sm:px-8 sm:py-9">
                        <div class="absolute -right-10 -top-14 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
                        <div
                            class="absolute bottom-0 left-0 h-32 w-32 -translate-x-10 translate-y-10 rounded-full bg-slate-950/20 blur-2xl">
                        </div>

                        <div class="relative">
                            <div
                                class="inline-flex items-center rounded-full bg-white/20 px-4 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-white/90">
                                {{ $announcement['badge'] ?? 'Announcement' }}
                            </div>

                            <h2 id="login-announcement-title"
                                class="mt-5 max-w-xl text-3xl font-black leading-tight sm:text-4xl">
                                {{ $announcement['headline'] ?? 'Company Announcement' }}
                            </h2>

                            @if (!empty($announcement['subheadline']))
                                <p class="mt-4 max-w-xl text-sm leading-6 text-white/85 sm:text-base">
                                    {{ $announcement['subheadline'] }}
                                </p>
                            @endif

                            @if (!empty($announcement['body']))
                                <p class="mt-4 max-w-lg text-sm leading-6 text-white/80">
                                    {{ $announcement['body'] }}
                                </p>
                            @endif

                            <div class="mt-8 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">Date
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">{{ $announcement['event_date'] ?? 'TBA' }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">Time
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">{{ $announcement['event_time'] ?? 'TBA' }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">Venue
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">{{ $announcement['event_location'] ?? 'TBA' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative bg-slate-50 px-6 py-7 dark:bg-slate-950/70 sm:px-8 sm:py-9">
                        <button type="button"
                            class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-slate-200"
                            x-on:click="open = false">
                            <span class="sr-only">Close announcement</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div
                            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.28em] text-rose-500 dark:text-amber-400">
                                New this week
                            </p>
                            <div
                                class="mt-4 rounded-[1.5rem] bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 px-5 py-6 text-white dark:from-slate-800 dark:via-slate-900 dark:to-black">
                                <p class="text-sm font-medium text-white/70">Featured Notice</p>
                                <p class="mt-2 text-2xl font-black leading-tight">
                                    {{ $announcement['headline'] ?? 'Team update' }}
                                </p>
                                {{-- <p class="mt-3 text-sm leading-6 text-white/80">
                                        Please review the event plan and share any conflicts with your supervisor early.
                                    </p> --}}
                            </div>

                            <div class="mt-5 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                                <div
                                    class="flex items-start gap-3 rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                                    <span
                                        class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300">
                                        <i class="fas fa-bullhorn text-xs"></i>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">Quick reminder</p>
                                        <p class="mt-1 leading-6">We update our team regularly with important
                                            announcements and events.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ $announcement['cta_url'] ?? '#' }}"
                                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200">
                                    {{ $announcement['cta_text'] ?? 'Open Details' }}
                                </a>
                                <button type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                    x-on:click="open = false">
                                    {{ $announcement['secondary_text'] ?? 'Close' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
