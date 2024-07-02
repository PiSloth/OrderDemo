<?php

namespace App\Livewire\Orders;

use App\Models\Design;
use App\Models\Grade;
use App\Models\Order;
use App\Models\Priority;
use Carbon\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use WireUi\Traits\Actions;
use App\Models\CommentPool;
use Illuminate\Support\Facades\Auth;

class Report extends Component
{
    use Actions;
    #[Title('Report')]
    public $gradeFilter = 0;
    public $priorityFilter = 0;
    public $designFilter = 0;
    public $durationFilter = 0;
    public $priority = '';
    public $date = '';

    public function render()
    {
        $orderQuery = Order::get();

        if ($this->gradeFilter) {
            $orderQuery = $orderQuery->where('grade_id', $this->gradeFilter);
        }

        if ($this->priorityFilter) {
            $orderQuery = $orderQuery->where('priority_id', $this->priorityFilter);
        }

        if ($this->designFilter) {
            $orderQuery = $orderQuery->where('design_id', $this->designFilter);
        }

        if ($this->durationFilter) {
            $currentTimeLine = Carbon::now();
            $monthDuration = $currentTimeLine->copy()->subMonth($this->durationFilter);

            $orderQuery = $orderQuery->whereBetween('created_at', [$monthDuration->startOfDay(), $currentTimeLine->endOfDay()]);
        }

        $orderQuery = $orderQuery->groupBy(function ($order) {
            return $order->branch->name;
        });



        $orders = $orderQuery->map(function ($order) {
            return $order->groupBy(function ($data) {
                return $data->status->name;
            });
        });

        // dd(CommentPool::where('completed', 'false')->where('user_id', '=', Auth::user()->id)->count());

        return view('livewire.orders.report', [
            'relevantMeetingCount' => CommentPool::where('completed', 'false')->where('user_id', '=', Auth::user()->id)->count(),
            'agmMeetingCount' => CommentPool::where('completed', 'false')->count(),
            'currentTime' => Carbon::now(),
            'orderGroup' => $orders,
            'grades' => Grade::get(),
            'priorities' => Priority::get(),
            'designs' => Design::get(),
            'currentTime' => Carbon::now(),
        ]);
    }
}
