<!-- resources/views/welcome.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'STT') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind + JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .heartbeat {
            animation: heartbeat 1.5s infinite;
        }

        @keyframes heartbeat {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .heartbeat {
                animation: none;
            }
        }
    </style>
</head>

<body class="font-sans antialiased text-gray-900 bg-gradient-to-br from-yellow-50 via-amber-50 to-yellow-100">
<h1>Test</h1>
    <div class="flex flex-col items-center justify-center min-h-screen px-6 py-12" x-data="{ ready: false, loginReady: false, footerReady: false, reduceMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches }"
        x-init="if (reduceMotion) {
            ready = true;
            loginReady = true;
            footerReady = true;
        } else {
            setTimeout(() => ready = true, 200);
            setTimeout(() => loginReady = true, 2900);
            setTimeout(() => footerReady = true, 3900);
        }">

        <!-- Logo -->
        <div class="mb-8" x-cloak x-show="ready" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <img src="{{ url('images/logo.png') }}" alt="Logo"
                class="w-24 h-24 rounded-2xl shadow-md ring-1 ring-white/60">
        </div>

        <!-- Animated Welcome -->
        <div x-cloak x-show="ready" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            class="mb-10 text-center">
            <h1 class="mb-4 text-4xl font-bold text-gray-900">Welcome to JewelTrack</h1>
            <p class="text-lg text-gray-700">Manage luxurious jewelry orders with elegance and efficiency.</p>
        </div>

        <!-- Animated Navigation Cards -->
        <div x-cloak x-show="ready" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="grid w-full max-w-6xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <a href="#"
                class="rounded-2xl border border-black/10 bg-white/60 p-6 shadow-md backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">📜 View Order Histories</h2>
                <p class="text-sm text-gray-500">Browse all past and recent orders.</p>
            </a>

            <a href="#"
                class="rounded-2xl border border-black/10 bg-white/60 p-6 shadow-md backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">➕ Create New Order</h2>
                <p class="text-sm text-gray-500">Place a new order in the system.</p>
            </a>

            <a href="#"
                class="rounded-2xl border border-black/10 bg-white/60 p-6 shadow-md backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">🚚 Order Status</h2>
                <p class="text-sm text-gray-500">Track pending and arrived orders.</p>
            </a>

            <a href="#"
                class="rounded-2xl border border-black/10 bg-white/60 p-6 shadow-md backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">🏢 Branch Performance</h2>
                <p class="text-sm text-gray-500">Analyze how each branch performs.</p>
            </a>

            <a href="#"
                class="rounded-2xl border border-black/10 bg-white/60 p-6 shadow-md backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">📊 Sales Rate</h2>
                <p class="text-sm text-gray-500">Visualize sales trends over time.</p>
            </a>

            <a href="#"
                class="rounded-2xl border border-black/10 bg-white/60 p-6 shadow-md backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">💍 Top Products</h2>
                <p class="text-sm text-gray-500">View most demanded jewelry pieces.</p>
            </a>
        </div>

        {{-- Login Button --}}
        <a href="{{ route('login') }}" x-cloak x-show="loginReady" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100" :class="reduceMotion ? '' : 'heartbeat'"
            class="my-6 inline-flex items-center justify-center rounded-full border border-black/10 bg-white/60 px-6 py-2 text-lg font-semibold text-gray-900 shadow-md backdrop-blur-md transition hover:bg-white/70 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-black/60 focus:ring-offset-2">
            Login
        </a>

        <!-- Optional Footer -->
        <div x-cloak x-show="footerReady" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            class="mt-10 text-sm text-gray-600">
            Invented by IT Department • Shwe Tatar
        </div>

    </div>

</body>

</html>
