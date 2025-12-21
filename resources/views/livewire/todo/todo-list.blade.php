<div>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-6">Todo Tasks</h1>

        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        <!-- Add New Task Form - Collapsible -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <button wire:click="toggleForm" class="flex items-center justify-between w-full text-left">
                    <h2 class="text-lg font-medium">Add New Todo Task</h2>
                    <svg class="w-5 h-5 transform transition-transform @if($isFormCollapsed) rotate-0 @else rotate-180 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>

            <div x-show="!$wire.isFormCollapsed" x-transition class="px-6 pb-6">
                <form wire:submit.prevent="createTask" class="space-y-6">
                    <!-- Job Title Section -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-md font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-tasks mr-2 text-gray-600"></i>
                            Task Details
                        </h3>
                        <div class="space-y-4">
                            <!-- Job Title (Due Time) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Job Title</label>
                                <x-select
                                    wire:model="selectedDueTimeId"
                                    placeholder="Search and select job title"
                                    :options="$this->formattedDueTimes"
                                    option-label="name"
                                    option-value="id"
                                    searchable
                                    class="mt-1"
                                />
                                @error('selectedDueTimeId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Task Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Task Description</label>
                                <textarea wire:model="task" rows="3" placeholder="Enter task description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                @error('task') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Request Flow -->
                    <div class="relative">
                        <!-- Request By and Request To Sections Side by Side -->
                        <div class="flex flex-col lg:flex-row gap-6 items-center">
                            <!-- Request By Section -->
                            <div class="bg-blue-50 border-2 border-blue-200 p-4 rounded-lg flex-1 w-full lg:w-auto">
                                <h3 class="text-md font-semibold text-blue-800 mb-3 flex items-center">
                                    <i class="fas fa-user-tag mr-2 text-blue-600"></i>
                                    Request By
                                </h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <!-- Requested By Branch -->
                                    <div>
                                        <label class="block text-sm font-medium text-blue-700">Branch</label>
                                        <select wire:model="requestedByBranchId" class="mt-1 block w-full border-blue-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                                            <option value="">Select Branch</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                                            @endforeach
                                        </select>
                                        @error('requestedByBranchId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Arrow Connector - Hidden on mobile, visible on desktop -->
                            <div class="hidden lg:flex flex-col items-center justify-center px-4">
                                <div class="flex items-center space-x-2 text-gray-500">
                                    <i class="fas fa-arrow-right text-2xl text-indigo-500"></i>
                                </div>
                                <div class="text-xs text-gray-500 mt-1 font-medium">Forward To</div>
                            </div>

                            <!-- Request To Section -->
                            <div class="bg-green-50 border-2 border-green-200 p-4 rounded-lg flex-1 w-full lg:w-auto">
                                <h3 class="text-md font-semibold text-green-800 mb-3 flex items-center">
                                    <i class="fas fa-user-check mr-2 text-green-600"></i>
                                    Assign To
                                </h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <!-- Assigned User -->
                                    <div>
                                        <label class="block text-sm font-medium text-green-700">တာဝန်ခံ</label>
                                        <x-select wire:model.live="assignedUserId" placeholder="Choose a user"
                                            :async-data="route('users.index')" option-label="name" option-value="id"
                                            class="bg-white" />
                                        @error('assignedUserId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Arrow Connector - Visible only on mobile -->
                        <div class="flex lg:hidden justify-center my-4">
                            <div class="flex items-center space-x-2 text-gray-500">
                                <div class="w-8 h-0.5 bg-gray-300"></div>
                                <i class="fas fa-arrow-down text-xl"></i>
                                <div class="w-8 h-0.5 bg-gray-300"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                            <i class="fas fa-plus mr-2"></i>
                            Create Task
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="bg-white shadow rounded-lg p-4 sm:p-6 mb-6">
            <div class="space-y-4">
                <!-- Filter Row 1: Branch and Department -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Branch Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Branch</label>
                        <select wire:model.live="filterBranchId" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Department Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Department</label>
                        <select wire:model.live="filterDepartmentId" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Filter Row 2: Status and Sort By -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                        <x-select
                            wire:model.live="selectedStatusIds"
                            placeholder="Select statuses"
                            multiselect
                            searchable
                            :options="$this->statusOptions"
                            option-label="name"
                            option-value="id"
                        />
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select wire:model.live="sortBy" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="due_date">Due Date</option>
                            <option value="created_at">Created Date</option>
                            <option value="priority">Priority</option>
                        </select>
                    </div>
                </div>

                <!-- Controls Row -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2 border-t border-gray-200">
                    <!-- Clear Filters -->
                    <button wire:click="clearFilters" class="w-full sm:w-auto px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm font-medium transition-colors duration-200">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Todo Tasks List -->
        <div class="bg-white shadow rounded-lg p-6">

            <!-- View Toggle -->
            <div class="mb-4 flex justify-between items-center">
                <h2 class="text-lg font-medium">Tasks</h2>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <button 
                            wire:click="toggleViewMode" 
                            class="px-3 py-1 text-sm rounded {{ $viewMode === 'list' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}"
                        >
                            List View
                        </button>
                        <button 
                            wire:click="toggleViewMode" 
                            class="px-3 py-1 text-sm rounded {{ $viewMode === 'calendar' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}"
                        >
                            Calendar View
                        </button>
                    </div>
                </div>
            </div>

            <!-- List View -->
            @if($viewMode === 'list')
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($todoLists as $task)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ Str::limit($task->task, 50) }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $task->dueTime->category->name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $task->dueTime->priority->level ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($task->status)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusBadgeClasses($task->status) }}">
                                                {{ $task->status->status }}
                                            </span>
                                        @else
                                            <span class="text-gray-500">Not Set</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($task->department)
                                            <div class="text-sm text-gray-900">{{ $task->department->name }}</div>
                                        @elseif($task->requestedByBranch)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ $task->requestedByBranch->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-500">Not Set</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($task->location)
                                            <span class="text-sm text-gray-900">{{ $task->location->name }}</span>
                                        @else
                                            <span class="text-gray-500">Not Set</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $task->assignedUser->name ?? 'Not Assigned' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'Not Set' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button wire:click="openTaskCommentsModal({{ $task->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">View</button>
                                        @if($task->status && in_array($task->status->status, ['Completed', 'Successed', 'Done']))
                                            <button wire:click="archiveTask({{ $task->id }})" class="text-orange-600 hover:text-orange-900">Archive</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                        No tasks found matching the current filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Calendar View -->
            @if($viewMode === 'calendar')
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Calendar View - Tasks by Due Date</h2>
                        <!-- Month Selector Dropdown -->
                        <div class="flex items-center space-x-2">
                            <label for="month-selector" class="text-sm font-medium text-gray-700">Filter by Month:</label>
                            <select 
                                id="month-selector"
                                wire:change="changeMonth($event.target.value)"
                                class="block border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                            >
                                @foreach($monthsWithTasks as $monthKey => $monthData)
                                    <option value="{{ $monthKey }}" {{ $monthKey === $selectedMonth ? 'selected' : '' }}>
                                        {{ $monthData['label'] }} ({{ $monthData['count'] }} tasks)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-7 gap-1 mb-2">
                        @php
                            $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        @endphp
                        @foreach($daysOfWeek as $day)
                            <div class="text-center font-semibold text-gray-600 py-2">{{ $day }}</div>
                        @endforeach
                    </div>
                    
                    @php
                        $calendarStartDate = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
                        $currentDate = $calendarStartDate->copy();
                        $endDate = $calendarStartDate->copy()->endOfMonth();
                        $firstDayOfWeek = $calendarStartDate->dayOfWeek;
                    @endphp
                    
                    <div class="grid grid-cols-7 gap-1">
                        <!-- Empty cells for days before the first day of the month -->
                        @for($i = 0; $i < $firstDayOfWeek; $i++)
                            <div class="h-24 bg-gray-100 rounded"></div>
                        @endfor
                        
                        <!-- Days of the month -->
                        @while($currentDate->lte($endDate))
                            @php
                                $dateKey = $currentDate->format('Y-m-d');
                                $isToday = $currentDate->isToday();
                                $hasTasks = isset($calendarTasks[$dateKey]) && count($calendarTasks[$dateKey]) > 0;
                            @endphp
                            <div class="h-24 bg-white border border-gray-200 rounded p-1 {{ $isToday ? 'bg-blue-50 border-blue-300' : '' }}">
                                <div class="text-xs font-medium text-gray-600 mb-1">{{ $currentDate->format('j') }}</div>
                                @if($hasTasks)
                                    <div class="space-y-1">
                                        @foreach($calendarTasks[$dateKey] as $categoryId => $categoryData)
                                            <button 
                                                wire:click="selectCategory('{{ $dateKey }}', {{ $categoryId }})"
                                                class="w-full text-left text-xs bg-indigo-100 hover:bg-indigo-200 text-indigo-800 px-1 py-0.5 rounded truncate"
                                                title="{{ $categoryData['name'] }}: {{ $categoryData['count'] }} tasks"
                                            >
                                                {{ $categoryData['name'] }} ({{ $categoryData['count'] }})
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @php $currentDate = $currentDate->addDay(); @endphp
                        @endwhile
                    </div>
                </div>
            @endif
        </div>

    <!-- Category Tasks Modal -->
    @if($selectedDate && $selectedCategory)
        @php
            $dateKey = is_string($selectedDate) ? $selectedDate : ($selectedDate ? $selectedDate->format('Y-m-d') : null);
            $categoryKey = (int)$selectedCategory;
            $categoryExists = $dateKey && isset($calendarTasks[$dateKey]) && isset($calendarTasks[$dateKey][$categoryKey]);
        @endphp
        @if($categoryExists)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="category-modal">
                <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Tasks for {{ \Carbon\Carbon::parse($dateKey)->format('F j, Y') }} - 
                                {{ $calendarTasks[$dateKey][$categoryKey]['name'] ?? 'Unknown Category' }}
                            </h3>
                            <button wire:click="closeCategoryModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($calendarTasks[$dateKey][$categoryKey]['tasks'] ?? [] as $task)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ Str::limit($task->task, 50) }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $task->dueTime->category->name ?? 'N/A' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $task->dueTime->priority->level ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($task->status)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusBadgeClasses($task->status) }}">
                                                        {{ $task->status->status }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-500">Not Set</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($task->department)
                                                    <div class="text-sm text-gray-900">{{ $task->department->name }}</div>
                                                @elseif($task->requestedByBranch)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $task->requestedByBranch->name }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-500">Not Set</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($task->location)
                                                    <span class="text-sm text-gray-900">{{ $task->location->name }}</span>
                                                @else
                                                    <span class="text-gray-500">Not Set</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $task->assignedUser->name ?? 'Not Assigned' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button wire:click="openTaskCommentsModal({{ $task->id }})" class="text-indigo-600 hover:text-indigo-900">View Details</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                                No tasks found for this date and category.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end mt-4">
                            <button wire:click="closeCategoryModal" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="error-modal">
                <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <p class="text-gray-700 mb-4">The data for this date could not be found. This might happen if the data was updated. Please close and try again.</p>
                        <button wire:click="closeCategoryModal" class="w-full bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
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
                    <!-- Debug info -->
                    {{-- <div class="mb-4 p-2 bg-yellow-100 text-yellow-800 text-sm">
                        Debug: selectedTaskId = {{ $selectedTaskId ?? 'null' }}, showTaskCommentsModal = {{ $showTaskCommentsModal ? 'true' : 'false' }}
                    </div> --}}
                    
                    @if($selectedTaskId)
                        <livewire:todo.task-comments :taskId="$selectedTaskId" :isModal="true" :key="'task-comments-modal-' . $selectedTaskId" />
                    @else
                        <div class="text-center py-8 text-gray-500">Select a task to view comments...</div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>


