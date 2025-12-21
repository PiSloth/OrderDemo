<?php

namespace App\Livewire\Orders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Design;
use App\Models\Grade;
use App\Models\Position;
use App\Models\Priority;
use App\Models\Quality;
use App\Models\Status;
use App\Models\User;
use App\Models\PsiProduct;
use App\Models\PsiPrice;
use App\Models\PsiOrder;
use App\Models\Order;
use App\Models\RealSale;
use App\Models\StockTransaction;
use App\Models\DailyReportRecord;
use App\Models\TodoList;
use App\Models\TaskComment;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use NunoMaduro\Collision\Adapters\Phpunit\State;
use App\Models\CommentPool;
use Illuminate\Support\Facades\Auth;

class Config extends Component
{

    public $position = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $position_id = '';
    public $category = '';
    public $status = '';
    public $design = '';
    public $quality = '';
    public $grade = '';
    public $branch_id;
    public $branch;
    public $priority = '';
    public $color;
    public $department_id;
    public $location_id;

    // User management properties
    public $showCreateUserModal = false;
    public $showEditUserModal = false;
    public $showChangePasswordModal = false;
    public $editingUserId = null;
    public $editUsername = '';
    public $editEmail = '';
    public $editPositionId = '';
    public $editBranchId = '';
    public $editDepartmentId = '';
    public $editLocationId = '';
    public $newPassword = '';
    public $confirmPassword = '';
    public $showSuspendedUsers = false;

    // Edit properties for other entities
    public $editingPositionId = null;
    public $editingPositionName = '';
    public $editingCategoryId = null;
    public $editingCategoryName = '';
    public $editingStatusId = null;
    public $editingStatusName = '';
    public $editingDesignId = null;
    public $editingDesignName = '';
    public $editingQualityId = null;
    public $editingQualityName = '';
    public $editingBranchId = null;
    public $editingBranchName = '';
    public $editingGradeId = null;
    public $editingGradeName = '';
    public $editingPriorityId = null;
    public $editingPriorityName = '';
    public $editingPriorityColor = '';

    // public function __construct(){
    //     $user = auth()->user();

    //     if(!Gate::allows('isIT',$user)){
    //         return redirect()->to('/order/list');
    //     }else{

    //     }
    // }

    public function create_position()
    {
        // dd($this->position);
        $this->validate([
            'position' => 'required|unique:positions,name'
        ]);

        Position::create([
            'name' => $this->position,
        ]);
        $this->reset('position');
    }

    public function create_user()
    {
        $this->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'position_id' => 'required|exists:positions,id',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required|exists:departments,id',
            'location_id' => 'required|exists:locations,id',
        ]);

        $user = User::create([
            'name' => $this->username,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'position_id' => $this->position_id,
            'branch_id' => $this->branch_id,
            'department_id' => $this->department_id,
            'location_id' => $this->location_id,
        ]);

        $this->reset(['username', 'email', 'password', 'position_id', 'branch_id', 'department_id', 'location_id']);
        $this->showCreateUserModal = false;
        session()->flash('message', 'User created successfully!');
    }

    public function openEditUserModal($userId)
    {
        $user = User::with('department', 'location')->find($userId);
        if ($user) {
            $this->editingUserId = $userId;
            $this->editUsername = $user->name;
            $this->editEmail = $user->email;
            $this->editPositionId = $user->position_id;
            $this->editBranchId = $user->branch_id;
            $this->editDepartmentId = $user->department_id ?? '';
            $this->editLocationId = $user->location_id ?? '';
            $this->showEditUserModal = true;
        }
    }

    public function updateUser()
    {
        $this->validate([
            'editUsername' => 'required|string|max:255',
            'editEmail' => 'required|email|unique:users,email,' . $this->editingUserId,
            'editPositionId' => 'required|exists:positions,id',
            'editBranchId' => 'required|exists:branches,id',
            'editDepartmentId' => 'required|exists:departments,id',
            'editLocationId' => 'required|exists:locations,id',
        ]);

        $user = User::find($this->editingUserId);
        if ($user) {
            $user->update([
                'name' => $this->editUsername,
                'email' => $this->editEmail,
                'position_id' => $this->editPositionId,
                'branch_id' => $this->editBranchId,
                'department_id' => $this->editDepartmentId,
                'location_id' => $this->editLocationId,
            ]);

            $this->reset(['editingUserId', 'editUsername', 'editEmail', 'editPositionId', 'editBranchId', 'editDepartmentId', 'editLocationId']);
            $this->showEditUserModal = false;
            session()->flash('message', 'User updated successfully!');
        }
    }

    public function openChangePasswordModal($userId)
    {
        $this->editingUserId = $userId;
        $this->showChangePasswordModal = true;
    }

    public function changePassword()
    {
        $this->validate([
            'newPassword' => 'required|string|min:8',
            'confirmPassword' => 'required|same:newPassword',
        ]);

        $user = User::find($this->editingUserId);
        if ($user) {
            $user->update([
                'password' => bcrypt($this->newPassword),
            ]);

            $this->reset(['editingUserId', 'newPassword', 'confirmPassword']);
            $this->showChangePasswordModal = false;
            session()->flash('message', 'Password changed successfully!');
        }
    }

    public function closeModals()
    {
        $this->showCreateUserModal = false;
        $this->showEditUserModal = false;
        $this->showChangePasswordModal = false;
        $this->reset(['editingUserId', 'editUsername', 'editEmail', 'editPositionId', 'editBranchId', 'editDepartmentId', 'editLocationId', 'newPassword', 'confirmPassword']);
    }

    public function create_category()
    {
        Category::create([
            'name' => $this->category,
        ]);
        $this->reset(['category']);
    }

    public function create_status()
    {
        Status::create([
            'name' => $this->status,
        ]);
        $this->reset('status');
    }

    public function create_design()
    {
        Design::create([
            'name' => $this->design,
        ]);
        $this->reset('design');
    }

    public function create_quality()
    {
        Quality::create([
            'name' => $this->quality,
        ]);
        $this->reset('quality');
    }

    public function create_branch()
    {
        Branch::create([
            'name' => $this->branch,
        ]);
        $this->reset('branch');
    }

    public function create_grade()
    {
        Grade::create([
            'name' => $this->grade,
        ]);
        $this->reset('grade');
    }
    public function create_priority()
    {
        Priority::create([
            'name' => $this->priority,
            'color' => $this->color,
        ]);
        $this->reset('priority');
    }

    public function delete_user($id)
    {
        $user = User::find($id);

        // Check for related records that would prevent deletion
        $relatedRecords = [];

        if ($user->psiProducts()->count() > 0) {
            $relatedRecords[] = 'PSI Products';
        }

        if ($user->psiPrices()->count() > 0) {
            $relatedRecords[] = 'PSI Prices';
        }

        if ($user->psiOrders()->count() > 0) {
            $relatedRecords[] = 'PSI Orders';
        }

        if ($user->orders()->count() > 0) {
            $relatedRecords[] = 'Orders';
        }

        if ($user->realSales()->count() > 0) {
            $relatedRecords[] = 'Real Sales';
        }

        if ($user->stockTransactions()->count() > 0) {
            $relatedRecords[] = 'Stock Transactions';
        }

        if ($user->dailyReportRecords()->count() > 0) {
            $relatedRecords[] = 'Daily Report Records';
        }

        if ($user->assignedTodos()->count() > 0 || $user->createdTodos()->count() > 0) {
            $relatedRecords[] = 'Todo Items';
        }

        if ($user->taskComments()->count() > 0) {
            $relatedRecords[] = 'Task Comments';
        }

        if (!empty($relatedRecords)) {
            $this->dispatch('show-error', message: 'Cannot delete user because they have associated records in: ' . implode(', ', $relatedRecords) . '. Please reassign or delete these records first.');
            return;
        }

        try {
            $user->delete();
            session()->flash('message', 'User deleted successfully!');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'An error occurred while deleting the user. Please try again.');
        }
    }

    public function suspendUser($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->update(['suspended' => !$user->suspended]);
            $status = $user->suspended ? 'suspended' : 'unsuspended';
            session()->flash('message', "User {$status} successfully!");
        }
    }

    public function toggleSuspendedView()
    {
        $this->showSuspendedUsers = !$this->showSuspendedUsers;
    }

    public function confirmDeleteUser($id)
    {
        $this->dispatch('confirm-delete', userId: $id);
    }

    public function confirmDeletePosition($id)
    {
        $this->dispatch('confirm-delete-position', positionId: $id);
    }

    public function confirmDeleteCategory($id)
    {
        $this->dispatch('confirm-delete-category', categoryId: $id);
    }

    public function confirmDeleteStatus($id)
    {
        $this->dispatch('confirm-delete-status', statusId: $id);
    }

    public function confirmDeleteDesign($id)
    {
        $this->dispatch('confirm-delete-design', designId: $id);
    }

    public function confirmDeleteQuality($id)
    {
        $this->dispatch('confirm-delete-quality', qualityId: $id);
    }

    public function confirmDeleteBranch($id)
    {
        $this->dispatch('confirm-delete-branch', branchId: $id);
    }

    public function confirmDeleteGrade($id)
    {
        $this->dispatch('confirm-delete-grade', gradeId: $id);
    }

    public function confirmDeletePriority($id)
    {
        $this->dispatch('confirm-delete-priority', priorityId: $id);
    }

    public function delete_category($id)
    {
        $category = Category::find($id);
        $category->delete();
    }

    public function delete_status($id)
    {
        $status = Status::find($id);
        $status->delete();
    }

    public function delete_design($id)
    {
        $design = Design::find($id);
        $design->delete();
    }
    public function delete_quality($id)
    {
        $quality = Quality::find($id);
        $quality->delete();
    }
    public function delete_branch($id)
    {
        $branch = Branch::find($id);
        $branch->delete();
    }
    public function delete_priority($id)
    {
        $priority = Priority::find($id);
        $priority->delete();
    }
    public function delete_grade($id)
    {
        $grade = Grade::find($id);
        $grade->delete();
    }

    // Edit methods
    public function editPosition($id)
    {
        $position = Position::find($id);
        $this->editingPositionId = $id;
        $this->editingPositionName = $position->name;
    }

    public function updatePosition()
    {
        $this->validate([
            'editingPositionName' => 'required|string|max:255|unique:positions,name,' . $this->editingPositionId,
        ]);
        Position::find($this->editingPositionId)->update([
            'name' => $this->editingPositionName,
        ]);
        $this->editingPositionId = null;
        $this->editingPositionName = '';
        session()->flash('message', 'Position updated successfully!');
    }

    public function cancelEditPosition()
    {
        $this->editingPositionId = null;
        $this->editingPositionName = '';
    }

    public function editCategory($id)
    {
        $category = Category::find($id);
        $this->editingCategoryId = $id;
        $this->editingCategoryName = $category->name;
    }

    public function updateCategory()
    {
        $this->validate([
            'editingCategoryName' => 'required|string|max:255|unique:categories,name,' . $this->editingCategoryId,
        ]);
        Category::find($this->editingCategoryId)->update([
            'name' => $this->editingCategoryName,
        ]);
        $this->editingCategoryId = null;
        $this->editingCategoryName = '';
        session()->flash('message', 'Category updated successfully!');
    }

    public function cancelEditCategory()
    {
        $this->editingCategoryId = null;
        $this->editingCategoryName = '';
    }

    public function editStatus($id)
    {
        $status = Status::find($id);
        $this->editingStatusId = $id;
        $this->editingStatusName = $status->name;
    }

    public function updateStatus()
    {
        $this->validate([
            'editingStatusName' => 'required|string|max:255|unique:statuses,name,' . $this->editingStatusId,
        ]);
        Status::find($this->editingStatusId)->update([
            'name' => $this->editingStatusName,
        ]);
        $this->editingStatusId = null;
        $this->editingStatusName = '';
        session()->flash('message', 'Status updated successfully!');
    }

    public function cancelEditStatus()
    {
        $this->editingStatusId = null;
        $this->editingStatusName = '';
    }

    public function editDesign($id)
    {
        $design = Design::find($id);
        $this->editingDesignId = $id;
        $this->editingDesignName = $design->name;
    }

    public function updateDesign()
    {
        $this->validate([
            'editingDesignName' => 'required|string|max:255|unique:designs,name,' . $this->editingDesignId,
        ]);
        Design::find($this->editingDesignId)->update([
            'name' => $this->editingDesignName,
        ]);
        $this->editingDesignId = null;
        $this->editingDesignName = '';
        session()->flash('message', 'Design updated successfully!');
    }

    public function cancelEditDesign()
    {
        $this->editingDesignId = null;
        $this->editingDesignName = '';
    }

    public function editQuality($id)
    {
        $quality = Quality::find($id);
        $this->editingQualityId = $id;
        $this->editingQualityName = $quality->name;
    }

    public function updateQuality()
    {
        $this->validate([
            'editingQualityName' => 'required|string|max:255|unique:qualities,name,' . $this->editingQualityId,
        ]);
        Quality::find($this->editingQualityId)->update([
            'name' => $this->editingQualityName,
        ]);
        $this->editingQualityId = null;
        $this->editingQualityName = '';
        session()->flash('message', 'Quality updated successfully!');
    }

    public function cancelEditQuality()
    {
        $this->editingQualityId = null;
        $this->editingQualityName = '';
    }

    public function editBranch($id)
    {
        $branch = Branch::find($id);
        $this->editingBranchId = $id;
        $this->editingBranchName = $branch->name;
    }

    public function updateBranch()
    {
        $this->validate([
            'editingBranchName' => 'required|string|max:255|unique:branches,name,' . $this->editingBranchId,
        ]);
        Branch::find($this->editingBranchId)->update([
            'name' => $this->editingBranchName,
        ]);
        $this->editingBranchId = null;
        $this->editingBranchName = '';
        session()->flash('message', 'Branch updated successfully!');
    }

    public function cancelEditBranch()
    {
        $this->editingBranchId = null;
        $this->editingBranchName = '';
    }

    public function editGrade($id)
    {
        $grade = Grade::find($id);
        $this->editingGradeId = $id;
        $this->editingGradeName = $grade->name;
    }

    public function updateGrade()
    {
        $this->validate([
            'editingGradeName' => 'required|string|max:255|unique:grades,name,' . $this->editingGradeId,
        ]);
        Grade::find($this->editingGradeId)->update([
            'name' => $this->editingGradeName,
        ]);
        $this->editingGradeId = null;
        $this->editingGradeName = '';
        session()->flash('message', 'Grade updated successfully!');
    }

    public function cancelEditGrade()
    {
        $this->editingGradeId = null;
        $this->editingGradeName = '';
    }

    public function editPriority($id)
    {
        $priority = Priority::find($id);
        $this->editingPriorityId = $id;
        $this->editingPriorityName = $priority->name;
        $this->editingPriorityColor = $priority->color;
    }

    public function updatePriority()
    {
        $this->validate([
            'editingPriorityName' => 'required|string|max:255|unique:priorities,name,' . $this->editingPriorityId,
            'editingPriorityColor' => 'required|string|max:7',
        ]);
        Priority::find($this->editingPriorityId)->update([
            'name' => $this->editingPriorityName,
            'color' => $this->editingPriorityColor,
        ]);
        $this->editingPriorityId = null;
        $this->editingPriorityName = '';
        $this->editingPriorityColor = '';
        session()->flash('message', 'Priority updated successfully!');
    }

    public function cancelEditPriority()
    {
        $this->editingPriorityId = null;
        $this->editingPriorityName = '';
        $this->editingPriorityColor = '';
    }


    public function render()
    {

        return view('livewire.orders.config', [
            'users' => User::with(['position', 'branch', 'department', 'location'])->where('suspended', $this->showSuspendedUsers)->get(),
            'positions' => Position::all(),
            'categories' => Category::all(),
            'statuses' => Status::all(),
            'designs' => Design::all(),
            'qualities' => Quality::all(),
            'branches' => Branch::all(),
            'departments' => \App\Models\Department::all(),
            'locations' => \App\Models\Location::all(),
            'grades' => Grade::all(),
            'priorities' => Priority::all(),
        ]);
    }
}
