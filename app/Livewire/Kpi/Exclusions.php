<?php

namespace App\Livewire\Kpi;

use App\Models\Kpi\KpiExclusionRequest;
use App\Models\Kpi\KpiTaskAssignment;
use App\Services\Kpi\KpiAvailabilityService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.kpi')]
class Exclusions extends Component
{
    public string $month = '';
    public string $requestType = 'day';
    public string $requestedDate = '';
    public string $requestTaskAssignmentId = '';
    public string $requestReason = '';
    public array $reviewRemarks = [];

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->requestedDate = now()->toDateString();
    }

    public function createRequest(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $validated = $this->validate([
            'requestType' => ['required', Rule::in(['day', 'task'])],
            'requestedDate' => ['required', 'date'],
            'requestTaskAssignmentId' => ['nullable'],
            'requestReason' => ['required', 'string'],
        ], [], [
            'requestType' => 'request type',
            'requestedDate' => 'requested date',
            'requestTaskAssignmentId' => 'task',
            'requestReason' => 'reason',
        ]);

        $assignmentId = null;

        if ($validated['requestType'] === 'task') {
            if ($validated['requestTaskAssignmentId'] === '') {
                throw ValidationException::withMessages([
                    'requestTaskAssignmentId' => 'Choose a task for task-level exclusion.',
                ]);
            }

            $assignment = KpiTaskAssignment::query()
                ->with('template')
                ->where('id', (int) $validated['requestTaskAssignmentId'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            if ($assignment->template?->frequency !== 'daily') {
                throw ValidationException::withMessages([
                    'requestTaskAssignmentId' => 'Task-level exclusion is currently available only for daily tasks.',
                ]);
            }

            $assignmentId = $assignment->id;
        }

        $duplicateExists = KpiExclusionRequest::query()
            ->where('user_id', $user->id)
            ->where('request_type', $validated['requestType'])
            ->whereDate('requested_date', $validated['requestedDate'])
            ->when(
                $assignmentId,
                fn (Builder $query) => $query->where('task_assignment_id', $assignmentId),
                fn (Builder $query) => $query->whereNull('task_assignment_id')
            )
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'requestedDate' => 'An active exclusion request already exists for this date and scope.',
            ]);
        }

        KpiExclusionRequest::query()->create([
            'user_id' => $user->id,
            'task_assignment_id' => $assignmentId,
            'request_type' => $validated['requestType'],
            'requested_date' => $validated['requestedDate'],
            'reason' => trim($validated['requestReason']),
            'status' => 'pending',
        ]);

        $this->resetRequestForm();
        session()->flash('message', 'Exclusion request submitted.');
    }

    public function approveRequest(int $requestId, KpiAvailabilityService $availability): void
    {
        Gate::authorize('kpiApproveExclusions');

        $request = $this->reviewableRequestsQuery()->whereKey($requestId)->firstOrFail();

        $request->update([
            'status' => 'approved',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'reviewer_remark' => $this->reviewerRemarkFor($requestId),
        ]);

        $availability->applyApprovedExclusionRequest($request->fresh());
        unset($this->reviewRemarks[$requestId]);

        session()->flash('message', 'Exclusion request approved.');
    }

    public function rejectRequest(int $requestId): void
    {
        Gate::authorize('kpiApproveExclusions');

        $request = $this->reviewableRequestsQuery()->whereKey($requestId)->firstOrFail();

        $request->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'reviewer_remark' => $this->reviewerRemarkFor($requestId),
        ]);

        unset($this->reviewRemarks[$requestId]);
        session()->flash('message', 'Exclusion request rejected.');
    }

    public function render()
    {
        $monthStart = $this->monthStart();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $user = Auth::user();

        $assignments = KpiTaskAssignment::query()
            ->with(['template.group'])
            ->where('user_id', $user?->id)
            ->where('is_active', true)
            ->whereHas('template', fn (Builder $query) => $query->where('frequency', 'daily'))
            ->where(function (Builder $query) use ($monthEnd): void {
                $query
                    ->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $monthEnd->toDateString());
            })
            ->where(function (Builder $query) use ($monthStart): void {
                $query
                    ->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $monthStart->toDateString());
            })
            ->orderBy('task_template_id')
            ->get();

        $myRequests = KpiExclusionRequest::query()
            ->with(['assignment.template.group', 'reviewedBy'])
            ->where('user_id', $user?->id)
            ->whereBetween('requested_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->latest('requested_date')
            ->latest('id')
            ->get();

        $pendingReviews = collect();

        if (Gate::allows('kpiApproveExclusions')) {
            $pendingReviews = $this->reviewableRequestsQuery()
                ->whereBetween('requested_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->orderBy('requested_date')
                ->orderBy('id')
                ->get();
        }

        return view('livewire.kpi.exclusions', [
            'taskAssignments' => $assignments,
            'myRequests' => $myRequests,
            'pendingReviews' => $pendingReviews,
            'canReviewRequests' => Gate::allows('kpiApproveExclusions'),
        ]);
    }

    protected function monthStart(): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        } catch (\Throwable) {
            $this->month = now()->format('Y-m');

            return now()->startOfMonth();
        }
    }

    protected function reviewableRequestsQuery(): Builder
    {
        $query = KpiExclusionRequest::query()
            ->with(['user.department', 'assignment.template.group'])
            ->where('status', 'pending');

        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (Gate::allows('kpiViewCompanyLeaderboard') || Gate::allows('kpiManageTemplates')) {
            return $query;
        }

        if ($user->department_id) {
            return $query->whereHas('user', function (Builder $userQuery) use ($user): void {
                $userQuery->where('department_id', $user->department_id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    protected function reviewerRemarkFor(int $requestId): ?string
    {
        $remark = trim((string) ($this->reviewRemarks[$requestId] ?? ''));

        return $remark !== '' ? $remark : null;
    }

    protected function resetRequestForm(): void
    {
        $this->requestType = 'day';
        $this->requestedDate = now()->toDateString();
        $this->requestTaskAssignmentId = '';
        $this->requestReason = '';
        $this->resetErrorBag();
    }
}
