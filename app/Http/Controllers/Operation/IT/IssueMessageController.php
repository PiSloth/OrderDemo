<?php

namespace App\Http\Controllers\Operation\IT;

use App\Http\Controllers\Controller;
use App\IssueTracking\Models\Issue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IssueMessageController extends Controller
{
    public function store(Request $request, Issue $issue): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'is_discussion' => ['nullable', 'boolean'],
            'is_log_note' => ['nullable', 'boolean'],
        ]);

        $issue->messages()->create([
            'message' => $validated['message'],
            'is_discussion' => (bool) ($validated['is_discussion'] ?? true),
            'is_log_note' => (bool) ($validated['is_log_note'] ?? false),
            'created_by' => auth()->id(),
        ]);

        return back()->with('message', 'Message added.');
    }
}
