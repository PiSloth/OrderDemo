<?php

namespace App\Http\Controllers\JewelrySales;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JewelrySalesInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $customer = trim((string) $request->input('customer', ''));
        $customerName = trim((string) $request->input('customer_name', ''));
        $phone = trim((string) $request->input('phone', ''));
        $productName = trim((string) $request->input('product_name', ''));
        $weightMin = $request->input('weight_min');
        $weightMax = $request->input('weight_max');
        $from = $request->input('from');
        $to = $request->input('to');
        $branchRaw = $request->input('branch');
        $dutyStaffId = $request->input('duty_staff_id');
        $inputStaffId = $request->input('input_staff_id');

        $query = Sale::query()
            ->with(['branch:id,name', 'customer:id,name,phone_normalized', 'dutyStaff:id,name', 'inputStaff:id,name'])
            ->withCount('items');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('customer_name_snapshot', 'like', "%{$search}%")
                    ->orWhere('customer_phone_snapshot', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($iq) => $iq->where('barcode', 'like', "%{$search}%"));
            });
        }

        if ($customer !== '') {
            $ascii = str_replace(
                ['၀', '၁', '၂', '၃', '၄', '၅', '၆', '၇', '၈', '၉'],
                ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                $customer
            );
            $digits = preg_replace('/\D+/', '', $ascii) ?? '';

            $query->where(function ($q) use ($customer, $ascii, $digits) {
                $q->where('customer_name_snapshot', 'like', "%{$customer}%")
                    ->orWhere('customer_phone_snapshot', 'like', "%{$ascii}%")
                    ->orWhereHas('customer', function ($cq) use ($customer, $digits) {
                        $cq->where('name', 'like', "%{$customer}%");
                        if ($digits !== '') {
                            $cq->orWhere('phone_normalized', 'like', "%{$digits}%");
                        }
                    });
            });
        }

        if ($customerName !== '') {
            $query->where(function ($q) use ($customerName) {
                $q->where('customer_name_snapshot', 'like', "%{$customerName}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$customerName}%"));
            });
        }

        if ($phone !== '') {
            $digits = preg_replace('/\D+/', '', $phone) ?? '';
            $query->where(function ($q) use ($phone, $digits) {
                $q->where('customer_phone_snapshot', 'like', "%{$phone}%");
                if ($digits !== '') {
                    $q->orWhereHas('customer', fn ($cq) => $cq->where('phone_normalized', 'like', "%{$digits}%"));
                }
            });
        }

        if ($productName !== '' || ($weightMin !== null && $weightMin !== '') || ($weightMax !== null && $weightMax !== '')) {
            $query->whereHas('items', function ($iq) use ($productName, $weightMin, $weightMax) {
                if ($productName !== '') {
                    $iq->whereHas('product', function ($pq) use ($productName) {
                        $pq->where('product_name', 'like', "%{$productName}%")
                            ->orWhere('item_name', 'like', "%{$productName}%");
                    });
                }
                if ($weightMin !== null && $weightMin !== '') {
                    $iq->where('item_total_weight_g', '>=', (float) $weightMin);
                }
                if ($weightMax !== null && $weightMax !== '') {
                    $iq->where('item_total_weight_g', '<=', (float) $weightMax);
                }
            });
        }

        if ($from) {
            $query->whereDate('sale_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('sale_at', '<=', $to);
        }
        if ($branchRaw) {
            $query->where('branch_name_raw', $branchRaw);
        }
        if ($dutyStaffId) {
            $query->where('duty_staff_id', $dutyStaffId);
        }
        if ($inputStaffId) {
            $query->where('input_staff_id', $inputStaffId);
        }

        $invoices = $query->orderByDesc('sale_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('JewelrySales/Invoices', [
            'invoices' => $invoices,
            'filterOptions' => [
                'branches' => Sale::whereNotNull('branch_name_raw')->distinct()->orderBy('branch_name_raw')->pluck('branch_name_raw'),
                'staff' => Staff::orderBy('name')->get(['id', 'name']),
            ],
            'filters' => [
                'search' => $search,
                'customer' => $customer,
                'customer_name' => $customerName,
                'phone' => $phone,
                'product_name' => $productName,
                'weight_min' => $weightMin,
                'weight_max' => $weightMax,
                'from' => $from,
                'to' => $to,
                'branch' => $branchRaw,
                'duty_staff_id' => $dutyStaffId,
                'input_staff_id' => $inputStaffId,
            ],
        ]);
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load([
            'customer.region:id,name,name_mm',
            'customer.township:id,name,name_mm',
            'dutyStaff:id,name',
            'inputStaff:id,name',
            'items.product',
        ]);

        return response()->json([
            'sale' => $sale,
        ]);
    }
}
