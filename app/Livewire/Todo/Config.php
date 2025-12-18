<?php

namespace App\Livewire\Todo;

use App\Models\TodoCategory;
use App\Models\TodoPriority;
use App\Models\TodoStatus;
use App\Models\Location;
use App\Models\Branch;
use App\Models\Department;
use App\Models\TodoDueTime;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Config extends Component
{
    // Properties for TodoCategory
    public $categories;
    public $newCategoryName = '';
    public $newCategoryDescription = '';
    public $editingCategoryId = null;
    public $editingCategoryName = '';
    public $editingCategoryDescription = '';

    // Properties for TodoPriority
    public $priorities;
    public $newPriorityLevel = '';
    public $newPriorityRank = '';
    public $editingPriorityId = null;
    public $editingPriorityLevel = '';
    public $editingPriorityRank = '';

    // Properties for TodoStatus
    public $statuses;
    public $newStatusStatus = '';
    public $newStatusDescription = '';
    public $newStatusColorCode = '';
    public $editingStatusId = null;
    public $editingStatusStatus = '';
    public $editingStatusDescription = '';
    public $editingStatusColorCode = '';

    // Properties for Location
    public $locations;
    public $newLocationName = '';
    public $newLocationAddress = '';
    public $editingLocationId = null;
    public $editingLocationName = '';
    public $editingLocationAddress = '';

    // Properties for Branch
    public $branches;
    public $newBranchName = '';
    public $editingBranchId = null;
    public $editingBranchName = '';

    // Properties for Department
    public $departments;
    public $newDepartmentName = '';
    public $newDepartmentLocationId = '';
    public $editingDepartmentId = null;
    public $editingDepartmentName = '';
    public $editingDepartmentLocationId = '';

    // Properties for TodoDueTime
    public $dueTimes;
    public $newDueTimeCategoryId = '';
    public $newDueTimePriorityId = '';
    public $newDueTimeDuration = '';
    public $newDueTimeDescription = '';
    public $editingDueTimeId = null;
    public $editingDueTimeCategoryId = '';
    public $editingDueTimePriorityId = '';
    public $editingDueTimeDuration = '';
    public $editingDueTimeDescription = '';

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->categories = TodoCategory::all();
        $this->priorities = TodoPriority::all();
        $this->statuses = TodoStatus::all();
        $this->locations = Location::all();
        $this->branches = Branch::all();
        $this->departments = Department::with('location')->get();
        $this->dueTimes = TodoDueTime::with('category', 'priority')->get();
    }

    // TodoCategory CRUD
    public function createCategory()
    {
        $this->validate([
            'newCategoryName' => 'required|string|max:255',
            'newCategoryDescription' => 'nullable|string',
        ]);
        TodoCategory::create([
            'name' => $this->newCategoryName,
            'description' => $this->newCategoryDescription,
        ]);
        $this->newCategoryName = '';
        $this->newCategoryDescription = '';
        $this->loadData();
        session()->flash('message', 'Todo Category Created');
    }

    public function editCategory($id)
    {
        $category = TodoCategory::find($id);
        $this->editingCategoryId = $id;
        $this->editingCategoryName = $category->name;
        $this->editingCategoryDescription = $category->description;
    }

    public function updateCategory()
    {
        $this->validate([
            'editingCategoryName' => 'required|string|max:255',
            'editingCategoryDescription' => 'nullable|string',
        ]);
        TodoCategory::find($this->editingCategoryId)->update([
            'name' => $this->editingCategoryName,
            'description' => $this->editingCategoryDescription,
        ]);
        $this->editingCategoryId = null;
        $this->editingCategoryName = '';
        $this->editingCategoryDescription = '';
        $this->loadData();
        session()->flash('message', 'Todo Category Edited');
    }

    public function deleteCategory($id)
    {
        TodoCategory::find($id)->delete();
        $this->loadData();
        session()->flash('message', 'Todo Category Deleted');
    }

    // TodoPriority CRUD
    public function createPriority()
    {
        $this->validate([
            'newPriorityLevel' => 'required|string|max:255',
            'newPriorityRank' => 'required|integer|unique:todo_priorities,rank',
        ]);
        TodoPriority::create([
            'level' => $this->newPriorityLevel,
            'rank' => $this->newPriorityRank,
        ]);
        $this->newPriorityLevel = '';
        $this->newPriorityRank = '';
        $this->loadData();
        session()->flash('message', 'Todo Priority Created');
    }

    public function editPriority($id)
    {
        $priority = TodoPriority::find($id);
        $this->editingPriorityId = $id;
        $this->editingPriorityLevel = $priority->level;
        $this->editingPriorityRank = $priority->rank;
    }

    public function updatePriority()
    {
        $this->validate([
            'editingPriorityLevel' => 'required|string|max:255',
            'editingPriorityRank' => 'required|integer|unique:todo_priorities,rank,' . $this->editingPriorityId,
        ]);
        TodoPriority::find($this->editingPriorityId)->update([
            'level' => $this->editingPriorityLevel,
            'rank' => $this->editingPriorityRank,
        ]);
        $this->editingPriorityId = null;
        $this->editingPriorityLevel = '';
        $this->editingPriorityRank = '';
        $this->loadData();
        session()->flash('message', 'Todo Priority Edited');
    }

    public function deletePriority($id)
    {
        TodoPriority::find($id)->delete();
        $this->loadData();
        session()->flash('message', 'Todo Priority Deleted');
    }

    // TodoStatus CRUD
    public function createStatus()
    {
        $this->validate([
            'newStatusStatus' => 'required|string|max:255',
            'newStatusDescription' => 'nullable|string',
            'newStatusColorCode' => 'nullable|string|max:7',
        ]);
        TodoStatus::create([
            'status' => $this->newStatusStatus,
            'description' => $this->newStatusDescription,
            'color_code' => $this->newStatusColorCode,
        ]);
        $this->newStatusStatus = '';
        $this->newStatusDescription = '';
        $this->newStatusColorCode = '';
        $this->loadData();
        session()->flash('message', 'Todo Status Created');
    }

    public function editStatus($id)
    {
        $status = TodoStatus::find($id);
        $this->editingStatusId = $id;
        $this->editingStatusStatus = $status->status;
        $this->editingStatusDescription = $status->description;
        $this->editingStatusColorCode = $status->color_code;
    }

    public function updateStatus()
    {
        $this->validate([
            'editingStatusStatus' => 'required|string|max:255',
            'editingStatusDescription' => 'nullable|string',
            'editingStatusColorCode' => 'nullable|string|max:7',
        ]);
        TodoStatus::find($this->editingStatusId)->update([
            'status' => $this->editingStatusStatus,
            'description' => $this->editingStatusDescription,
            'color_code' => $this->editingStatusColorCode,
        ]);
        $this->editingStatusId = null;
        $this->editingStatusStatus = '';
        $this->editingStatusDescription = '';
        $this->editingStatusColorCode = '';
        $this->loadData();
        session()->flash('message', 'Todo Status Edited');
    }

    public function deleteStatus($id)
    {
        TodoStatus::find($id)->delete();
        $this->loadData();
        session()->flash('message', 'Todo Status Deleted');
    }

    // Location CRUD
    public function createLocation()
    {
        $this->validate([
            'newLocationName' => 'required|string|max:255',
            'newLocationAddress' => 'nullable|string',
        ]);
        Location::create([
            'name' => $this->newLocationName,
            'address' => $this->newLocationAddress,
        ]);
        $this->newLocationName = '';
        $this->newLocationAddress = '';
        $this->loadData();
        session()->flash('message', 'Location Created');
    }

    public function editLocation($id)
    {
        $location = Location::find($id);
        $this->editingLocationId = $id;
        $this->editingLocationName = $location->name;
        $this->editingLocationAddress = $location->address;
    }

    public function updateLocation()
    {
        $this->validate([
            'editingLocationName' => 'required|string|max:255',
            'editingLocationAddress' => 'nullable|string',
        ]);
        Location::find($this->editingLocationId)->update([
            'name' => $this->editingLocationName,
            'address' => $this->editingLocationAddress,
        ]);
        $this->editingLocationId = null;
        $this->editingLocationName = '';
        $this->editingLocationAddress = '';
        $this->loadData();
        session()->flash('message', 'Location Edited');
    }

    public function deleteLocation($id)
    {
        Location::find($id)->delete();
        $this->loadData();
        session()->flash('message', 'Location Deleted');
    }

    // Branch CRUD
    public function createBranch()
    {
        $this->validate(['newBranchName' => 'required|string|max:255']);
        Branch::create(['name' => $this->newBranchName]);
        $this->newBranchName = '';
        $this->loadData();
        session()->flash('message', 'Branch Created');
    }

    public function editBranch($id)
    {
        $branch = Branch::find($id);
        $this->editingBranchId = $id;
        $this->editingBranchName = $branch->name;
    }

    public function updateBranch()
    {
        $this->validate(['editingBranchName' => 'required|string|max:255']);
        Branch::find($this->editingBranchId)->update(['name' => $this->editingBranchName]);
        $this->editingBranchId = null;
        $this->editingBranchName = '';
        $this->loadData();
        session()->flash('message', 'Branch Edited');
    }

    public function deleteBranch($id)
    {
        Branch::find($id)->delete();
        $this->loadData();
        session()->flash('message', 'Branch Deleted');
    }

    // Department CRUD
    public function createDepartment()
    {
        $this->validate([
            'newDepartmentName' => 'required|string|max:255',
            'newDepartmentLocationId' => 'required|exists:locations,id',
        ]);
        Department::create([
            'name' => $this->newDepartmentName,
            'location_id' => $this->newDepartmentLocationId,
        ]);
        $this->newDepartmentName = '';
        $this->newDepartmentLocationId = '';
        $this->loadData();
        session()->flash('message', 'Department Created');
    }

    public function editDepartment($id)
    {
        $department = Department::find($id);
        $this->editingDepartmentId = $id;
        $this->editingDepartmentName = $department->name;
        $this->editingDepartmentLocationId = $department->location_id;
    }

    public function updateDepartment()
    {
        $this->validate([
            'editingDepartmentName' => 'required|string|max:255',
            'editingDepartmentLocationId' => 'required|exists:locations,id',
        ]);
        Department::find($this->editingDepartmentId)->update([
            'name' => $this->editingDepartmentName,
            'location_id' => $this->editingDepartmentLocationId,
        ]);
        $this->editingDepartmentId = null;
        $this->editingDepartmentName = '';
        $this->editingDepartmentLocationId = '';
        $this->loadData();
        session()->flash('message', 'Department Edited');
    }

    public function deleteDepartment($id)
    {
        Department::find($id)->delete();
        $this->loadData();
        session()->flash('message', 'Department Deleted');
    }

    // TodoDueTime CRUD
    public function createDueTime()
    {
        $this->validate([
            'newDueTimeCategoryId' => 'required|exists:todo_categories,id',
            'newDueTimePriorityId' => 'required|exists:todo_priorities,id',
            'newDueTimeDuration' => 'required|integer|min:1',
            'newDueTimeDescription' => 'nullable|string',
        ]);
        TodoDueTime::create([
            'todo_category_id' => $this->newDueTimeCategoryId,
            'todo_priority_id' => $this->newDueTimePriorityId,
            'duration' => $this->newDueTimeDuration,
            'description' => $this->newDueTimeDescription,
        ]);
        $this->newDueTimeCategoryId = '';
        $this->newDueTimePriorityId = '';
        $this->newDueTimeDuration = '';
        $this->newDueTimeDescription = '';
        $this->loadData();
        session()->flash('message', 'Todo Due Time Created');
    }

    public function editDueTime($id)
    {
        $dueTime = TodoDueTime::find($id);
        $this->editingDueTimeId = $id;
        $this->editingDueTimeCategoryId = $dueTime->todo_category_id;
        $this->editingDueTimePriorityId = $dueTime->todo_priority_id;
        $this->editingDueTimeDuration = $dueTime->duration;
        $this->editingDueTimeDescription = $dueTime->description;
    }

    public function updateDueTime()
    {
        $this->validate([
            'editingDueTimeCategoryId' => 'required|exists:todo_categories,id',
            'editingDueTimePriorityId' => 'required|exists:todo_priorities,id',
            'editingDueTimeDuration' => 'required|integer|min:1',
            'editingDueTimeDescription' => 'nullable|string',
        ]);
        TodoDueTime::find($this->editingDueTimeId)->update([
            'todo_category_id' => $this->editingDueTimeCategoryId,
            'todo_priority_id' => $this->editingDueTimePriorityId,
            'duration' => $this->editingDueTimeDuration,
            'description' => $this->editingDueTimeDescription,
        ]);
        $this->editingDueTimeId = null;
        $this->editingDueTimeCategoryId = '';
        $this->editingDueTimePriorityId = '';
        $this->editingDueTimeDuration = '';
        $this->editingDueTimeDescription = '';
        $this->loadData();
        session()->flash('message', 'Todo Due Time Edited');
    }

    public function deleteDueTime($id)
    {
        TodoDueTime::find($id)->delete();
        $this->loadData();
        session()->flash('message', 'Todo Due Time Deleted');
    }

    // Cancel methods
    public function cancelCategory()
    {
        $this->editingCategoryId = null;
        $this->editingCategoryName = '';
        $this->editingCategoryDescription = '';
    }

    public function cancelPriority()
    {
        $this->editingPriorityId = null;
        $this->editingPriorityLevel = '';
        $this->editingPriorityRank = '';
    }

    public function cancelStatus()
    {
        $this->editingStatusId = null;
        $this->editingStatusStatus = '';
        $this->editingStatusDescription = '';
        $this->editingStatusColorCode = '';
    }

    public function cancelLocation()
    {
        $this->editingLocationId = null;
        $this->editingLocationName = '';
        $this->editingLocationAddress = '';
    }

    public function cancelBranch()
    {
        $this->editingBranchId = null;
        $this->editingBranchName = '';
    }

    public function cancelDepartment()
    {
        $this->editingDepartmentId = null;
        $this->editingDepartmentName = '';
        $this->editingDepartmentLocationId = '';
    }

    public function cancelDueTime()
    {
        $this->editingDueTimeId = null;
        $this->editingDueTimeCategoryId = '';
        $this->editingDueTimePriorityId = '';
        $this->editingDueTimeDuration = '';
        $this->editingDueTimeDescription = '';
    }

    #[Layout('components.layouts.todo')]
    public function render()
    {
        return view('livewire.todo.config');
    }
}
