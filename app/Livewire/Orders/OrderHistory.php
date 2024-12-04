<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Spatie\SimpleExcel\SimpleExcelWriter;

class OrderHistory extends Component
{

    public $start_date;
    public $end_date;

    public function export()
    {
        //end date found

        //start date found

        $orders = Order::with('branch', 'design', 'status', 'grade', 'priority')
            ->when($this->start_date && $this->end_date, function ($query) {
                return $query->whereBetween('created_at', [$this->start_date, $this->end_date]);
            })
            ->when($this->start_date && !$this->end_date, function ($query) {
                return $query->where('created_at', '>=', $this->start_date);
            })
            ->when(!$this->start_date && $this->end_date, function ($query) {
                return $query->where('created_at', '<=', $this->end_date);
            })
            ->get();

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
                'Status',
                'Approve remark',
                'Arrival Date',
                'Arrival Quantity',
                'Branch Recieved qty',
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
                $order->status->name,
                $order->fetchRemarkByStatus(4), // Approve status
                $order->fetchArrivalDAte(6), // Approve status
                $order->arqty,
                $order->closeqty,
                $order->fetchRemarkByStatus(8), // cancle remark
            ]);
        }
        $writer->close();

        // Stream the file to the browser
        return Response::download($tempFilePath, Carbon::now()->format('dmY_His') . '-branch-report.xlsx')->deleteFileAfterSend(true);
    }
    public function render()
    {
        $orders = Order::with('branch', 'design', 'status', 'grade', 'priority')
            ->when($this->start_date && $this->end_date, function ($query) {
                return $query->whereBetween('created_at', [$this->start_date, $this->end_date]);
            })
            ->when($this->start_date && !$this->end_date, function ($query) {
                return $query->where('created_at', '>=', $this->start_date);
            })
            ->when(!$this->start_date && $this->end_date, function ($query) {
                return $query->where('created_at', '<=', $this->end_date);
            })
            ->paginate(10);

        // dd($orders);

        return view('livewire.orders.order-history', [
            'orders' => $orders,
        ]);
    }
}
