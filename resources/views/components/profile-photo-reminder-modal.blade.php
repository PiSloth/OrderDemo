@props([
    'show' => null,
    'announcement' => [],
])

@php
    $user = auth()->user();
    $isEnabled = (bool) ($announcement['enabled'] ?? false);

    $photoPath = (string) ($user?->profile_photo_path ?? '');
    $hasCustomPhoto = filled(trim($photoPath))
        && !str_contains($photoPath, 'admin-icon.png')
        && !str_contains($photoPath, 'default-avatar');

    $shouldShowByRule = $isEnabled && $user && !$hasCustomPhoto;
    $shouldShow = is_null($show) ? $shouldShowByRule : ($isEnabled && (bool) $show);

    $usersWithPhoto = collect();
    if ($isEnabled) {
        $usersWithPhoto = \App\Models\User::query()
            ->whereNotNull('profile_photo_path')
            ->where('profile_photo_path', '!=', '')
            ->latest('updated_at')
            ->take(12)
            ->get();
    }

    $previewUsers = $usersWithPhoto->take(8);
@endphp

@if ($isEnabled)
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="{ open: @js($shouldShow) }" x-cloak x-show="open" x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-[75] overflow-y-auto" aria-labelledby="profile-photo-reminder-title" role="dialog"
        aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-8">
            <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm" x-on:click="open = false"></div>

            <div
                class="relative w-full max-w-2xl overflow-hidden rounded-[2rem] border border-white/20 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <button type="button"
                    class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-slate-200"
                    x-on:click="open = false">
                    <span class="sr-only">Close reminder</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <div class="grid gap-0 md:grid-cols-[1.05fr_0.95fr]">
                    <div
                        class="relative overflow-hidden bg-gradient-to-br from-sky-400 via-cyan-500 to-blue-600 px-6 py-7 text-white sm:px-8 sm:py-9">
                        <div class="absolute -right-10 -top-14 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
                        <div
                            class="absolute bottom-0 left-0 h-32 w-32 -translate-x-10 translate-y-10 rounded-full bg-slate-950/20 blur-2xl">
                        </div>

                        <div class="relative">
                            <div
                                class="inline-flex items-center rounded-full bg-white/20 px-4 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-white/90">
                                {{ $announcement['badge'] ?? 'Profile' }}
                            </div>

                            <h2 id="profile-photo-reminder-title"
                                class="mt-5 max-w-xl text-3xl font-black leading-tight sm:text-4xl">
                                {{ $announcement['headline'] ?? 'Add your profile photo' }}
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
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-7 dark:bg-slate-950/70 sm:px-8 sm:py-9">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                                Team already updated
                            </p>

                            <div class="mt-4 flex items-center">
                                <div class="flex -space-x-3">
                                    @forelse ($previewUsers as $member)
                                        <img src="{{ $member->profile_photo_url }}" alt="{{ $member->name }}"
                                            title="{{ $member->name }}"
                                            class="h-10 w-10 rounded-full border-2 border-white object-cover dark:border-slate-800">
                                    @empty
                                        <span class="text-sm text-slate-500 dark:text-slate-400">No profile photos yet</span>
                                    @endforelse
                                </div>
                                @if ($usersWithPhoto->count() > $previewUsers->count())
                                    <span
                                        class="ml-3 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        +{{ $usersWithPhoto->count() - $previewUsers->count() }}
                                    </span>
                                @endif
                            </div>

                            <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">
                                {{ $usersWithPhoto->count() }} teammate(s) already changed their photo.
                            </p>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ $announcement['cta_url'] ?? '/profile' }}"
                                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200">
                                    {{ $announcement['cta_text'] ?? 'Update Photo' }}
                                </a>
                                <button type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                    x-on:click="open = false">
                                    {{ $announcement['secondary_text'] ?? 'Later' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
