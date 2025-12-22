<aside id="asidebar"
    class="fixed top-0 left-0 z-50 w-64 h-screen transition-transform -translate-x-full lg:translate-x-0"
    x-bind:class="{ 'translate-x-0': asideOpen }"
    aria-label="Sidenav">
    <div
        class="h-full px-3 py-5 overflow-y-auto bg-white border-r border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <!-- Close button -->
        <button @click="$parent.asideOpen = false" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none lg:hidden">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <ul class="space-y-2">
            <li>
                <a href="{{ route('order-report') }}"
                    class="flex items-center p-2 text-center text-slate-900 rounded-lg dark:text-white">
                    <img src="{{ url('images/logo.png') }}" alt="STT Logo" class="w-12 h-10 mr-4 bg-white rounded-md">
                    <div>
                        <p class="text-base font-bold">ShweTatar</p>
                        <small class="text-slate-500 dark:text-slate-400">Gold & Jewellery</small>
                    </div>
                </a>
            </li>

            <!-- Todo Dashboard quick link (highlighted) -->
            <li class="mt-3">
                @php $active = request()->routeIs('todo-dashboard') || request()->is('todo-dashboard'); @endphp
                <a href="{{ route('todo.dashboard') }}"
                    class="flex items-center justify-center p-2 text-base font-semibold rounded-lg transition-shadow {{ $active ? 'ring-2 ring-red-300' : '' }}"
                    style="background: linear-gradient(90deg, #dc2626 0%, #f43f5e 100%); color: white;"
                    title="New: Todo Dashboard">
                    <!-- Yellow horn / megaphone icon -->
                    {{-- <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="none" aria-hidden="true">
                        <path d="M3 11v2a1 1 0 001 1h2v2a1 1 0 001 1h1v-8H6a1 1 0 00-1 1zm16-3.5a1 1 0 00-1 1V9a8 8 0 01-8 8H9v1a1 1 0 001 1h1a3 3 0 006 0h1a1 1 0 001-1v-7.5a1 1 0 00-1-1h-2z" fill="#FBBF24" />
                    </svg> --}}
                    <span class="ml-1 text-white">Todo Dashboard</span>
                </a>
            </li>

            {{-- <li>
                <a wire:navigate href="{{ route('psi_product') }}"
                    class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                    <img src="{{ url('images/chart-icon.png') }}" alt="chart-icon" class="w-6 h-6">
                    <span class="ml-3">PSI Product</span>
                </a>
            </li> --}}

            <li>
                @php $active = request()->routeIs('report-dashboard'); @endphp
                <a wire:navigate href="{{ route('report-dashboard') }}"
                    class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                    <x-icon name="chart-bar"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">Dashboard</span>
                </a>
            </li>
            <li>
                @php $active = request()->routeIs('mainboard'); @endphp
                <a wire:navigate href="{{ route('mainboard') }}"
                    class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                    <x-icon name="view-grid"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">PSI Dashboard</span>
                </a>
            </li>

            <li class="group">
                @php $active = request()->routeIs('sale_repurchase'); @endphp
                <a wire:navigate href="{{ route('sale_repurchase') }}"
                    class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                    <x-icon name="trending-up"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">Branches Scores</span>
                </a>
            </li>
            <li class="group">
                @php $active = request()->routeIs('order_histories'); @endphp
                <a wire:navigate href="{{ route('order_histories') }}"
                    class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                    <x-icon name="clock"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">Order History</span>
                </a>
            </li>

            <li class="group">
                @php $active = request()->routeIs('order-report'); @endphp
                <a wire:navigate href="{{ route('order-report') }}"
                    class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-707 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                    <x-icon name="clipboard-list"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">Orders</span>
                </a>
            </li>

            @can('isAuthorized')
                <li class="group">
                    @php $active = request()->routeIs('add_order'); @endphp
                    <a wire:navigate href="{{ route('add_order') }}"
                        class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                        <x-icon name="document-add"
                            class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                        <span class="ml-3">Add Order</span>
                    </a>
                </li>
                <li class="group">
                    @php $active = request()->routeIs('chat'); @endphp
                    <a wire:navigate href="{{ route('chat') }}"
                        class="relative flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                        <x-icon name="chat-alt-2"
                            class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
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

                <li class="group">
                    @php $active = request()->routeIs('comment-history'); @endphp
                    <a wire:navigate href="{{ route('comment-history') }}"
                        class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                        <x-icon name="annotation"
                            class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                        <span class="ml-3">Comments</span>
                    </a>
                </li>

                @can('isPurchaser')
                    <li>
                        @php $active = request()->is('addsupplier'); @endphp
                        <a wire:navigate href="/addsupplier"
                            class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                            <x-icon name="cog"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Supplier Config</span>
                        </a>
                    </li>
                @endcan



                @can('isSuperAdmin')
                    <li>
                        @php $active = request()->routeIs('config'); @endphp
                        <a wire:navigate href="{{ route('config') }}"
                            class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                            <x-icon name="cog"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Super Config</span>
                        </a>
                    </li>
                @endcan
            @endcan
        </ul>
        <ul class="pt-5 mt-5 space-y-2 border-t border-slate-200 dark:border-slate-700">
            <li>
                <a href="#"
                    class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 group">
                    <x-icon name="book-open"
                        class="w-5 h-5 text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200" />
                    <span class="ml-3">Docs</span>
                </a>
            </li>
            <li>
                @php $active = request()->routeIs('help'); @endphp
                <a href="{{ route('help') }}"
                    class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                    <x-icon name="question-mark-circle"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">Help</span>
                </a>
            </li>

            {{-- dashboard button  --}}
            <li>
                @php $active = request()->routeIs('order-dashboard'); @endphp
                <a href="{{ route('order-dashboard') }}"
                    class="flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group {{ $active ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">
                    <x-icon name="desktop-computer"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">Order Dashboard</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
