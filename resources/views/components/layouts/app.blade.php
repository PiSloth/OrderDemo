<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <wireui:scripts />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- @vite('resources/css/app.css') --}}

    @yield('styles')

    @wireUiScripts
    @livewireChartsScripts
    {{-- @livewireScriptConfig --}}
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" /> --}}
</head>

<body class="antialiased bg-gray-100 dark:bg-gray-800">
    <x-notifications z-index="z-50" position="bottom-right" />
    <x-dialog z-index="z-40" blur="md" align="center" />
    <x-modal>
        {{-- <span x-text="Hello World"></span> --}}
    </x-modal>

    <main class="">
        @include('components.layouts.parts.header')
        @include('components.layouts.parts.aside')

        <div class="px-10 py-5 m-0 text-sm bg-white md:m-10 lg:ml-72">
            {{ $slot }}
        </div>
    </main>
    @yield('script')
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script> --}}
</body>

</html>
