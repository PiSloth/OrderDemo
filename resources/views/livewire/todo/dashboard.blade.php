<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Todo Dashboard</h1>
        <p class="mt-2 text-sm text-gray-600">Overview of all your tasks and their current status</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <!-- Unfinished Tasks -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Unfinished Tasks</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['unfinished'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

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
    </div>

    <!-- Task Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Unfinished Tasks -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Unfinished Tasks</h3>
                <div class="space-y-3">
                    @forelse($unfinishedTasks->take(5) as $task)
                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg cursor-pointer hover:bg-yellow-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                <p class="text-xs text-gray-500">
                                    Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                {{ $task->status->status ?? 'Unknown' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No unfinished tasks</p>
                    @endforelse
                </div>
                @if($unfinishedTasks->count() > 5)
                    <div class="mt-4">
                        <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">
                            View all {{ $unfinishedTasks->count() }} unfinished tasks →
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Need to Do Tasks -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Need to Do (Next 7 Days)</h3>
                <div class="space-y-3">
                    @forelse($needToDoTasks->take(5) as $task)
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg cursor-pointer hover:bg-blue-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                <p class="text-xs text-gray-500">
                                    Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $task->status->status ?? 'Unknown' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No urgent tasks in the next 7 days</p>
                    @endforelse
                </div>
                @if($needToDoTasks->count() > 5)
                    <div class="mt-4">
                        <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">
                            View all {{ $needToDoTasks->count() }} urgent tasks →
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Overdue Tasks -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Overdue Tasks</h3>
                <div class="space-y-3">
                    @forelse($passedTasks->take(5) as $task)
                        <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg cursor-pointer hover:bg-red-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                <p class="text-xs text-gray-500">
                                    Due: {{ $task->due_date ? $task->due_date->format('M d, Y H:i') : 'No due date' }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ $task->status->status ?? 'Unknown' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No overdue tasks</p>
                    @endforelse
                </div>
                @if($passedTasks->count() > 5)
                    <div class="mt-4">
                        <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">
                            View all {{ $passedTasks->count() }} overdue tasks →
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Finished Tasks -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recently Finished</h3>
                <div class="space-y-3">
                    @forelse($finishedTasks->sortByDesc('updated_at')->take(5) as $task)
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg cursor-pointer hover:bg-green-100 transition-colors" wire:click="showTaskDetail({{ $task->id }})">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $task->task }}</p>
                                <p class="text-xs text-gray-500">
                                    Completed: {{ $task->updated_at ? $task->updated_at->format('M d, Y H:i') : 'Unknown' }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $task->status->status ?? 'Unknown' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No finished tasks yet</p>
                    @endforelse
                </div>
                @if($finishedTasks->count() > 5)
                    <div class="mt-4">
                        <a href="{{ route('todo_list') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-500">
                            View all {{ $finishedTasks->count() }} finished tasks →
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Task Detail Modal -->
    @if($showTaskModal && $selectedTask)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="task-modal">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Task Details</h3>
                    <button wire:click="closeTaskModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Task</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $selectedTask->task }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ optional($selectedTask->status)->color_code ? 'bg-'.optional($selectedTask->status)->color_code.'-100 text-'.optional($selectedTask->status)->color_code.'-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ optional($selectedTask->status)->status ?? 'Unknown' }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <p class="mt-1 text-sm text-gray-900">{{ optional(optional($selectedTask->dueTime)->priority)->level ?? 'Not set' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Due Date</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedTask->due_date ? $selectedTask->due_date->format('M d, Y H:i') : 'No due date' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <p class="mt-1 text-sm text-gray-900">{{ optional(optional($selectedTask->dueTime)->category)->name ?? 'Not set' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assigned User</label>
                            <p class="mt-1 text-sm text-gray-900">{{ optional($selectedTask->assignedUser)->name ?? 'Not assigned' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Created By</label>
                            <p class="mt-1 text-sm text-gray-900">{{ optional($selectedTask->createdByUser)->name ?? 'Unknown' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Requested Branch</label>
                            <p class="mt-1 text-sm text-gray-900">{{ optional($selectedTask->requestedByBranch)->name ?? 'Not set' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assigned Department</label>
                            <p class="mt-1 text-sm text-gray-900">{{ optional($selectedTask->department)->name ?? 'Not set' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Created At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedTask->created_at->format('M d, Y H:i') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Updated At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedTask->updated_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button wire:click="closeTaskModal" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>