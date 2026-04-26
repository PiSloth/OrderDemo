<?php

use App\Livewire\Actions\Logout;

$logout = function (Logout $logout) {
    $logout();

    $this->redirect('/', navigate: true);
};

?>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 dark:bg-gray-800 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="flex items-center shrink-0">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block w-auto text-gray-800 fill-current h-9 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('ord_list')" :active="request()->routeIs('ord_list')" wire:navigate>
                        {{ __('Orders') }}
                    </x-nav-link>
                    <x-nav-link :href="route('todo_list')" :active="request()->routeIs('todo_list')" wire:navigate>
                        {{ __('Todo') }}
                    </x-nav-link>
                    <x-nav-link :href="route('notifications')" :active="request()->routeIs('notifications')" wire:navigate>
                        <span class="relative">
                            {{ __('Notifications') }}
                            @php
                                $unreadCount = \App\Models\TaskNotification::forUser(auth()->id())->unread()->count();
                            @endphp
                            @if($unreadCount > 0)
                                <span class="absolute -top-2 -right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                </span>
                            @endif
                        </span>
                    </x-nav-link>
                    <div class="relative" x-data="{ open: request()->routeIs('operation.*') }">
                        <button type="button" @click="open = !open"
                            class="inline-flex items-center h-16 text-sm font-medium leading-5 transition duration-150 ease-in-out border-b-2 focus:outline-none {{ request()->routeIs('operation.*') ? 'border-indigo-400 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:border-gray-300' }}">
                            {{ __('Operations') }}
                            <svg class="w-4 h-4 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition
                            class="absolute left-0 z-30 mt-2 w-52 rounded-xl border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <a href="{{ route('operation.daily-notes') }}" wire:navigate
                                class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('operation.daily-notes') ? 'bg-indigo-50 text-indigo-700 dark:bg-gray-700 dark:text-gray-100' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                {{ __('Daily Notes') }}
                            </a>
                            @can('manageOperationTitles')
                                <a href="{{ route('operation.titles') }}" wire:navigate
                                    class="mt-1 block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('operation.titles') ? 'bg-indigo-50 text-indigo-700 dark:bg-gray-700 dark:text-gray-100' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                    {{ __('Titles') }}
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out bg-white border border-transparent rounded-md dark:text-gray-400 dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none">
                            <div class="flex items-center gap-2"
                                x-data="{
                                    name: @js(auth()->user()->name),
                                    photoUrl: @js(auth()->user()->profile_photo_url)
                                }"
                                x-on:profile-updated.window="
                                    if ($event.detail.name) name = $event.detail.name;
                                    if ($event.detail.photoUrl) photoUrl = $event.detail.photoUrl;
                                ">
                                <img src="{{ auth()->user()->profile_photo_url }}"
                                    :src="photoUrl"
                                    alt="{{ auth()->user()->name }}"
                                    class="h-8 w-8 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                                <div x-text="name"></div>
                            </div>

                            <div class="ml-1">
                                <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('config')" wire:navigate>
                            {{ __('Settings') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-left">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center -mr-2 sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 text-gray-400 transition duration-150 ease-in-out rounded-md dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400">
                    <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ord_list')" :active="request()->routeIs('ord_list')" wire:navigate>
                {{ __('Orders') }}
            </x-responsive-nav-link>
            <div class="px-4 pt-2" x-data="{ open: request()->routeIs('operation.*') }">
                <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('operation.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-gray-700 dark:text-gray-100' : 'text-gray-700 dark:text-gray-200' }}">
                    <span>{{ __('Operations') }}</span>
                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open" class="mt-2 space-y-1 pl-2" x-transition>
                    <x-responsive-nav-link :href="route('operation.daily-notes')" :active="request()->routeIs('operation.daily-notes')" wire:navigate>
                        {{ __('Daily Notes') }}
                    </x-responsive-nav-link>
                    @can('manageOperationTitles')
                        <x-responsive-nav-link :href="route('operation.titles')" :active="request()->routeIs('operation.titles')" wire:navigate>
                            {{ __('Titles') }}
                        </x-responsive-nav-link>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4"
                x-data="{
                    name: @js(auth()->user()->name),
                    photoUrl: @js(auth()->user()->profile_photo_url)
                }"
                x-on:profile-updated.window="
                    if ($event.detail.name) name = $event.detail.name;
                    if ($event.detail.photoUrl) photoUrl = $event.detail.photoUrl;
                ">
                <img src="{{ auth()->user()->profile_photo_url }}"
                    :src="photoUrl"
                    alt="{{ auth()->user()->name }}"
                    class="mb-3 h-10 w-10 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="text-base font-medium text-gray-800 dark:text-gray-200"
                    x-text="name"></div>
                <div class="text-sm font-medium text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('config')" wire:navigate>
                    {{ __('Settings') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-left">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
