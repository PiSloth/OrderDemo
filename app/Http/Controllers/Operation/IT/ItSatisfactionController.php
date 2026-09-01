<?php

namespace App\Http\Controllers\Operation\IT;

use App\Http\Controllers\Controller;
use App\Models\ItSatisfactionRating;
use App\Models\ItSatisfactionSurvey;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ItSatisfactionController extends Controller
{
    /**
     * Get active survey for current user if not submitted yet.
     */
    public function checkActiveSurvey(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['survey' => null]);
        }

        $activeSurvey = ItSatisfactionSurvey::activeNow()
            ->latest('id')
            ->first();

        if (!$activeSurvey || !$activeSurvey->isUserEligible($user)) {
            return response()->json(['survey' => null]);
        }

        $hasSubmitted = ItSatisfactionRating::where('survey_id', $activeSurvey->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($hasSubmitted) {
            return response()->json(['survey' => null]);
        }

        return response()->json([
            'survey' => [
                'id' => $activeSurvey->id,
                'title' => $activeSurvey->title,
                'description' => $activeSurvey->description,
                'badge_text' => $activeSurvey->badge_text,
                'start_date' => $activeSurvey->start_date?->format('Y-m-d'),
                'end_date' => $activeSurvey->end_date?->format('Y-m-d'),
                'start_date_formatted' => $activeSurvey->start_date?->format('d M Y'),
                'end_date_formatted' => $activeSurvey->end_date?->format('d M Y'),
                'rating_scale' => $activeSurvey->rating_scale,
                'is_mandatory' => $activeSurvey->is_mandatory,
                'target_scope' => $activeSurvey->target_scope,
                'criteria' => $activeSurvey->criteria ?? [
                    ['key' => 'speed', 'label' => 'Support Response Speed'],
                    ['key' => 'helpfulness', 'label' => 'Helpfulness & Communication'],
                    ['key' => 'stability', 'label' => 'Network & System Stability'],
                ],
            ]
        ]);
    }

    /**
     * Submit a satisfaction rating (1 to 5).
     */
    public function storeRating(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $surveyId = $request->input('survey_id');
        $survey = ItSatisfactionSurvey::find($surveyId);
        if ($survey && !$survey->isUserEligible($user)) {
            return response()->json(['message' => 'Your department or profile is not included in this satisfaction survey campaign.'], 403);
        }

        $rules = [
            'survey_id' => 'required|exists:it_satisfaction_surveys,id',
            'rating' => 'required|integer|min:1|max:5',
            'aspect_ratings' => 'nullable|array',
            'feedback' => 'nullable|string|max:2000',
        ];

        // If score is under 3 (1 or 2), feedback is strictly required
        if ((int) $request->input('rating') < 3) {
            $rules['feedback'] = 'required|string|min:5|max:2000';
        }

        $validated = $request->validate($rules, [
            'rating.required' => 'Please select a rating score from 1 to 5.',
            'rating.min' => 'Rating score must be at least 1.',
            'rating.max' => 'Rating score cannot exceed 5.',
            'feedback.required' => 'Feedback is required when rating is under 3 to help us understand how to improve.',
            'feedback.min' => 'Please provide at least a brief explanation (minimum 5 characters).',
        ]);

        // Prevent duplicate rating submission for this survey
        $existing = ItSatisfactionRating::where('survey_id', $validated['survey_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'already_submitted' => true,
                'message' => 'You have already submitted your rating for this survey.'
            ]);
        }

        $rating = ItSatisfactionRating::create([
            'survey_id' => $validated['survey_id'],
            'user_id' => $user->id,
            'user_name' => $user->name,
            'rating' => $validated['rating'],
            'aspect_ratings' => $validated['aspect_ratings'] ?? null,
            'feedback' => $validated['feedback'] ?? null,
            'submitted_at' => Carbon::now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your IT Department satisfaction rating has been recorded.',
                'redirect_url' => '/performance/sale-dashboard',
                'data' => $rating,
            ]);
        }

        return redirect('/performance/sale-dashboard')->with('message', 'Thank you! Your IT Department satisfaction rating has been recorded.');
    }

    /**
     * Check if the user has a specific satisfaction permission or super admin role.
     */
    protected function hasPermission($user, string $permission): bool
    {
        if (!$user) return false;
        $role = strtolower($user->role ?? '');
        if (in_array($role, ['super admin', 'super_admin', 'admin'])) return true;
        try {
            if ($user->hasRole(['Super Admin', 'super_admin', 'Admin', 'admin'])) return true;
            return $user->can($permission);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * IT Satisfaction Management & Analytics Dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$this->hasPermission($user, 'it.satisfaction.view')) {
            abort(403, 'Unauthorized. You do not have permission to view IT Satisfaction Survey results.');
        }

        $surveyId = $request->input('survey_id');
        $ratingFilter = $request->input('rating');
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // All campaigns
        $campaigns = ItSatisfactionSurvey::withCount('ratings')
            ->with(['creator'])
            ->latest('id')
            ->get()
            ->map(function ($c) {
                $avgRating = $c->ratings()->avg('rating');
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'description' => $c->description,
                    'badge_text' => $c->badge_text,
                    'start_date' => $c->start_date?->format('Y-m-d'),
                    'end_date' => $c->end_date?->format('Y-m-d'),
                    'start_date_formatted' => $c->start_date?->format('d M Y'),
                    'end_date_formatted' => $c->end_date?->format('d M Y'),
                    'is_active' => $c->is_active,
                    'is_mandatory' => $c->is_mandatory,
                    'rating_scale' => $c->rating_scale,
                    'target_scope' => $c->target_scope ?? [
                        'excluded_department_ids' => [1, 10],
                        'excluded_department_names' => ['IT Department', 'IT & Systems'],
                        'target_department_ids' => [],
                        'target_office_position_ids' => [],
                        'excluded_office_position_ids' => [],
                        'target_roles' => [],
                        'excluded_roles' => [],
                    ],
                    'criteria' => $c->criteria ?? [
                        ['key' => 'speed', 'label' => 'Support Response Speed'],
                        ['key' => 'helpfulness', 'label' => 'Helpfulness & Communication'],
                        ['key' => 'stability', 'label' => 'Network & System Stability'],
                    ],
                    'ratings_count' => $c->ratings_count,
                    'avg_rating' => $avgRating ? round($avgRating, 2) : 0,
                    'created_by_name' => $c->creator?->name ?? 'System',
                    'created_at' => $c->created_at?->format('d M Y, h:i A'),
                ];
            });

        // Selected or default active campaign
        $selectedCampaign = null;
        if ($surveyId) {
            $selectedCampaign = $campaigns->firstWhere('id', (int) $surveyId);
        } else {
            $selectedCampaign = $campaigns->firstWhere('is_active', true) ?? $campaigns->first();
        }

        // Query ratings
        $query = ItSatisfactionRating::with(['user', 'survey']);

        if ($selectedCampaign) {
            $query->where('survey_id', $selectedCampaign['id']);
        }

        if ($ratingFilter) {
            $query->where('rating', (int) $ratingFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('feedback', 'like', "%{$search}%");
            });
        }

        if ($startDate) {
            $query->whereDate('submitted_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('submitted_at', '<=', $endDate);
        }

        // Ratings list
        $ratings = (clone $query)
            ->latest('submitted_at')
            ->paginate(15)
            ->through(function ($r) {
                return [
                    'id' => $r->id,
                    'survey_id' => $r->survey_id,
                    'survey_title' => $r->survey?->title,
                    'user_id' => $r->user_id,
                    'user_name' => $r->user_name,
                    'user_email' => $r->user?->email,
                    'rating' => $r->rating,
                    'aspect_ratings' => $r->aspect_ratings,
                    'feedback' => $r->feedback,
                    'submitted_at' => $r->submitted_at?->format('d M Y, h:i A'),
                ];
            });

        // Analytics statistics
        $baseStatsQuery = ItSatisfactionRating::query();
        if ($selectedCampaign) {
            $baseStatsQuery->where('survey_id', $selectedCampaign['id']);
        }

        $totalResponses = (clone $baseStatsQuery)->count();
        $averageScore = (clone $baseStatsQuery)->avg('rating');
        $averageScore = $averageScore ? round($averageScore, 2) : 0;

        $starBreakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = (clone $baseStatsQuery)->where('rating', $i)->count();
            $percentage = $totalResponses > 0 ? round(($count / $totalResponses) * 100, 1) : 0;
            $starBreakdown[$i] = [
                'stars' => $i,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        $lowScoreCount = (clone $baseStatsQuery)->where('rating', '<', 3)->count();
        $highScoreCount = (clone $baseStatsQuery)->where('rating', '>=', 4)->count();
        $satisfactionRate = $totalResponses > 0 ? round(($highScoreCount / $totalResponses) * 100, 1) : 0;

        return Inertia::render('Operation/IT/Satisfaction/Index', [
            'campaigns' => $campaigns,
            'selectedCampaign' => $selectedCampaign,
            'departments' => \App\Models\Department::all(['id', 'name']),
            'positions' => \App\Models\Position::all(['id', 'name']),
            'ratings' => $ratings,
            'filters' => [
                'survey_id' => $surveyId,
                'rating' => $ratingFilter,
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'analytics' => [
                'total_responses' => $totalResponses,
                'average_score' => $averageScore,
                'satisfaction_rate' => $satisfactionRate,
                'low_score_count' => $lowScoreCount,
                'high_score_count' => $highScoreCount,
                'star_breakdown' => $starBreakdown,
            ],
            'permissions' => [
                'can_view' => $this->hasPermission($user, 'it.satisfaction.view'),
                'can_create' => $this->hasPermission($user, 'it.satisfaction.create'),
                'can_update' => $this->hasPermission($user, 'it.satisfaction.update'),
                'can_delete' => $this->hasPermission($user, 'it.satisfaction.delete'),
                'can_export' => $this->hasPermission($user, 'it.satisfaction.export'),
            ]
        ]);
    }

    /**
     * Store a new survey campaign (with start date & end date).
     */
    public function storeCampaign(Request $request)
    {
        $user = $request->user();
        if (!$this->hasPermission($user, 'it.satisfaction.create')) {
            abort(403, 'Unauthorized. You do not have permission to create survey campaigns.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'badge_text' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'is_mandatory' => 'boolean',
            'rating_scale' => 'integer|min:3|max:10',
            'target_scope' => 'nullable|array',
            'criteria' => 'nullable|array',
        ]);

        $defaultScope = [
            'excluded_department_ids' => [1, 10],
            'excluded_department_names' => ['IT Department', 'IT & Systems'],
            'target_department_ids' => [],
            'target_office_position_ids' => [],
            'excluded_office_position_ids' => [],
            'target_roles' => [],
            'excluded_roles' => [],
        ];

        $defaultCriteria = [
            ['key' => 'speed', 'label' => 'Support Response Speed'],
            ['key' => 'helpfulness', 'label' => 'Helpfulness & Communication'],
            ['key' => 'stability', 'label' => 'Network & System Stability'],
        ];

        $survey = ItSatisfactionSurvey::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? 'Please rate your overall experience and satisfaction with IT Department services.',
            'badge_text' => $validated['badge_text'] ?? 'IT Satisfaction Survey',
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $request->boolean('is_active', true),
            'is_mandatory' => $request->boolean('is_mandatory', true),
            'rating_scale' => $validated['rating_scale'] ?? 5,
            'target_scope' => $validated['target_scope'] ?? $defaultScope,
            'criteria' => $validated['criteria'] ?? $defaultCriteria,
            'created_by' => $user?->id,
        ]);

        return back()->with('message', 'IT Satisfaction Survey Campaign created successfully.');
    }

    /**
     * Update an existing campaign.
     */
    public function updateCampaign(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->hasPermission($user, 'it.satisfaction.update')) {
            abort(403, 'Unauthorized. You do not have permission to edit survey campaigns.');
        }

        $survey = ItSatisfactionSurvey::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'badge_text' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'is_mandatory' => 'boolean',
            'target_scope' => 'nullable|array',
            'criteria' => 'nullable|array',
        ]);

        $survey->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $survey->description,
            'badge_text' => $validated['badge_text'] ?? $survey->badge_text,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $request->boolean('is_active', $survey->is_active),
            'is_mandatory' => $request->boolean('is_mandatory', $survey->is_mandatory),
            'target_scope' => $request->has('target_scope') ? $validated['target_scope'] : $survey->target_scope,
            'criteria' => $request->has('criteria') ? $validated['criteria'] : $survey->criteria,
        ]);

        return back()->with('message', 'Campaign updated successfully.');
    }

    /**
     * Toggle active status of a campaign.
     */
    public function toggleCampaignStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->hasPermission($user, 'it.satisfaction.update')) {
            abort(403, 'Unauthorized. You do not have permission to toggle survey status.');
        }

        $survey = ItSatisfactionSurvey::findOrFail($id);
        $survey->is_active = !$survey->is_active;
        $survey->save();

        return back()->with('message', $survey->is_active ? 'Campaign activated.' : 'Campaign deactivated.');
    }

    /**
     * Delete a campaign.
     */
    public function destroyCampaign(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->hasPermission($user, 'it.satisfaction.delete')) {
            abort(403, 'Unauthorized. You do not have permission to delete survey campaigns.');
        }

        $survey = ItSatisfactionSurvey::findOrFail($id);
        $survey->delete();

        return back()->with('message', 'Campaign deleted successfully.');
    }

    /**
     * Export ratings to Excel (Anonymous, Grouped by Department, Pretty Separators).
     */
    public function export(Request $request)
    {
        $user = $request->user();
        if (!$this->hasPermission($user, 'it.satisfaction.export')) {
            abort(403, 'Unauthorized. You do not have permission to export survey results.');
        }

        $surveyId = $request->input('survey_id');
        $survey = $surveyId ? ItSatisfactionSurvey::find($surveyId) : null;

        $query = ItSatisfactionRating::with(['user.department', 'survey']);
        if ($surveyId) {
            $query->where('survey_id', $surveyId);
        }

        $ratings = $query->latest('submitted_at')->get();

        $filename = 'it_satisfaction_summary_by_department_' . now()->format('Ymd_His') . '.xlsx';
        $path = storage_path('app/' . $filename);

        $writer = SimpleExcelWriter::create($path);

        // Group ratings by department name
        $grouped = $ratings->groupBy(function ($rating) {
            return $rating->user?->department?->name ?? 'General / Unassigned';
        });

        foreach ($grouped as $departmentName => $groupRatings) {
            $totalCount = $groupRatings->count();
            $avgScore = $totalCount > 0 ? round($groupRatings->avg('rating'), 2) : 0;
            
            $star5 = $groupRatings->where('rating', 5)->count();
            $star4 = $groupRatings->where('rating', 4)->count();
            $star3 = $groupRatings->where('rating', 3)->count();
            $star2 = $groupRatings->where('rating', 2)->count();
            $star1 = $groupRatings->where('rating', 1)->count();

            $writer->addRow([
                'Department' => $departmentName,
                'Survey Period' => $survey ? ($survey->start_date?->format('d M Y') . ' - ' . $survey->end_date?->format('d M Y')) : 'All Periods',
                'Total Responses' => $totalCount,
                'Average Satisfaction Score' => "{$avgScore} / 5.0",
                '5-Star (Very Satisfied)' => $star5,
                '4-Star (Satisfied)' => $star4,
                '3-Star (Neutral)' => $star3,
                '2-Star (Dissatisfied)' => $star2,
                '1-Star (Very Dissatisfied)' => $star1,
            ]);
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }

    /**
     * Fetch complete structured report data for React PDF Export (Grouped by Score).
     */
    public function getReportData(Request $request)
    {
        $user = $request->user();
        if (!$this->hasPermission($user, 'it.satisfaction.export') && !$this->hasPermission($user, 'it.satisfaction.view')) {
            abort(403, 'Unauthorized to view report data.');
        }

        $surveyId = $request->input('survey_id');
        $query = ItSatisfactionRating::with(['user.department', 'survey']);
        if ($surveyId) {
            $query->where('survey_id', $surveyId);
        }

        $allRatings = $query->latest('submitted_at')->get();

        $survey = $surveyId 
            ? ItSatisfactionSurvey::find($surveyId) 
            : (ItSatisfactionSurvey::activeNow()->latest('id')->first() ?? ItSatisfactionSurvey::latest('id')->first());

        $totalResponses = $allRatings->count();
        $averageScore = $totalResponses > 0 ? round($allRatings->avg('rating'), 2) : 0;
        $satisfactionRate = $totalResponses > 0 ? round(($allRatings->where('rating', '>=', 4)->count() / $totalResponses) * 100, 1) : 0;

        // Group ratings by Rating Score (5 down to 1)
        $scoreGroups = [];
        for ($s = 5; $s >= 1; $s--) {
            $matching = $allRatings->where('rating', $s)->values();
            $feedbacks = $matching->map(function ($r) {
                return [
                    'id' => $r->id,
                    'department_name' => $r->user?->department?->name ?? 'General / Unassigned',
                    'feedback' => trim($r->feedback ?? ''),
                    'submitted_at' => $r->submitted_at?->format('d M Y'),
                ];
            });

            $scoreGroups[] = [
                'score' => $s,
                'total_count' => $matching->count(),
                'percentage' => $totalResponses > 0 ? round(($matching->count() / $totalResponses) * 100, 1) : 0,
                'feedbacks' => $feedbacks,
            ];
        }

        return response()->json([
            'survey' => $survey ? [
                'id' => $survey->id,
                'title' => $survey->title,
                'start_date_formatted' => $survey->start_date?->format('d M Y'),
                'end_date_formatted' => $survey->end_date?->format('d M Y'),
            ] : null,
            'analytics' => [
                'total_responses' => $totalResponses,
                'average_score' => $averageScore,
                'satisfaction_rate' => $satisfactionRate,
            ],
            'score_groups' => $scoreGroups,
        ]);
    }
}
