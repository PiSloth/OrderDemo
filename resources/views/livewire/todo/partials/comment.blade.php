@props(['comment', 'level' => 0])

<div class="comment {{ $level > 0 ? 'ml-' . ($level * 4) . ' border-l-2 border-gray-200 pl-4' : '' }}">
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-gray-700">
                    {{ substr($comment->user->name, 0, 1) }}
                </span>
            </div>
        </div>
        <div class="flex-1">
            <div class="flex items-center space-x-2">
                <span class="font-medium text-gray-900">{{ $comment->user->name }}</span>
                <span class="text-sm text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                @if($comment->isActionStep())
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                        @if($comment->isPendingAction()) bg-yellow-100 text-yellow-800
                        @elseif($comment->isAcceptedAction()) bg-green-100 text-green-800
                        @else bg-red-100 text-red-800 @endif">
                        Action Step
                        @if($comment->isPendingAction()) - Pending
                        @elseif($comment->isAcceptedAction()) - Accepted
                        @else - Rejected @endif
                    </span>
                @endif
            </div>

            <div class="mt-1 text-gray-700 {{ $comment->isActionStep() ? 'bg-yellow-50 p-2 rounded border-l-4 border-yellow-400' : '' }}">
                {{ $comment->comment }}

                @if($comment->isActionStep() && isset($comment->action_data['type']) && $comment->action_data['type'] === 'due_date_change')
                    <div class="mt-2 text-sm">
                        <strong>Due Date Change Request:</strong><br>
                        From: {{ \Carbon\Carbon::parse($comment->action_data['original_due_date'])->format('M d, Y H:i') }}<br>
                        To: {{ \Carbon\Carbon::parse($comment->action_data['new_due_date'])->format('M d, Y H:i') }}
                    </div>
                @endif
            </div>

            <div class="mt-2 flex items-center space-x-4">
                <button wire:click="replyToComment({{ $comment->id }})" class="text-sm text-blue-600 hover:text-blue-800">
                    Reply
                </button>

                @if($comment->isActionStep() && $comment->isPendingAction() && $comment->user_id !== auth()->id())
                    @if(isset($comment->action_data['type']) && $comment->action_data['type'] === 'due_date_change')
                        @if($comment->isInNegotiation())
                            <!-- Negotiation in progress - only show if user can respond -->
                            @if($comment->canUserRespond(auth()->id()))
                                <button wire:click="acceptNegotiation({{ $comment->id }})" class="text-sm text-green-600 hover:text-green-800">
                                    Accept Negotiation
                                </button>
                                <button wire:click="rejectNegotiation({{ $comment->id }})" class="text-sm text-red-600 hover:text-red-800">
                                    Reject Negotiation
                                </button>
                                <button wire:click="openNegotiationModal({{ $comment->id }})" class="text-sm text-blue-600 hover:text-blue-800">
                                    Propose Counter-Offer
                                </button>
                            @endif
                        @else
                            <!-- Initial due date change request - only show if user can respond -->
                            @if($comment->canUserRespond(auth()->id()))
                                <button wire:click="openNegotiationModal({{ $comment->id }})" class="text-sm text-blue-600 hover:text-blue-800">
                                    Propose Counter-Offer
                                </button>
                                <button 
                                    wire:click="acceptActionStep({{ $comment->id }})" 
                                    wire:confirm="{{ $comment->created_at > $comment->todoList->due_date ? 'Accepting this action step will mark the task as FAILED because the request was created after the due date. Are you sure?' : 'Are you sure you want to accept this action step?' }}"
                                    class="text-sm text-green-600 hover:text-green-800">
                                    Accept
                                </button>
                                <button wire:click="rejectActionStep({{ $comment->id }})" class="text-sm text-red-600 hover:text-red-800">
                                    Reject
                                </button>
                            @endif
                        @endif
                    @else
                        <!-- Regular action step - anyone can respond -->
                        <button 
                            wire:click="acceptActionStep({{ $comment->id }})" 
                            wire:confirm="{{ $comment->created_at > $comment->todoList->due_date ? 'Accepting this action step will mark the task as FAILED because the request was created after the due date. Are you sure?' : 'Are you sure you want to accept this action step?' }}"
                            class="text-sm text-green-600 hover:text-green-800">
                            Accept
                        </button>
                        <button wire:click="rejectActionStep({{ $comment->id }})" class="text-sm text-red-600 hover:text-red-800">
                            Reject
                        </button>
                    @endif
                @endif
            </div>

            <!-- Replies -->
            @if($comment->replies->count() > 0)
                <div class="mt-4 space-y-3">
                    @foreach($comment->replies as $reply)
                        @include('livewire.todo.partials.comment', ['comment' => $reply, 'level' => $level + 1])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>