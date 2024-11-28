<?php

namespace App\Livewire\Order\Psi;

use App\Models\PhotoShooting as ModelsPhotoShooting;
use App\Models\PhotoShootingStatus;
use App\Models\PhotoShootingStatusHistory;
use App\Models\PsiOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use WireUi\Traits\Actions;

class PhotoShooting extends Component
{
    use Actions;


    public function statusAction($id, $ordId, $status)
    {

        if ($status == 3 || $status == 6) {
            if (Gate::allows('isInventory')) {
                $this->statusChange($id, $ordId, $status);
            } else {
                // dd($response);

                $this->dialog([
                    'title' => 'No permission',
                    'description' => 'you have no permission',
                    'icon' => 'error'
                ]);
                // echo $response->message();
                return;
            }
        } else if ($status == 4 || $status == 5) {
            if (Gate::allows('isMarketing')) {
                $this->statusChange($id, $ordId, $status);
            } else {
                // dd($response);

                $this->dialog([
                    'title' => 'No permission',
                    'description' => 'you have no permission',
                    'icon' => 'error'
                ]);
                // echo $response->message();
                return;
            }
        } else {
            abort(404);
        }
    }

    public function statusChange($id, $ordId, $status)
    {
        $query = ModelsPhotoShooting::findOrFail($id);

        DB::transaction(function () use ($query, $status, $ordId) {
            $query->update([
                'photo_shooting_status_id' => $status,
            ]);

            PhotoShootingStatusHistory::create([
                'psi_order_id' => $ordId,
                'photo_shooting_status_id' => $status,
                'user_id' => auth()->user()->id,
            ]);

            if ($status === 6) {
                $order = PsiOrder::findOrFail($ordId);

                $order->update([
                    'psi_status_id' => 6
                ]);
            }
        });


        $this->notification([
            'title' => 'Success!',
            'description' => 'Status updated successfully',
            'icon' => 'success',
        ]);
    }

    public function render()
    {
        // dd(ModelsPhotoShooting::all());
        $jobs = ModelsPhotoShooting::orderBy('id', 'desc')->get();

        return view('livewire.order.psi.photo-shooting', [
            'jobs' => $jobs,
        ]);
    }
}
