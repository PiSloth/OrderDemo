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
                <form wire:submit.prevent="createTask" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Job Title (Due Time) - Full width -->
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

                        <!-- Selection boxes in 2 columns -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Requested By Branch -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Requested By Branch</label>
                                <select wire:model="requestedByBranchId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ ucfirst($branch->name) }}</option>
                                    @endforeach
                                </select>
                                @error('requestedByBranchId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Location -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Location</label>
                                <select wire:model="locationId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                @error('locationId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Department -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <select wire:model="departmentId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->location->name ?? 'N/A' }})</option>
                                    @endforeach
                                </select>
                                @error('departmentId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Assigned User -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Assigned User (Optional)</label>
                                <select wire:model="assignedUserId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('assignedUserId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Task - Full width -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Task</label>
                            <textarea wire:model="task" rows="4" placeholder="Enter task description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            @error('task') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            Add Task
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
                        <select wire:model.live="filterStatusId" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->status }}</option>
                            @endforeach
                        </select>
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
                    <!-- Daily Tasks Toggle -->
                    <label class="flex items-center text-sm">
                        <input type="checkbox" wire:model.live="showDailyTasks" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <span class="ml-2 text-gray-700">Show Daily Tasks Only</span>
                    </label>

                    <!-- Clear Filters -->
                    <button wire:click="clearFilters" class="w-full sm:w-auto px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm font-medium transition-colors duration-200">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Todo Tasks List -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="mb-4">
                <nav class="flex flex-wrap gap-2 sm:gap-4">
                    <button 
                        wire:click="$set('activeTab', 'active')" 
                        :class="activeTab === 'active' ? 'bg-indigo-600 text-white px-3 sm:px-4 py-2 rounded text-sm' : 'bg-gray-200 text-gray-700 px-3 sm:px-4 py-2 rounded text-sm'"
                    >
                        Active Tasks
                    </button>
                    <button 
                        wire:click="$set('activeTab', 'archived')" 
                        :class="activeTab === 'archived' ? 'bg-indigo-600 text-white px-3 sm:px-4 py-2 rounded text-sm' : 'bg-gray-200 text-gray-700 px-3 sm:px-4 py-2 rounded text-sm'"
                    >
                        Archived Tasks
                    </button>
                </nav>
            </div>

            <!-- View Style Toggle -->
            <div class="mb-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-0">
                <div class="flex items-center justify-center sm:justify-end space-x-2">
                    <span class="text-sm text-gray-600">View:</span>
                    <div class="flex rounded-md overflow-hidden border border-gray-300">
                        <button 
                            wire:click="toggleViewStyle" 
                            :class="$wire.viewStyle === 'card' ? 'bg-indigo-600 text-white px-3 py-1 text-sm' : 'bg-gray-200 text-gray-700 px-3 py-1 text-sm'"
                        >
                            Card
                        </button>
                        <button 
                            wire:click="toggleViewStyle" 
                            :class="$wire.viewStyle === 'table' ? 'bg-indigo-600 text-white px-3 py-1 text-sm' : 'bg-gray-200 text-gray-700 px-3 py-1 text-sm'"
                        >
                            Table
                        </button>
                    </div>
                </div>
            </div>

            @if($activeTab === 'active')
                <h2 class="text-lg font-medium mb-4">Active Todo Tasks</h2>
                @if($todoLists->count() > 0)
                    @if($viewStyle === 'card')
                        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach($todoLists as $todo)
                                <div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
                                    <!-- Header with Job Title and Status -->
                                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-lg font-bold text-white mb-1 truncate">
                                                {{ $todo->dueTime->category->name ?? 'N/A' }}
                                            </h3>
                                            <p class="text-indigo-100 text-xs">
                                                Priority: {{ $todo->dueTime->priority->level ?? 'N/A' }} • {{ $todo->dueTime->duration }}h
                                            </p>
                                        </div>
                                        <div class="ml-2 flex-shrink-0">
                                            @if($todo->status)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $this->getStatusBadgeClasses($todo->status) }}">
                                                    {{ $todo->status->status }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                                    Not Set
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Main Content -->
                                <div class="p-4">
                                    <!-- Task Description -->
                                    <div class="mb-3">
                                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Task Details</h4>
                                        <p class="text-gray-700 leading-relaxed">{{ $todo->task }}</p>
                                    </div>

                                    <!-- Job Description (Highlighted) -->
                                    @if($todo->dueTime->description)
                                        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-4">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-amber-800">Job Description</p>
                                                    <p class="mt-1 text-sm text-amber-700">{{ $todo->dueTime->description }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Request Branch (Highlighted) -->
                                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-blue-800">Requested By Branch</p>
                                                <p class="mt-1 text-sm text-blue-700 font-semibold">{{ $todo->requestedByBranch->name ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Task Details Grid (Compact) -->
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="bg-gray-50 p-2 rounded">
                                            <span class="font-medium text-gray-600 block">Due</span>
                                            <span class="text-gray-900">{{ $todo->due_date->format('M d, H:i') }}</span>
                                        </div>
                                        <div class="bg-gray-50 p-2 rounded">
                                            <span class="font-medium text-gray-600 block">Assigned</span>
                                            <span class="text-gray-900">{{ strlen($todo->assignedUser->name ?? 'Not Assigned') > 10 ? substr($todo->assignedUser->name ?? 'Not Assigned', 0, 10) . '...' : $todo->assignedUser->name ?? 'Not Assigned' }}</span>
                                        </div>
                                        <div class="bg-gray-50 p-2 rounded">
                                            <span class="font-medium text-gray-600 block">Created</span>
                                            <span class="text-gray-900">{{ strlen($todo->createdByUser->name) > 10 ? substr($todo->createdByUser->name, 0, 10) . '...' : $todo->createdByUser->name }}</span>
                                        </div>
                                        <div class="bg-gray-50 p-2 rounded">
                                            <span class="font-medium text-gray-600 block">Location</span>
                                            <span class="text-gray-900">{{ $todo->location->name }} / {{ $todo->department->name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions Footer -->
                                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <a href="{{ route('task_comments', $todo->id) }}" class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors duration-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03 8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                            {{ $todo->comments->count() }}
                                        </a>

                                        <div class="flex space-x-1">
                                            @if(!$todo->status)
                                                <button wire:click="closeTask({{ $todo->id }})" class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors duration-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Close
                                                </button>
                                            @else
                                                <button wire:click="archiveTask({{ $todo->id }})" class="inline-flex items-center px-3 py-1 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors duration-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                                    </svg>
                                                    Archive
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @else
                        <!-- Table View -->
                        <div class="overflow-x-auto -mx-4 sm:mx-0">
                            <div class="inline-block min-w-full align-middle">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Branch</th>
                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description / Priority</th>
                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($todoLists as $todo)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $todo->dueTime->category->name ?? 'N/A' }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ $todo->dueTime->priority->level ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900 font-semibold">
                                                        {{ $todo->requestedByBranch->name ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="px-3 sm:px-6 py-4 max-w-xs">
                                                    <div class="text-sm text-gray-900">
                                                        {{ $todo->dueTime->description ?? 'No description' }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        Priority: {{ $todo->dueTime->priority->level ?? 'N/A' }} • Duration: {{ $todo->dueTime->duration }}h
                                                    </div>
                                                </td>
                                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex flex-col sm:flex-row gap-1 sm:gap-2">
                                                        <a href="{{ route('task_comments', $todo->id) }}" class="text-blue-600 hover:text-blue-900 text-xs sm:text-sm">
                                                            Comments ({{ $todo->comments->count() }})
                                                        </a>
                                                        @if(!$todo->status)
                                                            <button wire:click="closeTask({{ $todo->id }})" class="text-green-600 hover:text-green-900 text-xs sm:text-sm">
                                                                Close
                                                            </button>
                                                        @else
                                                            <button wire:click="archiveTask({{ $todo->id }})" class="text-red-600 hover:text-red-900 text-xs sm:text-sm">
                                                                Archive
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 text-center py-8">No active todo tasks found. Create your first task above!</p>
                @endif
            @else
                <h2 class="text-lg font-medium mb-4">Archived Todo Tasks History</h2>
                @if($archivedTasks->count() > 0)
                    @if($viewStyle === 'card')
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @foreach($archivedTasks as $todo)
                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden opacity-75">
                                    <!-- Header with Job Title and Status -->
                                    <div class="bg-gradient-to-r from-gray-500 to-gray-600 px-6 py-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h3 class="text-xl font-bold text-white mb-1">
                                                {{ $todo->dueTime->category->name ?? 'N/A' }}
                                            </h3>
                                            <p class="text-gray-200 text-sm">
                                                Priority: {{ $todo->dueTime->priority->level ?? 'N/A' }} • Duration: {{ $todo->dueTime->duration }}h
                                            </p>
                                        </div>
                                        <div class="ml-4">
                                            @if($todo->status)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $this->getStatusBadgeClasses($todo->status) }}">
                                                    {{ $todo->status->status }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                                    Not Set
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Main Content -->
                                <div class="p-6">
                                    <!-- Task Description -->
                                    <div class="mb-3">
                                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Task Details</h4>
                                        <p class="text-gray-700 leading-relaxed">{{ $todo->task }}</p>
                                    </div>

                                    <!-- Job Description (Highlighted) -->
                                    @if($todo->dueTime->description)
                                        <div class="bg-amber-50 border-l-4 border-amber-400 p-3 mb-3">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-amber-800">Job Description</p>
                                                    <p class="mt-1 text-sm text-amber-700">{{ $todo->dueTime->description }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Request Branch (Highlighted) -->
                                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-blue-800">Requested By Branch</p>
                                                <p class="mt-1 text-sm text-blue-700 font-semibold">{{ $todo->requestedByBranch->name ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Task Details Grid -->
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="font-medium text-gray-600 block">Due Date</span>
                                            <span class="text-gray-900">{{ $todo->due_date->format('M d, Y H:i') }}</span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="font-medium text-gray-600 block">Assigned To</span>
                                            <span class="text-gray-900">{{ $todo->assignedUser->name ?? 'Not Assigned' }}</span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="font-medium text-gray-600 block">Created By</span>
                                            <span class="text-gray-900">{{ $todo->createdByUser->name }}</span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="font-medium text-gray-600 block">Location</span>
                                            <span class="text-gray-900">{{ $todo->location->name }}</span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="font-medium text-gray-600 block">Department</span>
                                            <span class="text-gray-900">{{ $todo->department->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <span class="font-medium text-gray-600 block">Archived Date</span>
                                            <span class="text-gray-900">{{ $todo->deleted_at ? $todo->deleted_at->format('M d, Y H:i') : 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions Footer -->
                                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                                    <div class="flex justify-end">
                                        <button wire:click="restoreTask({{ $todo->id }})" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                            </svg>
                                            Unarchive
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @else
                        <!-- Table View for Archived Tasks -->
                        <div class="overflow-x-auto -mx-4 sm:mx-0">
                            <div class="inline-block min-w-full align-middle">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Branch</th>
                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description / Priority</th>
                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Archived Date</th>
                                            <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($archivedTasks as $todo)
                                            <tr class="hover:bg-gray-50 opacity-75">
                                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $todo->dueTime->category->name ?? 'N/A' }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ $todo->dueTime->priority->level ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900 font-semibold">
                                                        {{ $todo->requestedByBranch->name ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="px-3 sm:px-6 py-4 max-w-xs">
                                                    <div class="text-sm text-gray-900">
                                                        {{ $todo->dueTime->description ?? 'No description' }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        Priority: {{ $todo->dueTime->priority->level ?? 'N/A' }} • Duration: {{ $todo->dueTime->duration }}h
                                                    </div>
                                                </td>
                                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        {{ $todo->deleted_at ? $todo->deleted_at->format('M d, Y H:i') : 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button wire:click="restoreTask({{ $todo->id }})" class="text-blue-600 hover:text-blue-900 text-xs sm:text-sm">
                                                        Unarchive
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 text-center py-8">No archived tasks found.</p>
                @endif
            @endif
        </div>
    </div>
</div>
