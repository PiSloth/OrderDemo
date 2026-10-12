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
        $performanceGroupActive = request()->routeIs('sale_repurchase') || request()->routeIs('report-dashboard');

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

        $kpiTasksGroupActive = request()->routeIs('kpi.*') && !request()->routeIs('kpi.sale-kpi');

        $todoGroupActive = request()->routeIs('todo.dashboard') || request()->routeIs('todo_list') || request()->routeIs('operation.daily-notes');

        $operationGroupActive =
            (request()->routeIs('operation.*') || request()->routeIs('operations.*')) &&
            !request()->routeIs('operation.daily-notes');

        $reportsGroupActive = request()->routeIs('reports.*') || request()->routeIs('taxonomies.*');

        $documentGroupActive = request()->routeIs('document.*');

        $whiteboardGroupActive = request()->routeIs('whiteboard.*');

        $trainingGroupActive = request()->routeIs('training.*');

        $digitalAssetsGroupActive = request()->routeIs('digital-assets.*');

        $officeAssetGroupActive = request()->routeIs('office-asset.*');

        $jewelryGroupActive = request()->routeIs('jewelry.*');

        $calendarActive = request()->routeIs('calendar.*');

        $initialOpenGroup = $performanceGroupActive
            ? 'performance'
            : ($orderGroupActive
                ? 'order'
                : ($psiGroupActive
                    ? 'psi'
                    : ($kpiTasksGroupActive
                        ? 'kpi'
                        : ($todoGroupActive
                            ? 'todo'
                            : ($operationGroupActive
                                ? 'operation'
                                : ($reportsGroupActive
                                    ? 'reports'
                                    : ($documentGroupActive
                                        ? 'document'
                                        : ($whiteboardGroupActive
                                            ? 'whiteboard'
                                            : ($trainingGroupActive
                                                ? 'training'
                                                : ($digitalAssetsGroupActive
                                                    ? 'digital-assets'
                                                    : ($officeAssetGroupActive
                                                        ? 'office-asset'
                                                        : ($jewelryGroupActive
                                                            ? 'jewelry'
                                                            : ''))))))))))));

        $linkBase =
            'flex items-center p-2 text-sm font-medium rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group';
        $linkActive = 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-semibold';
        $linkInactive = 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200';
    @endphp
    <div x-data="{ openGroup: '{{ $initialOpenGroup }}', search: '' }"
        class="h-full px-3 py-4 overflow-y-auto aside-slim-scroll bg-white border-r border-slate-200 dark:bg-slate-900 dark:border-slate-800 flex flex-col justify-between">
        <div>
            <!-- Close button (Mobile) -->
            <button @click="$parent.asideOpen = false"
                class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Brand Header -->
            <div class="mb-3">
                <a href="{{ route('order-dashboard') }}"
                    class="flex items-center p-1.5 text-slate-900 rounded-lg dark:text-white">
                    <img src="{{ url('images/logo.png') }}" alt="STT Logo" class="w-9 h-7 mr-3 bg-white rounded-lg p-0.5 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <div>
                        <p class="text-sm font-bold leading-tight">ShweTatar</p>
                        <small class="text-xs text-slate-500 dark:text-slate-400">Gold & Jewellery</small>
                    </div>
                </a>
            </div>

            <!-- Untitled UI Compact Search Box -->
            <div class="mb-2">
                <div class="flex items-center h-[38px] px-2.5 bg-white dark:bg-slate-900/60 border border-slate-300 dark:border-slate-700 rounded-lg shadow-sm focus-within:border-purple-600 focus-within:ring-2 focus-within:ring-purple-500/20 transition-all">
                    <x-icon name="search" class="w-4 h-4 text-slate-400 flex-shrink-0" />
                    <input type="text" x-model="search" placeholder="Search"
                        class="w-full bg-transparent border-0 p-0 ml-2 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-0">
                    <kbd class="text-[11px] font-medium text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded px-1.5 py-0.5 ml-1 font-mono">⌘K</kbd>
                </div>
            </div>

            <!-- Quick Action Button -->
            <div class="mb-3">
                <button type="button"
                    onclick="if (window.location.pathname.startsWith('/todo')) { window.dispatchEvent(new CustomEvent('open-create-todo-modal')); } else { window.location.href = '/todo/list?createTask=1'; }"
                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-slate-900 dark:bg-slate-100 py-2 px-3 text-xs font-semibold text-white dark:text-slate-900 shadow-sm transition hover:bg-slate-800 dark:hover:bg-white active:scale-95">
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Create Todo Task</span>
                </button>
            </div>

            <!-- Navigation Links List (Synchronized Order) -->
            <ul class="space-y-1">

                <!-- 1. Performance Group -->
                <li x-show="!search || 'performance daily scores sale'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'performance' ? '' : 'performance'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'performance' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="trending-up" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Performance</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'performance' }" />
                    </button>

                    <ul x-show="openGroup === 'performance'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('sale_repurchase'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('sale_repurchase') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Daily Scores</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('report-dashboard'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('report-dashboard') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Sale</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 2. Order Group -->
                <li x-show="!search || 'order add summary dashboard export meeting comments supplier'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'order' ? '' : 'order'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'order' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="clipboard-list" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Order</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'order' }" />
                    </button>

                    <ul x-show="openGroup === 'order'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @can('isAuthorized')
                            @php $active = request()->routeIs('add_order'); @endphp
                            <li>
                                <a wire:navigate href="{{ route('add_order') }}"
                                    class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                    <span class="ml-1">Add Order</span>
                                </a>
                            </li>
                        @endcan

                        @php $active = request()->routeIs('order-dashboard'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('order-dashboard') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Summary</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('order-report'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('order-report') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Dashboard</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('order-export'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('order-export') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Export</span>
                            </a>
                        </li>

                        @can('isAuthorized')
                            @php $active = request()->routeIs('chat'); @endphp
                            <li>
                                <a wire:navigate href="{{ route('chat') }}"
                                    class="relative {{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                    <span class="ml-1">i-Meeting</span>
                                    <span class="ml-auto inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        @can('isAGM')
                                            {{ $agmMeetingCount ?? 0 }}
                                        @else
                                            {{ $relevantMeetingCount ?? 0 }}
                                        @endcan
                                    </span>
                                </a>
                            </li>

                            @php $active = request()->routeIs('comment-history'); @endphp
                            <li>
                                <a wire:navigate href="{{ route('comment-history') }}"
                                    class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                    <span class="ml-1">Comments</span>
                                </a>
                            </li>

                            @can('isPurchaser')
                                @php $active = request()->routeIs('addsupplier'); @endphp
                                <li>
                                    <a wire:navigate href="{{ route('addsupplier') }}"
                                        class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                        <span class="ml-1">Supplier Config</span>
                                    </a>
                                </li>
                            @endcan
                        @endcan
                    </ul>
                </li>

                <!-- 3. PSI Group -->
                <li x-show="!search || 'psi mainboard oos daily sale report'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'psi' ? '' : 'psi'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'psi' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="view-grid" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">PSI</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'psi' }" />
                    </button>

                    <ul x-show="openGroup === 'psi'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('mainboard'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('mainboard') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Mainboard</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('oos'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('oos') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">OoS</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('daily_sale'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('daily_sale') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Daily Sale</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('psi-report'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('psi-report') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">PSI Report</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 4. Sale KPI (Standalone Link) -->
                <li x-show="!search || 'sale kpi'.includes(search.toLowerCase())">
                    @php $active = request()->routeIs('kpi.sale-kpi'); @endphp
                    <a wire:navigate href="{{ route('kpi.sale-kpi') }}"
                        class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                        <x-icon name="chart-bar" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Sale KPI</span>
                    </a>
                </li>

                <!-- 5. KPI Tasks Group -->
                <li x-show="!search || 'kpi tasks dashboard approvals'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'kpi' ? '' : 'kpi'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'kpi' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="clipboard-check" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">KPI Tasks</span>
                        </span>
                        <div class="flex items-center gap-1.5">
                            <span class="px-1.5 py-0.2 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">10</span>
                            <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                x-bind:class="{ 'rotate-180': openGroup === 'kpi' }" />
                        </div>
                    </button>

                    <ul x-show="openGroup === 'kpi'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('kpi.dashboard') || request()->routeIs('kpi.dashboard.home'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('kpi.dashboard') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Dashboard</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('kpi.tasks'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('kpi.tasks') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">My Tasks</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('kpi.approvals'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('kpi.approvals') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Approvals</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 6. Todo Group -->
                <li x-show="!search || 'todo task list dashboard daily notes'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'todo' ? '' : 'todo'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'todo' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="check-circle" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Todo</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'todo' }" />
                    </button>

                    <ul x-show="openGroup === 'todo'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('todo.dashboard'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('todo.dashboard') }}"
                                 class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Dashboard</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('todo_list'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('todo_list') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Task List</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('operation.daily-notes'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('operation.daily-notes') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Daily Notes</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 7. ERP Issue Group -->
                <li x-show="!search || 'erp issue sla analytics tickets configuration satisfaction'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'operation' ? '' : 'operation'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'operation' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="database" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">ERP Issue</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'operation' }" />
                    </button>

                    <ul x-show="openGroup === 'operation'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('operation.it.issues.index'); @endphp
                        <li>
                            <a href="{{ route('operation.it.issues.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Issue Tracking List</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('operation.it.issues.dashboard'); @endphp
                        <li>
                            <a href="{{ route('operation.it.issues.dashboard') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">SLA Analytics Board</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('operation.it.issues.create'); @endphp
                        <li>
                            <a href="{{ route('operation.it.issues.create') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">New Ticket Studio</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('operation.it.issues.configure'); @endphp
                        <li>
                            <a href="{{ route('operation.it.issues.configure') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Issue Configuration</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('operation.it.issues.reports'); @endphp
                        <li>
                            <a href="{{ route('operation.it.issues.reports') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">SLA & Credit Reports</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('operation.it.satisfaction.index'); @endphp
                        <li>
                            <a href="{{ route('operation.it.satisfaction.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">IT Satisfaction Surveys</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 8. Rich Text Reports Group -->
                <li x-show="!search || 'rich text reports analytic studio taxonomy'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'reports' ? '' : 'reports'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'reports' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="chart-pie" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Rich Text Reports</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'reports' }" />
                    </button>

                    <ul x-show="openGroup === 'reports'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('reports.analytic-board'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('reports.analytic-board') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Analytic Report</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('reports.create'); @endphp
                        <li>
                            <a href="{{ route('reports.create') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">New Report Studio</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('taxonomies.index'); @endphp
                        <li>
                            <a href="{{ route('taxonomies.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Taxonomy Admin</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 9. Document Library Group -->
                <li x-show="!search || 'document library browse new email list'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'document' ? '' : 'document'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'document' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="folder" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Document Library</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'document' }" />
                    </button>

                    <ul x-show="openGroup === 'document'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('document.library.index'); @endphp
                        <li>
                            <a href="{{ route('document.library.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Browse Documents</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('document.library.create'); @endphp
                        <li>
                            <a href="{{ route('document.library.create') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">New Document</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('document.email-list'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('document.email-list') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Email List</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 10. Whiteboard Group -->
                <li x-show="!search || 'whiteboard board configuration'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'whiteboard' ? '' : 'whiteboard'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'whiteboard' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="desktop-computer" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Whiteboard</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'whiteboard' }" />
                    </button>

                    <ul x-show="openGroup === 'whiteboard'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('whiteboard.board') || request()->routeIs('whiteboard.show'); @endphp
                        <li>
                            <a href="{{ route('whiteboard.board') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Board</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('whiteboard.config'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('whiteboard.config') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Configuration</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 11. Training & Compliance Group -->
                <li x-show="!search || 'training compliance dashboard catalog office positions sessions attendance tests'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'training' ? '' : 'training'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'training' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="academic-cap" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Training & Compliance</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'training' }" />
                    </button>

                    <ul x-show="openGroup === 'training'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('training.dashboard'); @endphp
                        <li>
                            <a href="{{ route('training.dashboard') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Compliance Dashboard</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('training.trainings.*'); @endphp
                        <li>
                            <a href="{{ route('training.trainings.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Training Catalog</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('training.office-positions.*'); @endphp
                        <li>
                            <a href="{{ route('training.office-positions.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Office Positions</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('training.sessions.*'); @endphp
                        <li>
                            <a href="{{ route('training.sessions.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Sessions & Attendance</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('training.employee.*'); @endphp
                        <li>
                            <a href="{{ route('training.employee.my-trainings') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">My Trainings & Tests</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 12. Digital Assets Group -->
                <li x-show="!search || 'digital assets software accounts platforms property reviews devices audit logs'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'digital-assets' ? '' : 'digital-assets'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'digital-assets' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="shield-check" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Digital Assets</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'digital-assets' }" />
                    </button>

                    <ul x-show="openGroup === 'digital-assets'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('digital-assets.accounts.*'); @endphp
                        <li>
                            <a href="{{ route('digital-assets.accounts.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Software Accounts</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('digital-assets.platforms.*'); @endphp
                        <li>
                            <a href="{{ route('digital-assets.platforms.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Platforms Catalog</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('digital-assets.properties.*'); @endphp
                        <li>
                            <a href="{{ route('digital-assets.properties.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Security Properties</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('digital-assets.reviews.*'); @endphp
                        <li>
                            <a href="{{ route('digital-assets.reviews.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Compliance Reviews</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('digital-assets.devices.*'); @endphp
                        <li>
                            <a href="{{ route('digital-assets.devices.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Device Inventory</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('digital-assets.audit.*'); @endphp
                        <li>
                            <a href="{{ route('digital-assets.audit.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Security Audit Logs</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 13. Office Asset Group -->
                <li x-show="!search || 'office asset category list'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'office-asset' ? '' : 'office-asset'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'office-asset' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="desktop-computer" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Office Asset</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'office-asset' }" />
                    </button>

                    <ul x-show="openGroup === 'office-asset'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('office-asset.index') && request()->query('groupBy') !== 'category'; @endphp
                        <li>
                            <a wire:navigate href="{{ route('office-asset.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Asset List</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('office-asset.index') && request()->query('groupBy') === 'category'; @endphp
                        <li>
                            <a wire:navigate href="{{ route('office-asset.index', ['groupBy' => 'category']) }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Category Summary</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 14. Jewelry Group -->
                <li x-show="!search || 'jewelry dashboard groups'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'jewelry' ? '' : 'jewelry'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'jewelry' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="sparkles" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Jewelry</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'jewelry' }" />
                    </button>

                    <ul x-show="openGroup === 'jewelry'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('jewelry.dashboard'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('jewelry.dashboard') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Dashboard</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('jewelry.groups.*'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('jewelry.groups.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Groups</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 15. Calendar Group -->
                <li x-show="!search || 'calendar auto sync'.includes(search.toLowerCase())">
                    <button type="button" @click="openGroup = openGroup === 'calendar' ? '' : 'calendar'"
                        class="w-full flex items-center justify-between p-2 text-sm font-semibold rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        x-bind:class="openGroup === 'calendar' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300'">
                        <span class="flex items-center">
                            <x-icon name="calendar" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Calendar</span>
                        </span>
                        <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': openGroup === 'calendar' }" />
                    </button>

                    <ul x-show="openGroup === 'calendar'" x-cloak class="mt-1 space-y-1 pl-2 tree-submenu">
                        @php $active = request()->routeIs('calendar.index'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('calendar.index') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Calendar</span>
                            </a>
                        </li>

                        @php $active = request()->routeIs('calendar.auto-sync'); @endphp
                        <li>
                            <a wire:navigate href="{{ route('calendar.auto-sync') }}"
                                class="{{ $linkBase }} {{ $active ? $linkActive : $linkInactive }}">
                                <span class="ml-1">Calendar Auto Sync</span>
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>

        <!-- Bottom Utilities -->
        <div class="pt-3 mt-4 border-t border-slate-200 dark:border-slate-800">
            <ul class="space-y-1">
                <li>
                    <a href="#"
                        class="flex items-center p-2 text-sm font-medium rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 transition group">
                        <x-icon name="book-open" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Docs</span>
                    </a>
                </li>
                <li>
                    @php $active = request()->routeIs('help'); @endphp
                    <a href="{{ route('help') }}"
                        class="flex items-center p-2 text-sm font-medium rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition group {{ $active ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <x-icon name="question-mark-circle" class="w-5 h-5 text-slate-400" />
                        <span class="ml-3">Help</span>
                    </a>
                </li>

                @can('isSuperAdmin')
                    <li>
                        @php $active = request()->routeIs('config'); @endphp
                        <a wire:navigate href="{{ route('config') }}"
                            class="flex items-center p-2 text-sm font-medium rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition group {{ $active ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100' }}">
                            <x-icon name="cog" class="w-5 h-5 text-slate-400" />
                            <span class="ml-3">Super Config</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </div>
    </div>
</aside>
