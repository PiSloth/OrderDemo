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

        $query = SoftwareAccountReview::with(['account.platform', 'assignedToUser', 'completedByUser']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('result')) {
            $query->where('result', $request->input('result'));
        }

        $reviews = $query->orderBy('scheduled_at', 'desc')->paginate(15)->withQueryString();

        $accounts = SoftwareAccount::where('lifecycle_status', 'active')
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'username']);

        $reviewers = User::where('suspended', false)->orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('DigitalAsset/Reviews/Index', [
            'reviews' => $reviews,
            'accounts' => $accounts,
            'reviewers' => $reviewers,
            'filters' => $request->only(['status', 'result']),
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

            return back()->with('success', 'Security review completed and account status synchronized.');
        } catch (\Throwable $e) {
            return back()->withErrors(['complete' => $e->getMessage()]);
        }
    }
}
