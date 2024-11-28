<?php

namespace App\Livewire\Order\Psi;

use App\Models\PsiOrder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use WireUi\Traits\Actions;

class PsiOrderHsitory extends Component
{
    use Actions;
    #[Url(as: 'stus')]
    public $status_id;

    public function registered($id)
    {
        if (! Gate::allows('isInventory')) {
            abort(401);
        }

        $query = PsiOrder::findOrFail($id);

        $query->update([
            'psi_status_id' => 7, //End Registeration
        ]);

        $this->notification([
            'title' => 'Updated',
            'description' => 'Order updated successfully.',
            'icon' => 'success'

        ]);
    }

    public function startRegisteration($id)
    {
        if (! Gate::allows('isInventory')) {
            abort(401);
        }

        $query = PsiOrder::findOrFail($id);

        $query->update([
            'psi_status_id' => 6, //Start  Registeration
        ]);

        $this->notification([
            'title' => 'Updated',
            'description' => 'Order updated successfully.',
            'icon' => 'success'

        ]);
    }

    public function receiveByBranch($id)
    {
        if (! Gate::allows('isBranchSupervisor')) {
            abort(401);
        }

        $query = PsiOrder::findOrFail($id);

        $query->update([
            'psi_status_id' => 8, //Received product
        ]);

        $this->notification([
            'title' => 'Updated',
            'description' => 'Order updated successfully.',
            'icon' => 'success'

        ]);
    }

    public function render()
    {
        $orders = PsiOrder::where('psi_status_id', '=', $this->status_id)
            ->orderBy('id', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            abort(404, 'Order not found.');
        }

        return view('livewire.order.psi.psi-order-hsitory', [
            'orders' => $orders,
        ]);
    }
}
