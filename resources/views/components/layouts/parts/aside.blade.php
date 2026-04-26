<aside id="asidebar"
    class="fixed top-0 left-0 z-50 w-64 h-screen transition-transform -translate-x-full lg:translate-x-0"
    x-bind:class="{ 'translate-x-0': asideOpen }" aria-label="Sidenav">
    <style>
        .tree-submenu {
            position: relative;
            margin-left: 0.5rem;
            padding-left: 1rem;
            border-left: 1px solid rgb(226 232 240);
        }

        .dark .tree-submenu {
            border-left-color: rgb(71 85 105);
        }

        .tree-submenu>li {
            position: relative;
            padding-left: 0.5rem;
        }

        .tree-submenu>li::before {
            content: '';
            position: absolute;
            left: -1rem;
            top: 50%;
            width: 0.75rem;
            height: 1px;
            background: rgb(203 213 225);
            transform: translateY(-50%);
        }

        .dark .tree-submenu>li::before {
            background: rgb(100 116 139);
        }
    </style>
    @php
        $orderGroupActive =
            request()->routeIs('add_order') ||
            request()->routeIs('order-dashboard') ||
            request()->routeIs('order-report') ||
            request()->routeIs('chat') ||
            request()->routeIs('comment-history') ||
            request()->routeIs('order-export') ||
            request()->routeIs('addsupplier');

        $psiGroupActive =
            request()->routeIs('mainboard') ||
            request()->routeIs('oos') ||
            request()->routeIs('daily_sale') ||
            request()->routeIs('psi-report');

        $performanceGroupActive = request()->routeIs('sale_repurchase') || request()->routeIs('report-dashboard');

        $jewelryGroupActive = request()->routeIs('jewelry.*');

        $todoGroupActive = request()->routeIs('todo.dashboard') || request()->routeIs('todo_list');

        $operationGroupActive =
            request()->routeIs('operation.dashboard') ||
            request()->routeIs('operation.titles') ||
            request()->routeIs('operation.daily-notes');

        $kpiGroupActive = request()->routeIs('kpi.*');

        $whiteboardGroupActive = request()->routeIs('whiteboard.*');

        $documentGroupActive = request()->routeIs('document.email-list') || request()->routeIs('document.library.*');

        $calendarActive = request()->routeIs('calendar.*');

        $initialOpenGroup = $performanceGroupActive
            ? 'performance'
            : ($orderGroupActive
                ? 'order'
                : ($psiGroupActive
                    ? 'psi'
                    : ($todoGroupActive
                        ? 'todo'
                        : ($operationGroupActive
                            ? 'operation'
                            : ($kpiGroupActive
                                ? 'kpi'
                                : ($whiteboardGroupActive
                                    ? 'whiteboard'
                                    : ($jewelryGroupActive
                                        ? 'jewelry'
                                        : ($documentGroupActive ? 'document' : ''))))))));

        $linkBase =
            'flex items-center p-2 text-base font-normal rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 group';
        $linkActive = 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white';
        $linkInactive = 'text-slate-500 dark:text-slate-400';
    @endphp
    <div x-data="{ openGroup: '{{ $initialOpenGroup }}' }"
        class="h-full px-3 py-5 overflow-y-auto bg-white border-r border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <!-- Close button -->
        <button @click="$parent.asideOpen = false"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none lg:hidden">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <ul class="space-y-2">
            <li>
                <a href="{{ route('order-dashboard') }}"
                    class="flex items-center p-2 text-center text-slate-900 rounded-lg dark:text-white">
                    <img src="{{ url('images/logo.png') }}" alt="STT Logo" class="w-12 h-10 mr-4 bg-white rounded-md">
                    <div>
                        <p class="text-base font-bold">ShweTatar</p>
                        <small class="text-slate-500 dark:text-slate-400">Gold & Jewellery</small>
                    </div>
                </a>
            </li>

            <!-- Performance Group -->
            <li class="mt-2">
                <button type="button"
                    @click="openGroup = openGroup === 'performance' ? '' : 'performance'"
                    class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                    x-bind:class="openGroup === 'performance' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-200'">
                    <span class="flex items-center">
                        <x-icon name="trending-up" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Performance</span>
                    </span>
                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400"
                        x-bind:class="{ 'rotate-180': openGroup === 'performance' }" />
                </button>

                <ul x-show="openGroup === 'performance'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                    @php $active = request()->routeIs('sale_repurchase'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('sale_repurchase') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="trending-up"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Daily Scores</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('report-dashboard'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('report-dashboard') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="chart-pie"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Sale</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Order Group -->
            <li class="mt-3">
                <button type="button" @click="openGroup = openGroup === 'order' ? '' : 'order'"
                    class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                    x-bind:class="openGroup === 'order' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-200'">
                    <span class="flex items-center">
                        <x-icon name="clipboard-list" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Order</span>
                    </span>
                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400"
                        x-bind:class="{ 'rotate-180': openGroup === 'order' }" />
                </button>

                <ul x-show="openGroup === 'order'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                    @can('isAuthorized')
                        @php $active = request()->routeIs('add_order'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('add_order') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <x-icon name="document-add"
                                    class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                                <span class="ml-3">Add Order</span>
                            </a>
                        </li>
                    @endcan

                    @php $active = request()->routeIs('order-dashboard'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('order-dashboard') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="desktop-computer"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Summary</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('order-report'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('order-report') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="chart-bar"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('order-export'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('order-export') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="download"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Export</span>
                        </a>
                    </li>

                    @can('isAuthorized')
                        @php $active = request()->routeIs('chat'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('chat') }}"
                                class="relative {{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <x-icon name="chat-alt-2"
                                    class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                                <span class="ml-3">i-Meeting</span>
                                <span
                                    class="absolute inline-flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full top-1.5 right-2 text-primary-800 bg-primary-100 dark:bg-primary-200 dark:text-primary-800">
                                    @can('isAGM')
                                        {{ $agmMeetingCount }}
                                    @else
                                        {{ $relevantMeetingCount }}
                                    @endcan
                                </span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('comment-history'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('comment-history') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <x-icon name="annotation"
                                    class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                                <span class="ml-3">Comments</span>
                            </a>
                        </li>

                        @can('isPurchaser')
                            @php $active = request()->routeIs('addsupplier'); @endphp
                            <li>
                                <a wire:navigate href="{{ route('addsupplier') }}"
                                    class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                    <x-icon name="cog"
                                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                                    <span class="ml-3">Supplier Config</span>
                                </a>
                            </li>
                        @endcan
                    @endcan
                </ul>
            </li>

            <!-- PSI Group -->
            <li class="mt-2">
                <button type="button" @click="openGroup = openGroup === 'psi' ? '' : 'psi'"
                    class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                    x-bind:class="openGroup === 'psi' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-200'">
                    <span class="flex items-center">
                        <x-icon name="view-grid" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">PSI</span>
                    </span>
                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400"
                        x-bind:class="{ 'rotate-180': openGroup === 'psi' }" />
                </button>

                <ul x-show="openGroup === 'psi'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                    @php $active = request()->routeIs('mainboard'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('mainboard') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="view-grid"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Mainboard</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('oos'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('oos') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="exclamation"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">OoS</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('daily_sale'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('daily_sale') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="calendar"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Daily Sale</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('psi-report'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('psi-report') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="chart-bar"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">PSI Report</span>
                        </a>
                    </li>
                </ul>
            </li>



            <!-- Todo Group -->
            <li class="mt-2">
                <button type="button" @click="openGroup = openGroup === 'todo' ? '' : 'todo'"
                    class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                    x-bind:class="openGroup === 'todo' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-200'">
                    <span class="flex items-center">
                        <x-icon name="check-circle" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Todo</span>
                    </span>
                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400"
                        x-bind:class="{ 'rotate-180': openGroup === 'todo' }" />
                </button>

                <ul x-show="openGroup === 'todo'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                    @php $active = request()->routeIs('todo.dashboard'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('todo.dashboard') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="chart-bar"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('todo_list'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('todo_list') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="clipboard-list"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Task List</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Operation Group -->
            <li class="mt-2">
                <button type="button" @click="openGroup = openGroup === 'operation' ? '' : 'operation'"
                    class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                    x-bind:class="openGroup === 'operation' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-200'">
                    <span class="flex items-center">
                        <x-icon name="globe" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Operation</span>
                    </span>
                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400"
                        x-bind:class="{ 'rotate-180': openGroup === 'operation' }" />
                </button>

                <ul x-show="openGroup === 'operation'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                    @php $active = request()->routeIs('operation.daily-notes'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('operation.daily-notes') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="book-open"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Daily Notes</span>
                        </a>
                    </li>

                    {{-- @php $active = request()->routeIs('operation.titles'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('operation.titles') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="clipboard-list"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Operation Titles</span>
                        </a>
                    </li> --}}
                </ul>
            </li>

            <!-- KPI Group -->
            <li class="mt-2">
                <button type="button" @click="openGroup = openGroup === 'kpi' ? '' : 'kpi'"
                    class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                    x-bind:class="openGroup === 'kpi' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-200'">
                    <span class="flex items-center">
                        <x-icon name="clipboard-check" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">KPI Tasks</span>
                    </span>
                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400"
                        x-bind:class="{ 'rotate-180': openGroup === 'kpi' }" />
                </button>

                <ul x-show="openGroup === 'kpi'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                    @php $active = request()->routeIs('kpi.dashboard') || request()->routeIs('kpi.dashboard.home'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('kpi.dashboard') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="chart-square-bar"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('kpi.tasks'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('kpi.tasks') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="calendar"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">My Tasks</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('kpi.approvals'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('kpi.approvals') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="badge-check"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Approvals</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Whiteboard Group -->
            <li class="mt-2">
                <button type="button" @click="openGroup = openGroup === 'whiteboard' ? '' : 'whiteboard'"
                    class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                    x-bind:class="openGroup === 'whiteboard' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-200'">
                    <span class="flex items-center">
                        <x-icon name="view-grid" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Whiteboard</span>
                    </span>
                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400"
                        x-bind:class="{ 'rotate-180': openGroup === 'whiteboard' }" />
                </button>

                <ul x-show="openGroup === 'whiteboard'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                    @php $active = request()->routeIs('whiteboard.board') || request()->routeIs('whiteboard.show'); @endphp
                    <li>
                        <a href="{{ route('whiteboard.board') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="collection"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Board</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('whiteboard.config'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('whiteboard.config') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="cog"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Configuration</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Office Asset -->
            <li class="mt-2">
                @php $active = request()->routeIs('office-asset.index'); @endphp
                <a wire:navigate href="{{ route('office-asset.index') }}"
                    class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                    <x-icon name="desktop-computer"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">Office Asset</span>
                </a>
            </li>

            <!-- Calendar -->
            <li class="mt-2">
                @php $active = $calendarActive; @endphp
                <a wire:navigate href="{{ route('calendar.index') }}"
                    class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                    <x-icon name="calendar"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">Calendar</span>
                </a>
            </li>

            <!-- Calendar Auto Sync -->
            <li class="mt-2">
                @php $active = request()->routeIs('calendar.auto-sync'); @endphp
                <a wire:navigate href="{{ route('calendar.auto-sync') }}"
                    class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                    <x-icon name="refresh"
                        class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                    <span class="ml-3">Calendar Auto Sync</span>
                </a>
            </li>

            <!-- Jewelry Group -->
            <li class="mt-2">
                <button type="button" @click="openGroup = openGroup === 'jewelry' ? '' : 'jewelry'"
                    class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                    x-bind:class="openGroup === 'jewelry' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-200'">
                    <span class="flex items-center">
                        <x-icon name="clipboard-list" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Jewelry</span>
                    </span>
                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400"
                        x-bind:class="{ 'rotate-180': openGroup === 'jewelry' }" />
                </button>

                <ul x-show="openGroup === 'jewelry'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                    @php $active = request()->routeIs('jewelry.dashboard'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('jewelry.dashboard') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="chart-pie"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('jewelry.groups.*'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('jewelry.groups.index') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="clipboard-list"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Groups</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Document Group -->
            <li class="mt-2">
                <button type="button" @click="openGroup = openGroup === 'document' ? '' : 'document'"
                    class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                    x-bind:class="openGroup === 'document' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-200'">
                    <span class="flex items-center">
                        <x-icon name="folder" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Document</span>
                    </span>
                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400"
                        x-bind:class="{ 'rotate-180': openGroup === 'document' }" />
                </button>

                <ul x-show="openGroup === 'document'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                    @php $active = request()->routeIs('document.email-list'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('document.email-list') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="mail"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Email List</span>
                        </a>
                    </li>

                    @php $active = request()->routeIs('document.library.*'); @endphp
                    <li>
                        <a wire:navigate href="{{ route('document.library.index') }}"
                            class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                            <x-icon name="document-text"
                                class="w-5 h-5 {{ $active ? 'text-slate-900 dark:text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}" />
                            <span class="ml-3">Library</span>
                        </a>
                    </li>
                </ul>
            </li>

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
        </ul>
    </div>
</aside>

