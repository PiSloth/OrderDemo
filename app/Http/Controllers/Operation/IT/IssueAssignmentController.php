<?php

namespace App\Http\Controllers\Operation\IT;

use App\Http\Controllers\Controller;
use App\IssueTracking\Models\Issue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IssueAssignmentController extends Controller
{
    public function update(Request $request, Issue $issue): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_department_id' => ['required', 'exists:departments,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $oldAssigned = $issue->assigned_user_id;
        $issue->update($validated);

        if ((int) ($oldAssigned ?? 0) !== (int) ($validated['assigned_user_id'] ?? 0)) {
            $issue->activityLogs()->create([
                'action' => 'assigned',
                'description' => 'Assigned user changed.',
                'performed_by' => auth()->id(),
            ]);
        }

        return back()->with('message', 'Assignment updated.');
    }
}
