<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiDependencyGroup;
use App\Models\Kpi\KpiDependencyGroupMember;
use App\Models\Kpi\KpiDependencyGroupApprovalStep;
use App\Models\Kpi\KpiDependencyGroupRun;
use App\Models\Kpi\KpiDependencyGroupRunMember;
use App\Models\Kpi\KpiDependencyGroupSubmission;
use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskTemplate;
use App\Services\Kpi\KpiSubmissionImageResizer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.kpi')]
#[Title('Associate Tasks')]
class AssociateTasks extends Component
{
    use WithFileUploads;

    public $groups;
    public $myRuns;
    public $myPendingAccepts;
    public $myAssociatePendingApprovals;
    public $templates;
    public $assignments;

    public string $selectedGroupId = '';
    public string $submissionRemark = '';
    public array $submissionPhotos = [];
    public array $submissionPhotoTitles = [];
    public array $submissionPhotoRemarks = [];
    public ?int $selectedRunId = null;
    public ?int $selectedAssociateApprovalStepId = null;
    public string $associateApprovalRemark = '';
    public string $groupName = '';
    public string $groupTemplateId = '';
    public string $groupFrequency = 'daily';
    public string $groupCutoffTime = '';
    public string $groupFirstApproverUserId = '';
    public string $groupFinalApproverUserId = '';
    public string $groupMemberAssignmentId = '';
    public bool $groupMemberRequired = true;
    public ?int $manageGroupId = null;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $userId = Auth::id();

        $this->groups = KpiDependencyGroup::query()
            ->with('template')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $this->templates = KpiTaskTemplate::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        $this->assignments = KpiTaskAssignment::query()
            ->with('user')
            ->where('is_active', true)
            ->orderBy('user_id')
            ->limit(300)
            ->get();

        $this->myRuns = KpiDependencyGroupRun::query()
            ->with(['group.template', 'members.user', 'submission.images'])
            ->where('initiated_by_user_id', $userId)
            ->orderByDesc('run_date')
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $this->myPendingAccepts = KpiDependencyGroupRunMember::query()
            ->with(['run.group.template'])
            ->where('user_id', $userId)
            ->where('member_status', 'pending')
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $this->myAssociatePendingApprovals = KpiDependencyGroupApprovalStep::query()
            ->with(['run.group', 'run.submission.images'])
            ->where('approver_user_id', $userId)
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get()
            ->filter(function (KpiDependencyGroupApprovalStep $step): bool {
                $previousPending = KpiDependencyGroupApprovalStep::query()
                    ->where('dependency_group_run_id', $step->dependency_group_run_id)
                    ->where('step_order', '<', $step->step_order)
                    ->where('status', '!=', 'approved')
                    ->exists();

                return !$previousPending;
            })
            ->values();
    }

    public function createRun(): void
    {
        Gate::authorize('kpiManageAssignments');

        $validated = $this->validate([
            'selectedGroupId' => ['required', 'exists:kpi_dependency_groups,id'],
        ]);

        $group = KpiDependencyGroup::query()
            ->with(['members' => fn($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->findOrFail((int) $validated['selectedGroupId']);

        if ($group->members->isEmpty()) {
            throw ValidationException::withMessages([
                'selectedGroupId' => 'Selected associate group has no active members.',
            ]);
        }

        $runDate = now()->toDateString();

        $run = KpiDependencyGroupRun::query()->firstOrCreate(
            [
                'dependency_group_id' => $group->id,
                'period_type' => $group->frequency,
                'run_date' => $runDate,
            ],
            [
                'period_start' => now()->startOfDay()->toDateString(),
                'period_end' => now()->endOfDay()->toDateString(),
                'status' => 'pending_submission',
                'initiated_by_user_id' => Auth::id(),
                'required_member_count' => $group->members->where('is_required', true)->count(),
                'confirmed_member_count' => 0,
            ]
        );

        foreach ($group->members as $member) {
            KpiDependencyGroupRunMember::query()->firstOrCreate(
                [
                    'dependency_group_run_id' => $run->id,
                    'task_assignment_id' => $member->task_assignment_id,
                    'user_id' => $member->user_id,
                ],
                [
                    'member_status' => 'pending',
                    'role_type' => $member->user_id === Auth::id() ? 'uploader' : 'associate',
                    'is_required' => (bool) $member->is_required,
                ]
            );
        }

        $this->selectedRunId = $run->id;
        $this->submissionPhotos = [];
        $this->submissionPhotoTitles = [];
        $this->submissionPhotoRemarks = [];
        $this->submissionRemark = '';
        $this->loadData();

        session()->flash('message', 'Associate run created. Upload once and request acceptance.');
    }

    public function selectRun(int $runId): void
    {
        $run = KpiDependencyGroupRun::query()
            ->where('id', $runId)
            ->where('initiated_by_user_id', Auth::id())
            ->firstOrFail();

        $this->selectedRunId = $run->id;
        $this->submissionPhotos = [];
        $this->submissionPhotoTitles = [];
        $this->submissionPhotoRemarks = [];
        $this->submissionRemark = '';
    }

    public function submitRun(KpiSubmissionImageResizer $resizer): void
    {
        if (!$this->selectedRunId) {
            throw ValidationException::withMessages([
                'selectedRunId' => 'Select a run first.',
            ]);
        }

        $run = KpiDependencyGroupRun::query()
            ->with(['members', 'submission'])
            ->where('id', $this->selectedRunId)
            ->where('initiated_by_user_id', Auth::id())
            ->firstOrFail();

        $this->validate([
            'submissionPhotos' => ['array', 'min:1', 'max:20'],
            'submissionPhotos.*' => ['image', 'max:10240'],
            'submissionRemark' => ['nullable', 'string'],
        ]);

        $storedPaths = [];

        try {
            foreach ($this->submissionPhotos as $index => $photo) {
                if ($photo instanceof TemporaryUploadedFile) {
                    $path = $resizer->store($photo, 900);
                    $storedPaths[$index] = $path;
                }
            }

            DB::transaction(function () use ($run, $storedPaths): void {
                $submission = $run->submission;

                if ($submission) {
                    $oldImages = $submission->images()->get();
                    foreach ($oldImages as $oldImage) {
                        Storage::disk('public')->delete((string) $oldImage->image_path);
                        $oldImage->delete();
                    }

                    $submission->update([
                        'submitted_by_user_id' => Auth::id(),
                        'submitted_at' => now(),
                        'status' => 'submitted',
                        'employee_remark' => $this->submissionRemark !== '' ? $this->submissionRemark : null,
                        'rejection_reason' => null,
                        'reopened_at' => now(),
                    ]);
                } else {
                    $submission = KpiDependencyGroupSubmission::create([
                        'dependency_group_run_id' => $run->id,
                        'submitted_by_user_id' => Auth::id(),
                        'submitted_at' => now(),
                        'status' => 'submitted',
                        'employee_remark' => $this->submissionRemark !== '' ? $this->submissionRemark : null,
                    ]);
                }

                foreach ($this->submissionPhotos as $index => $photo) {
                    $path = $storedPaths[$index] ?? null;
                    if ($path === null) {
                        continue;
                    }

                    $submission->images()->create([
                        'image_path' => $path,
                        'title' => trim((string) ($this->submissionPhotoTitles[$index] ?? '')) ?: null,
                        'remark' => trim((string) ($this->submissionPhotoRemarks[$index] ?? '')) ?: null,
                        'sort_order' => $index + 1,
                    ]);
                }

                $run->members()->update([
                    'member_status' => 'pending',
                    'acted_at' => null,
                    'comment' => null,
                    'rejection_comment' => null,
                ]);

                $run->approvalSteps()->delete();
                $run->update([
                    'status' => 'waiting_member_acceptance',
                    'submitted_at' => now(),
                    'first_confirmed_at' => null,
                    'fully_confirmed_at' => null,
                    'confirmed_member_count' => 0,
                ]);
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        $this->submissionPhotos = [];
        $this->submissionPhotoTitles = [];
        $this->submissionPhotoRemarks = [];
        $this->submissionRemark = '';
        $this->loadData();

        session()->flash('message', 'Submitted. Waiting for associated members to accept.');
    }

    public function acceptAsAssociate(int $runMemberId): void
    {
        $member = KpiDependencyGroupRunMember::query()
            ->with('run.group')
            ->where('id', $runMemberId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($member->member_status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($member): void {
            $member->update([
                'member_status' => 'accepted',
                'acted_at' => now(),
            ]);

            $run = $member->run()->with(['members', 'group'])->firstOrFail();
            $required = $run->members->where('is_required', true);
            $acceptedRequired = $required->where('member_status', 'accepted')->count();

            $updates = [
                'confirmed_member_count' => $acceptedRequired,
            ];

            if ($run->first_confirmed_at === null) {
                $updates['first_confirmed_at'] = now();
            }

            if ($required->count() > 0 && $acceptedRequired === $required->count()) {
                $updates['fully_confirmed_at'] = now();
                $updates['status'] = 'waiting_first_approval';
                $this->syncApprovalSteps($run->fresh('group'));
            }

            $run->update($updates);
        });

        $this->loadData();
        session()->flash('message', 'Accepted. This run will move to approver when all required associates accept.');
    }

    public function rejectAsAssociate(int $runMemberId): void
    {
        $member = KpiDependencyGroupRunMember::query()
            ->with('run')
            ->where('id', $runMemberId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($member->member_status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($member): void {
            $member->update([
                'member_status' => 'rejected',
                'acted_at' => now(),
            ]);

            $member->run()->update([
                'status' => 'reopened_by_associate',
                'reopened_count' => DB::raw('reopened_count + 1'),
            ]);
        });

        $this->loadData();
        session()->flash('message', 'Rejected. Uploader needs to submit again with correction.');
    }

    public function openAssociateApprovalStep(int $stepId): void
    {
        $step = KpiDependencyGroupApprovalStep::query()
            ->where('id', $stepId)
            ->where('approver_user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $this->selectedAssociateApprovalStepId = $step->id;
        $this->associateApprovalRemark = '';
    }

    public function approveAssociateStep(): void
    {
        $this->decideAssociateStep('approved');
    }

    public function rejectAssociateStep(): void
    {
        if (trim($this->associateApprovalRemark) === '') {
            throw ValidationException::withMessages([
                'associateApprovalRemark' => 'Remark is required when rejecting.',
            ]);
        }

        $this->decideAssociateStep('rejected');
    }

    protected function decideAssociateStep(string $decision): void
    {
        if (!$this->selectedAssociateApprovalStepId) {
            return;
        }

        DB::transaction(function () use ($decision): void {
            $step = KpiDependencyGroupApprovalStep::query()
                ->where('id', $this->selectedAssociateApprovalStepId)
                ->where('approver_user_id', Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            if ($step->status !== 'pending') {
                return;
            }

            $run = KpiDependencyGroupRun::query()->with(['approvalSteps', 'submission'])->findOrFail($step->dependency_group_run_id);

            $step->update([
                'status' => $decision,
                'acted_at' => now(),
                'remark' => trim($this->associateApprovalRemark) !== '' ? trim($this->associateApprovalRemark) : null,
            ]);

            if ($decision === 'rejected') {
                $run->approvalSteps()
                    ->where('step_order', '>', $step->step_order)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'cancelled',
                        'acted_at' => now(),
                        'remark' => 'Stopped because previous approver rejected.',
                    ]);

                if ($run->submission) {
                    $run->submission->update([
                        'status' => 'rejected',
                        'rejection_reason' => trim($this->associateApprovalRemark) !== '' ? trim($this->associateApprovalRemark) : null,
                    ]);
                }

                $run->members()->update([
                    'member_status' => 'pending',
                    'acted_at' => null,
                ]);

                $run->update([
                    'status' => 'reopened_by_approver',
                    'reopened_count' => DB::raw('reopened_count + 1'),
                    'fully_confirmed_at' => null,
                    'first_confirmed_at' => null,
                    'confirmed_member_count' => 0,
                ]);

                return;
            }

            $nextPending = $run->approvalSteps()
                ->where('step_order', '>', $step->step_order)
                ->where('status', 'pending')
                ->exists();

            if ($nextPending) {
                $run->update([
                    'status' => 'waiting_final_approval',
                ]);

                return;
            }

            if ($run->submission) {
                $run->submission->update([
                    'status' => 'approved',
                    'rejection_reason' => null,
                ]);
            }

            $run->update([
                'status' => 'approved',
                'final_outcome' => 'passed',
                'finalized_at' => now(),
            ]);
        });

        $this->selectedAssociateApprovalStepId = null;
        $this->associateApprovalRemark = '';
        $this->loadData();
        session()->flash('message', $decision === 'approved' ? 'Associate run approved.' : 'Associate run rejected and reopened.');
    }

    public function createGroup(): void
    {
        Gate::authorize('kpiManageAssignments');

        $validated = $this->validate([
            'groupName' => ['required', 'string', 'max:255'],
            'groupTemplateId' => ['required', 'exists:kpi_task_templates,id'],
            'groupFrequency' => ['required', 'in:daily,weekly,monthly'],
            'groupCutoffTime' => ['nullable', 'date_format:H:i'],
            'groupFirstApproverUserId' => ['nullable', 'exists:users,id'],
            'groupFinalApproverUserId' => ['nullable', 'exists:users,id'],
        ]);

        $group = KpiDependencyGroup::create([
            'name' => $validated['groupName'],
            'task_template_id' => (int) $validated['groupTemplateId'],
            'frequency' => $validated['groupFrequency'],
            'cutoff_time' => $validated['groupCutoffTime'] !== '' ? $validated['groupCutoffTime'] : null,
            'first_approver_user_id' => $validated['groupFirstApproverUserId'] !== '' ? (int) $validated['groupFirstApproverUserId'] : null,
            'final_approver_user_id' => $validated['groupFinalApproverUserId'] !== '' ? (int) $validated['groupFinalApproverUserId'] : null,
            'is_active' => true,
        ]);

        $this->manageGroupId = $group->id;
        $this->groupName = '';
        $this->groupTemplateId = '';
        $this->groupFrequency = 'daily';
        $this->groupCutoffTime = '';
        $this->groupFirstApproverUserId = '';
        $this->groupFinalApproverUserId = '';
        $this->loadData();

        session()->flash('message', 'Associate group created.');
    }

    public function selectManageGroup(int $groupId): void
    {
        Gate::authorize('kpiManageAssignments');
        $this->manageGroupId = $groupId;
    }

    public function addGroupMember(): void
    {
        Gate::authorize('kpiManageAssignments');

        if (!$this->manageGroupId) {
            throw ValidationException::withMessages([
                'groupMemberAssignmentId' => 'Select group first.',
            ]);
        }

        $validated = $this->validate([
            'groupMemberAssignmentId' => ['required', 'exists:kpi_task_assignments,id'],
            'groupMemberRequired' => ['boolean'],
        ]);

        $assignment = KpiTaskAssignment::query()->findOrFail((int) $validated['groupMemberAssignmentId']);

        KpiDependencyGroupMember::query()->updateOrCreate(
            [
                'dependency_group_id' => $this->manageGroupId,
                'task_assignment_id' => $assignment->id,
            ],
            [
                'user_id' => $assignment->user_id,
                'is_required' => (bool) $validated['groupMemberRequired'],
                'is_active' => true,
            ]
        );

        $this->groupMemberAssignmentId = '';
        $this->groupMemberRequired = true;
        $this->loadData();
        session()->flash('message', 'Member added to associate group.');
    }

    public function removeGroupMember(int $groupId, int $assignmentId): void
    {
        Gate::authorize('kpiManageAssignments');

        KpiDependencyGroupMember::query()
            ->where('dependency_group_id', $groupId)
            ->where('task_assignment_id', $assignmentId)
            ->delete();

        $this->loadData();
        session()->flash('message', 'Member removed.');
    }

    protected function syncApprovalSteps(KpiDependencyGroupRun $run): void
    {
        $firstApproverId = $run->group?->first_approver_user_id;
        $finalApproverId = $run->group?->final_approver_user_id;

        if ($firstApproverId) {
            KpiDependencyGroupApprovalStep::query()->updateOrCreate(
                ['dependency_group_run_id' => $run->id, 'step_order' => 1],
                [
                    'approver_user_id' => $firstApproverId,
                    'role_label' => 'First Approver',
                    'status' => 'pending',
                ]
            );
        }

        if ($finalApproverId) {
            KpiDependencyGroupApprovalStep::query()->updateOrCreate(
                ['dependency_group_run_id' => $run->id, 'step_order' => 2],
                [
                    'approver_user_id' => $finalApproverId,
                    'role_label' => 'Final Approver',
                    'status' => 'pending',
                ]
            );
        }
    }

    public function render()
    {
        $managedGroup = $this->manageGroupId
            ? KpiDependencyGroup::query()
                ->with(['members.assignment.user', 'members.assignment.template'])
                ->find($this->manageGroupId)
            : null;

        return view('livewire.kpi.associate-tasks', [
            'managedGroup' => $managedGroup,
        ]);
    }
}
