<?php

namespace App\Livewire\Operation\IT\Issue;

use App\IssueTracking\Models\Issue;
use App\IssueTracking\Models\IssueImportanceLevel;
use App\IssueTracking\Models\IssuePriority;
use App\IssueTracking\Models\IssueStatus;
use App\IssueTracking\Services\IssueWorkflowService;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\Actions;

#[Layout('components.layouts.operation')]
#[Title('Issues')]
class Index extends Component
{
    use WithPagination;
    use Actions;

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
    public ?bool $is_third_party_resolver = false;
    public ?int $resolution_sequence = null;
    public ?string $follow_up_date = null;
    public string $status_code = '';
    public string $message = '';
    public bool $isDiscussionMode = true;
    public array $selectedRootCauseIds = [];
    public bool $useOtherRootCause = false;
    public string $otherRootCauseName = '';
    public bool $is_erp = true;
    public ?string $issueDateRange = null;
    public ?string $issueDateStart = null;
    public ?string $issueDateEnd = null;
    public ?string $statusFilter = null;
    public ?int $branchFilter = null;

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
        $this->is_third_party_resolver = (bool) $issue->is_third_party_resolver;
        $this->resolution_sequence = $issue->resolution_sequence;
        $this->follow_up_date = $issue->follow_up_date?->format('Y-m-d\\TH:i');
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

    public function openSeverityForIssue(int $issueId): void
    {
        $issue = Issue::query()->with('status')->findOrFail($issueId);
        $this->selectedIssueId = $issue->id;
        $this->title = $issue->title;
        $this->description = $issue->description;
        $this->resolution_department_id = $issue->resolution_department_id;
        $this->assigned_user_id = $issue->assigned_user_id;
        $this->issue_priority_id = $issue->issue_priority_id;
        $this->issue_importance_id = $issue->issue_importance_id;
        $this->proposed_solution = $issue->proposed_solution;
        $this->due_date = $issue->due_date?->format('Y-m-d\\TH:i');
        $this->is_third_party_resolver = (bool) $issue->is_third_party_resolver;
        $this->resolution_sequence = $issue->resolution_sequence;
        $this->follow_up_date = $issue->follow_up_date?->format('Y-m-d\\TH:i');
        $this->status_code = $issue->status?->code ?? '';
        $this->showSeverityModal = true;
    }

    public function updatedIssueDateRange($value): void
    {
        $this->issueDateStart = null;
        $this->issueDateEnd = null;

        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $parts = preg_split('/\s+to\s+/i', $value);

        if (count($parts) === 2) {
            $this->issueDateStart = trim($parts[0]);
            $this->issueDateEnd = trim($parts[1]);
            return;
        }

        $single = trim($value);
        $this->issueDateStart = $single;
        $this->issueDateEnd = $single;
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
        $this->useOtherRootCause = false;
        $this->otherRootCauseName = '';
        $this->showCloseModal = true;
    }

    public function closeCloseModal(): void
    {
        $this->showCloseModal = false;
        $this->selectedRootCauseIds = [];
        $this->useOtherRootCause = false;
        $this->otherRootCauseName = '';
    }

    public function saveIssue(): void
    {
        $this->validate([
            'selectedIssueId' => ['required', 'exists:issues,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'resolution_department_id' => ['required', 'exists:departments,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'proposed_solution' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'resolution_sequence' => ['nullable', 'integer', 'min:1'],
            'is_third_party_resolver' => ['boolean'],
            'follow_up_date' => ['nullable', 'date'],
        ]);

        $issue = Issue::query()->findOrFail($this->selectedIssueId);
        $oldAssigned = $issue->assigned_user_id;
        $oldFollowUpDate = $issue->follow_up_date?->format('Y-m-d H:i:s');
        $newFollowUpDate = $this->follow_up_date ? date('Y-m-d H:i:s', strtotime($this->follow_up_date)) : null;
        $oldIsThirdParty = (bool) $issue->is_third_party_resolver;
        $newIsThirdParty = (bool) $this->is_third_party_resolver;
        $oldSequence = $issue->resolution_sequence;
        $forceNullSequence = $oldIsThirdParty && !$newIsThirdParty;
        $issue->update([
            'title' => $this->title,
            'description' => $this->description,
            'resolution_department_id' => $this->resolution_department_id,
            'assigned_user_id' => $this->assigned_user_id,
            'proposed_solution' => $this->proposed_solution,
            'due_date' => $this->due_date ?: null,
            'is_third_party_resolver' => $newIsThirdParty,
            'resolution_sequence' => $forceNullSequence ? null : $this->resolution_sequence,
            'follow_up_date' => $newFollowUpDate,
            'follow_up_updated_by' => $oldFollowUpDate !== $newFollowUpDate ? auth()->id() : $issue->follow_up_updated_by,
        ]);

        if ($forceNullSequence && $oldSequence !== null) {
            Issue::query()
                ->where('id', '!=', $issue->id)
                ->where('is_third_party_resolver', true)
                ->whereNotNull('resolution_sequence')
                ->where('resolution_sequence', '>', $oldSequence)
                ->whereHas('status', fn($q) => $q->whereNotIn('code', ['DONE', 'CLOSED']))
                ->decrement('resolution_sequence');
        }

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

        if ($oldFollowUpDate !== $newFollowUpDate) {
            $issue->messages()->create([
                'message' => 'Follow-up date changed from '
                    . ($oldFollowUpDate ?? 'empty')
                    . ' to '
                    . ($newFollowUpDate ?? 'empty')
                    . ' by '
                    . (auth()->user()?->name ?? 'Unknown') . '.',
                'is_discussion' => false,
                'is_log_note' => true,
                'created_by' => auth()->id(),
            ]);
        }

        $this->notification([
            'title' => 'Success',
            'description' => 'The issue has been updated successfully.',
            'icon' => 'success',
        ]);
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
        if ($toCode === 'CLOSED') {
            if ($this->selectedIssueId) {
                $this->openCloseModal($this->selectedIssueId);
            }
            return;
        }

        $issueBeforeTransition = $this->selectedIssueId
            ? Issue::query()->with('status')->find($this->selectedIssueId)
            : null;

        $this->status_code = $toCode;
        $this->changeStatus($workflow);

        if ($issueBeforeTransition && in_array($toCode, ['DONE', 'CLOSED'], true) && $issueBeforeTransition->resolution_sequence !== null) {
            $finishedSequence = (int) $issueBeforeTransition->resolution_sequence;
            $isThirdParty = (bool) $issueBeforeTransition->is_third_party_resolver;

            $issueBeforeTransition->update(['resolution_sequence' => null]);

            Issue::query()
                ->where('id', '!=', $issueBeforeTransition->id)
                ->where('is_third_party_resolver', $isThirdParty)
                ->whereNotNull('resolution_sequence')
                ->where('resolution_sequence', '>', $finishedSequence)
                ->whereHas('status', fn($q) => $q->whereNotIn('code', ['DONE', 'CLOSED']))
                ->decrement('resolution_sequence');
        }

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

        $this->notification([
            'title' => 'Success',
            'description' => 'Your message has been added.',
            'icon' => 'success',
        ]);
    }

    public function closeIssue(IssueWorkflowService $workflow): void
    {
        $this->validate([
            'selectedIssueId' => ['required', 'exists:issues,id'],
            'selectedRootCauseIds' => ['array'],
            'selectedRootCauseIds.*' => ['exists:issue_root_causes,id'],
            'useOtherRootCause' => ['boolean'],
            'otherRootCauseName' => ['nullable', 'string', 'max:255'],
        ]);

        if (count($this->selectedRootCauseIds) < 1 && !$this->useOtherRootCause) {
            $this->addError('selectedRootCauseIds', 'Please select at least one root cause or use Other.');
            return;
        }

        $issue = Issue::query()->with('status')->findOrFail($this->selectedIssueId);
        $rootCauseIds = $this->selectedRootCauseIds;

        if ($this->useOtherRootCause) {
            $name = trim($this->otherRootCauseName);
            if ($name === '') {
                $this->addError('otherRootCauseName', 'Please type root cause name for Other.');
                return;
            }

            $otherRootCause = \App\IssueTracking\Models\IssueRootCause::query()->firstOrCreate(['name' => $name]);
            $rootCauseIds[] = $otherRootCause->id;
        }

        $issue->rootCauses()->sync(array_values(array_unique($rootCauseIds)));
        $issue->activityLogs()->create([
            'action' => 'updated',
            'description' => 'Root cause updated before close.',
            'performed_by' => auth()->id(),
        ]);

        $workflow->transition($issue, 'CLOSED', auth()->id());
        $this->closeCloseModal();
        $this->notification([
            'title' => 'Success',
            'description' => 'Issue closed successfully.',
            'icon' => 'success',
        ]);
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

    public function setIssueSequence(int $issueId, ?int $sequence): void
    {
        $data = validator(
            ['sequence' => $sequence],
            ['sequence' => ['nullable', 'integer', 'min:1']]
        )->validate();

        $issue = Issue::query()->with('status')->findOrFail($issueId);

        if (in_array($issue->status?->code, ['DONE', 'CLOSED'], true)) {
            return;
        }

        $currentSequence = $issue->resolution_sequence;
        $newSequence = $data['sequence'] ?? null;

        if ($newSequence === $currentSequence) {
            return;
        }

        if ($newSequence === null) {
            if ($currentSequence !== null) {
                Issue::query()
                    ->where('id', '!=', $issue->id)
                    ->where('is_third_party_resolver', (bool) $issue->is_third_party_resolver)
                    ->whereNotNull('resolution_sequence')
                    ->where('resolution_sequence', '>', $currentSequence)
                    ->whereHas('status', fn($q) => $q->whereNotIn('code', ['DONE', 'CLOSED']))
                    ->decrement('resolution_sequence');
            }

            $issue->update(['resolution_sequence' => null]);
            $this->normalizeSequenceQueue((bool) $issue->is_third_party_resolver);
            return;
        }

        $scopeQuery = Issue::query()
            ->where('id', '!=', $issue->id)
            ->where('is_third_party_resolver', (bool) $issue->is_third_party_resolver)
            ->whereHas('status', fn($q) => $q->whereNotIn('code', ['DONE', 'CLOSED']));

        $maxSequence = (int) (clone $scopeQuery)->whereNotNull('resolution_sequence')->max('resolution_sequence');

        if ($currentSequence !== null && $maxSequence > 0) {
            $newSequence = min($newSequence, $maxSequence);
        }

        if ($currentSequence === null) {
            $newSequence = min($newSequence, $maxSequence + 1);

            Issue::query()
                ->where('id', '!=', $issue->id)
                ->where('is_third_party_resolver', (bool) $issue->is_third_party_resolver)
                ->whereNotNull('resolution_sequence')
                ->where('resolution_sequence', '>=', $newSequence)
                ->whereHas('status', fn($q) => $q->whereNotIn('code', ['DONE', 'CLOSED']))
                ->increment('resolution_sequence');

            $issue->update(['resolution_sequence' => $newSequence]);
            $this->normalizeSequenceQueue((bool) $issue->is_third_party_resolver);
            return;
        }

        $targetIssue = Issue::query()
            ->where('id', '!=', $issue->id)
            ->where('is_third_party_resolver', (bool) $issue->is_third_party_resolver)
            ->whereHas('status', fn($q) => $q->whereNotIn('code', ['DONE', 'CLOSED']))
            ->where('resolution_sequence', $newSequence)
            ->first();

        if ($targetIssue) {
            $targetIssue->update(['resolution_sequence' => $currentSequence]);
        }

        $issue->update(['resolution_sequence' => $newSequence]);
        $this->normalizeSequenceQueue((bool) $issue->is_third_party_resolver);
    }

    public function addIssueSequenceToEnd(int $issueId): void
    {
        $issue = Issue::query()->with('status')->findOrFail($issueId);

        if (in_array($issue->status?->code, ['DONE', 'CLOSED'], true)) {
            return;
        }

        if ($issue->resolution_sequence !== null) {
            return;
        }

        $maxSequence = (int) Issue::query()
            ->where('id', '!=', $issue->id)
            ->where('is_third_party_resolver', (bool) $issue->is_third_party_resolver)
            ->whereHas('status', fn($q) => $q->whereNotIn('code', ['DONE', 'CLOSED']))
            ->whereNotNull('resolution_sequence')
            ->max('resolution_sequence');

        $this->setIssueSequence($issue->id, $maxSequence + 1);
    }

    protected function normalizeSequenceQueue(bool $isThirdPartyResolver): void
    {
        $items = Issue::query()
            ->where('is_third_party_resolver', $isThirdPartyResolver)
            ->whereNotNull('resolution_sequence')
            ->whereHas('status', fn($q) => $q->whereNotIn('code', ['DONE', 'CLOSED']))
            ->orderBy('resolution_sequence')
            ->orderBy('id')
            ->get(['id', 'resolution_sequence']);

        $next = 1;
        foreach ($items as $item) {
            if ((int) $item->resolution_sequence !== $next) {
                Issue::query()->where('id', $item->id)->update(['resolution_sequence' => $next]);
            }
            $next++;
        }
    }

    public function toggleThirdPartyResolver(int $issueId, bool $enabled): void
    {
        $issue = Issue::query()->findOrFail($issueId);
        $oldSequence = $issue->resolution_sequence;
        $oldType = (bool) $issue->is_third_party_resolver;

        $issue->update([
            'is_third_party_resolver' => $enabled,
            'resolution_sequence' => null,
        ]);

        if ($enabled && $oldSequence !== null) {
            Issue::query()
                ->where('id', '!=', $issue->id)
                ->where('is_third_party_resolver', $oldType)
                ->whereNotNull('resolution_sequence')
                ->where('resolution_sequence', '>', $oldSequence)
                ->whereHas('status', fn($q) => $q->whereNotIn('code', ['DONE', 'CLOSED']))
                ->decrement('resolution_sequence');
        }

        $issue->activityLogs()->create([
            'action' => 'updated',
            'description' => 'Third-party resolver flag changed to ' . ($enabled ? 'ON' : 'OFF') . '.',
            'performed_by' => auth()->id(),
        ]);
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
            'issues' => Issue::query()
                ->with(['status', 'priority', 'importance', 'category', 'assignedUser', 'resolutionDepartment'])
                ->latest()
                ->paginate(20),
            'mustDoIssues' => Issue::query()
                ->with(['status', 'priority', 'importance', 'creator.branch'])
                ->where('is_third_party_resolver', false)
                ->when(
                    $this->statusFilter,
                    fn($q) => $q->whereHas('status', fn($s) => $s->where('code', $this->statusFilter)),
                    fn($q) => $q->whereHas('status', fn($s) => $s->where('code', '!=', 'CLOSED'))
                )
                ->when($this->branchFilter, fn($q) => $q->whereHas('creator', fn($c) => $c->where('branch_id', $this->branchFilter)))
                ->when($this->issueDateStart, fn($q) => $q->whereDate('issue_at', '>=', $this->issueDateStart))
                ->when($this->issueDateEnd, fn($q) => $q->whereDate('issue_at', '<=', $this->issueDateEnd))
                ->orderByDesc(IssuePriority::query()->select('level')->whereColumn('issue_priorities.id', 'issues.issue_priority_id'))
                ->orderByDesc(IssueImportanceLevel::query()->select('level')->whereColumn('issue_importance_levels.id', 'issues.issue_importance_id'))
                ->orderByRaw('CASE WHEN resolution_sequence IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('resolution_sequence')
                ->orderByRaw('CASE WHEN DATE(due_date) = ? THEN 0 ELSE 1 END ASC', [now()->toDateString()])
                ->orderBy('due_date')
                ->get(),
            'thirdPartyIssues' => Issue::query()
                ->with(['status', 'priority', 'importance', 'followUpUpdater'])
                ->where('is_third_party_resolver', true)
                ->when(
                    $this->statusFilter,
                    fn($q) => $q->whereHas('status', fn($s) => $s->where('code', $this->statusFilter)),
                    fn($q) => $q->whereHas('status', fn($s) => $s->where('code', '!=', 'CLOSED'))
                )
                ->when($this->branchFilter, fn($q) => $q->whereHas('creator', fn($c) => $c->where('branch_id', $this->branchFilter)))
                ->when($this->issueDateStart, fn($q) => $q->whereDate('issue_at', '>=', $this->issueDateStart))
                ->when($this->issueDateEnd, fn($q) => $q->whereDate('issue_at', '<=', $this->issueDateEnd))
                ->orderByDesc(IssuePriority::query()->select('level')->whereColumn('issue_priorities.id', 'issues.issue_priority_id'))
                ->orderByDesc(IssueImportanceLevel::query()->select('level')->whereColumn('issue_importance_levels.id', 'issues.issue_importance_id'))
                ->orderByRaw('CASE WHEN resolution_sequence IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('resolution_sequence')
                ->orderByRaw('CASE WHEN DATE(due_date) = ? THEN 0 ELSE 1 END ASC', [now()->toDateString()])
                ->orderBy('due_date')
                ->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
            'branches' => Branch::query()->orderBy('name')->get(),
            'priorities' => IssuePriority::query()->orderBy('level')->get(),
            'importanceLevels' => IssueImportanceLevel::query()->orderBy('level')->get(),
            'statuses' => IssueStatus::query()->orderBy('id')->get(),
            'rootCauses' => \App\IssueTracking\Models\IssueRootCause::query()->orderBy('name')->get(),
            'selectedIssue' => $this->selectedIssueId ? Issue::query()->with([
                'priority',
                'importance',
                'followUpUpdater',
                'messages' => fn($q) => $q->with('creator')->orderByDesc('is_log_note')->latest(),
                'activityLogs.performer'
            ])->find($this->selectedIssueId) : null,
            'statusSteps' => $statusSteps,
            'transitionMap' => $transitionMap,
        ]);
    }
}
