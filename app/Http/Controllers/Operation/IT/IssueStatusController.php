<?php

namespace App\Http\Controllers\Operation\IT;

use App\Http\Controllers\Controller;
use App\IssueTracking\Models\Issue;
use App\IssueTracking\Models\IssueStatus;
use App\IssueTracking\Services\IssueWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IssueStatusController extends Controller
{
    public function update(Request $request, Issue $issue, IssueWorkflowService $workflow): RedirectResponse
    {
        $request->validate(['status_code' => ['required', 'exists:issue_statuses,code']]);
        $workflow->transition($issue->load('status'), $request->string('status_code')->toString(), auth()->id());
        return back()->with('message', 'Status updated.');
    }
}
