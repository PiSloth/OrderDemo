<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\OfficePosition;
use App\Models\User;
use App\Services\Training\TrainingAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficePositionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $query = OfficePosition::query()
            ->with(['users:id,name,email,department_id,office_position_id'])
            ->withCount('users');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $positions = $query->orderBy('name')->paginate(15)->withQueryString();
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $allUsers = User::query()
            ->where('suspended', false)
            ->with(['department:id,name', 'officePosition:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department_id', 'office_position_id']);

        return Inertia::render('Training/OfficePositions/Index', [
            'positions' => $positions,
            'departments' => $departments,
            'allUsers' => $allUsers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        OfficePosition::create($validated);

        return back()->with('message', 'Office Position created successfully.');
    }

    public function update(Request $request, OfficePosition $officePosition): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $officePosition->update($validated);

        return back()->with('message', 'Office Position updated successfully.');
    }

    public function destroy(OfficePosition $officePosition): RedirectResponse
    {
        // Unassign users first
        User::where('office_position_id', $officePosition->id)->update(['office_position_id' => null]);
        $officePosition->delete();

        return back()->with('message', 'Office Position deleted successfully.');
    }

    public function assignUsers(
        Request $request,
        OfficePosition $officePosition,
        TrainingAssignmentService $assignmentService
    ): RedirectResponse {
        $validated = $request->validate([
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $targetUserIds = $validated['user_ids'] ?? [];

        // 1. Remove users no longer assigned
        User::where('office_position_id', $officePosition->id)
            ->whereNotIn('id', $targetUserIds)
            ->update(['office_position_id' => null]);

        // 2. Assign selected users
        $assignedCount = 0;
        foreach ($targetUserIds as $userId) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $user->office_position_id = $officePosition->id;
            $user->save();

            // Trigger onboarding/scoping training assignments
            $assignmentService->assignNewUser($user);
            $assignedCount++;
        }

        return back()->with('message', "{$assignedCount} active employee(s) assigned to '{$officePosition->name}'. Matching training requirements updated.");
    }
}
