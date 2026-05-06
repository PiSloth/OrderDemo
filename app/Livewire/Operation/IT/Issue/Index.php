<?php

namespace App\Livewire\Operation\IT\Issue;

use App\IssueTracking\Models\Issue;
use App\IssueTracking\Models\IssueImportanceLevel;
use App\IssueTracking\Models\IssuePriority;
use App\IssueTracking\Models\IssueStatus;
use App\IssueTracking\Services\IssueWorkflowService;
use App\Models\Department;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.operation')]
#[Title('Issues')]
class Index extends Component
{
    use WithPagination;

    public ?int $selectedIssueId = null;
    public bool $showManageModal = false;
    public bool $showCloseModal = false;
    public bool $showMessageModal = false;
    public bool $showSeverityModal = false;
    public bool $showPlanModal = false;
    public string $title = '';
    public string $description = '';
    public ?int $resolution_department_id = null;
    public ?int $assigned_user_id = null;
    public ?int $issue_priority_id = null;
    public ?int $issue_importance_id = null;
    public ?string $proposed_solution = null;
    public ?string $due_date = null;
    public string $status_code = '';
    public string $message = '';
    public bool $isDiscussionMode = true;
    public array $selectedRootCauseIds = [];
    public bool $is_erp = true;

    public function selectIssue(int $issueId): void
    {
        $issue = Issue::query()->with('status')->findOrFail($issueId);
        $this->selectedIssueId = $issue->id;
        $this->showManageModal = true;
        $this->title = $issue->title;
        $this->description = $issue->description;
        $this->resolution_department_id = $issue->resolution_department_id;
        $this->assigned_user_id = $issue->assigned_user_id;
        $this->issue_priority_id = $issue->issue_priority_id;
        $this->issue_importance_id = $issue->issue_importance_id;
        $this->proposed_solution = $issue->proposed_solution;
        $this->due_date = $issue->due_date?->format('Y-m-d\\TH:i');
        $this->status_code = $issue->status?->code ?? '';
        $this->message = '';
    }

    public function closeManageModal(): void
    {
        $this->showManageModal = false;
    }

    public function openSeverityModal(): void
    {
        $this->showSeverityModal = true;
    }

    public function closeSeverityModal(): void
    {
        $this->showSeverityModal = false;
    }

    public function openPlanModal(): void
    {
        $this->showPlanModal = true;
    }

    public function closePlanModal(): void
    {
        $this->showPlanModal = false;
    }

    // public function openMessageModal(): void
    // {
    //     $this->showMessageModal = true;
    //     $this->message = '';
    //     $this->isDiscussionMode = true;
    // }

    public function openMessageModal(): void
    {
        $this->showMessageModal = true;
        $this->message = '';
        $this->isDiscussionMode = true;
    }

    public function closeMessageModal(): void
    {
        $this->showMessageModal = false;
    }

    public function openCloseModal(int $issueId): void
    {
        $issue = Issue::query()->findOrFail($issueId);
        $this->selectedIssueId = $issue->id;
        $this->selectedRootCauseIds = [];
        $this->showCloseModal = true;
    }

    public function closeCloseModal(): void
    {
        $this->showCloseModal = false;
        $this->selectedRootCauseIds = [];
    }

    public function saveIssue(): void
    {
        $this->validate([
            'selectedIssueId' => ['required', 'exists:issues,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'resolution_department_id' => ['required', 'exists:departments,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $issue = Issue::query()->findOrFail($this->selectedIssueId);
        $oldAssigned = $issue->assigned_user_id;
        $issue->update([
            'title' => $this->title,
            'description' => $this->description,
            'resolution_department_id' => $this->resolution_department_id,
            'assigned_user_id' => $this->assigned_user_id,
            'proposed_solution' => $this->proposed_solution,
            'due_date' => $this->due_date ?: null,
        ]);

        if ((int)($oldAssigned ?? 0) !== (int)($this->assigned_user_id ?? 0)) {
            $issue->activityLogs()->create([
                'action' => 'assigned',
                'description' => 'Assigned user changed.',
                'performed_by' => auth()->id(),
            ]);
        }

        $issue->activityLogs()->create([
            'action' => 'updated',
            'description' => 'Issue updated from issue table action.',
            'performed_by' => auth()->id(),
        ]);

        session()->flash('message', 'Issue updated.');
    }

    public function changeStatus(IssueWorkflowService $workflow): void
    {
        $this->validate([
            'selectedIssueId' => ['required', 'exists:issues,id'],
            'status_code' => ['required', 'exists:issue_statuses,code'],
        ]);

        $issue = Issue::query()->with('status')->findOrFail($this->selectedIssueId);
        $workflow->transition($issue, $this->status_code, auth()->id());
        session()->flash('message', 'Status changed.');
    }

    public function transitionTo(string $toCode, IssueWorkflowService $workflow): void
    {
        $this->status_code = $toCode;
        $this->changeStatus($workflow);
        if ($this->selectedIssueId) {
            $this->selectIssue($this->selectedIssueId);
        }
    }

    public function addMessage(): void
    {
        $this->validate([
            'selectedIssueId' => ['required', 'exists:issues,id'],
            'message' => ['required', 'string'],
        ]);

        $issue = Issue::query()->findOrFail($this->selectedIssueId);
        $issue->messages()->create([
            'message' => $this->message,
            'is_discussion' => $this->isDiscussionMode,
            'is_log_note' => !$this->isDiscussionMode,
            'created_by' => auth()->id(),
        ]);

        $this->message = '';
        $this->showMessageModal = false;
        session()->flash('message', 'Message added.');
    }

    public function closeIssue(IssueWorkflowService $workflow): void
    {
        $this->validate([
            'selectedIssueId' => ['required', 'exists:issues,id'],
            'selectedRootCauseIds' => ['required', 'array', 'min:1'],
            'selectedRootCauseIds.*' => ['exists:issue_root_causes,id'],
        ]);

        $issue = Issue::query()->with('status')->findOrFail($this->selectedIssueId);
        $issue->rootCauses()->sync($this->selectedRootCauseIds);
        $issue->activityLogs()->create([
            'action' => 'updated',
            'description' => 'Root cause updated before close.',
            'performed_by' => auth()->id(),
        ]);

        $workflow->transition($issue, 'CLOSED', auth()->id());
        $this->closeCloseModal();
        session()->flash('message', 'Issue closed successfully.');
    }

    public function updatedIssuePriorityId($value): void
    {
        if (!$this->selectedIssueId || !$value) {
            return;
        }

        $issue = Issue::query()->findOrFail($this->selectedIssueId);
        $issue->update(['issue_priority_id' => (int) $value]);
        $issue->activityLogs()->create([
            'action' => 'updated',
            'description' => 'Priority changed from severity modal.',
            'performed_by' => auth()->id(),
        ]);
    }

    public function updatedIssueImportanceId($value): void
    {
        if (!$this->selectedIssueId || !$value) {
            return;
        }

        $issue = Issue::query()->findOrFail($this->selectedIssueId);
        $issue->update(['issue_importance_id' => (int) $value]);
        $issue->activityLogs()->create([
            'action' => 'updated',
            'description' => 'Importance changed from severity modal.',
            'performed_by' => auth()->id(),
        ]);
    }

    public function deleteIssue(int $id): void
    {
        $issue = Issue::query()->findOrFail($id);
        $issue->delete();

        if ($this->selectedIssueId === $id) {
            $this->selectedIssueId = null;
        }

        session()->flash('message', 'Issue deleted.');
    }

    public function savePlan(): void
    {
        $this->validate([
            'selectedIssueId' => ['required', 'exists:issues,id'],
            'proposed_solution' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ]);

        $issue = Issue::query()->findOrFail($this->selectedIssueId);
        $issue->update([
            'proposed_solution' => $this->proposed_solution,
            'due_date' => $this->due_date ?: null,
        ]);

        $issue->activityLogs()->create([
            'action' => 'updated',
            'description' => 'Proposed solution or due date updated.',
            'performed_by' => auth()->id(),
        ]);

        $this->showPlanModal = false;
        session()->flash('message', 'Plan updated.');
    }

    public function render()
    {
        $statusSteps = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'PENDING', 'DONE', 'CLOSED'];
        $transitionMap = [
            'OPEN' => ['ASSIGNED'],
            'ASSIGNED' => ['IN_PROGRESS'],
            'IN_PROGRESS' => ['PENDING', 'DONE'],
            'PENDING' => ['IN_PROGRESS'],
            'DONE' => ['CLOSED'],
            'CLOSED' => [],
        ];

        return view('livewire.operation.it.issue.index', [
            'issues' => Issue::query()->with(['status', 'priority', 'importance', 'category', 'assignedUser', 'resolutionDepartment'])->latest()->paginate(20),
            'departments' => Department::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
            'priorities' => IssuePriority::query()->orderBy('level')->get(),
            'importanceLevels' => IssueImportanceLevel::query()->orderBy('level')->get(),
            'statuses' => IssueStatus::query()->orderBy('id')->get(),
            'rootCauses' => \App\IssueTracking\Models\IssueRootCause::query()->orderBy('name')->get(),
            'selectedIssue' => $this->selectedIssueId ? Issue::query()->with([
                'priority',
                'importance',
                'messages' => fn($q) => $q->with('creator')->orderByDesc('is_log_note')->latest(),
                'activityLogs.performer'
            ])->find($this->selectedIssueId) : null,
            'statusSteps' => $statusSteps,
            'transitionMap' => $transitionMap,
        ]);
    }
}
