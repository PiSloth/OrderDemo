<?php

namespace App\Http\Controllers\Operation\IT;

use App\Http\Controllers\Controller;
use App\IssueTracking\Models\Issue;
use App\IssueTracking\Models\IssueCategory;
use App\IssueTracking\Models\IssueImportanceLevel;
use App\IssueTracking\Models\IssueMessage;
use App\IssueTracking\Models\IssuePriority;
use App\IssueTracking\Models\IssueRootCause;
use App\IssueTracking\Models\IssueStatus;
use App\IssueTracking\Services\SlaCalculationService;
use App\IssueTracking\Services\SlaReportService;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ItIssueController extends Controller
{
    public function __construct(
        protected SlaCalculationService $slaCalculationService,
        protected SlaReportService $slaReportService
    ) {}

    /**
     * Display issue list (React Index page)
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status');
        $branchFilter = $request->input('branch_id');
        $tabFilter = $request->input('tab', 'all'); // all, erp, third
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Issue::query()
            ->with(['category', 'priority', 'importance', 'status', 'creator.branch', 'assignedUser', 'resolutionDepartment', 'messages' => fn($q) => $q->where('is_log_note', true)->with('creator')->latest()])
            ->when($tabFilter === 'third', fn($q) => $q->where('is_third_party_resolver', true))
            ->when($tabFilter === 'erp', fn($q) => $q->whereHas('category', fn($c) => $c->where('is_erp', true)))
            ->when($statusFilter, fn($q) => $q->whereHas('status', fn($s) => $s->where('code', $statusFilter)))
            ->when($branchFilter, fn($q) => $q->whereHas('creator', fn($c) => $c->where('branch_id', $branchFilter)))
            ->when($startDate, fn($q) => $q->whereDate('issue_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('issue_at', '<=', $endDate))
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('issue_by', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('CASE WHEN resolution_sequence IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('resolution_sequence')
            ->orderByDesc('issue_at')
            ->orderByDesc('id');

        $issues = $query->paginate(15)->withQueryString();

        $categories = IssueCategory::all();
        $priorities = IssuePriority::orderBy('level')->get();
        $importanceLevels = IssueImportanceLevel::orderBy('level')->get();
        $statuses = IssueStatus::all();
        $departments = Department::all();
        $users = User::select('id', 'name', 'email', 'department_id', 'branch_id')->with(['department:id,name', 'branch:id,name'])->orderBy('name')->get();
        $branches = Branch::all();

        return Inertia::render('Operation/IT/Issues/Index', [
            'issues' => $issues,
            'filters' => [
                'status' => $statusFilter,
                'branch_id' => $branchFilter,
                'tab' => $tabFilter,
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'categories' => $categories,
            'priorities' => $priorities,
            'importanceLevels' => $importanceLevels,
            'statuses' => $statuses,
            'departments' => $departments,
            'users' => $users,
            'branches' => $branches,
            'rootCauses' => IssueRootCause::orderBy('name')->get(),
        ]);
    }

    /**
     * Render Issue Creation Form
     */
    public function create()
    {
        return Inertia::render('Operation/IT/Issues/Create', [
            'categories' => IssueCategory::all(),
            'priorities' => IssuePriority::orderBy('level')->get(),
            'importanceLevels' => IssueImportanceLevel::orderBy('level')->get(),
            'departments' => Department::all(),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    /**
     * Store a newly created issue
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'issue_category_id' => ['required', 'exists:issue_categories,id'],
            'issue_priority_id' => ['nullable', 'exists:issue_priorities,id'],
            'issue_importance_id' => ['nullable', 'exists:issue_importance_levels,id'],
            'resolution_department_id' => ['nullable', 'exists:departments,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'proposed_solution' => ['nullable', 'string'],
            'issue_by' => ['nullable', 'string'],
            'is_third_party_resolver' => ['nullable', 'boolean'],
            'due_date' => ['nullable', 'date'],
        ]);

        $defaultPriority = IssuePriority::orderBy('level')->first();
        $defaultImportance = IssueImportanceLevel::orderBy('level')->first();
        $openStatus = IssueStatus::where('code', 'OPEN')->firstOrFail();
        $itDept = Department::where('name', 'like', '%IT%')->first() ?? Department::first();

        $priority = isset($validated['issue_priority_id'])
            ? IssuePriority::find($validated['issue_priority_id'])
            : $defaultPriority;

        $issueAt = now();
        $calculatedDueDate = $priority ? $this->slaCalculationService->calculateDueDate($issueAt, $priority) : null;
        $dueDate = $calculatedDueDate ?? (isset($validated['due_date']) && $validated['due_date'] ? Carbon::parse($validated['due_date']) : null);

        $issue = Issue::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'issue_category_id' => $validated['issue_category_id'],
            'issue_priority_id' => $priority?->id ?? $defaultPriority?->id,
            'issue_importance_id' => $validated['issue_importance_id'] ?? $defaultImportance?->id,
            'issue_by' => $validated['issue_by'] ?? auth()->user()->name,
            'issue_at' => $issueAt,
            'due_date' => $dueDate,
            'created_by' => auth()->id(),
            'proposed_solution' => $validated['proposed_solution'] ?? null,
            'resolution_department_id' => $validated['resolution_department_id'] ?? $itDept?->id,
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'issue_status_id' => $openStatus->id,
            'is_third_party_resolver' => (bool)($validated['is_third_party_resolver'] ?? false),
        ]);

        return redirect()->route('operation.it.issues.index')->with('success', 'Issue created successfully!');
    }

    /**
     * Update full issue details (Edit CRUD)
     */
    public function update(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'issue_category_id' => ['required', 'exists:issue_categories,id'],
            'issue_priority_id' => ['nullable', 'exists:issue_priorities,id'],
            'issue_importance_id' => ['nullable', 'exists:issue_importance_levels,id'],
            'resolution_department_id' => ['nullable', 'exists:departments,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'proposed_solution' => ['nullable', 'string'],
            'issue_by' => ['nullable', 'string'],
            'is_third_party_resolver' => ['nullable', 'boolean'],
            'resolution_sequence' => ['nullable', 'integer'],
        ]);

        $startAt = $issue->issue_at ?? now();
        $dueDate = $issue->due_date;

        if (isset($validated['issue_priority_id']) && $validated['issue_priority_id'] != $issue->issue_priority_id) {
            $priority = IssuePriority::find($validated['issue_priority_id']);
            if ($priority) {
                $dueDate = $this->slaCalculationService->calculateDueDate($startAt, $priority);
            }
        }

        $issue->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'issue_category_id' => $validated['issue_category_id'],
            'issue_priority_id' => $validated['issue_priority_id'] ?? $issue->issue_priority_id,
            'issue_importance_id' => $validated['issue_importance_id'] ?? $issue->issue_importance_id,
            'resolution_department_id' => $validated['resolution_department_id'] ?? $issue->resolution_department_id,
            'assigned_user_id' => $validated['assigned_user_id'] ?? $issue->assigned_user_id,
            'proposed_solution' => $validated['proposed_solution'] ?? $issue->proposed_solution,
            'issue_by' => $validated['issue_by'] ?? $issue->issue_by,
            'is_third_party_resolver' => (bool)($validated['is_third_party_resolver'] ?? $issue->is_third_party_resolver),
            'resolution_sequence' => $validated['resolution_sequence'] ?? $issue->resolution_sequence,
            'due_date' => $dueDate,
        ]);

        return back()->with('success', 'Issue details updated successfully!');
    }

    /**
     * Delete issue (Delete CRUD)
     */
    public function destroy(Issue $issue)
    {
        $issue->messages()->delete();
        $issue->statusHistories()->delete();
        $issue->activityLogs()->delete();
        $issue->delete();

        return back()->with('success', 'Issue deleted successfully!');
    }

    /**
     * Add Discussion Message / Log Note
     */
    public function addMessage(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'is_log_note' => ['nullable', 'boolean'],
        ]);

        IssueMessage::create([
            'issue_id' => $issue->id,
            'created_by' => auth()->id(),
            'message' => $validated['message'],
            'is_log_note' => (bool)($validated['is_log_note'] ?? false),
        ]);

        return back()->with('success', 'Message added successfully!');
    }

    /**
     * Reorder issue sequence via Drag and Drop
     */
    public function reorderSequence(Request $request)
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['exists:issues,id'],
        ]);

        foreach ($validated['ordered_ids'] as $index => $issueId) {
            Issue::where('id', $issueId)->update([
                'resolution_sequence' => $index + 1,
            ]);
        }

        return back()->with('success', 'Sequence reordered successfully!');
    }

    /**
     * Update Priority (P-Level) and automatically recalculate Due Date based on SLA office hours / 24h format
     */
    public function updatePriority(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'issue_priority_id' => ['required', 'exists:issue_priorities,id'],
        ]);

        $priority = IssuePriority::findOrFail($validated['issue_priority_id']);
        $startAt = $issue->issue_at ?? now();

        $newDueDate = $this->slaCalculationService->calculateDueDate($startAt, $priority);

        $issue->update([
            'issue_priority_id' => $priority->id,
            'due_date' => $newDueDate,
        ]);

        // Create log note
        IssueMessage::create([
            'issue_id' => $issue->id,
            'created_by' => auth()->id(),
            'message' => "Priority level updated to {$priority->name} (Code: {$priority->code}). Due date automatically recalculated to {$newDueDate->format('Y-m-d H:i')}.",
            'is_log_note' => true,
        ]);

        return back()->with('success', 'Priority updated and due date recalculated based on SLA guide!');
    }

    /**
     * Update Reported Date (issue_at) and automatically recalculate Due Date based on SLA service.
     * Log user remark as yellow system log note.
     */
    public function updateReportedDate(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'issue_at' => ['required', 'date'],
            'remark' => ['required', 'string'],
        ]);

        $oldIssueAt = $issue->issue_at ? Carbon::parse($issue->issue_at)->format('Y-m-d H:i') : 'N/A';
        $newIssueAt = Carbon::parse($validated['issue_at']);

        // Auto recalculate due date based on SLA service and priority if available
        $newDueDate = $issue->priority
            ? $this->slaCalculationService->calculateDueDate($newIssueAt, $issue->priority)
            : $issue->due_date;

        $issue->update([
            'issue_at' => $newIssueAt,
            'due_date' => $newDueDate,
        ]);

        $newDueDateStr = $newDueDate ? $newDueDate->format('Y-m-d H:i') : 'N/A';
        $newIssueAtStr = $newIssueAt->format('Y-m-d H:i');

        $logMsg = "[REPORTED DATE UPDATED]: Date changed from {$oldIssueAt} to {$newIssueAtStr}. Due date recalculated to {$newDueDateStr}. Remark: {$validated['remark']}";

        IssueMessage::create([
            'issue_id' => $issue->id,
            'created_by' => auth()->id(),
            'message' => $logMsg,
            'is_log_note' => true,
        ]);

        return back()->with('success', 'Reported date updated, due date recalculated, and remark saved to log notes!');
    }

    /**
     * Update Due Date manually for manual schedule priorities.
     * Log user remark as yellow system log note.
     */
    public function updateDueDate(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'remark'   => ['required', 'string'],
        ]);

        $oldDueDate = $issue->due_date ? Carbon::parse($issue->due_date)->format('Y-m-d H:i') : 'N/A';
        $newDueDate = Carbon::parse($validated['due_date']);
        $newDueDateStr = $newDueDate->format('Y-m-d H:i');

        $issue->update([
            'due_date' => $newDueDate,
        ]);

        $logMsg = "[DUE DATE UPDATED]: Manual schedule due date set from {$oldDueDate} to {$newDueDateStr}. Remark: {$validated['remark']}";

        IssueMessage::create([
            'issue_id' => $issue->id,
            'created_by' => auth()->id(),
            'message' => $logMsg,
            'is_log_note' => true,
        ]);

        return back()->with('success', 'Due date updated and remark saved to log notes!');
    }

    /**
     * Update Issue Status
     */
    public function updateStatus(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'issue_status_id' => ['required', 'exists:issue_statuses,id'],
            'proposed_solution' => ['nullable', 'string'],
            'root_cause_id' => ['nullable', 'exists:issue_root_causes,id'],
            'remark' => ['nullable', 'string'],
        ]);

        $currentStatus = $issue->status;
        $isCurrentlyClosedOrDone = $currentStatus && in_array($currentStatus->code, ['CLOSED', 'DONE']);

        $status = IssueStatus::findOrFail($validated['issue_status_id']);
        $isClosing = $status->code === 'CLOSED';
        $isDone = $status->code === 'DONE';
        $isClosingOrDone = $isClosing || $isDone;

        // 1. If moving to CLOSED or DONE, require a Root Cause
        if ($isClosingOrDone) {
            $hasExistingRootCause = $issue->rootCauses()->exists();
            if (!$hasExistingRootCause && empty($validated['root_cause_id'])) {
                return back()->withErrors(['root_cause_id' => "Root cause is required before changing status to {$status->name}."]);
            }
        }

        // 2. If moving back from CLOSED or DONE to an open status, require a Reason / Remark
        if ($isCurrentlyClosedOrDone && !$isClosingOrDone) {
            if (empty(trim($validated['remark'] ?? ''))) {
                return back()->withErrors(['remark' => "A remark / log note is required when reopening or changing status from {$currentStatus->name} to {$status->name}."]);
            }
            $closedDate = null; // Reset closed date upon reopening
        } else {
            $closedDate = $isClosing ? now() : $issue->closed_date;
        }

        $isFailed = $issue->is_sla_failed;
        $failPoints = $issue->fail_points;

        if ($isClosing && $issue->due_date) {
            if ($closedDate && $closedDate->gt($issue->due_date)) {
                $isFailed = true;
                $failPoints = $issue->priority ? $this->slaCalculationService->getFailPoints($issue->priority) : 1;
            } else {
                $isFailed = false;
                $failPoints = 0;
            }
        }

        $issue->update([
            'issue_status_id' => $status->id,
            'closed_date' => $closedDate,
            'proposed_solution' => $validated['proposed_solution'] ?? $issue->proposed_solution,
            'is_sla_failed' => $isFailed,
            'fail_points' => $failPoints,
        ]);

        if (!empty($validated['root_cause_id'])) {
            $issue->rootCauses()->syncWithoutDetaching([$validated['root_cause_id']]);
        }

        $logMsg = $currentStatus ? "Status changed from {$currentStatus->name} to {$status->name}." : "Status changed to {$status->name}.";
        if (!empty($validated['root_cause_id'])) {
            $rc = IssueRootCause::find($validated['root_cause_id']);
            if ($rc) {
                $logMsg .= " Root Cause: {$rc->name}.";
            }
        }
        if (!empty($validated['remark'])) {
            $logMsg .= " Reason / Remark: {$validated['remark']}";
        }

        IssueMessage::create([
            'issue_id' => $issue->id,
            'created_by' => auth()->id(),
            'message' => $logMsg,
            'is_log_note' => true,
        ]);

        return back()->with('success', 'Issue status updated successfully!');
    }

    /**
     * Admin SLA Override (Marks Success, resets fail points to 0, and records mandatory admin remark as log note)
     */
    public function overrideSla(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'admin_remark' => ['required', 'string', 'min:3'],
        ]);

        $issue->update([
            'is_sla_failed' => false,
            'fail_points' => 0,
        ]);

        IssueMessage::create([
            'issue_id' => $issue->id,
            'created_by' => auth()->id(),
            'message' => "[ADMIN SLA OVERRIDE -> SUCCESS]: " . $validated['admin_remark'],
            'is_log_note' => true,
        ]);

        return back()->with('success', 'SLA status overridden to Success with admin remark recorded!');
    }

    /**
     * SLA Overview Dashboard (React Dashboard page)
     */
    public function dashboard()
    {
        $totalCount = Issue::count();
        $openCount = Issue::whereHas('status', fn($s) => $s->where('code', 'OPEN'))->count();
        $inProgressCount = Issue::whereHas('status', fn($s) => $s->where('code', 'IN_PROGRESS'))->count();
        $closedCount = Issue::whereHas('status', fn($s) => $s->where('code', 'CLOSED'))->count();
        $failedCount = Issue::where('is_sla_failed', true)->count();

        $recentIssues = Issue::with(['status', 'priority', 'category', 'assignedUser'])
            ->orderByDesc('issue_at')
            ->limit(10)
            ->get();

        return Inertia::render('Operation/IT/Issues/Dashboard', [
            'metrics' => [
                'total' => $totalCount,
                'open' => $openCount,
                'in_progress' => $inProgressCount,
                'closed' => $closedCount,
                'failed' => $failedCount,
            ],
            'recentIssues' => $recentIssues,
        ]);
    }

    /**
     * Weekly & Monthly SLA & Service Credit Report (React Reports page)
     */
    public function reports(Request $request)
    {
        $periodType = $request->input('period_type', 'weekly');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $resolverType = $request->input('resolver_type', 'all');
        $categoryIds = $request->input('category_ids');
        $statusCodes = $request->input('status_codes', $request->input('status_code'));

        $report = $this->slaReportService->generateReport($periodType, $startDate, $endDate, $resolverType, $categoryIds, $statusCodes);

        $authUser = auth()->user()?->load('department');

        return Inertia::render('Operation/IT/Issues/Reports', [
            'report'          => $report,
            'filters'         => [
                'period_type'   => $periodType,
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'resolver_type' => $resolverType,
                'category_ids'  => is_array($categoryIds) ? $categoryIds : ($categoryIds ? explode(',', $categoryIds) : []),
                'status_codes'  => is_array($statusCodes) ? $statusCodes : ($statusCodes ? explode(',', $statusCodes) : []),
            ],
            'auth_user'       => [
                'id'          => $authUser?->id,
                'name'        => $authUser?->name ?? '',
                'department'  => $authUser?->department?->name ?? '',
            ],
            'app_name'        => config('app.name'),
            'categories'      => IssueCategory::all(),
            'priorities'      => IssuePriority::orderBy('level')->get(),
            'importanceLevels' => IssueImportanceLevel::orderBy('level')->get(),
            'statuses'        => IssueStatus::all(),
            'departments'     => Department::all(),
            'users'           => User::select('id', 'name', 'email', 'department_id', 'branch_id')->with(['department:id,name', 'branch:id,name'])->orderBy('name')->get(),
            'rootCauses'      => IssueRootCause::orderBy('name')->get(),
        ]);
    }

    /**
     * Download Excel Report for Weekly/Monthly SLA & Service Credit
     */
    public function exportReport(Request $request)
    {
        $periodType = $request->input('period_type', 'weekly');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $resolverType = $request->input('resolver_type', 'all');
        $categoryIds = $request->input('category_ids');
        $statusCodes = $request->input('status_codes', $request->input('status_code'));

        $report = $this->slaReportService->generateReport($periodType, $startDate, $endDate, $resolverType, $categoryIds, $statusCodes);

        $filename = "SLA_Service_Credit_Report_{$periodType}_" . now()->format('Y-m-d') . ".xlsx";
        $writer = SimpleExcelWriter::streamDownload($filename);

        $writer->addRow([
            'SLA & Service Credit Report' => $report['period_label'],
            'Period Type' => ucfirst($periodType),
            'Total Issues' => $report['summary']['total_issues'],
            'SLA Passed' => $report['summary']['passed_issues'],
            'SLA Failed' => $report['summary']['failed_issues'],
            'Total Weekly Fail Points' => $report['summary']['total_fail_points'],
            'Resolution Rate' => $report['summary']['resolution_rate'] . '%',
            'Service Credit Refund %' => $report['summary']['service_credit_pct'] . '%',
        ]);

        $writer->addRow([]); // blank line

        $writer->addHeader([
            'Issue ID',
            'Title',
            'Category',
            'Priority',
            'Status',
            'Assigned To',
            'Reported Date',
            'Due Date',
            'Closed Date',
            'SLA Status',
            'Fail Points',
            'Admin Remark / Log Note',
        ]);

        foreach ($report['items'] as $item) {
            $writer->addRow([
                'Issue ID' => '#' . $item['id'],
                'Title' => $item['title'],
                'Category' => $item['category_name'],
                'Priority' => $item['priority_code'] . ' - ' . $item['priority_name'],
                'Status' => $item['status_name'],
                'Assigned To' => $item['assigned_user_name'],
                'Reported Date' => $item['issue_at'] ?? 'N/A',
                'Due Date' => $item['due_date'] ?? 'N/A',
                'Closed Date' => $item['closed_date'] ?? 'N/A',
                'SLA Status' => $item['is_sla_failed'] ? 'FAILED' : 'PASSED',
                'Fail Points' => $item['fail_points'],
                'Admin Remark / Log Note' => $item['admin_remark'] ?? '',
            ]);
        }

        return $writer->toBrowser();
    }

    /**
     * Configure Page (Categories, Priorities, Importance Levels, Statuses, Root Causes, Office Hours)
     */
    public function configure()
    {
        return Inertia::render('Operation/IT/Issues/Configure', [
            'categories' => IssueCategory::all(),
            'priorities' => IssuePriority::orderBy('level')->get(),
            'importanceLevels' => IssueImportanceLevel::orderBy('level')->get(),
            'statuses' => IssueStatus::orderBy('id')->get(),
            'rootCauses' => IssueRootCause::orderBy('id')->get(),
        ]);
    }

    // Priority Configuration CRUD & Swap
    public function storePriorityConfig(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'integer', 'min:1'],
            'clock_type' => ['required', 'string', 'in:continuous_24h,office_hours,manual_schedule'],
            'target_hours' => ['nullable', 'numeric', 'min:0'],
            'fail_points' => ['required', 'integer', 'min:0'],
        ]);

        $level = $validated['level'] ?? ((IssuePriority::max('level') ?? 0) + 1);
        $clockType = $validated['clock_type'];
        $isManual = $clockType === 'manual_schedule';

        IssuePriority::create([
            'name' => $validated['name'],
            'level' => $level,
            'settings' => [
                'clock_type' => $clockType,
                'target_hours' => $isManual ? null : ($validated['target_hours'] !== null && $validated['target_hours'] !== '' ? (float)$validated['target_hours'] : null),
                'fail_points' => (int)$validated['fail_points'],
                'is_manual_schedule' => $isManual,
            ],
        ]);

        return back()->with('success', 'Priority level created successfully!');
    }

    public function updatePriorityConfig(Request $request, $id)
    {
        $priority = IssuePriority::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1'],
            'clock_type' => ['required', 'string', 'in:continuous_24h,office_hours,manual_schedule'],
            'target_hours' => ['nullable', 'numeric', 'min:0'],
            'fail_points' => ['required', 'integer', 'min:0'],
        ]);

        $clockType = $validated['clock_type'];
        $isManual = $clockType === 'manual_schedule';

        $priority->update([
            'name' => $validated['name'],
            'level' => $validated['level'],
            'settings' => [
                'clock_type' => $clockType,
                'target_hours' => $isManual ? null : ($validated['target_hours'] !== null && $validated['target_hours'] !== '' ? (float)$validated['target_hours'] : null),
                'fail_points' => (int)$validated['fail_points'],
                'is_manual_schedule' => $isManual,
            ],
        ]);

        return back()->with('success', 'Priority level updated successfully!');
    }

    public function destroyPriorityConfig($id)
    {
        $priority = IssuePriority::findOrFail($id);

        $inUse = Issue::where('issue_priority_id', $id)->exists();
        if ($inUse) {
            return back()->withErrors(['error' => "Cannot delete Priority '{$priority->name}' because it is assigned to active IT issues."]);
        }

        $priority->delete();

        return back()->with('success', 'Priority level deleted successfully!');
    }

    public function swapPriorityConfig(Request $request)
    {
        $validated = $request->validate([
            'id1' => ['required', 'exists:issue_priorities,id'],
            'id2' => ['required', 'exists:issue_priorities,id'],
        ]);

        $p1 = IssuePriority::findOrFail($validated['id1']);
        $p2 = IssuePriority::findOrFail($validated['id2']);

        $tempLevel = $p1->level;
        $p1->level = $p2->level;
        $p2->level = $tempLevel;

        $p1->save();
        $p2->save();

        return back()->with('success', 'Priority levels swapped successfully!');
    }

    // Importance Level Configuration CRUD & Swap
    public function storeImportanceConfig(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'integer', 'min:1'],
        ]);

        $level = $validated['level'] ?? ((IssueImportanceLevel::max('level') ?? 0) + 1);

        IssueImportanceLevel::create([
            'name' => $validated['name'],
            'level' => $level,
        ]);

        return back()->with('success', 'Importance level created successfully!');
    }

    public function updateImportanceConfig(Request $request, $id)
    {
        $importance = IssueImportanceLevel::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1'],
        ]);

        $importance->update($validated);

        return back()->with('success', 'Importance level updated successfully!');
    }

    public function destroyImportanceConfig($id)
    {
        $importance = IssueImportanceLevel::findOrFail($id);

        $inUse = Issue::where('issue_importance_id', $id)->exists();
        if ($inUse) {
            return back()->withErrors(['error' => "Cannot delete Importance Level '{$importance->name}' because it is assigned to active IT issues."]);
        }

        $importance->delete();

        return back()->with('success', 'Importance level deleted successfully!');
    }

    public function swapImportanceConfig(Request $request)
    {
        $validated = $request->validate([
            'id1' => ['required', 'exists:issue_importance_levels,id'],
            'id2' => ['required', 'exists:issue_importance_levels,id'],
        ]);

        $i1 = IssueImportanceLevel::findOrFail($validated['id1']);
        $i2 = IssueImportanceLevel::findOrFail($validated['id2']);

        $tempLevel = $i1->level;
        $i1->level = $i2->level;
        $i2->level = $tempLevel;

        $i1->save();
        $i2->save();

        return back()->with('success', 'Importance levels swapped successfully!');
    }

    // Status Configuration CRUD & Swap
    public function storeStatusConfig(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:issue_statuses,code'],
        ]);

        IssueStatus::create([
            'name' => $validated['name'],
            'code' => strtoupper(str_replace(' ', '_', $validated['code'])),
        ]);

        return back()->with('success', 'Issue status created successfully!');
    }

    public function updateStatusConfig(Request $request, $id)
    {
        $status = IssueStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:issue_statuses,code,' . $id],
        ]);

        $status->update([
            'name' => $validated['name'],
            'code' => strtoupper(str_replace(' ', '_', $validated['code'])),
        ]);

        return back()->with('success', 'Issue status updated successfully!');
    }

    public function destroyStatusConfig($id)
    {
        $status = IssueStatus::findOrFail($id);

        $inUse = Issue::where('issue_status_id', $id)->exists();
        if ($inUse) {
            return back()->withErrors(['error' => "Cannot delete Status '{$status->name}' because it is assigned to active IT issues."]);
        }

        $status->delete();

        return back()->with('success', 'Issue status deleted successfully!');
    }

    public function swapStatusConfig(Request $request)
    {
        $validated = $request->validate([
            'id1' => ['required', 'exists:issue_statuses,id'],
            'id2' => ['required', 'exists:issue_statuses,id'],
        ]);

        $s1 = IssueStatus::findOrFail($validated['id1']);
        $s2 = IssueStatus::findOrFail($validated['id2']);

        $tempName = $s1->name;
        $tempCode = $s1->code;

        $s1->name = $s2->name;
        $s1->code = $s2->code;

        $s2->name = $tempName;
        $s2->code = $tempCode;

        $s1->save();
        $s2->save();

        return back()->with('success', 'Statuses swapped successfully!');
    }

    // Root Cause Configuration CRUD & Swap
    public function storeRootCauseConfig(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        IssueRootCause::create([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Root cause created successfully!');
    }

    public function updateRootCauseConfig(Request $request, $id)
    {
        $rootCause = IssueRootCause::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $rootCause->update($validated);

        return back()->with('success', 'Root cause updated successfully!');
    }

    public function destroyRootCauseConfig($id)
    {
        $rootCause = IssueRootCause::findOrFail($id);

        $inUse = \Illuminate\Support\Facades\DB::table('issue_root_cause_logs')->where('root_cause_id', $id)->exists();
        if ($inUse) {
            return back()->withErrors(['error' => "Cannot delete Root Cause '{$rootCause->name}' because it is linked to logged issue root causes."]);
        }

        $rootCause->delete();

        return back()->with('success', 'Root cause deleted successfully!');
    }

    public function swapRootCauseConfig(Request $request)
    {
        $validated = $request->validate([
            'id1' => ['required', 'exists:issue_root_causes,id'],
            'id2' => ['required', 'exists:issue_root_causes,id'],
        ]);

        $rc1 = IssueRootCause::findOrFail($validated['id1']);
        $rc2 = IssueRootCause::findOrFail($validated['id2']);

        $tempName = $rc1->name;
        $rc1->name = $rc2->name;
        $rc2->name = $tempName;

        $rc1->save();
        $rc2->save();

        return back()->with('success', 'Root causes swapped successfully!');
    }
}
