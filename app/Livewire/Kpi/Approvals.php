<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiTaskApprovalStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Approvals extends Component
{
    public $pendingSteps;
    public $recentSteps;
    public array $summaryCards = [];

    public ?int $selectedStepId = null;
    public string $decisionRemark = '';

    #[Url(keep: true)]
    public $filterEmployeeId = 'all';

    #[Url(keep: true)]
    public $filterDate = '';

    #[Url(keep: true)]
    public $filterTemplateId = 'all';

    #[Url(keep: true)]
    public $filterFrequency = 'all';

    public array $filterEmployees = [];
    public array $filterMonths = [];
    public array $filterTemplates = [];


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
            ->filter(fn(KpiTaskApprovalStep $step) => $this->isActionableStep($step))
            ->values();

        $todayStr = now()->toDateString();

        $pendingSteps->transform(function (KpiTaskApprovalStep $step) use ($todayStr) {
            $freq = $step->submission?->instance?->template?->frequency ?? '';
            $date = $step->submission?->submitted_at?->toDateString()
                ?? $step->submission?->instance?->task_date?->toDateString()
                ?? $step->created_at?->toDateString();

            $step->is_on_demand = ($freq === 'on_demand');
            $step->is_today = ($date === $todayStr);
            $step->is_today_on_demand = ($step->is_on_demand && $step->is_today);

            return $step;
        });

        $recentSteps = KpiTaskApprovalStep::query()
            ->with($this->approvalRelations())
            ->where('approver_user_id', $userId)
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('acted_at')
            ->limit(10)
            ->get();

        $this->pendingSteps = $pendingSteps;
        $this->recentSteps = $recentSteps;

        $this->filterEmployees = $pendingSteps->map(function ($step) {
            $user = $step->submission?->instance?->user;
            return $user ? ['id' => $user->id, 'name' => $user->name] : null;
        })->filter()->unique('id')->values()->toArray();

        $this->filterMonths = $pendingSteps->map(function ($step) {
            $date = $step->submission?->submitted_at ?? $step->submission?->created_at ?? $step->submission?->instance?->task_date;
            return $date ? $date->format('Y-m') : null;
        })->filter()->unique()->sort()->values()->toArray();

        $this->filterTemplates = $pendingSteps->map(function ($step) {
            $template = $step->submission?->instance?->template;
            return $template ? ['id' => $template->id, 'title' => $template->title] : null;
        })->filter()->unique('id')->values()->toArray();

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
                'label' => 'Today On-Demand',
                'value' => $pendingSteps->filter(fn($s) => $s->is_today_on_demand)->count(),
            ],
            [
                'label' => 'Total Pending',
                'value' => $pendingSteps->count(),
            ],
        ];

        if (
            $this->selectedStepId &&
            !$pendingSteps->contains(fn(KpiTaskApprovalStep $step) => $step->id === $this->selectedStepId)
        ) {
            $this->cancelDecision();
        }
    }

    public function getFilteredStepsProperty()
    {
        $steps = $this->pendingSteps;

        if ($this->filterEmployeeId && $this->filterEmployeeId !== 'all') {
            $steps = $steps->filter(function ($step) {
                return ($step->submission?->instance?->user_id ?? null) == $this->filterEmployeeId;
            });
        }

        if ($this->filterDate) {
            $monthStr = \Illuminate\Support\Carbon::parse($this->filterDate)->format('Y-m');
            $steps = $steps->filter(function ($step) use ($monthStr) {
                $date = $step->submission?->submitted_at ?? $step->submission?->created_at ?? $step->submission?->instance?->task_date;
                return $date ? $date->format('Y-m') === $monthStr : false;
            });
        }

        if ($this->filterTemplateId && $this->filterTemplateId !== 'all') {
            $steps = $steps->filter(function ($step) {
                return ($step->submission?->instance?->task_template_id ?? null) == $this->filterTemplateId;
            });
        }

        if ($this->filterFrequency && $this->filterFrequency !== 'all') {
            $steps = $steps->filter(function ($step) {
                $freq = $step->submission?->instance?->template?->frequency ?? '';
                return $freq === $this->filterFrequency;
            });
        }

        return $steps->values();
    }

    public function approveStepDirectly(int $stepId, string $remark = ''): void
    {
        $this->selectedStepId = $stepId;
        $this->decisionRemark = $remark;
        $this->decideSelected('approved');
    }

    public function rejectStepDirectly(int $stepId, string $remark): void
    {
        $this->selectedStepId = $stepId;
        $this->decisionRemark = $remark;
        if (trim($remark) === '') {
            throw ValidationException::withMessages([
                'decisionRemark' => 'Remark is required when rejecting a submission.',
            ]);
        }
        $this->decideSelected('rejected');
    }

    public function render()
    {
        return view('livewire.kpi.approvals', [
            'selectedStep' => $this->getSelectedStepProperty(),
            'pendingFirstSteps' => $this->pendingSteps->where('step_order', 1)->values(),
            'pendingFinalSteps' => $this->pendingSteps->where('step_order', '>', 1)->values(),
            'pendingFirstStepGroups' => $this->buildFirstStepQueueGroups(
                $this->pendingSteps->where('step_order', 1)->values()
            ),
            'filteredSteps' => $this->filteredSteps,
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
                ->contains(fn(KpiTaskApprovalStep $previousStep) => $previousStep->status !== 'approved')
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
                ->first(fn(KpiTaskApprovalStep $approvalStep) => $approvalStep->step_order > $lockedStep->step_order && $approvalStep->status === 'pending');

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

            \App\Models\TodoList::syncKpiApproval($submission->instance);
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
            ->every(fn(KpiTaskApprovalStep $previousStep) => $previousStep->status === 'approved');
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
            'submission.instance.template',
        ];
    }

    protected function buildFirstStepQueueGroups(Collection $steps): array
    {
        return $steps
            ->groupBy(function (KpiTaskApprovalStep $step): string {
                return $step->submission?->submittedBy?->name ?? $step->submission?->instance?->user?->name ?? 'Unknown submitter';
            })
            ->sortKeys()
            ->map(function (Collection $submitterSteps, string $submitterName): array {
                $requestedDateGroups = $submitterSteps
                    ->groupBy(function (KpiTaskApprovalStep $step): string {
                        $requestedAt = $step->submission?->submitted_at ?? $step->submission?->created_at;

                        return $requestedAt?->toDateString() ?? 'Unknown date';
                    })
                    ->sortKeysDesc()
                    ->map(function (Collection $datedSteps, string $requestedDate): array {
                        return [
                            'requested_date' => $requestedDate,
                            'requested_date_label' => $requestedDate === 'Unknown date'
                                ? 'Unknown date'
                                : \Illuminate\Support\Carbon::parse($requestedDate)->format('F j, Y'),
                            'items' => $datedSteps
                                ->sortByDesc(fn (KpiTaskApprovalStep $step) => $step->submitted_at?->timestamp ?? $step->created_at?->timestamp ?? 0)
                                ->values()
                                ->all(),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'submitter_name' => $submitterName,
                    'items_count' => $submitterSteps->count(),
                    'requested_dates' => $requestedDateGroups,
                ];
            })
            ->values()
            ->all();
    }
}
