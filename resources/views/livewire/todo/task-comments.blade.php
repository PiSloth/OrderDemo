<div class="{{ $isModal ? '' : 'max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8' }}">
    @if(!$isModal)
    <div class="mb-6">
        <a href="{{ route('todo_list') }}" wire:navigate class="text-blue-600 hover:text-blue-800">&larr; Back to Todo List</a>
    </div>
    @endif

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

        <!-- Debug flags for inner modals -->
        <div class="mb-2 text-xs text-gray-600 bg-yellow-100 p-2 rounded">
            StatusModal: {{ $showStatusRequestModal ? 'on' : 'off' }},
            ResolverModal: {{ $showResolverChangeModal ? 'on' : 'off' }},
            CustomModal: {{ $showCustomRequestModal ? 'on' : 'off' }},
            NegotiationModal: {{ $showNegotiationModal ? 'on' : 'off' }}
        </div>

        @if($task)
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h1 class="text-2xl font-bold mb-4">Comments for: {{ $task->task }}</h1>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm mb-6">
                    <div>
                        <span class="font-medium">Job Title:</span>
                        {{ $task->dueTime->category->name ?? 'N/A' }} - {{ $task->dueTime->priority->level ?? 'N/A' }} ({{ $task->dueTime->duration }}h)
                    </div>
                    <div>
                        <span class="font-medium">Status:</span>
                        @if($task->status)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: {{ $task->status->color_code ?? '#e5e7eb' }}; color: #000;">
                                {{ $task->status->status }}
                            </span>
                        @else
                            <span class="text-gray-500">Not Set</span>
                        @endif
                    </div>
                    <div>
                        <span class="font-medium">Due Date:</span>
                        {{ $task->due_date->format('M d, Y H:i') }}
                    </div>
                    <div>
                        <span class="font-medium">Assigned To:</span>
                        @if($task->assignedUser)
                            {{ $task->assignedUser->name }}
                        @else
                            <span class="text-gray-500">Not Assigned</span>
                        @endif
                    </div>
                    <div>
                        <span class="font-medium">Department/Branch:</span>
                        @if($task->department)
                            @php
                                $currentUser = Auth::user();
                                $isUserDepartment = $currentUser && $currentUser->department_id == $task->department->id;
                            @endphp
                            @if($isUserDepartment)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    US
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $task->department->name }}
                                </span>
                            @endif
                        @elseif($task->requestedByBranch)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $task->requestedByBranch->name }}
                            </span>
                        @else
                            <span class="text-gray-500">Not Set</span>
                        @endif
                    </div>
                    <div>
                        <span class="font-medium">Created By:</span>
                        {{ $task->createdByUser->name }}
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <span class="font-medium">Location:</span>
                        {{ $task->location ? $task->location->name : 'N/A' }}
                    </div>
                </div>

                <!-- Timeline Progress Bar -->
                @php
                    $now = now();
                    $dueDate = $task->due_date;
                    $createdAt = $task->created_at;
                    $totalDuration = $task->dueTime->duration ?? 0; // hours

                    // Calculate hours remaining
                    $hoursRemaining = $now->diffInHours($dueDate, false); // false to get negative if overdue
                    $isOverdue = $hoursRemaining < 0;

                    // Calculate progress percentage (time elapsed vs total duration)
                    $totalScheduledHours = $createdAt->diffInHours($dueDate, false);
                    $elapsedHours = $createdAt->diffInHours($now, false);
                    $progressPercentage = $totalScheduledHours > 0 ? min(100, max(0, ($elapsedHours / $totalScheduledHours) * 100)) : 0;

                    // Determine progress bar color based on status
                    $progressColor = 'bg-blue-500';
                    if ($isOverdue) {
                        $progressColor = 'bg-red-500';
                    } elseif ($progressPercentage > 80) {
                        $progressColor = 'bg-yellow-500';
                    } elseif ($progressPercentage > 60) {
                        $progressColor = 'bg-orange-500';
                    }
                @endphp

                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700">Timeline Progress</span>
                        <div class="text-xs text-gray-600">
                            <span class="font-medium">{{ $totalDuration }}h</span> scheduled
                            @if($isOverdue)
                                <span class="text-red-600 font-medium">• {{ abs($hoursRemaining) }}h overdue</span>
                            @else
                                <span class="text-green-600 font-medium">• {{ $hoursRemaining }}h remaining</span>
                            @endif
                        </div>
                    </div>

                    <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                        <div class="{{ $progressColor }} h-3 rounded-full transition-all duration-300 ease-out" style="width: {{ $progressPercentage }}%"></div>
                    </div>

                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Started: {{ $createdAt->format('M d, H:i') }}</span>
                        <span>{{ number_format($progressPercentage, 1) }}% complete</span>
                        <span>Due: {{ $dueDate->format('M d, H:i') }}</span>
                    </div>
                </div>

                @if($task->dueTime->description)
                    <div class="text-sm text-gray-600 mb-4">
                        <span class="font-medium">Description:</span>
                        {{ $task->dueTime->description }}
                    </div>
                @endif

                @if($this->canAcknowledgeTask($task))
                    <div class="flex justify-end">
                        <button wire:click="acknowledgeTask({{ $task->id }})"
                                wire:confirm="Are you sure you want to acknowledge this task?"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Acknowledge Task
                        </button>
                    </div>
                @endif
            </div>

            <!-- Comments Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Comments ({{ $comments->sum(function($comment) { return 1 + $comment->replies->count(); }) }})</h2>
                    <button
                        wire:click="copyUrl"
                        class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700 text-sm"
                    >
                        Copy Page URL
                    </button>
                </div>

                <!-- Add Comment Form -->
                <div class="mb-6 border-b pb-6">
                    @if($replyToCommentId)
                        @php
                            $replyComment = \App\Models\TaskComment::find($replyToCommentId);
                        @endphp
                        <div class="mb-3 p-2 bg-blue-50 rounded flex justify-between items-center">
                            <span class="text-sm text-blue-700">Replying to: {{ strlen($replyComment->comment) > 50 ? substr($replyComment->comment, 0, 50) . '...' : $replyComment->comment }}</span>
                            <button wire:click="cancelReply" class="text-blue-600 hover:text-blue-800 text-sm">Cancel</button>
                        </div>
                    @endif

                    <form wire:submit.prevent="addComment" class="space-y-3">
                        <div>
                            <textarea
                                wire:model="newComment"
                                placeholder="Add a comment..."
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                rows="3"
                            ></textarea>
                            @error('newComment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model.live="isActionStep" wire:change="$refresh" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Action Step</span>
                            </label>

                            @if($isActionStep)
                                @php
                                    $currentUser = Auth::user();
                                    $canRequestDueStatus = $task->assignedUser && $currentUser && $task->assignedUser->department_id === $currentUser->department_id;
                                @endphp
                                <div class="flex space-x-2">
                                    <button
                                        type="button"
                                        wire:click="requestDueDateChange('{{ now()->addHours(24)->format('Y-m-d\TH:i') }}')"
                                        class="text-sm bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700"
                                        @if(!$canRequestDueStatus)
                                            disabled
                                            title="Only users from the assigned user's department can request due date changes"
                                        @endif
                                    >
                                        Request 24h Extension
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="openCustomRequestModal"
                                        class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"
                                        @if(!$canRequestDueStatus)
                                            disabled
                                            title="Only users from the assigned user's department can request due date changes"
                                        @endif
                                    >
                                        Custom Due Date Request
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="openStatusRequestModal"
                                        class="text-sm bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700"
                                        @if(!$canRequestDueStatus)
                                            disabled
                                            title="Only users from the assigned user's department can request status changes"
                                        @endif
                                    >
                                        Request Status Change
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="openResolverChangeModal"
                                        class="text-sm bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700"
                                        @if(!$canRequestDueStatus)
                                            disabled
                                            title="Only users from the assigned user's department can request resolver changes"
                                        @endif
                                    >
                                        Request Resolver Change
                                    </button>
                                </div>
                            @endif

                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm">
                                {{ $replyToCommentId ? 'Reply' : 'Add Comment' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Comments List -->
                <div class="space-y-6">
                    @if($comments->count() > 0)
                        @foreach($comments as $comment)
                            <div id="comment-{{ $comment->id }}" class="comment border-b border-gray-100 pb-6 last:border-b-0">
                                @include('livewire.todo.partials.comment', ['comment' => $comment, 'level' => 0])
                                <div class="mt-2 ml-11">
                                    <button
                                        onclick="copyToClipboard('{{ url("/todo/comments/{$taskId}#comment-{$comment->id}") }}')"
                                        class="text-xs text-gray-500 hover:text-gray-700"
                                    >
                                        Copy Link
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 text-center py-8">No comments yet. Be the first to comment!</p>
                    @endif
                </div>
            </div>
        @else
            <div class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-500 text-center py-8">Task not found.</p>
            </div>
        @endif

    <!-- Negotiation Modal -->
    <div
        wire:ignore.self
        wire:key="negotiation-modal-{{ $negotiationCommentId }}"
        class="fixed inset-0 {{ $isModal ? '' : 'bg-gray-600 bg-opacity-50' }} overflow-y-auto h-full w-full z-50 @if(!$showNegotiationModal) hidden @endif"
        id="negotiation-modal"
        style="z-index: 99999;"
    >
        @if($showNegotiationModal)
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Propose Counter-Offer</h3>
                    <form wire:submit.prevent="proposeCounterOffer" class="space-y-4">
                        <div>
                            <label for="proposed_date" class="block text-sm font-medium text-gray-700">Proposed Due Date</label>
                            <input
                                type="datetime-local"
                                id="proposed_date"
                                wire:model="proposedDate"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            >
                            @error('proposedDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="negotiation_reason" class="block text-sm font-medium text-gray-700">Reason for Counter-Offer</label>
                            <textarea
                                id="negotiation_reason"
                                wire:model="negotiationReason"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Explain why you're proposing this date..."
                                required
                            ></textarea>
                            @error('negotiationReason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                wire:click="closeNegotiationModal"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
                            >
                                Propose Counter-Offer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- Custom Due Date Request Modal -->
    <div
        class="fixed inset-0 {{ $isModal ? '' : 'bg-gray-600 bg-opacity-50' }} overflow-y-auto h-full w-full z-50"
        id="custom-request-modal"
        style="z-index: 99999; display: {{ $showCustomRequestModal ? 'block' : 'none' }};"
    >
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Request Custom Due Date</h3>
                    <form wire:submit.prevent="submitCustomDueDateRequest" class="space-y-4">
                        <div>
                            <label for="custom_due_date" class="block text-sm font-medium text-gray-700">Requested Due Date</label>
                            <input
                                type="datetime-local"
                                id="custom_due_date"
                                wire:model="customDueDate"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            >
                            @error('customDueDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="custom_request_reason" class="block text-sm font-medium text-gray-700">Reason for Request (Optional)</label>
                            <textarea
                                id="custom_request_reason"
                                wire:model="customRequestReason"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Explain why you need this due date change..."
                            ></textarea>
                            @error('customRequestReason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                wire:click="closeCustomRequestModal"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
                            >
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>

    <!-- Status Change Request Modal -->
    <div
        class="fixed inset-0 {{ $isModal ? '' : 'bg-gray-600 bg-opacity-50' }} overflow-y-auto h-full w-full z-50"
        id="status-request-modal"
        style="z-index: 99999; display: {{ $showStatusRequestModal ? 'block' : 'none' }};"
    >
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Request Status Change</h3>
                    <form wire:submit.prevent="submitStatusChangeRequest" class="space-y-4">
                        <div>
                            <label for="requested_status" class="block text-sm font-medium text-gray-700">Requested Status</label>
                            <select
                                id="requested_status"
                                wire:model.live="requestedStatus"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            >
                                <option value="">Select Status</option>
                                @foreach(\App\Models\TodoStatus::all() as $status)
                                    <option value="{{ $status->id }}">{{ $status->status }}</option>
                                @endforeach
                            </select>
                            @error('requestedStatus') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="status_request_reason" class="block text-sm font-medium text-gray-700">Reason for Request (Optional)</label>
                            <textarea
                                id="status_request_reason"
                                wire:model="statusRequestReason"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Explain why you need this status change..."
                            ></textarea>
                            @error('statusRequestReason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" wire:click="closeStatusRequestModal" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                            <button
                                type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
                            >
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>

   

    <!-- Resolver Change Request Modal -->
    <div
        class="fixed inset-0 {{ $isModal ? '' : 'bg-gray-600 bg-opacity-50' }} overflow-y-auto h-full w-full z-50"
        id="resolver-change-modal"
        style="z-index: 99999; display: {{ $showResolverChangeModal ? 'block' : 'none' }};"
    >
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Request Resolver Change</h3>
                <form wire:submit.prevent="submitResolverChangeRequest" class="space-y-4">
                    <div>
                        <label for="requested_resolver" class="block text-sm font-medium text-gray-700">New Resolver</label>
                        <x-select
                            wire:model.live="requestedResolverId"
                            placeholder="Select new resolver"
                            :async-data="route('users.index')"
                            option-label="name"
                            option-value="id"
                            class="mt-1"
                        />
                        @error('requestedResolverId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="resolver_change_reason" class="block text-sm font-medium text-gray-700">Reason for Request (Optional)</label>
                        <textarea
                            id="resolver_change_reason"
                            wire:model="resolverChangeReason"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Explain why you need to change the resolver..."
                        ></textarea>
                        @error('resolverChangeReason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="closeResolverChangeModal" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                        <button
                            type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
                        >
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(!$isModal)
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show a temporary success message
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-[70]';
                notification.textContent = 'URL copied to clipboard!';
                document.body.appendChild(notification);
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
            });
        }

        // Listen for URL copied event from Livewire
        document.addEventListener('livewire:loaded', () => {
            Livewire.on('url-copied', (data) => {
                copyToClipboard(data.url);
            });
        });

        // Browser Notifications
        let lastNotificationCheck = new Date().toISOString();
        let notificationCheckInterval;

        function requestNotificationPermission() {
            if ('Notification' in window) {
                if (Notification.permission === 'default') {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === 'granted') {
                            console.log('Notification permission granted');
                        }
                    });
                }
            }
        }

        function showBrowserNotification(title, body, icon = null) {
            if ('Notification' in window && Notification.permission === 'granted') {
                const options = {
                    body: body,
                    icon: icon || '/favicon.ico',
                    badge: '/favicon.ico',
                    tag: 'task-comment-notification',
                    requireInteraction: false,
                    silent: false
                };

                const notification = new Notification(title, options);

                // Auto close after 5 seconds
                setTimeout(() => {
                    notification.close();
                }, 5000);

                // Click to focus window
                notification.onclick = function() {
                    window.focus();
                    notification.close();
                };
            }
        }

        function checkForNewNotifications() {
            fetch('/api/task-notifications/check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    last_check: lastNotificationCheck
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notification => {
                        showBrowserNotification(
                            notification.title,
                            notification.message
                        );
                    });
                }
                lastNotificationCheck = new Date().toISOString();
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
        }

        // Initialize notifications when page loads
        document.addEventListener('DOMContentLoaded', function() {
            requestNotificationPermission();

            // Check for notifications every 30 seconds
            notificationCheckInterval = setInterval(checkForNewNotifications, 30000);

            // Initial check
            setTimeout(checkForNewNotifications, 2000);
        });

        // Clean up interval when page unloads
        window.addEventListener('beforeunload', function() {
            if (notificationCheckInterval) {
                clearInterval(notificationCheckInterval);
            }
        });
    </script>
    @endif
</div>
