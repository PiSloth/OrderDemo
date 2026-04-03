<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskApprovalStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Approvals extends Component
{
    public $pendingSteps;
    public $recentSteps;
    public array $summaryCards = [];

    public ?int $selectedStepId = null;
    public string $decisionRemark = '';

    public function mount(): void
    {
        $this->pendingSteps = collect();
        $this->recentSteps = collect();
        $this->loadQueue();
    }

    public function loadQueue(): void
    {
        $userId = Auth::id();

        $pendingSteps = KpiTaskApprovalStep::query()
            ->with($this->approvalRelations())
            ->where('approver_user_id', $userId)
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get()
            ->filter(fn (KpiTaskApprovalStep $step) => $this->isActionableStep($step))
            ->values();

        $recentSteps = KpiTaskApprovalStep::query()
            ->with($this->approvalRelations())
            ->where('approver_user_id', $userId)
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('acted_at')
            ->limit(10)
            ->get();

        $this->pendingSteps = $pendingSteps;
        $this->recentSteps = $recentSteps;

        $this->summaryCards = [
            [
                'label' => 'Pending First Step',
                'value' => $pendingSteps->where('step_order', 1)->count(),
            ],
            [
                'label' => 'Pending Final Step',
                'value' => $pendingSteps->where('step_order', '>', 1)->count(),
            ],
            [
                'label' => 'Total Pending',
                'value' => $pendingSteps->count(),
            ],
            [
                'label' => 'Recently Acted',
                'value' => $recentSteps->count(),
            ],
        ];

        if (
            $this->selectedStepId &&
            !$pendingSteps->contains(fn (KpiTaskApprovalStep $step) => $step->id === $this->selectedStepId)
        ) {
            $this->cancelDecision();
        }
    }

    public function openStep(int $stepId): void
    {
        $step = $this->findOwnedPendingStep($stepId);

        if (!$this->isActionableStep($step)) {
            throw ValidationException::withMessages([
                'selectedStepId' => 'This approval is not ready yet.',
            ]);
        }

        $this->selectedStepId = $step->id;
        $this->decisionRemark = '';
        $this->resetErrorBag();
    }

    public function cancelDecision(): void
    {
        $this->selectedStepId = null;
        $this->decisionRemark = '';
        $this->resetErrorBag();
    }

    public function approveSelected(): void
    {
        $this->decideSelected('approved');
    }

    public function rejectSelected(): void
    {
        if (trim($this->decisionRemark) === '') {
            throw ValidationException::withMessages([
                'decisionRemark' => 'Remark is required when rejecting a submission.',
            ]);
        }

        $this->decideSelected('rejected');
    }

    public function getSelectedStepProperty(): ?KpiTaskApprovalStep
    {
        if (!$this->selectedStepId) {
            return null;
        }

        return KpiTaskApprovalStep::query()
            ->with($this->approvalRelations())
            ->where('id', $this->selectedStepId)
            ->where('approver_user_id', Auth::id())
            ->where('status', 'pending')
            ->first();
    }

    public function render()
    {
        return view('livewire.kpi.approvals', [
            'selectedStep' => $this->getSelectedStepProperty(),
            'pendingFirstSteps' => $this->pendingSteps->where('step_order', 1)->values(),
            'pendingFinalSteps' => $this->pendingSteps->where('step_order', '>', 1)->values(),
        ]);
    }

    protected function decideSelected(string $decision): void
    {
        $step = $this->getSelectedStepProperty();

        if (!$step) {
            throw ValidationException::withMessages([
                'selectedStepId' => 'Select a pending approval first.',
            ]);
        }

        if (!$this->isActionableStep($step)) {
            throw ValidationException::withMessages([
                'selectedStepId' => 'This approval is no longer actionable.',
            ]);
        }

        DB::transaction(function () use ($step, $decision): void {
            $lockedStep = KpiTaskApprovalStep::query()
                ->whereKey($step->id)
                ->where('approver_user_id', Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedStep->status !== 'pending') {
                throw ValidationException::withMessages([
                    'selectedStepId' => 'This approval has already been processed.',
                ]);
            }

            $submission = $lockedStep->submission()
                ->with(['instance', 'approvalSteps'])
                ->firstOrFail();

            $approvalSteps = $submission->approvalSteps->sortBy('step_order')->values();

            if ($approvalSteps
                ->where('step_order', '<', $lockedStep->step_order)
                ->contains(fn (KpiTaskApprovalStep $previousStep) => $previousStep->status !== 'approved')
            ) {
                throw ValidationException::withMessages([
                    'selectedStepId' => 'A previous approval step is still pending.',
                ]);
            }

            $now = now();
            $remark = trim($this->decisionRemark) !== '' ? trim($this->decisionRemark) : null;

            $lockedStep->update([
                'status' => $decision,
                'acted_at' => $now,
                'remark' => $remark,
            ]);

            if ($decision === 'rejected') {
                $submission->approvalSteps()
                    ->where('step_order', '>', $lockedStep->step_order)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'cancelled',
                        'acted_at' => $now,
                        'remark' => 'Stopped because an earlier approver rejected the submission.',
                    ]);

                $submission->update([
                    'status' => 'rejected',
                    'rejection_reason' => $remark,
                ]);

                $submission->instance->update([
                    'status' => 'rejected',
                    'failure_reason' => $remark,
                    'final_outcome' => null,
                    'finalized_at' => null,
                ]);

                return;
            }

            $nextPendingStep = $approvalSteps
                ->first(fn (KpiTaskApprovalStep $approvalStep) => $approvalStep->step_order > $lockedStep->step_order && $approvalStep->status === 'pending');

            if ($nextPendingStep) {
                $submission->update([
                    'status' => 'waiting_final_approval',
                    'first_approved_at' => $submission->first_approved_at ?: $now,
                ]);

                $submission->instance->update([
                    'status' => 'waiting_final_approval',
                    'failure_reason' => null,
                ]);

                return;
            }

            $finalStatus = $submission->is_late ? 'failed_late' : 'passed';
            $finalReason = $submission->is_late ? 'Approved after cutoff time.' : null;

            $submission->update([
                'status' => 'approved',
                'first_approved_at' => $submission->first_approved_at ?: $now,
                'final_approved_at' => $now,
                'rejection_reason' => null,
            ]);

            $submission->instance->update([
                'status' => $finalStatus,
                'final_outcome' => $finalStatus,
                'finalized_at' => $now,
                'failure_reason' => $finalReason,
            ]);
        });

        $approved = $decision === 'approved';

        $this->cancelDecision();
        $this->loadQueue();

        session()->flash(
            'message',
            $approved ? 'Submission approved.' : 'Submission rejected and returned to the employee.'
        );
    }

    protected function findOwnedPendingStep(int $stepId): KpiTaskApprovalStep
    {
        return KpiTaskApprovalStep::query()
            ->with($this->approvalRelations())
            ->where('id', $stepId)
            ->where('approver_user_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();
    }

    protected function isActionableStep(KpiTaskApprovalStep $step): bool
    {
        $submission = $step->submission;

        if (!$submission || $submission->status === 'rejected') {
            return false;
        }

        return $submission->approvalSteps
            ->where('step_order', '<', $step->step_order)
            ->every(fn (KpiTaskApprovalStep $previousStep) => $previousStep->status === 'approved');
    }

    protected function approvalRelations(): array
    {
        return [
            'approver',
            'submission.images',
            'submission.submittedBy',
            'submission.approvalSteps.approver',
            'submission.instance.template.group',
            'submission.instance.user',
        ];
    }
}
