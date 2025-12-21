<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Todo Dashboard</h1>
        <p class="mt-2 text-sm text-gray-600">Overview of all your tasks and their current status</p>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 sm:p-6 mb-6">
        <div class="space-y-4">
            <h3 class="text-lg font-medium text-gray-900">Filters</h3>

            <!-- Date Range (JS Date Range Picker) -->
            <div class="flex flex-col sm:flex-row sm:items-end sm:gap-4" wire:ignore x-data="{
                dateFrom: @entangle('dateFrom').live,
                dateTo: @entangle('dateTo').live,
                picker: null,
                initPicker() {
                    if (!window.flatpickr) {
                        setTimeout(() => this.initPicker(), 100);
                        return;
                    }

                    if (!$refs.dueDateRange) {
                        setTimeout(() => this.initPicker(), 50);
                        return;
                    }

                    // Avoid double-initializing Flatpickr on the same element
                    if ($refs.dueDateRange._flatpickr) {
                        return;
                    }

                    const alpine = this;

                    this.picker = window.flatpickr($refs.dueDateRange, {
                        mode: 'range',
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'M d, Y',
                        altInputClass: 'block w-full sm:w-80 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm',
                        defaultDate: (alpine.dateFrom && alpine.dateTo) ? [alpine.dateFrom, alpine.dateTo] : null,
                        allowInput: true,
                        appendTo: document.body,
                        onReady(selectedDates, dateStr, instance) {
                            // Ensure the calendar isn't clipped/hidden behind containers
                            try {
                                if (instance && instance.calendarContainer) {
                                    instance.calendarContainer.style.zIndex = '9999';
                                }
                            } catch (e) {}

                            // Ensure the default range is rendered into the altInput consistently
                            if (alpine.dateFrom && alpine.dateTo) {
                                instance.setDate([alpine.dateFrom, alpine.dateTo], false);
                            } else {
                                instance.clear();
                            }

                            // If Flatpickr created an altInput, keep the placeholder on the visible input too
                            try {
                                if (instance && instance.altInput) {
                                    instance.altInput.placeholder = 'Select date range';
                                }
                            } catch (e) {}
                        },
                        onChange(selectedDates, dateStr, instance) {
                            // Clear -> remove date filters
                            if (!selectedDates || selectedDates.length === 0 || !dateStr || String(dateStr).trim() === '') {
                                alpine.dateFrom = '';
                                alpine.dateTo = '';
                                $wire.set('dateFrom', '');
                                $wire.set('dateTo', '');
                                return;
                            }

                            if (selectedDates.length < 2) {
                                return;
                            }

                            const start = instance.formatDate(selectedDates[0], 'Y-m-d');
                            const end = instance.formatDate(selectedDates[1], 'Y-m-d');
                            alpine.dateFrom = start;
                            alpine.dateTo = end;
                            $wire.set('dateFrom', start);
                            $wire.set('dateTo', end);
                        }
                    });
                },
                updatePicker() {
                    if (this.picker && this.dateFrom && this.dateTo) {
                        this.picker.setDate([this.dateFrom, this.dateTo], false);
                    } else if (this.picker) {
                        this.picker.clear();
                    }
                }
            }" x-init="
                // Wait one tick so $refs is guaranteed populated
                $nextTick(() => { initPicker(); });

                // Watch for changes in dateFrom and dateTo
                $watch('dateFrom', () => { updatePicker(); });
                $watch('dateTo', () => { updatePicker(); });
            ">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date Range</label>
                    <input
                        type="text"
                        x-ref="dueDateRange"
                        wire:ignore
                        placeholder="Select date range"
                        class="block w-full sm:w-80 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    />
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Requested Filters (Red Background) -->
                <div class="bg-red-50 rounded-lg p-4 w-full lg:w-1/2">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-red-900">Requested Filters</h4>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-4">
                        <div class="w-full lg:w-1/2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Requested Branch</label>
                            <x-select
                                wire:model.live="requestedBranchIds"
                                placeholder="Select requested branches"
                                multiselect
                                searchable
                                :options="$this->branches"
                                option-label="name"
                                option-value="id"
                            />
                        </div>

                        <div class="w-full lg:w-1/2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Requested Department</label>
                            <x-select
                                wire:model.live="requestedDepartmentIds"
                                placeholder="Select requested departments"
                                multiselect
                                searchable
                                :options="$this->departments"
                                option-label="name"
                                option-value="id"
                            />
                        </div>
                    </div>
                </div>

                <!-- Assigned Filters (Green Background) -->
                <div class="bg-green-50 rounded-lg p-4 w-full lg:w-1/2">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-green-900">Assigned Filters</h4>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-4">
                        <div class="w-full lg:w-1/2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Branch</label>
                            <x-select
                                wire:model.live="assignedBranchIds"
                                placeholder="Select assigned branches"
                                multiselect
                                searchable
                                :options="$this->branches"
                                option-label="name"
                                option-value="id"
                            />
                        </div>

                        <div class="w-full lg:w-1/2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Department</label>
                            <x-select
                                wire:model.live="assignedDepartmentIds"
                                placeholder="Select assigned departments"
                                multiselect
                                searchable
                                :options="$this->departments"
                                option-label="name"
                                option-value="id"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-6 mb-8">
        <!-- Finished Tasks -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Finished Tasks</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['finished'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Passed Tasks -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Overdue Tasks</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['passed'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed Tasks -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Failed Tasks</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['failed'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Need to Do Tasks -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Need to Do</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['need_to_do'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Tasks -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">New Tasks</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['new'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Action Steps -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Pending Actions</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['pending_action_steps'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Success Rate</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['success_rate'] }}%</dd>
                            <dd class="text-xs text-gray-500">{{ $stats['finished'] }} / {{ $stats['total_tasks_in_range'] }} tasks</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status and Priority Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Status Distribution -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Status Distribution</h3>
            <div class="space-y-4">
                @if(count($stats['status_chart']) > 0)
                    @php
                        $maxCount = max($stats['status_chart']);
                        $totalTasks = array_sum($stats['status_chart']);
                    @endphp
                    @foreach($stats['status_chart'] as $status => $count)
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700">{{ $status }}</span>
                                    <span class="text-sm text-gray-500">{{ $count }} tasks ({{ round(($count / $totalTasks) * 100, 1) }}%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $maxCount > 0 ? ($count / $maxCount) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-gray-500 text-sm">No tasks found in the selected date range</p>
                @endif
            </div>
        </div>

        <!-- Priority Distribution -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Priority Distribution</h3>
            @if(count($stats['priority_chart']) > 0)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div id="priority-radar-chart" class="w-full" wire:ignore></div>
                </div>
            @else
                <p class="text-gray-500 text-sm">No tasks found in the selected date range</p>
            @endif
        </div>
    </div>

    <!-- Task Category Distribution + Department Request Handling -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Category Distribution Donut Chart -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Task Category Distribution</h3>
            @if(count($stats['category_chart']) > 0)
                <div class="flex flex-col lg:flex-row items-center justify-between">
                    <!-- Donut Chart -->
                    <div class="relative w-48 h-48 mb-4 lg:mb-0">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            @php
                                $total = array_sum($stats['category_chart']);
                                $colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#06B6D4', '#F97316', '#84CC16'];
                                $currentAngle = 0;
                                $colorIndex = 0;
                            @endphp
                            @foreach($stats['category_chart'] as $category => $count)
                                @php
                                    $percentage = ($count / $total) * 100;
                                    $angle = ($percentage / 100) * 360;
                                    $startAngle = $currentAngle;
                                    $endAngle = $currentAngle + $angle;

                                    $x1 = 18 + 15 * cos(deg2rad($startAngle));
                                    $y1 = 18 + 15 * sin(deg2rad($startAngle));
                                    $x2 = 18 + 15 * cos(deg2rad($endAngle));
                                    $y2 = 18 + 15 * sin(deg2rad($endAngle));

                                    $largeArcFlag = $angle > 180 ? 1 : 0;

                                    $currentAngle = $endAngle;
                                    $color = $colors[$colorIndex % count($colors)];
                                    $colorIndex++;
                                @endphp
                                <path
                                    d="M18,18 L{{ $x1 }},{{ $y1 }} A15,15 0 {{ $largeArcFlag }},1 {{ $x2 }},{{ $y2 }} Z"
                                    fill="{{ $color }}"
                                    stroke="white"
                                    stroke-width="0.5"
                                ></path>
                            @endforeach
                            <!-- Center circle for donut effect -->
                            <circle cx="18" cy="18" r="9" fill="white"></circle>
                        </svg>
                        <!-- Center text -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ $total }}</div>
                                <div class="text-xs text-gray-500">Total Tasks</div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend and Details -->
                    <div class="flex-1 lg:ml-8">
                        <div class="space-y-3">
                            @php
                                $colorIndex = 0;
                                $topCategories = array_slice($stats['category_chart'], 0, 5, true);
                            @endphp
                            @foreach($topCategories as $category => $count)
                                @php
                                    $color = $colors[$colorIndex % count($colors)];
                                    $percentage = round(($count / $total) * 100, 1);
                                    $colorIndex++;
                                @endphp
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 rounded-full mr-3" style="background-color: {{ $color }}"></div>
                                        <span class="text-sm font-medium text-gray-700">{{ $category }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                                        <span class="text-xs text-gray-500 ml-1">({{ $percentage }}%)</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if(count($stats['category_chart']) > 5)
                            <div class="mt-4 pt-3 border-t border-gray-200">
                                <p class="text-xs text-gray-500">
                                    +{{ count($stats['category_chart']) - 5 }} more categories
                                </p>
                            </div>
                        @endif

                        <!-- Most Demanded Category Highlight -->
                        @if(count($stats['category_chart']) > 0)
                            @php
                                $mostDemanded = array_key_first($stats['category_chart']);
                                $mostDemandedCount = $stats['category_chart'][$mostDemanded];
                                $mostDemandedPercentage = round(($mostDemandedCount / $total) * 100, 1);
                            @endphp
                            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-blue-900">Most Demanded Category</h4>
                                        <p class="text-sm text-blue-700">
                                            <strong>{{ $mostDemanded }}</strong> - {{ $mostDemandedCount }} tasks ({{ $mostDemandedPercentage }}% of total)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 0 01-2-2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No category data</h3>
                    <p class="mt-1 text-sm text-gray-500">No tasks found in the selected date range with category information.</p>
                </div>
            @endif
        </div>

        <!-- Department Handling Statistics -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Department Request Handling</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if(count($stats['department_handling_stats']) > 0)
                                @foreach(array_slice($stats['department_handling_stats'], 0, 10) as $department => $stats)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $department }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stats['total_assigned'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stats['completed'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $stats['success_rate'] >= 80 ? 'bg-green-100 text-green-800' :
                                                   ($stats['success_rate'] >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ $stats['success_rate'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No department data available</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Request Patterns -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Branch Request Patterns</h3>

            <!-- Line Chart: Top Requesting Branches Over Time -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-md font-medium text-gray-700">Top Requesting Branches (Trend)</h4>
                    @if(($stats['requesting_branches_line']['top_branch'] ?? null) && ($stats['requesting_branches_line']['top_count'] ?? 0) > 0)
                        <div class="text-sm text-gray-500">
                            Top: <span class="font-medium text-gray-900">{{ $stats['requesting_branches_line']['top_branch'] }}</span>
                            <span class="text-gray-500">({{ $stats['requesting_branches_line']['top_count'] }})</span>
                        </div>
                    @endif
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <div id="branch-requests-line-chart" class="w-full" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Handling Request Patterns -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Department Handling Request Patterns</h3>

            <!-- Line Chart: Top Handling Departments Over Time -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-md font-medium text-gray-700">Top Handling Departments (Trend)</h4>
                    @if(($stats['department_handling_line']['top_department'] ?? null) && ($stats['department_handling_line']['top_count'] ?? 0) > 0)
                        <div class="text-sm text-gray-500">
                            Top: <span class="font-medium text-gray-900">{{ $stats['department_handling_line']['top_department'] }}</span>
                            <span class="text-gray-500">({{ $stats['department_handling_line']['top_count'] }})</span>
                        </div>
                    @endif
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <div id="department-handling-line-chart" class="w-full" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const initialChartData = @json($stats['requesting_branches_line'] ?? ['categories' => [], 'series' => []]);
            const initialDepartmentChartData = @json($stats['department_handling_line'] ?? ['categories' => [], 'series' => []]);
            const initialPriorityRadarData = @json($stats['priority_radar'] ?? ['categories' => [], 'series' => []]);

            function renderPriorityRadarChart(chartData) {
                if (typeof window.ApexCharts === 'undefined') {
                    return;
                }

                const el = document.getElementById('priority-radar-chart');
                if (!el) {
                    return;
                }

                const categories = (chartData && chartData.categories) ? chartData.categories : [];
                const series = (chartData && chartData.series) ? chartData.series : [];

                const options = {
                    chart: {
                        type: 'radar',
                        height: 320,
                        toolbar: { show: false },
                    },
                    series: series,
                    xaxis: {
                        categories: categories,
                    },
                    stroke: {
                        width: 2,
                    },
                    fill: {
                        opacity: 0.25,
                    },
                    markers: {
                        size: 3,
                    },
                    dataLabels: { enabled: false },
                    yaxis: {
                        labels: {
                            formatter: function (val) {
                                return Math.round(val);
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return Math.round(val);
                            }
                        }
                    },
                };

                if (window.priorityRadarChart) {
                    window.priorityRadarChart.updateOptions(options, true, true);
                    return;
                }

                window.priorityRadarChart = new window.ApexCharts(el, options);
                window.priorityRadarChart.render();
            }

            function renderBranchRequestsLineChart(chartData) {
                if (typeof window.ApexCharts === 'undefined') {
                    return;
                }

                const el = document.getElementById('branch-requests-line-chart');
                if (!el) {
                    return;
                }

                const categories = (chartData && chartData.categories) ? chartData.categories : [];
                const series = (chartData && chartData.series) ? chartData.series : [];

                const options = {
                    chart: {
                        type: 'line',
                        height: 320,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                    },
                    stroke: { curve: 'smooth', width: 2 },
                    markers: { size: 0 },
                    dataLabels: { enabled: false },
                    legend: { position: 'top', horizontalAlign: 'left' },
                    series: series,
                    xaxis: {
                        categories: categories,
                        labels: { rotate: -45, hideOverlappingLabels: true },
                        tickPlacement: 'on',
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            formatter: function (val) {
                                return Math.round(val);
                            }
                        }
                    },
                    grid: { strokeDashArray: 4 },
                    tooltip: { shared: true, intersect: false },
                };

                if (window.branchRequestsLineChart) {
                    window.branchRequestsLineChart.updateOptions(options, true, true);
                    return;
                }

                window.branchRequestsLineChart = new window.ApexCharts(el, options);
                window.branchRequestsLineChart.render();
            }

            function renderDepartmentHandlingLineChart(chartData) {
                if (typeof window.ApexCharts === 'undefined') {
                    return;
                }

                const el = document.getElementById('department-handling-line-chart');
                if (!el) {
                    return;
                }

                const categories = (chartData && chartData.categories) ? chartData.categories : [];
                const series = (chartData && chartData.series) ? chartData.series : [];

                const options = {
                    chart: {
                        type: 'line',
                        height: 320,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                    },
                    stroke: { curve: 'smooth', width: 2 },
                    markers: { size: 0 },
                    dataLabels: { enabled: false },
                    legend: { position: 'top', horizontalAlign: 'left' },
                    series: series,
                    xaxis: {
                        categories: categories,
                        labels: { rotate: -45, hideOverlappingLabels: true },
                        tickPlacement: 'on',
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            formatter: function (val) {
                                return Math.round(val);
                            }
                        }
                    },
                    grid: { strokeDashArray: 4 },
                    tooltip: { shared: true, intersect: false },
                };

                if (window.departmentHandlingLineChart) {
                    window.departmentHandlingLineChart.updateOptions(options, true, true);
                    return;
                }

                window.departmentHandlingLineChart = new window.ApexCharts(el, options);
                window.departmentHandlingLineChart.render();
            }

            function renderAllInitialCharts() {
                renderPriorityRadarChart(initialPriorityRadarData);
                renderBranchRequestsLineChart(initialChartData);
                renderDepartmentHandlingLineChart(initialDepartmentChartData);
            }

            let initialRenderInterval = null;

            function renderInitialChartsWhenReady() {
                if (typeof window.ApexCharts !== 'undefined') {
                    renderAllInitialCharts();
                    return;
                }

                // Avoid starting multiple polling loops
                if (initialRenderInterval) {
                    return;
                }

                let retries = 50; // ~5 seconds max
                initialRenderInterval = setInterval(function () {
                    if (typeof window.ApexCharts !== 'undefined') {
                        clearInterval(initialRenderInterval);
                        initialRenderInterval = null;
                        renderAllInitialCharts();
                    } else if (--retries <= 0) {
                        clearInterval(initialRenderInterval);
                        initialRenderInterval = null;
                    }
                }, 100);
            }

            function scheduleInitialChartRender() {
                // This script can be injected after DOMContentLoaded in Livewire navigations.
                // So we render immediately + next frame (ensures chart containers exist).
                try {
                    renderInitialChartsWhenReady();
                    if (typeof requestAnimationFrame === 'function') {
                        requestAnimationFrame(function () {
                            renderInitialChartsWhenReady();
                        });
                    }
                    setTimeout(function () {
                        renderInitialChartsWhenReady();
                    }, 0);
                } catch (e) {
                    // no-op
                }
            }

            // Initial render
            document.addEventListener('DOMContentLoaded', function () {
                scheduleInitialChartRender();
            });

            // Also run immediately (handles cases where DOMContentLoaded already fired)
            scheduleInitialChartRender();

            // Livewire updates
            document.addEventListener('livewire:init', function () {
                if (window.Livewire && typeof window.Livewire.on === 'function') {
                    window.Livewire.on('priority-radar-chart-updated', function (payload) {
                        renderPriorityRadarChart(payload && payload.chart ? payload.chart : payload);
                    });

                    window.Livewire.on('branch-requests-chart-updated', function (payload) {
                        renderBranchRequestsLineChart(payload && payload.chart ? payload.chart : payload);
                    });

                    window.Livewire.on('department-handling-chart-updated', function (payload) {
                        renderDepartmentHandlingLineChart(payload && payload.chart ? payload.chart : payload);
                    });
                }
            });

            // SPA navigations in Livewire v3
            document.addEventListener('livewire:navigated', function () {
                scheduleInitialChartRender();
            });
        })();
    </script>



    <!-- Task Lists (Grouped) -->
    <div x-data="{ tab: 'my_department' }" class="space-y-4">
        <!-- Group Tabs -->
        <div class="bg-white shadow rounded-lg p-4 sm:p-6">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="tab = 'my_department'"
                    class="px-4 py-2 rounded-md text-sm font-medium border"
                    :class="tab === 'my_department' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                >
                    Tasks Our Department Must Do
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                          :class="tab === 'my_department' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700'">
                        {{ count($taskGroups['my_department']['tasks'] ?? []) }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="tab = 'my_requests'"
                    class="px-4 py-2 rounded-md text-sm font-medium border"
                    :class="tab === 'my_requests' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                >
                    Tasks Our Department Requested
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                          :class="tab === 'my_requests' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700'">
                        {{ count($taskGroups['my_requests']['tasks'] ?? []) }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="tab = 'others'"
                    class="px-4 py-2 rounded-md text-sm font-medium border"
                    :class="tab === 'others' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                >
                    Other Tasks
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                          :class="tab === 'others' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700'">
                        {{ count($taskGroups['others']['tasks'] ?? []) }}
                    </span>
                </button>
            </div>
        </div>

        <div x-show="tab === 'my_department'">
            @php $group = $taskGroups['my_department'] ?? []; @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- New Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-2">NEW</span>
                            New Tasks
                            @if(count($group['new'] ?? []) > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500 text-white">{{ count($group['new'] ?? []) }}</span>
                            @endif
                        </h3>
                        <div class="space-y-3">
                            @forelse(collect($group['new'] ?? [])->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg cursor-pointer hover:bg-green-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $task->department->name ?? 'Unknown Dept' }}
                                            </span>
                                            <span class="text-xs text-gray-400">→</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ $task->assignedUser->department->name ?? 'Unknown Dept' }}
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $task->status->status ?? 'Unknown' }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No new tasks</p>
                            @endforelse
                        </div>
                        @if(count($group['new'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['new'] ?? []) }} new tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Pending Action Steps -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 mr-2">ACTION</span>
                            Pending Action Steps
                            @if(count($group['pending_action_steps'] ?? []) > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-500 text-white">{{ count($group['pending_action_steps'] ?? []) }}</span>
                            @endif
                        </h3>
                        <div class="space-y-3">
                            @forelse(collect($group['pending_action_steps'] ?? [])->take(5) as $actionStep)
                                <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg cursor-pointer hover:bg-orange-100 transition-colors" wire:click="showActionStepDetail({{ $actionStep->id }})">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $actionStep->user->name }}</span>
                                            <span class="text-xs text-gray-400">requests</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                @if($actionStep->isDueDateChangeRequest())
                                                    Due Date Change
                                                @elseif($actionStep->isStatusChangeRequest())
                                                    Status Change
                                                @elseif($actionStep->isResolverChangeRequest())
                                                    Resolver Change
                                                @else
                                                    Custom Request
                                                @endif
                                            </span>
                                            <span class="text-xs text-gray-400">from</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                @if($actionStep->todoList && $actionStep->todoList->assignedUser)
                                                    {{ $actionStep->todoList->assignedUser->name }}
                                                @else
                                                    Task Assignee
                                                @endif
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900">{{ Str::limit($actionStep->todoList->task ?? '', 60) }}</p>
                                        <p class="text-xs text-gray-500">
                                            Requested: {{ $actionStep->created_at->format('M d, Y H:i') }}
                                            @if($actionStep->isDueDateChangeRequest() && isset($actionStep->action_data['new_due_date']))
                                                | New due date: {{ \Carbon\Carbon::parse($actionStep->action_data['new_due_date'])->format('M d, Y H:i') }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Pending</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No pending action step requests</p>
                            @endforelse
                        </div>
                        @if(count($group['pending_action_steps'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['pending_action_steps'] ?? []) }} pending action steps →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Need to Do Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Need to Do (Next 7 Days)</h3>
                        <div class="space-y-3">
                            @forelse(collect($group['need_to_do'] ?? [])->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg cursor-pointer hover:bg-blue-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $task->status->status ?? 'Unknown' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No urgent tasks in the next 7 days</p>
                            @endforelse
                        </div>
                        @if(count($group['need_to_do'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['need_to_do'] ?? []) }} urgent tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Overdue Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Overdue Tasks</h3>
                        <div class="space-y-3">
                            @forelse(collect($group['overdue'] ?? [])->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg cursor-pointer hover:bg-red-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $task->status->status ?? 'Unknown' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No overdue tasks</p>
                            @endforelse
                        </div>
                        @if(count($group['overdue'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['overdue'] ?? []) }} overdue tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Finished Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recently Finished</h3>
                        <div class="space-y-3">
                            @forelse(collect($group['finished'] ?? [])->sortByDesc('updated_at')->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg cursor-pointer hover:bg-green-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Completed: {{ $task->updated_at ? $task->updated_at->format('M d, Y H:i') : 'Unknown' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $task->status->status ?? 'Unknown' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No finished tasks yet</p>
                            @endforelse
                        </div>
                        @if(count($group['finished'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['finished'] ?? []) }} finished tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tab === 'my_requests'">
            @php $group = $taskGroups['my_requests'] ?? []; @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- New Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-2">NEW</span>
                            New Tasks
                            @if(count($group['new'] ?? []) > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500 text-white">{{ count($group['new'] ?? []) }}</span>
                            @endif
                        </h3>
                        <div class="space-y-3">
                            @forelse(collect($group['new'] ?? [])->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg cursor-pointer hover:bg-green-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $task->department->name ?? 'Unknown Dept' }}
                                            </span>
                                            <span class="text-xs text-gray-400">→</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ $task->assignedUser->department->name ?? 'Unknown Dept' }}
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $task->status->status ?? 'Unknown' }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No new tasks</p>
                            @endforelse
                        </div>
                        @if(count($group['new'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['new'] ?? []) }} new tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Pending Action Steps -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 mr-2">ACTION</span>
                            Pending Action Steps
                            @if(count($group['pending_action_steps'] ?? []) > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-500 text-white">{{ count($group['pending_action_steps'] ?? []) }}</span>
                            @endif
                        </h3>
                        <div class="space-y-3">
                            @forelse(collect($group['pending_action_steps'] ?? [])->take(5) as $actionStep)
                                <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg cursor-pointer hover:bg-orange-100 transition-colors" wire:click="showActionStepDetail({{ $actionStep->id }})">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $actionStep->user->name }}</span>
                                            <span class="text-xs text-gray-400">requests</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                @if($actionStep->isDueDateChangeRequest())
                                                    Due Date Change
                                                @elseif($actionStep->isStatusChangeRequest())
                                                    Status Change
                                                @elseif($actionStep->isResolverChangeRequest())
                                                    Resolver Change
                                                @else
                                                    Custom Request
                                                @endif
                                            </span>
                                            <span class="text-xs text-gray-400">from</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                @if($actionStep->todoList && $actionStep->todoList->assignedUser)
                                                    {{ $actionStep->todoList->assignedUser->name }}
                                                @else
                                                    Task Assignee
                                                @endif
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900">{{ Str::limit($actionStep->todoList->task ?? '', 60) }}</p>
                                        <p class="text-xs text-gray-500">
                                            Requested: {{ $actionStep->created_at->format('M d, Y H:i') }}
                                            @if($actionStep->isDueDateChangeRequest() && isset($actionStep->action_data['new_due_date']))
                                                | New due date: {{ \Carbon\Carbon::parse($actionStep->action_data['new_due_date'])->format('M d, Y H:i') }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Pending</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No pending action step requests</p>
                            @endforelse
                        </div>
                        @if(count($group['pending_action_steps'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['pending_action_steps'] ?? []) }} pending action steps →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Need to Do Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Need to Do (Next 7 Days)</h3>
                        <div class="space-y-3">
                            @forelse(collect($group['need_to_do'] ?? [])->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg cursor-pointer hover:bg-blue-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $task->status->status ?? 'Unknown' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No urgent tasks in the next 7 days</p>
                            @endforelse
                        </div>
                        @if(count($group['need_to_do'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['need_to_do'] ?? []) }} urgent tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Overdue Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Overdue Tasks</h3>
                        <div class="space-y-3">
                            @forelse(collect($group['overdue'] ?? [])->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg cursor-pointer hover:bg-red-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $task->status->status ?? 'Unknown' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No overdue tasks</p>
                            @endforelse
                        </div>
                        @if(count($group['overdue'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['overdue'] ?? []) }} overdue tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Finished Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recently Finished</h3>
                        <div class="space-y-3">
                            @forelse(collect($group['finished'] ?? [])->sortByDesc('updated_at')->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg cursor-pointer hover:bg-green-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Completed: {{ $task->updated_at ? $task->updated_at->format('M d, Y H:i') : 'Unknown' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $task->status->status ?? 'Unknown' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No finished tasks yet</p>
                            @endforelse
                        </div>
                        @if(count($group['finished'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['finished'] ?? []) }} finished tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tab === 'others'">
            @php $group = $taskGroups['others'] ?? []; @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- New Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-2">NEW</span>
                            New Tasks
                            @if(count($group['new'] ?? []) > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500 text-white">{{ count($group['new'] ?? []) }}</span>
                            @endif
                        </h3>
                        <div class="space-y-3">
                            @forelse(collect($group['new'] ?? [])->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg cursor-pointer hover:bg-green-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $task->department->name ?? 'Unknown Dept' }}
                                            </span>
                                            <span class="text-xs text-gray-400">→</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ $task->assignedUser->department->name ?? 'Unknown Dept' }}
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $task->status->status ?? 'Unknown' }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No new tasks</p>
                            @endforelse
                        </div>
                        @if(count($group['new'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['new'] ?? []) }} new tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Pending Action Steps -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 mr-2">ACTION</span>
                            Pending Action Steps
                            @if(count($group['pending_action_steps'] ?? []) > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-500 text-white">{{ count($group['pending_action_steps'] ?? []) }}</span>
                            @endif
                        </h3>
                        <div class="space-y-3">
                            @forelse(collect($group['pending_action_steps'] ?? [])->take(5) as $actionStep)
                                <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg cursor-pointer hover:bg-orange-100 transition-colors" wire:click="showActionStepDetail({{ $actionStep->id }})">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $actionStep->user->name }}</span>
                                            <span class="text-xs text-gray-400">requests</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                @if($actionStep->isDueDateChangeRequest())
                                                    Due Date Change
                                                @elseif($actionStep->isStatusChangeRequest())
                                                    Status Change
                                                @elseif($actionStep->isResolverChangeRequest())
                                                    Resolver Change
                                                @else
                                                    Custom Request
                                                @endif
                                            </span>
                                            <span class="text-xs text-gray-400">from</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                @if($actionStep->todoList && $actionStep->todoList->assignedUser)
                                                    {{ $actionStep->todoList->assignedUser->name }}
                                                @else
                                                    Task Assignee
                                                @endif
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900">{{ Str::limit($actionStep->todoList->task ?? '', 60) }}</p>
                                        <p class="text-xs text-gray-500">
                                            Requested: {{ $actionStep->created_at->format('M d, Y H:i') }}
                                            @if($actionStep->isDueDateChangeRequest() && isset($actionStep->action_data['new_due_date']))
                                                | New due date: {{ \Carbon\Carbon::parse($actionStep->action_data['new_due_date'])->format('M d, Y H:i') }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Pending</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No pending action step requests</p>
                            @endforelse
                        </div>
                        @if(count($group['pending_action_steps'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['pending_action_steps'] ?? []) }} pending action steps →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Need to Do Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Need to Do (Next 7 Days)</h3>
                        <div class="space-y-3">
                            @forelse(collect($group['need_to_do'] ?? [])->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg cursor-pointer hover:bg-blue-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $task->status->status ?? 'Unknown' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No urgent tasks in the next 7 days</p>
                            @endforelse
                        </div>
                        @if(count($group['need_to_do'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['need_to_do'] ?? []) }} urgent tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Overdue Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Overdue Tasks</h3>
                        <div class="space-y-3">
                            @forelse(collect($group['overdue'] ?? [])->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg cursor-pointer hover:bg-red-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $task->status->status ?? 'Unknown' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No overdue tasks</p>
                            @endforelse
                        </div>
                        @if(count($group['overdue'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['overdue'] ?? []) }} overdue tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Finished Tasks -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recently Finished</h3>
                        <div class="space-y-3">
                            @forelse(collect($group['finished'] ?? [])->sortByDesc('updated_at')->take(5) as $task)
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg cursor-pointer hover:bg-green-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                        <p class="text-xs text-gray-500">Completed: {{ $task->updated_at ? $task->updated_at->format('M d, Y H:i') : 'Unknown' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $task->status->status ?? 'Unknown' }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm">No finished tasks yet</p>
                            @endforelse
                        </div>
                        @if(count($group['finished'] ?? []) > 5)
                            <div class="mt-4">
                                <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">View all {{ count($group['finished'] ?? []) }} finished tasks →</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Comments Modal -->
    @if($showTaskCommentsModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="task-comments-modal" wire:ignore>
            <div class="relative min-h-screen w-full bg-white" wire:ignore.self>
                <div class="flex items-center justify-between p-4 border-b bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Task Comments</h3>
                    <button wire:click="closeTaskCommentsModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-6 max-h-screen overflow-y-auto">
                    @if($selectedTaskId)
                        <livewire:todo.task-comments :taskId="$selectedTaskId" :isModal="true" :key="'task-comments-modal-' . $selectedTaskId" />
                    @else
                        <div class="text-center py-8 text-gray-500">Select a task to view comments...</div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Floating Action Button for My Department Tasks -->
    <div class="fixed bottom-6 left-6 z-50">
        <button 
            wire:click="filterMyDepartmentTasks"
            class="{{ $isDepartmentFilterActive ? 'bg-red-600 hover:bg-red-700 focus:ring-red-300' : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-300' }} text-white rounded-full p-4 shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4"
            title="{{ $isDepartmentFilterActive ? 'Remove Department Filter' : 'View My Department Tasks' }}"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </button>
    </div>
</div>