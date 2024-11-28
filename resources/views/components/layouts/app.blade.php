<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <wireui:scripts />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- @vite('resources/css/app.css') --}}

    @yield('styles')

    @livewireChartsScripts
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" /> --}}
    @wireUiScripts
</head>

<body class="bg-gray-100 dark:bg-gray-800">
    <x-notifications z-index="z-50" position="bottom-right" />
    <x-dialog z-index="z-40" blur="md" align="center" />

    <main class="">
        @include('components.layouts.parts.header')
        @include('components.layouts.parts.aside')

        <div class="px-10 py-5 mt-10 ml-10 mr-10 text-sm bg-white lg:ml-72">
            {{ $slot }}
        </div>
    </main>
    @yield('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>

</html>
