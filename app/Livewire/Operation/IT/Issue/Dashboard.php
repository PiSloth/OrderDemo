<?php

namespace App\Livewire\Operation\IT\Issue;

use App\IssueTracking\Models\Issue;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.operation')]
#[Title('Issue Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $today = now();
        $todayDate = $today->toDateString();
        $isSunday = $today->isSunday();

        $branchStatusSummary = Branch::query()
            ->leftJoin('users', 'users.branch_id', '=', 'branches.id')
            ->leftJoin('issues', function ($join) {
                $join->on('issues.created_by', '=', 'users.id')
                    ->where('issues.is_third_party_resolver', false);
            })
            ->leftJoin('issue_statuses', 'issue_statuses.id', '=', 'issues.issue_status_id')
            ->groupBy('branches.id', 'branches.name')
            ->orderBy('branches.name')
            ->select([
                'branches.id',
                'branches.name',
                DB::raw("SUM(CASE WHEN issue_statuses.code = 'CLOSED' THEN 1 ELSE 0 END) as closed_count"),
                DB::raw("SUM(CASE WHEN issue_statuses.code <> 'CLOSED' OR issue_statuses.code IS NULL THEN CASE WHEN issues.id IS NULL THEN 0 ELSE 1 END ELSE 0 END) as not_closed_count"),
            ])
            ->get();

        $statusSummaryWithoutThirdParty = Issue::query()
            ->join('issue_statuses', 'issue_statuses.id', '=', 'issues.issue_status_id')
            ->where('issues.is_third_party_resolver', false)
            ->groupBy('issue_statuses.code', 'issue_statuses.name')
            ->orderBy('issue_statuses.id')
            ->select([
                'issue_statuses.code',
                'issue_statuses.name',
                DB::raw('COUNT(*) as total'),
            ])
            ->get();

        $thirdPartyStatusSummary = Issue::query()
            ->join('issue_statuses', 'issue_statuses.id', '=', 'issues.issue_status_id')
            ->where('issues.is_third_party_resolver', true)
            ->groupBy('issue_statuses.code', 'issue_statuses.name')
            ->orderBy('issue_statuses.id')
            ->select([
                'issue_statuses.code',
                'issue_statuses.name',
                DB::raw('COUNT(*) as total'),
            ])
            ->get();

        $topSequenceIssues = Issue::query()
            ->with(['priority', 'importance', 'status'])
            ->where('is_third_party_resolver', false)
            ->whereNotNull('resolution_sequence')
            ->whereHas('status', fn($q) => $q->where('code', '!=', 'CLOSED'))
            ->orderBy('resolution_sequence')
            ->limit(3)
            ->get();

        $todayFollowUpItems = Issue::query()
            ->with(['status', 'followUpUpdater'])
            ->where('is_third_party_resolver', true)
            ->whereDate('follow_up_date', $todayDate)
            ->orderBy('follow_up_date')
            ->get();

        $todayFollowUpCount = $todayFollowUpItems->count();
        $showFollowUpAlert = !$isSunday && $todayFollowUpCount < 2;

        $dailySolvedItems = Issue::query()
            ->with(['creator.branch', 'status'])
            ->whereHas('status', fn($q) => $q->where('code', 'CLOSED'))
            ->whereDate('closed_date', $todayDate)
            ->orderByDesc('closed_date')
            ->get();

        $monthRootCauseByBranch = DB::table('issue_root_cause_logs as ircl')
            ->join('issues', 'issues.id', '=', 'ircl.issue_id')
            ->join('users', 'users.id', '=', 'issues.created_by')
            ->leftJoin('branches', 'branches.id', '=', 'users.branch_id')
            ->join('issue_root_causes', 'issue_root_causes.id', '=', 'ircl.root_cause_id')
            ->whereMonth('ircl.created_at', $today->month)
            ->whereYear('ircl.created_at', $today->year)
            ->groupBy('issue_root_causes.name', 'branches.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->select([
                'issue_root_causes.name as root_cause_name',
                DB::raw("COALESCE(branches.name, 'No Branch') as branch_name"),
                DB::raw('COUNT(*) as total'),
            ])
            ->get();

        $overdueIssues = Issue::query()
            ->with(['status', 'priority', 'importance'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $todayDate)
            ->whereHas('status', fn($q) => $q->where('code', '!=', 'CLOSED'))
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return view('livewire.operation.it.issue.dashboard', [
            'branchStatusSummary' => $branchStatusSummary,
            'statusSummaryWithoutThirdParty' => $statusSummaryWithoutThirdParty,
            'thirdPartyStatusSummary' => $thirdPartyStatusSummary,
            'topSequenceIssues' => $topSequenceIssues,
            'todayFollowUpItems' => $todayFollowUpItems,
            'todayFollowUpCount' => $todayFollowUpCount,
            'showFollowUpAlert' => $showFollowUpAlert,
            'dailySolvedItems' => $dailySolvedItems,
            'monthRootCauseByBranch' => $monthRootCauseByBranch,
            'overdueIssues' => $overdueIssues,
            'isSunday' => $isSunday,
        ]);
    }
}

