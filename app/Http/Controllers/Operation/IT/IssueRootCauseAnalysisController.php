<?php

namespace App\Http\Controllers\Operation\IT;

use App\Http\Controllers\Controller;
use App\IssueTracking\Models\Issue;
use App\IssueTracking\Models\IssueCategory;
use App\IssueTracking\Models\IssueRootCause;
use App\IssueTracking\Models\IssueRootCauseLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IssueRootCauseAnalysisController extends Controller
{
    /**
     * Display IT Issues Root Cause Analysis page with 2 tabs:
     * Tab 1: Issues list with root cause & remark (paginated 5 per page)
     * Tab 2: Root cause summary grouped by root cause category -> issue category
     */
    public function index(Request $request)
    {
        $rootCauseDate = $request->input('root_cause_date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');
        $activeTab = $request->input('tab', 'list'); // 'list' | 'summary'

        // 1. Query issues for Tab 1
        $query = Issue::with([
            'category',
            'status',
            'assignedUser',
            'rootCauses',
            'messages' => fn($q) => $q->where('is_log_note', true)->latest(),
        ]);

        // Filter by root cause created date
        if ($rootCauseDate) {
            $query->whereHas('rootCauses', function ($q) use ($rootCauseDate) {
                $q->whereDate('issue_root_cause_logs.created_at', $rootCauseDate);
            });
        } elseif ($startDate && $endDate) {
            $query->whereHas('rootCauses', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('issue_root_cause_logs.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            });
        } elseif ($startDate) {
            $query->whereHas('rootCauses', function ($q) use ($startDate) {
                $q->whereDate('issue_root_cause_logs.created_at', '>=', $startDate);
            });
        } elseif ($endDate) {
            $query->whereHas('rootCauses', function ($q) use ($endDate) {
                $q->whereDate('issue_root_cause_logs.created_at', '<=', $endDate);
            });
        }

        // Search query
        if ($search) {
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('rootCauses', function ($rcQ) use ($search) {
                        $rcQ->where('name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%")
                            ->orWhere('issue_root_cause_logs.remark', 'like', "%{$search}%");
                    });
            });
        }

        // Order by latest
        $query->orderByDesc('id');

        // Paginate 5 items per page as requested
        $issues = $query->paginate(5)->withQueryString();

        // If remark was previously logged in an issue message when closing, use it as fallback
        $issues->getCollection()->transform(function ($issue) {
            $rc = $issue->rootCauses->first();
            if ($rc && empty($rc->pivot->remark) && $issue->relationLoaded('messages')) {
                $logNote = $issue->messages->first(function ($m) {
                    return str_contains($m->message, 'Remark:');
                });
                if ($logNote && preg_match('/(?:Reason \/ )?Remark:\s*(.+)$/i', $logNote->message, $matches)) {
                    $rc->pivot->remark = trim($matches[1]);
                }
            }
            return $issue;
        });

        // 2. Tab 2: Root cause summary grouped by root cause category and then by issue category
        $summaryQuery = DB::table('issue_root_cause_logs as log')
            ->join('issue_root_causes as rc', 'log.root_cause_id', '=', 'rc.id')
            ->join('issues as i', 'log.issue_id', '=', 'i.id')
            ->leftJoin('issue_categories as ic', 'i.issue_category_id', '=', 'ic.id')
            ->whereNull('i.deleted_at');

        if ($rootCauseDate) {
            $summaryQuery->whereDate('log.created_at', $rootCauseDate);
        } elseif ($startDate && $endDate) {
            $summaryQuery->whereBetween('log.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } elseif ($startDate) {
            $summaryQuery->whereDate('log.created_at', '>=', $startDate);
        } elseif ($endDate) {
            $summaryQuery->whereDate('log.created_at', '<=', $endDate);
        }

        $summaryLogs = $summaryQuery->select([
            'rc.id as root_cause_id',
            'rc.name as root_cause_name',
            'rc.category as root_cause_category',
            'ic.id as issue_category_id',
            'ic.name as issue_category_name',
            'i.id as issue_id',
        ])->get();

        $groupedSummary = [];
        foreach ($summaryLogs as $row) {
            $rcCategory = !empty($row->root_cause_category) ? trim($row->root_cause_category) : 'General';
            $issueCategory = !empty($row->issue_category_name) ? trim($row->issue_category_name) : 'Unassigned Category';
            $issueCategoryId = $row->issue_category_id ?? 0;
            $rcName = trim($row->root_cause_name);

            if (!isset($groupedSummary[$rcCategory])) {
                $groupedSummary[$rcCategory] = [
                    'root_cause_category' => $rcCategory,
                    'total_count' => 0,
                    'issue_categories' => [],
                ];
            }
            $groupedSummary[$rcCategory]['total_count']++;

            if (!isset($groupedSummary[$rcCategory]['issue_categories'][$issueCategory])) {
                $groupedSummary[$rcCategory]['issue_categories'][$issueCategory] = [
                    'issue_category_id' => $issueCategoryId,
                    'issue_category_name' => $issueCategory,
                    'total_count' => 0,
                    'root_causes' => [],
                ];
            }
            $groupedSummary[$rcCategory]['issue_categories'][$issueCategory]['total_count']++;

            if (!isset($groupedSummary[$rcCategory]['issue_categories'][$issueCategory]['root_causes'][$rcName])) {
                $groupedSummary[$rcCategory]['issue_categories'][$issueCategory]['root_causes'][$rcName] = [
                    'root_cause_id' => $row->root_cause_id,
                    'name' => $rcName,
                    'count' => 0,
                ];
            }
            $groupedSummary[$rcCategory]['issue_categories'][$issueCategory]['root_causes'][$rcName]['count']++;
        }

        // Format summary into index arrays for frontend consumption
        $formattedSummary = array_values(array_map(function ($rcCatGroup) {
            $rcCatGroup['issue_categories'] = array_values(array_map(function ($issueCatGroup) {
                $issueCatGroup['root_causes'] = array_values($issueCatGroup['root_causes']);
                return $issueCatGroup;
            }, $rcCatGroup['issue_categories']));
            return $rcCatGroup;
        }, $groupedSummary));

        // Sort root cause categories alphabetically
        usort($formattedSummary, fn($a, $b) => strcmp($a['root_cause_category'], $b['root_cause_category']));

        // Master data
        $rootCauses = IssueRootCause::orderBy('category')->orderBy('name')->get();
        $existingRootCauseCategories = IssueRootCause::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        $defaultCategories = ['Software', 'Hardware', 'Network', 'Human Factor', 'Process', 'Vendor', 'General'];
        $suggestedCategories = collect($existingRootCauseCategories)
            ->merge($defaultCategories)
            ->unique()
            ->values();

        return Inertia::render('Operation/IT/Issues/RootCauses', [
            'issues' => $issues,
            'summary' => $formattedSummary,
            'rootCauses' => $rootCauses,
            'suggestedCategories' => $suggestedCategories,
            'filters' => [
                'root_cause_date' => $rootCauseDate,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
                'tab' => $activeTab,
            ],
            'totalIssuesCount' => Issue::count(),
            'totalLoggedCount' => DB::table('issue_root_cause_logs')->count(),
        ]);
    }

    /**
     * Update or assign root cause type and remark for an issue
     */
    public function updateIssueRootCause(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'root_cause_id' => ['required', 'exists:issue_root_causes,id'],
            'remark' => ['nullable', 'string', 'max:5000'],
        ]);

        $rootCause = IssueRootCause::findOrFail($validated['root_cause_id']);

        // Sync root cause with remark
        $issue->rootCauses()->sync([
            $validated['root_cause_id'] => [
                'remark' => $validated['remark'] ?? null,
                'updated_at' => now(),
            ],
        ]);

        // Record activity log
        $issue->activityLogs()->create([
            'action' => 'updated_root_cause',
            'description' => "Root cause set to '{$rootCause->name}' ({$rootCause->category}). Remark: " . ($validated['remark'] ?: 'None'),
            'performed_by' => auth()->id() ?? $issue->created_by,
        ]);

        return back()->with('success', "Root cause updated to '{$rootCause->name}' successfully.");
    }

    /**
     * Create a new root cause type inline with category
     */
    public function storeRootCause(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:issue_root_causes,name'],
            'category' => ['required', 'string', 'max:100'],
        ]);

        $rootCause = IssueRootCause::create([
            'name' => trim($validated['name']),
            'category' => trim($validated['category']),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'rootCause' => $rootCause,
            ]);
        }

        return back()->with('success', "Root cause '{$rootCause->name}' created successfully.")
            ->with('newRootCause', $rootCause);
    }
}
