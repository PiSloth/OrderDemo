<?php

namespace App\Http\Controllers\Todo;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskTemplate;
use App\Models\Location;
use App\Models\TodoCategory;
use App\Models\TodoDueTime;
use App\Models\TodoPriority;
use App\Models\TodoStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TodoConfigController extends Controller
{
    public function index(): Response
    {
        $categories = TodoCategory::with('department')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $priorities = TodoPriority::orderBy('rank')->get();
        $statuses = TodoStatus::orderBy('status')->get();
        $locations = Location::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $dueTimes = TodoDueTime::with(['category', 'priority', 'kpiGroup', 'kpiTemplate', 'kpiAssignedUser', 'kpiApproverUser'])->get();
        
        $kpiGroups = KpiGroup::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $kpiTemplates = KpiTaskTemplate::where('is_active', true)->orderBy('title')->get(['id', 'title', 'kpi_group_id', 'frequency', 'requires_images']);
        $users = User::with('department')->orderBy('name')->get(['id', 'name', 'department_id']);

        return Inertia::render('Todo/Config', [
            'categories' => $categories,
            'departments' => $departments,
            'priorities' => $priorities,
            'statuses' => $statuses,
            'locations' => $locations,
            'branches' => $branches,
            'dueTimes' => $dueTimes,
            'kpiGroups' => $kpiGroups,
            'kpiTemplates' => $kpiTemplates,
            'users' => $users,
        ]);
    }

    // Category CRUD
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        TodoCategory::create($validated);

        return redirect()->back()->with('message', 'Category Created Successfully');
    }

    public function updateCategory(Request $request, $id)
    {
        $category = TodoCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $category->update($validated);

        return redirect()->back()->with('message', 'Category Updated Successfully');
    }

    public function destroyCategory($id)
    {
        $category = TodoCategory::findOrFail($id);
        $category->delete();

        return redirect()->back()->with('message', 'Category Deleted Successfully');
    }

    // Due Time CRUD
    public function storeDueTime(Request $request)
    {
        $validated = $request->validate([
            'todo_category_id' => ['required', 'exists:todo_categories,id'],
            'todo_priority_id' => ['required', 'exists:todo_priorities,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'generate_kpi_instance' => ['nullable', 'boolean'],
            'kpi_group_id' => [
                Rule::requiredIf(fn () => (bool) $request->input('generate_kpi_instance')),
                'nullable',
                'exists:kpi_groups,id',
            ],
            'kpi_task_template_id' => [
                Rule::requiredIf(fn () => (bool) $request->input('generate_kpi_instance')),
                'nullable',
                'exists:kpi_task_templates,id',
            ],
            'kpi_assigned_user_id' => ['nullable', 'exists:users,id'],
            'kpi_assigned_user_ids' => ['nullable', 'array'],
            'kpi_assigned_user_ids.*' => ['exists:users,id'],
            'kpi_approver_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $validated['generate_kpi_instance'] = (bool) ($validated['generate_kpi_instance'] ?? false);

        TodoDueTime::create($validated);

        return redirect()->back()->with('message', 'Job Title / Due Time Created Successfully');
    }

    public function updateDueTime(Request $request, $id)
    {
        $dueTime = TodoDueTime::findOrFail($id);

        $validated = $request->validate([
            'todo_category_id' => ['required', 'exists:todo_categories,id'],
            'todo_priority_id' => ['required', 'exists:todo_priorities,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'generate_kpi_instance' => ['nullable', 'boolean'],
            'kpi_group_id' => [
                Rule::requiredIf(fn () => (bool) $request->input('generate_kpi_instance')),
                'nullable',
                'exists:kpi_groups,id',
            ],
            'kpi_task_template_id' => [
                Rule::requiredIf(fn () => (bool) $request->input('generate_kpi_instance')),
                'nullable',
                'exists:kpi_task_templates,id',
            ],
            'kpi_assigned_user_id' => ['nullable', 'exists:users,id'],
            'kpi_assigned_user_ids' => ['nullable', 'array'],
            'kpi_assigned_user_ids.*' => ['exists:users,id'],
            'kpi_approver_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $validated['generate_kpi_instance'] = (bool) ($validated['generate_kpi_instance'] ?? false);

        $dueTime->update($validated);

        return redirect()->back()->with('message', 'Job Title / Due Time Updated Successfully');
    }

    public function destroyDueTime($id)
    {
        $dueTime = TodoDueTime::findOrFail($id);
        $dueTime->delete();

        return redirect()->back()->with('message', 'Job Title / Due Time Deleted Successfully');
    }

    // Status CRUD
    public function storeStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color_code' => ['nullable', 'string', 'max:50'],
        ]);

        TodoStatus::create($validated);

        return redirect()->back()->with('message', 'Status Created Successfully');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = TodoStatus::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color_code' => ['nullable', 'string', 'max:50'],
        ]);

        $status->update($validated);

        return redirect()->back()->with('message', 'Status Updated Successfully');
    }

    public function destroyStatus($id)
    {
        $status = TodoStatus::findOrFail($id);
        $status->delete();

        return redirect()->back()->with('message', 'Status Deleted Successfully');
    }

    // Priority CRUD
    public function storePriority(Request $request)
    {
        $validated = $request->validate([
            'level' => ['required', 'string', 'max:255'],
            'rank' => ['required', 'integer', 'unique:todo_priorities,rank'],
            'color_code' => ['nullable', 'string', 'max:50'],
        ]);

        TodoPriority::create($validated);

        return redirect()->back()->with('message', 'Priority Created Successfully');
    }

    public function updatePriority(Request $request, $id)
    {
        $priority = TodoPriority::findOrFail($id);

        $validated = $request->validate([
            'level' => ['required', 'string', 'max:255'],
            'rank' => ['required', 'integer', 'unique:todo_priorities,rank,' . $id],
            'color_code' => ['nullable', 'string', 'max:50'],
        ]);

        $priority->update($validated);

        return redirect()->back()->with('message', 'Priority Updated Successfully');
    }

    public function destroyPriority($id)
    {
        $priority = TodoPriority::findOrFail($id);
        $priority->delete();

        return redirect()->back()->with('message', 'Priority Deleted Successfully');
    }
}
