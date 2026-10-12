<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountEvidence;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use App\Services\DigitalAsset\SoftwareAccountEvidenceService;
use Illuminate\Http\Request;

class SoftwareAccountEvidenceController extends Controller
{
    public function __construct(protected SoftwareAccountEvidenceService $evidenceService) {}

    public function upload(Request $request, SoftwareAccount $account)
    {
        $this->authorize('uploadEvidence', $account);

        $validated = $request->validate([
            'property_definition_id' => 'required|exists:software_property_definitions,id',
            'file' => 'required|file|max:10240', // 10MB
            'property_history_id' => 'nullable|exists:software_account_property_history,id',
        ]);

        $definition = SoftwarePropertyDefinition::findOrFail($validated['property_definition_id']);

        try {
            $this->evidenceService->uploadEvidence(
                account: $account,
                definition: $definition,
                file: $request->file('file'),
                uploadedByUserId: auth()->id(),
                propertyHistoryId: $validated['property_history_id'] ?? null
            );

            return back()->with('success', 'Security evidence uploaded securely.');
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function verify(Request $request, SoftwareAccountEvidence $evidence)
    {
        $this->authorize('verifyEvidence', $evidence->account);

        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->evidenceService->verifyEvidence(
                evidence: $evidence,
                status: $validated['status'],
                verifierUserId: auth()->id(),
                reviewNotes: $validated['review_notes'] ?? null
            );

            return back()->with('success', 'Evidence verification status updated.');
        } catch (\Throwable $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function download(SoftwareAccountEvidence $evidence)
    {
        $this->authorize('view', $evidence->account);

        return $this->evidenceService->downloadEvidence($evidence, auth()->id());
    }
}
