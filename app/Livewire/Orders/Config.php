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
        Position::create([
            'name' => $this->position,
        ]);
        $this->reset('position');
    }

    public function create_user()
    {
        User::create([
            'name' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'position_id' => $this->position_id,
            'branch_id' => $this->branch_id,
        ]);
        // dd($this->branch_id);
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
        $user->delete();
    }

    public function delete_position($id)
    {
        $position = Position::find($id);
        $position->delete();
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


    public function render()
    {

        return view('livewire.orders.config', [
            'users' => User::all(),
            'positions' => Position::all(),
            'categories' => Category::all(),
            'statuses' => Status::all(),
            'designs' => Design::all(),
            'qualities' => Quality::all(),
            'branches' => Branch::all(),
            'grades' => Grade::all(),
            'priorities' => Priority::all(),
        ]);
    }
}
