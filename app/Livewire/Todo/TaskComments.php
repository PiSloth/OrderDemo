<?php

namespace App\Livewire\Todo;

use App\Models\TodoList;
use App\Models\TaskComment;
use App\Models\TaskNotification;
use App\Models\TodoStatus;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
    public $showStatusRequestModal = false;
    public $requestedStatus = '';
    public $statusRequestReason = '';
    public $showResolverChangeModal = false;
    public $requestedResolverId = '';
    public $resolverChangeReason = '';
    public $isModal = false;

    protected $listeners = ['commentAdded' => 'loadComments', 'loadTask' => 'loadSpecificTask'];

    public function mount($taskId = null, $isModal = false)
    {
        $this->taskId = $taskId;
        $this->isModal = $isModal;
        $this->loadTask();
        $this->loadComments();
    }

    public function loadSpecificTask($taskId)
    {
        \Log::info("TaskComments: loadSpecificTask called with taskId: " . ($taskId ?? 'null'));
        $this->taskId = $taskId;
        $this->loadTask();
        $this->loadComments();
    }

    public function loadTask()
    {
        if ($this->taskId) {
            \Log::info("TaskComments: Attempting to load task with ID: " . $this->taskId);

            // First check if task exists (including soft deleted)
            $taskExists = \App\Models\TodoList::withTrashed()->find($this->taskId);
            \Log::info("TaskComments: Task exists (including trashed): " . ($taskExists ? 'YES' : 'NO'));

            if ($taskExists && $taskExists->trashed()) {
                \Log::info("TaskComments: Task is soft deleted");
            }

            // Now try to load without trashed (normal find) with optimized relationships
            $this->task = TodoList::with([
                'dueTime.category',
                'dueTime.priority',
                'status',
                'assignedUser:id,name,email,branch_id,department_id,position_id',
                'assignedUser.branch:id,name',
                'assignedUser.department:id,name',
                'createdByUser:id,name,email,branch_id,department_id,position_id',
                'createdByUser.branch:id,name',
                'createdByUser.department:id,name',
                'department:id,name',
                'requestedByBranch:id,name'
            ])->find($this->taskId);

            if ($this->task) {
                \Log::info("TaskComments: Task loaded successfully: " . $this->task->task);
            } else {
                \Log::error("TaskComments: Task not found for ID: " . $this->taskId);
            }
        } else {
            \Log::warning("TaskComments: No taskId provided to loadTask");
        }
    }

    public function loadComments()
    {
        if ($this->taskId) {
            $this->comments = TaskComment::with([
                'user:id,name,email,branch_id,department_id,position_id',
                'user.branch:id,name',
                'user.department:id,name',
                'replies.user:id,name,email,branch_id,department_id,position_id',
                'replies.user.branch:id,name',
                'replies.user.department:id,name'
            ])
                ->where('todo_list_id', $this->taskId)
                ->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }

    public function addComment()
    {
        try {
            $this->validate([
                'newComment' => 'required|string|max:1000',
            ]);
        } catch (ValidationException $e) {
            session()->flash('error', 'Please correct the validation errors before submitting.');
            return;
        }

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

        // Add task assignee (if exists and not current user)
        if ($task->assigned_user_id && $task->assigned_user_id !== $currentUserId) {
            $relevantUserIds[] = $task->assigned_user_id;
        }

        // Add task creator (if exists and not current user)
        if ($task->created_by_user_id && $task->created_by_user_id !== $currentUserId) {
            $relevantUserIds[] = $task->created_by_user_id;
        }

        // Add users from the requesting department (excluding current user and already added users)
        if ($task->requested_by_department_id) {
            $departmentUsers = \App\Models\User::where('department_id', $task->requested_by_department_id)
                ->where('id', '!=', $currentUserId)
                ->whereNotIn('id', $relevantUserIds)
                ->pluck('id')
                ->toArray();
            $relevantUserIds = array_merge($relevantUserIds, $departmentUsers);
        }

        // Add users who have commented on this task before (excluding current user and already added users)
        $previousCommenters = TaskComment::where('todo_list_id', $task->id)
            ->where('user_id', '!=', $currentUserId)
            ->whereNotIn('user_id', $relevantUserIds)
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();

        $relevantUserIds = array_unique(array_merge($relevantUserIds, $previousCommenters));

        // If this is a reply, also notify the parent comment author (if not already included)
        if ($comment->parent_id) {
            $parentComment = TaskComment::find($comment->parent_id);
            if ($parentComment && $parentComment->user_id !== $currentUserId && !in_array($parentComment->user_id, $relevantUserIds)) {
                $relevantUserIds[] = $parentComment->user_id;
            }
        }

        // Remove current user from notification list (double check)
        $relevantUserIds = array_diff($relevantUserIds, [$currentUserId]);

        // Create notifications with optimized data loading
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
                    'task_title' => $this->sanitizeUtf8($task->task),
                    'comment_preview' => strlen($comment->comment) > 100 ? $this->sanitizeUtf8(substr($comment->comment, 0, 100)) . '...' : $this->sanitizeUtf8($comment->comment),
                    'requester_department' => $task->department ? $task->department->name : null,
                    'assignee_department' => $task->assignedUser && $task->assignedUser->department ? $task->assignedUser->department->name : null,
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
        $userName = $this->sanitizeUtf8($comment->user->name);
        $taskTitle = $this->sanitizeUtf8(strlen($comment->todoList->task) > 50 ? substr($comment->todoList->task, 0, 50) . '...' : $comment->todoList->task);

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

            // Check permissions for status change requests
            if ($comment->isStatusChangeRequest() && !$comment->canUserRespond(Auth::id())) {
                session()->flash('error', 'You do not have permission to accept this status change request');
                return;
            }

            // Check permissions for resolver change requests
            if ($comment->isResolverChangeRequest() && !$comment->canUserRespond(Auth::id())) {
                session()->flash('error', 'You do not have permission to accept this resolver change request');
                return;
            }

            $comment->update(['action_status' => 'accepted']);

            // If it's a due date change request, update the task
            if (isset($comment->action_data['type']) && $comment->action_data['type'] === 'due_date_change') {
                $task = $comment->todoList;
                $task->update(['due_date' => $comment->action_data['new_due_date']]);
                $this->loadTask(); // Refresh the task data in the component
            }

            // If it's a status change request, update the task status
            if (isset($comment->action_data['type']) && $comment->action_data['type'] === 'status_change') {
                $task = $comment->todoList;
                $task->update(['todo_status_id' => $comment->action_data['new_status_id']]);
                $this->loadTask(); // Refresh the task data in the component
            }

            // If it's a resolver change request, update the task assignee
            if (isset($comment->action_data['type']) && $comment->action_data['type'] === 'resolver_change') {
                $task = $comment->todoList;
                $task->update(['assigned_user_id' => $comment->action_data['new_assigned_user_id']]);
                $this->loadTask(); // Refresh the task data in the component
            }

            // Check if the action step request was created after the due date
            // If so, mark the task as failed
            $task = $comment->todoList;
            if ($comment->created_at > $task->due_date) {
                $failedStatus = TodoStatus::where('status', 'Failed')->first();
                if ($failedStatus) {
                    $task->update(['todo_status_id' => $failedStatus->id]);
                    $this->loadTask(); // Refresh the task data in the component
                }
            }

            // Create notification for the action step creator
            TaskNotification::create([
                'type' => 'action_accepted',
                'title' => 'Action Step Accepted',
                'message' => $this->sanitizeUtf8(Auth::user()->name) . ' accepted your action step on "' . $this->sanitizeUtf8($comment->todoList->task) . '"',
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

            // Check permissions for status change requests
            if ($comment->isStatusChangeRequest() && !$comment->canUserRespond(Auth::id())) {
                session()->flash('error', 'You do not have permission to reject this status change request');
                return;
            }

            // Check permissions for resolver change requests
            if ($comment->isResolverChangeRequest() && !$comment->canUserRespond(Auth::id())) {
                session()->flash('error', 'You do not have permission to reject this resolver change request');
                return;
            }

            $comment->update(['action_status' => 'rejected']);

            // Create notification for the action step creator
            TaskNotification::create([
                'type' => 'action_rejected',
                'title' => 'Action Step Rejected',
                'message' => $this->sanitizeUtf8(Auth::user()->name) . ' rejected your action step on "' . $this->sanitizeUtf8($comment->todoList->task) . '"',
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
        if (!$this->ensureAssignedDepartmentAccess('Only users from the assigned user\'s department can request due date changes.')) {
            return;
        }

        \Log::info('TaskComments: openCustomRequestModal invoked');
        $this->customDueDate = now()->addHours(24)->format('Y-m-d\TH:i');
        $this->customRequestReason = '';
        $this->showCustomRequestModal = true;
        \Log::info('TaskComments: showCustomRequestModal set to true');
    }

    public function closeCustomRequestModal()
    {
        $this->showCustomRequestModal = false;
        $this->customDueDate = '';
        $this->customRequestReason = '';
    }

    public function openStatusRequestModal()
    {
        if (!$this->ensureAssignedDepartmentAccess('Only users from the assigned user\'s department can request status changes.')) {
            return;
        }

        // Check if task is in failed status - prevent opening status change modal
        $failedStatusIds = TodoStatus::whereIn('status', ['Cancelled', 'Failed', 'Rejected'])->pluck('id')->toArray();
        if (in_array($this->task->todo_status_id, $failedStatusIds)) {
            session()->flash('error', 'Cannot request status changes for tasks that are in failed/cancelled/rejected status.');
            return;
        }

        \Log::info('TaskComments: openStatusRequestModal invoked');
        $this->requestedStatus = $this->task->todo_status_id ?? '';
        $this->statusRequestReason = '';
        $this->showStatusRequestModal = true;
        \Log::info('TaskComments: showStatusRequestModal set to true with requestedStatus: ' . ($this->requestedStatus ?: 'null'));
    }

    public function closeStatusRequestModal()
    {
        \Log::info('TaskComments: closeStatusRequestModal invoked');
        $this->showStatusRequestModal = false;
        $this->requestedStatus = '';
        $this->statusRequestReason = '';
    }

    public function proposeCounterOffer()
    {
        try {
            $this->validate([
                'proposedDate' => 'required|date|after:today',
                'negotiationReason' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            session()->flash('error', 'Please correct the validation errors before submitting.');
            return;
        }

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
            'message' => $this->sanitizeUtf8(Auth::user()->name) . ' proposed a new due date for your request on "' . $this->sanitizeUtf8($comment->todoList->task) . '"',
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
                'message' => $this->sanitizeUtf8(Auth::user()->name) . ' accepted your proposed due date for "' . $this->sanitizeUtf8($comment->todoList->task) . '"',
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
                'message' => $this->sanitizeUtf8(Auth::user()->name) . ' rejected your proposed due date for "' . $this->sanitizeUtf8($comment->todoList->task) . '"',
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
        if (!$this->ensureAssignedDepartmentAccess('Only users from the assigned user\'s department can request due date changes.')) {
            return;
        }

        if ($this->hasPendingActionStep()) {
            session()->flash('error', 'Cannot create a new action step request while another action step is pending approval.');
            return;
        }

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
        if (!$this->ensureAssignedDepartmentAccess('Only users from the assigned user\'s department can request due date changes.')) {
            return;
        }

        if ($this->hasPendingActionStep()) {
            session()->flash('error', 'Cannot create a new action step request while another action step is pending approval.');
            return;
        }

        try {
            $this->validate([
                'customDueDate' => 'required|date|after:today',
                'customRequestReason' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            session()->flash('error', 'Please correct the validation errors before submitting.');
            return;
        }

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

    public function submitStatusChangeRequest()
    {
        if (!$this->ensureAssignedDepartmentAccess('Only users from the assigned user\'s department can request status changes.')) {
            return;
        }

        // Check if task is in failed status - prevent status change requests
        $failedStatusIds = TodoStatus::whereIn('status', ['Cancelled', 'Failed', 'Rejected'])->pluck('id')->toArray();
        if (in_array($this->task->todo_status_id, $failedStatusIds)) {
            session()->flash('error', 'Cannot request status changes for tasks that are in failed/cancelled/rejected status.');
            return;
        }

        if ($this->hasPendingActionStep()) {
            session()->flash('error', 'Cannot create a new action step request while another action step is pending approval.');
            return;
        }

        try {
            $this->validate([
                'requestedStatus' => 'required|exists:todo_statuses,id',
                'statusRequestReason' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            session()->flash('error', 'Please correct the validation errors before submitting.');
            return;
        }

        $this->isActionStep = true;
        $this->actionStepData = [
            'type' => 'status_change',
            'new_status_id' => $this->requestedStatus,
            'original_status_id' => $this->task->todo_status_id,
        ];

        $statusName = \App\Models\TodoStatus::find($this->requestedStatus)->status ?? 'Unknown';
        $reasonText = $this->statusRequestReason ? " Reason: " . $this->statusRequestReason : "";
        $this->newComment = "Requesting status change to: " . $statusName . $reasonText;

        $this->closeStatusRequestModal();
    }

    public function copyCommentUrl($commentId)
    {
        $url = url("/todo/comments/{$this->taskId}#comment-{$commentId}");
        $this->dispatch('url-copied', url: $url);
    }

    public function canAcknowledgeTask($task)
    {
        $user = Auth::user();

        // Check if task exists and has a status
        if (!$task || !$task->status) {
            \Log::info('Task or status missing', ['task_id' => $task ? $task->id : 'null']);
            return false;
        }

        // Check if task is in a status that can be acknowledged (new, pending, etc.)
        $acknowledgeableStatuses = ['new', 'New', 'pending', 'Pending', 'unassigned'];
        if (!in_array($task->status->status, $acknowledgeableStatuses)) {
            \Log::info('Task status not acknowledgeable', [
                'task_id' => $task->id,
                'status' => $task->status->status,
                'acknowledgeable' => $acknowledgeableStatuses
            ]);
            return false;
        }

        // Check if user belongs to the task's requested department
        if (!$task->department || !$user) {
            \Log::info('Task department or user missing', [
                'task_id' => $task->id,
                'department_id' => $task->requested_by_department_id,
                'user_id' => $user ? $user->id : 'null'
            ]);
            return false;
        }

        // Check if user has the task's requested department
        $hasDepartment = $user->department_id == $task->requested_by_department_id;
        \Log::info('Department check result', [
            'user_id' => $user->id,
            'task_requested_department_id' => $task->requested_by_department_id,
            'user_department_id' => $user->department_id,
            'has_department' => $hasDepartment
        ]);

        return $hasDepartment;
    }

    /**
     * Check if the current user can perform actions on a task based on department relationships
     */
    public function canUserActOnTask($task, $action = 'view')
    {
        $user = Auth::user();

        if (!$user || !$task) {
            return false;
        }

        // Task creator can always view/act on their tasks
        if ($task->created_by_user_id === $user->id) {
            return true;
        }

        // Task assignee can always view/act on their tasks
        if ($task->assigned_user_id === $user->id) {
            return true;
        }

        // Users in the requesting department can view tasks
        if ($task->requested_by_department_id === $user->department_id) {
            return true;
        }

        // Users in the assigned user's department can view tasks
        if ($task->assignedUser && $task->assignedUser->department_id === $user->department_id) {
            return true;
        }

        // For stricter actions like acknowledge, only requesting department users can act
        if ($action === 'acknowledge') {
            return $task->requested_by_department_id === $user->department_id;
        }

        return false;
    }

    public function acknowledgeTask($taskId)
    {
        $task = TodoList::find($taskId);

        if (!$task) {
            session()->flash('error', 'Task not found.');
            return;
        }

        // Debug: Check task status and department
        \Log::info('Acknowledge Task Debug', [
            'task_id' => $taskId,
            'task_status' => $task->status ? $task->status->status : 'no status',
            'task_requested_department_id' => $task->requested_by_department_id,
            'user_id' => auth()->id(),
            'user_department_id' => auth()->user()->department_id,
        ]);

        if (!$this->canAcknowledgeTask($task)) {
            session()->flash('error', 'You are not authorized to acknowledge this task. Task status: ' . ($task->status ? $task->status->status : 'no status'));
            return;
        }

        // Find or create "acknowledged" status
        $acknowledgedStatus = TodoStatus::where('status', 'Acknowledged')->first();
        if (!$acknowledgedStatus) {
            $acknowledgedStatus = TodoStatus::create([
                'status' => 'Acknowledged',
                'description' => 'Task has been acknowledged by the assigned department',
                'color_code' => 'blue'
            ]);
        }

        // Update task status
        $task->update([
            'todo_status_id' => $acknowledgedStatus->id
        ]);

        // Verify the update
        $task->refresh();
        \Log::info('Task status updated', [
            'task_id' => $taskId,
            'new_status' => $task->status ? $task->status->status : 'no status',
            'status_id' => $task->todo_status_id
        ]);

        // Refresh the task data in the component
        $this->loadTask();

        session()->flash('message', 'Task acknowledged successfully!');
    }

    private function resetCommentForm()
    {
        $this->newComment = '';
        $this->replyToCommentId = null;
        $this->isActionStep = false;
        $this->actionStepData = [];
    }

    private function sanitizeUtf8($string)
    {
        if (mb_check_encoding($string, 'UTF-8')) {
            return $string;
        } else {
            $converted = mb_convert_encoding($string, 'UTF-8', 'auto');
            return $converted !== false ? $converted : '';
        }
    }

    private function hasPendingActionStep()
    {
        if (!$this->taskId) {
            return false;
        }

        return TaskComment::where('todo_list_id', $this->taskId)
            ->where('comment_type', 'action_step')
            ->where('action_status', 'pending')
            ->exists();
    }

    private function ensureAssignedDepartmentAccess(string $errorMessage = 'Only users from the assigned user\'s department can perform this action.')
    {
        $currentUser = Auth::user();

        if (!$this->task || !$this->task->assignedUser || $this->task->assignedUser->department_id !== $currentUser->department_id) {
            session()->flash('error', $errorMessage);
            return false;
        }

        return true;
    }

    public function copyUrl()
    {
        $url = route('task_comments', ['taskId' => $this->taskId]);

        $this->dispatch('copy-to-clipboard', url: $url);
    }

    public function openResolverChangeModal()
    {
        if (!$this->ensureAssignedDepartmentAccess('Only users from the assigned user\'s department can request resolver changes.')) {
            return;
        }

        $this->showResolverChangeModal = true;
    }

    public function closeResolverChangeModal()
    {
        $this->showResolverChangeModal = false;
        $this->requestedResolverId = '';
        $this->resolverChangeReason = '';
    }

    public function submitResolverChangeRequest()
    {
        if (!$this->ensureAssignedDepartmentAccess('Only users from the assigned user\'s department can request resolver changes.')) {
            return;
        }

        $this->validate([
            'requestedResolverId' => 'required|exists:users,id',
            'resolverChangeReason' => 'required|string|min:5',
        ]);

        $this->newComment = $this->resolverChangeReason;
        $this->isActionStep = true;
        $this->actionStepData = [
            'type' => 'resolver_change',
            'new_assigned_user_id' => $this->requestedResolverId,
        ];

        $this->addComment();

        $this->closeResolverChangeModal();
    }

    public function render()
    {
        if ($this->isModal) {
            return view('livewire.todo.task-comments');
        }
        return view('livewire.todo.task-comments')->layout('components.layouts.todo');
    }
}
