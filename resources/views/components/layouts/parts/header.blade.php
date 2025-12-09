<header>
    <nav class="bg-white shadow px-4 py-2.5 dark:bg-gray-800">
        <div class="flex justify-between items-center flex-row-reverse">
            <div class="flex items-center">
                <span class="text-xs text-gray-600 dark:text-white animate-pulse mr-4">K L A Y S O 3 K G</span>
                {{-- <div class="relative cursor-pointer">
                    <span><img src="{{ url('images/notibell-icon.png') }}" class="w-8 h-8 mr-4"></span>
                    <span
                        class="absolute inline-flex items-center justify-center w-5 h-5 text-xs font-semibold text-red-800 bg-red-100 rounded-full bottom-5 left-5 dark:bg-red-200 dark:text-red-800">10</span>
                </div> --}}
                @if (Auth::user())
                    <span class="flex items-center mr-4">
                        <img src="{{ url('images/admin-icon.png') }}" alt="admin-profile" class="w-8 h-8 mr-1">
                        <a href="#"
                            class="mr-2 text-sm font-medium rounded-lg text-amber-600 dark:text-amber-400">{{ Auth::user()->name }}</a>
                    </span>
                @endif
                @if (Auth::user())
                    <a href="{{ route('doLogout') }}"
                        class="bg-gray-600 text-white focus:ring-4 focus:ring-gray-300 font-medium rounded text-sm px-1.5 py-1 mr-2 focus:outline-none dark:focus:ring-gray-800">
                        Log out
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="bg-gray-600 text-white focus:ring-4 focus:ring-gray-300 font-medium rounded text-sm px-1.5 py-1 mr-2 focus:outline-none dark:focus:ring-gray-800">
                        Log in
                    </a>
                @endif
            </div>
        </div>
    </nav>
</header>
