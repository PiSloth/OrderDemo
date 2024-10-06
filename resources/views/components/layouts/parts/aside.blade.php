<aside id="asidebar"
    class="fixed top-0 left-0 w-64 h-screen transition-transform -translate-x-full z-50 lg:translate-x-0"
    aria-label="Sidenav">
    <div
        class="h-full px-3 py-5 overflow-y-auto bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <ul class="space-y-2">
            <li>
                <a href="{{ route('order-report') }}"
                    class="flex items-center p-2 text-center text-gray-900 rounded-lg dark:text-white">
                    <img src="{{ url('images/logo.png') }}" alt="STT Logo" class="w-12 h-10 mr-4 bg-white">
                    <div>
                        <p class="font-bold font-2xl ">ShweTatar</p>
                        <small class="">Gold & Jewellery</small>
                    </div>
                </a>
            </li>

            <li>
                <a wire:navigate href="{{ route('order-report') }}"
                    class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                    <img src="{{ url('images/chart-icon.png') }}" alt="chart-icon" class="w-6 h-6">
                    <span class="ml-3">Order Reports</span>
                </a>
            </li>

            @can('isAuthorized')
                <li>
                    <a wire:navigate href="{{ route('add_order') }}"
                        class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                        <img src="{{ url('images/order-icon.png') }}" alt="chart-icon" class="w-6 h-6">
                        <span class="ml-3">Add Order</span>
                    </a>
                </li>
                <li>
                    <a wire:navigate href="{{ route('chat') }}"
                        class="relative flex items-center p-2 text-base font-normal text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                        <img src="{{ url('images/chat-icon.png') }}" alt="chart-icon" class="w-6 h-6">
                        <span class="ml-3">i-Meeting</span>
                        <span
                            class="absolute inline-flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full bottom-5 left-28 text-primary-800 bg-primary-100 dark:bg-primary-200 dark:text-primary-800">

                            @can('isAGM')
                                {{ $agmMeetingCount }}
                            @else
                                {{ $relevantMeetingCount }}
                            @endcan

                        </span>
                    </a>
                </li>

                <li>
                        <a wire:navigate href="{{ route('comment-history') }}"
                            class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <img src="{{ url('images/history.png') }}" alt="chart-icon" class="w-6 h-6">
                            <span class="ml-3">Comments</span>
                        </a>
                    </li>

                @can('isPurchaser')
                    <li>
                        <a wire:navigate href="/addsupplier"
                            class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <img src="{{ url('images/config-icon.png') }}" alt="chart-icon" class="w-6 h-6">
                            <span class="ml-3">Supplier Config</span>
                        </a>
                    </li>
                @endcan



                @can('isSuperAdmin')
                    <li>
                        <a wire:navigate href="{{ route('config') }}"
                            class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <img src="{{ url('images/config-icon.png') }}" alt="chart-icon" class="w-6 h-6">
                            <span class="ml-3">Super Config</span>
                        </a>
                    </li>
                @endcan
            @endcan
        </ul>
        <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
            <li>
                <a href="#"
                    class="flex items-center p-2 text-base font-normal text-gray-900 transition duration-75 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                    <svg aria-hidden="true"
                        class="flex-shrink-0 w-6 h-6 text-gray-400 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                        fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd"
                            d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-3">Docs</span>
                </a>
            </li>
            <li>
                <a href="{{ route('help') }}"
                    class="flex items-center p-2 text-base font-normal text-gray-900 transition duration-75 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                    <img src="{{ url('images/help-icon.png') }}" alt="Help Icon" class="w-8 h-8">
                    <span class="ml-3">Help</span>
                </a>
            </li>

            {{-- dashboard button  --}}
            <li>
                <a href="{{ route('order-dashboard') }}"
                    class="flex items-center p-2 text-base font-normal text-gray-900 transition duration-75 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                    <img src="{{ url('images/speedometer.png') }}" alt="Dashboard Icon" class="w-6 h-6">
                    <span class="ml-3">Order Dashboard</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
