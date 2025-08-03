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
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        .neomorph {
            background: #f0f0f3;
            border-radius: 50px;
            box-shadow: 8px 8px 16px #d1d9e6,
                -8px -8px 16px #ffffff;
            transition: all 0.3s ease-in-out;
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
    </style>
</head>

<body class="font-sans antialiased text-gray-900 bg-gradient-to-br from-white via-stone-100 to-white">

    <div class="flex flex-col items-center justify-center min-h-screen px-6 py-12" x-data="{ show: false }"
        x-init="setTimeout(() => show = true, 300)">

        <!-- Logo -->
        <div class="mb-8">
            <img src="{{ url('images/logo.png') }}" alt="Logo" class="w-24 h-24 rounded shadow-md">
        </div>

        <!-- Animated Welcome -->
        <div x-show="show" x-transition:enter="transition-opacity duration-1000" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" class="mb-10 text-center">
            <h1 class="mb-4 font-serif text-4xl font-bold text-gray-900">Welcome to JewelTrack</h1>
            <p class="text-lg text-gray-600">Manage luxurious jewelry orders with elegance and efficiency.</p>
        </div>

        <!-- Animated Navigation Cards -->
        <div x-show="show" x-transition:enter="transition-opacity duration-1000 delay-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="grid w-full max-w-6xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <a href="#"
                class="p-6 transition bg-white border border-gray-200 shadow-md hover:shadow-xl rounded-2xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">📜 View Order Histories</h2>
                <p class="text-sm text-gray-500">Browse all past and recent orders.</p>
            </a>

            <a href="#"
                class="p-6 transition bg-white border border-gray-200 shadow-md hover:shadow-xl rounded-2xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">➕ Create New Order</h2>
                <p class="text-sm text-gray-500">Place a new order in the system.</p>
            </a>

            <a href="#"
                class="p-6 transition bg-white border border-gray-200 shadow-md hover:shadow-xl rounded-2xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">🚚 Order Status</h2>
                <p class="text-sm text-gray-500">Track pending and arrived orders.</p>
            </a>

            <a href="#"
                class="p-6 transition bg-white border border-gray-200 shadow-md hover:shadow-xl rounded-2xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">🏢 Branch Performance</h2>
                <p class="text-sm text-gray-500">Analyze how each branch performs.</p>
            </a>

            <a href="#"
                class="p-6 transition bg-white border border-gray-200 shadow-md hover:shadow-xl rounded-2xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">📊 Sales Rate</h2>
                <p class="text-sm text-gray-500">Visualize sales trends over time.</p>
            </a>

            <a href="#"
                class="p-6 transition bg-white border border-gray-200 shadow-md hover:shadow-xl rounded-2xl">
                <h2 class="mb-2 text-xl font-semibold text-gray-800">💍 Top Products</h2>
                <p class="text-sm text-gray-500">View most demanded jewelry pieces.</p>
            </a>
        </div>

        {{-- Login Button --}}
        <a href="{{ route('login') }}"
            class="px-6 py-2 my-4 text-lg font-semibold text-gray-700 neomorph heartbeat hover:text-indigo-600">
            Login
        </a>

        <!-- Optional Footer -->
        <div class="mt-10 text-sm text-gray-500">
            Invented by IT Department • Shwe Tatar
        </div>

    </div>

</body>

</html>
