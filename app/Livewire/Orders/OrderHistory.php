<?php

namespace App\Livewire\Orders;

use App\Models\Branch;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\SimpleExcel\SimpleExcelWriter;
use WireUi\Traits\Actions;

class OrderHistory extends Component
{

    use WithPagination;
    use Actions;
    public $start_date;
    public $end_date;
    public $branch_id;

    public function export()
    {
        //end date found

        //start date found

        $start_date = Carbon::parse($this->start_date)->startOfDay();
        $end_date = Carbon::parse($this->end_date)->endOfDay();


        $orders = Order::with('branch', 'design', 'status', 'grade', 'priority')
            ->when($this->start_date && $this->end_date, function ($query) use ($start_date, $end_date) {
                return $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($this->start_date && !$this->end_date, function ($query) use ($start_date) {
                return $query->where('created_at', '>=', $start_date);
            })
            ->when(!$this->start_date && $this->end_date, function ($query) use ($end_date) {
                return $query->where('created_at', '<=', $end_date);
            })
            ->when($this->branch_id, function ($query) {
                return $query->where('branch_id', '=', $this->branch_id);
            })
            ->get();

        if (count($orders) == 0) {
            $this->dialog([
                'title'       => 'No record found!',
                'description' => 'Please try again with valid date range.',
                'icon'        => 'warning'
            ]);
            return;
        }


        // Create a temporary file
        // Create a temporary file with .xlsx extension
        $tempFilePath = tempnam(sys_get_temp_dir(), 'pos') . '.xlsx';

        // dd($tempFilePath);

        // Create the Excel file at the temporary location
        $writer = SimpleExcelWriter::create($tempFilePath)
            ->addHeader([
                'Order တင်သည့်ရက်',
                'Branch',
                'Shop', //Category
                'Product', //Design
                'Design', //Detial Design
                'Size',
                'Quantity',
                'Weight',
                'Order တင်ရသော မှတ်ချက်',
                'ပစ္စည်းအမျိုးအစား',
                'Status',
                'Approve remark',
                'Supplier Date',
                'Arrival Quantity',
                'Branch Recieved qty',
                'Branch Recieved Date',
                'Cancle Remark',
            ]);

        foreach ($orders as $order) {
            $writer->addRow([
                date_format($order->created_at, 'F j, Y'),
                $order->branch->name,
                $order->category->name,
                $order->design->name,
                $order->detail,
                $order->size,
                $order->qty,
                $order->weight,
                $order->note,
                $order->grade->name,
                $order->status->name,
                $order->fetchRemarkByStatus(4), // Approve status
                $order->fetchArrivalDate(6), // arrival date
                $order->arqty,
                $order->closeqty,
                $order->fetchArrivalDate(7), // branch received date
                $order->fetchRemarkByStatus(8), // cancle remark
            ]);
        }
        $writer->close();

        // Stream the file to the browser
        return Response::download($tempFilePath, Carbon::now()->format('dmY_His') . '-branch-report.xlsx')->deleteFileAfterSend(true);
    }
    public function render()
    {
        $start_date = Carbon::parse($this->start_date)->startOfDay();
        $end_date = Carbon::parse($this->end_date)->endOfDay();

        $orders = Order::with('branch', 'design', 'status', 'grade', 'priority')
            ->when($this->start_date && $this->end_date, function ($query) use ($start_date, $end_date) {
                return $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($this->start_date && !$this->end_date, function ($query) use ($start_date) {
                return $query->where('created_at', '>=', $start_date);
            })
            ->when(!$this->start_date && $this->end_date, function ($query) use ($end_date) {
                return $query->where('created_at', '<=', $end_date);
            })->when($this->branch_id, function ($query) {
                return $query->where('branch_id', '=', $this->branch_id);
            })
            ->paginate(10);

        // dd($orders);

        return view('livewire.orders.order-history', [
            'orders' => $orders,
            'branches' => Branch::all(),
        ]);
    }
}
