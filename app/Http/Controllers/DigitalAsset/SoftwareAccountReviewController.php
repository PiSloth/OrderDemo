<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountReview;
use App\Models\User;
use App\Services\DigitalAsset\SoftwareAccountReviewService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SoftwareAccountReviewController extends Controller
{
    public function __construct(protected SoftwareAccountReviewService $reviewService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SoftwareAccountReview::class);

        $query = SoftwareAccountReview::with([
            'account.platform',
            'assignedToUser',
            'completedByUser',
            'todoReferences.todo.status',
            'todoReferences.todo.assignedUser',
            'todoReferences.todo.closedByUser',
            'todoReferences.todo.createdByUser',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('result')) {
            $query->where('result', $request->input('result'));
        }

        if ($request->filled('todo_status')) {
            $todoStatus = $request->input('todo_status');
            if ($todoStatus === 'pending') {
                $query->whereHas('todoReferences', function ($refQ) {
                    $refQ->whereHas('todo', function ($todoQ) {
                        $todoQ->whereNull('closed_at');
                    });
                });
            } elseif ($todoStatus === 'completed') {
                $query->whereHas('todoReferences', function ($refQ) {
                    $refQ->whereHas('todo', function ($todoQ) {
                        $todoQ->whereNotNull('closed_at');
                    });
                });
            } elseif ($todoStatus === 'needs_todo') {
                $query->whereIn('result', ['needs_action', 'at_risk'])
                    ->whereDoesntHave('todoReferences');
            } elseif ($todoStatus === 'has_todo') {
                $query->whereHas('todoReferences');
            } elseif ($todoStatus === 'no_todo') {
                $query->whereDoesntHave('todoReferences');
            } elseif (is_numeric($todoStatus)) {
                $query->whereHas('todoReferences', function ($refQ) use ($todoStatus) {
                    $refQ->whereHas('todo', function ($todoQ) use ($todoStatus) {
                        $todoQ->where('todo_status_id', $todoStatus);
                    });
                });
            }
        }

        $reviews = $query->orderBy('scheduled_at', 'desc')->paginate(15)->withQueryString();

        $accounts = SoftwareAccount::where('lifecycle_status', 'active')
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'username']);

        $reviewers = User::where('suspended', false)->orderBy('name')->get(['id', 'name', 'email']);

        $dueTimes = \App\Models\TodoDueTime::with(['category', 'priority'])->get()->map(function ($dt) {
            return [
                'id' => $dt->id,
                'name' => ($dt->category?->name ?? 'General') . ' (' . ($dt->priority?->level ?? 'Standard') . ') - ' . $dt->duration . 'h',
                'duration' => $dt->duration,
            ];
        });

        $todoStatuses = \App\Models\TodoStatus::orderBy('id')->get(['id', 'status']);

        // Fetch Unaligned / Compliance Gap accounts (Tab 1: Live Radar)
        $gapFilters = [
            'search' => $request->input('gap_search'),
            'gap_type' => $request->input('gap_type'),
            'todo_status' => $request->input('gap_todo_status'),
        ];
        $totalActiveAccounts = SoftwareAccount::where('lifecycle_status', '!=', 'retired')->count();
        $allUnfilteredGaps = $this->reviewService->getComplianceGapAccounts();
        $unalignedCount = $allUnfilteredGaps->count();
        $alignedCount = max(0, $totalActiveAccounts - $unalignedCount);
        $healthIndex = $totalActiveAccounts > 0 ? round(($alignedCount / $totalActiveAccounts) * 100, 1) : 100.0;

        $atRiskCount = $allUnfilteredGaps->filter(function ($acc) {
            return collect($acc->compliance_gaps)->contains('type', 'missing_property') || $acc->security_status === 'at_risk';
        })->count();

        $orphanCount = $allUnfilteredGaps->filter(function ($acc) {
            return collect($acc->compliance_gaps)->contains('type', 'missing_pic');
        })->count();

        $kpiMetrics = [
            'total_accounts' => $totalActiveAccounts,
            'aligned_accounts' => $alignedCount,
            'health_index' => $healthIndex,
            'at_risk_count' => $atRiskCount,
            'orphan_count' => $orphanCount,
        ];

        $unalignedAccounts = ($request->filled('gap_search') || $request->filled('gap_type') || $request->filled('gap_todo_status'))
            ? $this->reviewService->getComplianceGapAccounts($gapFilters)
            : $allUnfilteredGaps;

        return Inertia::render('DigitalAsset/Reviews/Index', [
            'reviews' => $reviews,
            'unalignedAccounts' => $unalignedAccounts->values(),
            'unalignedCount' => $unalignedCount,
            'reviewsCount' => $reviews->total(),
            'kpiMetrics' => $kpiMetrics,
            'accounts' => $accounts,
            'reviewers' => $reviewers,
            'dueTimes' => $dueTimes,
            'todoStatuses' => $todoStatuses,
            'filters' => $request->only(['status', 'result', 'todo_status']),
            'gapFilters' => $request->only(['gap_search', 'gap_type', 'gap_todo_status']),
            'initialTab' => (int) $request->input('tab', 0),
        ]);
    }

    public function schedule(Request $request, SoftwareAccount $account)
    {
        $this->authorize('scheduleReviews', $account);

        $validated = $request->validate([
            'review_type' => 'required|in:scheduled,ad_hoc,incident',
            'scheduled_at' => 'required|date',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->reviewService->scheduleReview(
                account: $account,
                reviewType: $validated['review_type'],
                scheduledAt: $validated['scheduled_at'],
                assignedToUserId: $validated['assigned_to'] ?? null,
                notes: $validated['notes'] ?? null,
                actorUserId: auth()->id()
            );

            return back()->with('success', 'Security review scheduled.');
        } catch (\Throwable $e) {
            return back()->withErrors(['schedule' => $e->getMessage()]);
        }
    }

    public function start(SoftwareAccountReview $review)
    {
        $this->authorize('completeReviews', $review->account);

        try {
            $this->reviewService->startReview($review, auth()->id());
            return back()->with('success', 'Review started.');
        } catch (\Throwable $e) {
            return back()->withErrors(['start' => $e->getMessage()]);
        }
    }

    public function complete(Request $request, SoftwareAccountReview $review)
    {
        $this->authorize('completeReviews', $review->account);

        $validated = $request->validate([
            'result' => 'required|in:compliant,needs_action,at_risk',
            'findings' => 'nullable|array',
            'notes' => 'nullable|string|max:2000',
            'next_review_days' => 'nullable|integer|min:7|max:365',
            'create_todo' => 'nullable|boolean',
            'todo_task' => 'nullable|string|max:1000',
            'todo_assigned_to' => 'nullable|exists:users,id',
            'todo_due_date' => 'nullable|date',
            'todo_due_time_id' => 'nullable|exists:todo_due_times,id',
            'todo_reason' => 'nullable|string|max:1000',
        ]);

        try {
            $this->reviewService->completeReview(
                review: $review,
                result: $validated['result'],
                completedByUserId: auth()->id(),
                findings: $validated['findings'] ?? null,
                notes: $validated['notes'] ?? null,
                nextReviewDays: $validated['next_review_days'] ?? 90
            );

            // Create referenced follow-up ToDo task if requested
            if (!empty($validated['create_todo'])) {
                $taskTitle = $validated['todo_task'] ?: ('[Remediation Required] ' . $review->account->account_name . ' - ' . ($validated['notes'] ?: 'Action required'));
                $reason = $validated['todo_reason'] ?: $validated['notes'] ?: "Review finalized as '{$validated['result']}'.";

                $todoRefService = app(\App\Services\Todo\TodoReferenceService::class);
                $todoRefService->createReferencedTask(
                    referenceModel: $review,
                    taskData: [
                        'task' => $taskTitle,
                        'assigned_user_id' => $validated['todo_assigned_to'] ?? $review->assigned_to ?? auth()->id(),
                        'due_date' => $validated['todo_due_date'] ?? now()->addDays(14)->toDateTimeString(),
                        'selected_due_time_id' => $validated['todo_due_time_id'] ?? null,
                    ],
                    reason: $reason,
                    module: 'digital_asset_review',
                    actionType: 'remediation',
                    metadata: [
                        'account_id' => $review->software_account_id,
                        'account_name' => $review->account->account_name,
                        'review_result' => $validated['result'],
                    ]
                );
            }

            return back()->with('success', 'Security review completed and account status synchronized.');
        } catch (\Throwable $e) {
            return back()->withErrors(['complete' => $e->getMessage()]);
        }
    }

    public function createTodo(Request $request, SoftwareAccountReview $review)
    {
        $this->authorize('completeReviews', $review->account);

        $validated = $request->validate([
            'task' => 'required|string|max:1000',
            'assigned_user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'todo_due_time_id' => 'nullable|exists:todo_due_times,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $todoRefService = app(\App\Services\Todo\TodoReferenceService::class);
            $ref = $todoRefService->createReferencedTask(
                referenceModel: $review,
                taskData: [
                    'task' => $validated['task'],
                    'assigned_user_id' => $validated['assigned_user_id'] ?? auth()->id(),
                    'due_date' => $validated['due_date'] ?? now()->addDays(14)->toDateTimeString(),
                    'selected_due_time_id' => $validated['todo_due_time_id'] ?? null,
                ],
                reason: $validated['reason'] ?: $review->notes ?: "Compliance follow-up action required",
                module: 'digital_asset_review',
                actionType: 'remediation',
                metadata: [
                    'account_id' => $review->software_account_id,
                    'account_name' => $review->account->account_name,
                    'review_result' => $review->result,
                ]
            );

            return back()->with('success', 'Action ToDo task #' . $ref->todo_list_id . ' created and linked.');
        } catch (\Throwable $e) {
            return back()->withErrors(['todo' => $e->getMessage()]);
        }
    }
}
