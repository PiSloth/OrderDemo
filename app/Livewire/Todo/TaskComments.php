<?php

namespace App\Livewire\Todo;

use App\Models\TodoList;
use App\Models\TaskComment;
use App\Models\TaskNotification;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.todo')]
class TaskComments extends Component
{
    public $taskId;
    public $task;
    public $comments;
    public $newComment = '';
    public $replyToCommentId = null;
    public $isActionStep = false;
    public $actionStepData = [];
    public $showNegotiationModal = false;
    public $negotiationCommentId = null;
    public $proposedDate = '';
    public $negotiationReason = '';
    public $showCustomRequestModal = false;
    public $customDueDate = '';
    public $customRequestReason = '';

    protected $listeners = ['commentAdded' => 'loadComments'];

    public function mount($taskId = null)
    {
        $this->taskId = $taskId;
        $this->loadTask();
        $this->loadComments();
    }

    public function loadTask()
    {
        if ($this->taskId) {
            $this->task = TodoList::with('dueTime.category', 'dueTime.priority', 'status', 'assignedUser', 'createdByUser', 'location', 'department', 'requestedByBranch')
                ->find($this->taskId);
        }
    }

    public function loadComments()
    {
        if ($this->taskId) {
            $this->comments = TaskComment::with('user', 'replies.user')
                ->where('todo_list_id', $this->taskId)
                ->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|string|max:1000',
        ]);

        $commentData = [
            'todo_list_id' => $this->taskId,
            'user_id' => Auth::id(),
            'comment' => $this->newComment,
            'comment_type' => $this->isActionStep ? 'action_step' : 'normal',
            'parent_id' => $this->replyToCommentId,
        ];

        if ($this->isActionStep) {
            $commentData['action_status'] = 'pending';
            if (!empty($this->actionStepData)) {
                $commentData['action_data'] = $this->actionStepData;
            }
        }

        $comment = TaskComment::create($commentData);

        // Create notifications for relevant users
        $this->createNotificationsForComment($comment);

        $this->resetCommentForm();
        $this->loadComments();
        session()->flash('message', 'Comment added successfully');
    }

    private function createNotificationsForComment(TaskComment $comment)
    {
        $task = $comment->todoList;
        $currentUserId = Auth::id();
        $relevantUserIds = [];

        // Add task assignee
        if ($task->assigned_user_id && $task->assigned_user_id !== $currentUserId) {
            $relevantUserIds[] = $task->assigned_user_id;
        }

        // Add task creator
        if ($task->created_by_user_id !== $currentUserId) {
            $relevantUserIds[] = $task->created_by_user_id;
        }

        // Add users who have commented on this task before
        $previousCommenters = TaskComment::where('todo_list_id', $task->id)
            ->where('user_id', '!=', $currentUserId)
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();

        $relevantUserIds = array_unique(array_merge($relevantUserIds, $previousCommenters));

        // If this is a reply, also notify the parent comment author
        if ($comment->parent_id) {
            $parentComment = TaskComment::find($comment->parent_id);
            if ($parentComment && $parentComment->user_id !== $currentUserId) {
                $relevantUserIds[] = $parentComment->user_id;
            }
        }

        // Remove current user from notification list
        $relevantUserIds = array_diff($relevantUserIds, [$currentUserId]);

        // Create notifications
        foreach ($relevantUserIds as $userId) {
            $notificationType = $comment->parent_id ? 'reply' : 'comment';
            if ($comment->isActionStep()) {
                $notificationType = 'action_step';
            }

            $title = $this->getNotificationTitle($notificationType, $comment);
            $message = $this->getNotificationMessage($notificationType, $comment);

            TaskNotification::create([
                'type' => $notificationType,
                'title' => $title,
                'message' => $message,
                'user_id' => $userId,
                'task_comment_id' => $comment->id,
                'todo_list_id' => $task->id,
                'data' => [
                    'task_title' => $task->task,
                    'comment_preview' => strlen($comment->comment) > 100 ? substr($comment->comment, 0, 100) . '...' : $comment->comment,
                ],
            ]);
        }
    }

    private function getNotificationTitle(string $type, TaskComment $comment): string
    {
        return match ($type) {
            'comment' => 'New Comment',
            'reply' => 'New Reply',
            'action_step' => 'Action Required',
            default => 'New Activity',
        };
    }

    private function getNotificationMessage(string $type, TaskComment $comment): string
    {
        $userName = $comment->user->name;
        $taskTitle = strlen($comment->todoList->task) > 50 ? substr($comment->todoList->task, 0, 50) . '...' : $comment->todoList->task;

        return match ($type) {
            'comment' => "{$userName} commented on '{$taskTitle}'",
            'reply' => "{$userName} replied to a comment on '{$taskTitle}'",
            'action_step' => "{$userName} created an action step on '{$taskTitle}'",
            default => "{$userName} added activity to '{$taskTitle}'",
        };
    }

    public function replyToComment($commentId)
    {
        $this->replyToCommentId = $commentId;
    }

    public function cancelReply()
    {
        $this->replyToCommentId = null;
    }

    public function acceptActionStep($commentId)
    {
        $comment = TaskComment::find($commentId);
        if ($comment && $comment->isActionStep() && $comment->isPendingAction()) {
            // Check permissions for due date change requests
            if ($comment->isDueDateChangeRequest() && !$comment->canUserRespond(Auth::id())) {
                session()->flash('error', 'You do not have permission to accept this due date change request');
                return;
            }

            $comment->update(['action_status' => 'accepted']);

            // If it's a due date change request, update the task
            if (isset($comment->action_data['type']) && $comment->action_data['type'] === 'due_date_change') {
                $task = $comment->todoList;
                $task->update(['due_date' => $comment->action_data['new_due_date']]);
                $this->loadTask(); // Refresh the task data in the component
            }

            // Create notification for the action step creator
            TaskNotification::create([
                'type' => 'action_accepted',
                'title' => 'Action Step Accepted',
                'message' => Auth::user()->name . ' accepted your action step on "' . $comment->todoList->task . '"',
                'user_id' => $comment->user_id,
                'task_comment_id' => $comment->id,
                'todo_list_id' => $comment->todo_list_id,
                'data' => [
                    'action_type' => isset($comment->action_data['type']) ? $comment->action_data['type'] : null,
                ],
            ]);

            $this->loadComments();
            session()->flash('message', 'Action step accepted');
        }
    }

    public function rejectActionStep($commentId)
    {
        $comment = TaskComment::find($commentId);
        if ($comment && $comment->isActionStep() && $comment->isPendingAction()) {
            // Check permissions for due date change requests
            if ($comment->isDueDateChangeRequest() && !$comment->canUserRespond(Auth::id())) {
                session()->flash('error', 'You do not have permission to reject this due date change request');
                return;
            }

            $comment->update(['action_status' => 'rejected']);

            // Create notification for the action step creator
            TaskNotification::create([
                'type' => 'action_rejected',
                'title' => 'Action Step Rejected',
                'message' => Auth::user()->name . ' rejected your action step on "' . $comment->todoList->task . '"',
                'user_id' => $comment->user_id,
                'task_comment_id' => $comment->id,
                'todo_list_id' => $comment->todo_list_id,
                'data' => [
                    'action_type' => isset($comment->action_data['type']) ? $comment->action_data['type'] : null,
                ],
            ]);

            $this->loadComments();
            session()->flash('message', 'Action step rejected');
        }
    }

    public function openNegotiationModal($commentId)
    {
        $this->negotiationCommentId = $commentId;
        $comment = TaskComment::find($commentId);

        if ($comment && $comment->isDueDateChangeRequest()) {
            $this->proposedDate = $comment->getCurrentProposedDate() ?? '';
            $this->negotiationReason = '';
            $this->showNegotiationModal = true;
        }
    }

    public function closeNegotiationModal()
    {
        $this->showNegotiationModal = false;
        $this->negotiationCommentId = null;
        $this->proposedDate = '';
        $this->negotiationReason = '';
    }

    public function openCustomRequestModal()
    {
        $this->customDueDate = now()->addHours(24)->format('Y-m-d\TH:i');
        $this->customRequestReason = '';
        $this->showCustomRequestModal = true;
    }

    public function closeCustomRequestModal()
    {
        $this->showCustomRequestModal = false;
        $this->customDueDate = '';
        $this->customRequestReason = '';
    }

    public function proposeCounterOffer()
    {
        $this->validate([
            'proposedDate' => 'required|date|after:today',
            'negotiationReason' => 'nullable|string|max:255',
        ]);

        $comment = TaskComment::find($this->negotiationCommentId);
        if (!$comment || !$comment->isDueDateChangeRequest()) {
            session()->flash('error', 'Invalid action step');
            return;
        }

        if (!$comment->canUserRespond(Auth::id())) {
            session()->flash('error', 'You cannot respond to this negotiation');
            return;
        }

        // Add the counter proposal
        $comment->addNegotiationProposal(Auth::id(), $this->proposedDate, $this->negotiationReason);

        // Create notification for the original requester
        TaskNotification::create([
            'type' => 'negotiation_proposal',
            'title' => 'Due Date Counter-Proposal',
            'message' => Auth::user()->name . ' proposed a new due date for your request on "' . $comment->todoList->task . '"',
            'user_id' => $comment->user_id,
            'task_comment_id' => $comment->id,
            'todo_list_id' => $comment->todo_list_id,
            'data' => [
                'proposed_date' => $this->proposedDate,
                'reason' => $this->negotiationReason,
            ],
        ]);

        $this->closeNegotiationModal();
        $this->loadComments();
        session()->flash('message', 'Counter-proposal sent successfully');
    }

    public function acceptNegotiation($commentId)
    {
        $comment = TaskComment::find($commentId);
        if (!$comment || !$comment->isDueDateChangeRequest()) {
            session()->flash('error', 'Invalid action step');
            return;
        }

        if (!$comment->canUserRespond(Auth::id())) {
            session()->flash('error', 'You cannot respond to this negotiation');
            return;
        }

        $finalDate = $comment->getCurrentProposedDate();
        $comment->finalizeNegotiation($finalDate);
        $comment->update(['action_status' => 'accepted']);

        // Update the task due date
        $comment->todoList->update(['due_date' => $finalDate]);
        $this->loadTask(); // Refresh the task data in the component

        // Create notification for the other party
        $notifyUserId = $comment->getNegotiatorUserId();
        if ($notifyUserId) {
            TaskNotification::create([
                'type' => 'negotiation_accepted',
                'title' => 'Due Date Negotiation Accepted',
                'message' => Auth::user()->name . ' accepted your proposed due date for "' . $comment->todoList->task . '"',
                'user_id' => $notifyUserId,
                'task_comment_id' => $comment->id,
                'todo_list_id' => $comment->todo_list_id,
                'data' => [
                    'final_date' => $finalDate,
                ],
            ]);
        }

        $this->loadComments();
        session()->flash('message', 'Due date updated successfully through negotiation');
    }

    public function rejectNegotiation($commentId)
    {
        $comment = TaskComment::find($commentId);
        if (!$comment || !$comment->isDueDateChangeRequest()) {
            session()->flash('error', 'Invalid action step');
            return;
        }

        if (!$comment->canUserRespond(Auth::id())) {
            session()->flash('error', 'You cannot respond to this negotiation');
            return;
        }

        $comment->update(['action_status' => 'rejected']);

        // Create notification for the other party
        $notifyUserId = $comment->getNegotiatorUserId();
        if ($notifyUserId) {
            TaskNotification::create([
                'type' => 'negotiation_rejected',
                'title' => 'Due Date Negotiation Rejected',
                'message' => Auth::user()->name . ' rejected your proposed due date for "' . $comment->todoList->task . '"',
                'user_id' => $notifyUserId,
                'task_comment_id' => $comment->id,
                'todo_list_id' => $comment->todo_list_id,
            ]);
        }

        $this->loadComments();
        session()->flash('message', 'Negotiation rejected');
    }

    public function requestDueDateChange($newDueDate)
    {
        $this->isActionStep = true;
        $this->actionStepData = [
            'type' => 'due_date_change',
            'new_due_date' => $newDueDate,
            'original_due_date' => $this->task->due_date->format('Y-m-d H:i:s'),
        ];
        $this->newComment = "Requesting due date change to: " . $newDueDate;
    }

    public function submitCustomDueDateRequest()
    {
        $this->validate([
            'customDueDate' => 'required|date|after:today',
            'customRequestReason' => 'nullable|string|max:500',
        ]);

        $this->isActionStep = true;
        $this->actionStepData = [
            'type' => 'due_date_change',
            'new_due_date' => $this->customDueDate,
            'original_due_date' => $this->task->due_date->format('Y-m-d H:i:s'),
        ];

        $reasonText = $this->customRequestReason ? " Reason: " . $this->customRequestReason : "";
        $this->newComment = "Requesting due date change to: " . $this->customDueDate . $reasonText;

        $this->closeCustomRequestModal();
    }

    public function copyCommentUrl($commentId)
    {
        $url = url("/todo/comments/{$this->taskId}#comment-{$commentId}");
        $this->dispatch('url-copied', url: $url);
    }

    private function resetCommentForm()
    {
        $this->newComment = '';
        $this->replyToCommentId = null;
        $this->isActionStep = false;
        $this->actionStepData = [];
    }

    public function render()
    {
        return view('livewire.todo.task-comments');
    }
}
