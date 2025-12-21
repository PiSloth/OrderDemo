<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
</head>

<body class="antialiased bg-gray-100 dark:bg-gray-800">
    <x-notifications z-index="z-50" position="bottom-right" />
    <x-dialog z-index="z-40" blur="md" align="center" />

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('report-dashboard') }}" wire:navigate>
                        <x-icon black name="home" class="w-6 h-6 mr-2 hover:text-gray-700 dark:hover:text-gray-300 hover:cursor-pointer" />
                        </a>
                        <h1 class="text-xl font-bold text-gray-900">Todo Management</h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('todo.dashboard') }}" wire:navigate class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium {{ request()->routeIs('todo.dashboard') ? 'border-indigo-500 text-gray-900' : '' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('todo_list') }}" wire:navigate class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium {{ request()->routeIs('todo_list') ? 'border-indigo-500 text-gray-900' : '' }}">
                            Task List
                        </a>
                        <a href="{{ route('todo_config') }}" wire:navigate class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium {{ request()->routeIs('todo_config') ? 'border-indigo-500 text-gray-900' : '' }}">
                            Configuration
                        </a>
                        <a href="{{ route('notifications') }}" wire:navigate class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium {{ request()->routeIs('notifications') ? 'border-indigo-500 text-gray-900' : '' }}">
                            Notifications
                        </a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <div class="ml-3 relative">
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700">{{ Auth::user()->name }}</span>
                            <form method="GET" action="{{ route('doLogout') }}">
                                @csrf
                                <button type="submit" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    @yield('script')
    <wireui:scripts />
    @wireUiScripts
    @livewireScripts

    <script>
        document.addEventListener('livewire:navigated', () => {
            // Listen for copy-to-clipboard events from Livewire
            Livewire.on('copy-to-clipboard', (data) => {
                copyToClipboard(data.url);
            });
        });

        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                // Use the Clipboard API when available and in secure context
                navigator.clipboard.writeText(text).then(function() {
                    showCopySuccess();
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                // Fallback for older browsers or non-secure contexts
                fallbackCopyTextToClipboard(text);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            console.log('fallbackCopyTextToClipboard called with:', text);
            
            var textArea = document.createElement("textarea");
            textArea.value = text;
            
            // Avoid scrolling to bottom
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            textArea.style.opacity = "0";

            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                var successful = document.execCommand('copy');
                console.log('execCommand result:', successful);
                if (successful) {
                    showCopySuccess();
                } else {
                    console.error('Fallback: Copy command was unsuccessful');
                    alert('Failed to copy URL. Please copy manually: ' + text);
                }
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
                alert('Failed to copy URL. Please copy manually: ' + text);
            }

            document.body.removeChild(textArea);
        }

        function showCopySuccess() {
            console.log('Showing success message');
            // Create a temporary success message
            var notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
            notification.textContent = 'URL copied to clipboard!';
            document.body.appendChild(notification);

            // Remove the notification after 3 seconds
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }
    </script>
</body>

</html>
