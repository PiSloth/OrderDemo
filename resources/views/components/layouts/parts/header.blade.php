<header>
    <nav class="bg-white shadow px-4 py-2.5 dark:bg-gray-800">
        <div class="flex items-center justify-between mx-auto lg:justify-end">
            <button data-drawer-target="asidebar" data-drawer-toggle="asidebar" aria-controls="asidebar" type="button"
                class="inline-flex items-center p-2 mt-2 ml-3 text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                <span class="sr-only">Open sidebar</span>
                <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg">
                    <path clip-rule="evenodd" fill-rule="evenodd"
                        d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z">
                    </path>
                </svg>
            </button>

            <div class="flex items-center lg:order-2">
                <div class="relative cursor-pointer p-3">
                    {{-- <span><img src="{{ url('images/notibell-icon.png') }}" class="w-8 h-8 mr-4"></span> --}}
                    <marquee  behavior="scroll" direction="left" scrollamount="3">
                        <span
                        class=" justify-center w-5 h-5 text-xs font-semibold text-gray-300 dark:text-gray-400">K  4  S  0  3  K </span>
                    </marquee>
                </div>
                <span class="flex items-center mr-4">
                    <img src="{{ url('images/admin-icon.png') }}" alt="admin-profile" class="w-8 h-8 mr-1">
                    @if (Auth::user())
                        <a href="#"
                            class="mr-2 text-sm font-medium rounded-lg text-amber-600 dark:text-amber-400">{{ Auth::user()->name }}</a>
                    @endif
                </span>
                <a href="{{ route('doLogout') }}"
                    class="text-gray-800 bg-gray-600 text-white focus:ring-4 focus:ring-gray-300 font-medium rounded text-sm px-1.5 py-1 mr-2 focus:outline-none dark:focus:ring-gray-800">
                    {{ Auth::user() ? 'Log out' : 'Log in' }}
                </a>
            </div>
        </div>
    </nav>
</header>
