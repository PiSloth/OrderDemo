<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountPic;
use App\Services\DigitalAsset\SoftwareAccountPicService;
use Illuminate\Http\Request;

class SoftwareAccountPicController extends Controller
{
    public function __construct(protected SoftwareAccountPicService $picService) {}

    public function assign(Request $request, SoftwareAccount $account)
    {
        $this->authorize('managePics', $account);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->picService->assignPic(
                account: $account,
                userId: $validated['user_id'],
                actorUserId: auth()->id(),
                reason: $validated['reason'] ?? null
            );

            return back()->with('success', 'Person in Charge (PIC) assigned successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['user_id' => $e->getMessage()]);
        }
    }

    public function revoke(Request $request, SoftwareAccountPic $pic)
    {
        $this->authorize('managePics', $pic->account);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->picService->revokePic(
                pic: $pic,
                actorUserId: auth()->id(),
                reason: $validated['reason']
            );

            return back()->with('success', 'PIC assignment revoked successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['reason' => $e->getMessage()]);
        }
    }

    public function reactivate(Request $request, SoftwareAccountPic $pic)
    {
        $this->authorize('managePics', $pic->account);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->picService->reactivatePic(
                pic: $pic,
                actorUserId: auth()->id(),
                reason: $validated['reason'] ?? null
            );

            return back()->with('success', 'PIC assignment reactivated successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['reactivate' => $e->getMessage()]);
        }
    }
}
