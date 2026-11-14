<?php

namespace App\Http\Controllers\JewelrySales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Region;
use App\Models\Township;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JewelrySalesCustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $regionId = $request->input('region_id');
        $townshipId = $request->input('township_id');
        $segment = $request->input('segment'); // 'repeat' | 'one_time' | null
        $sort = $request->input('sort', 'total_spent');

        $query = Customer::query()
            ->with(['region:id,name,name_mm', 'township:id,name,name_mm'])
            ->withCount('sales')
            ->withSum('sales as total_spent', 'invoice_total')
            ->withSum('sales as total_weight_g', 'invoice_total_weight_g')
            ->withMax('sales as last_sale_at', 'sale_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_normalized', 'like', "%{$search}%")
                    ->orWhere('phone_raw', 'like', "%{$search}%")
                    ->orWhere('address_raw', 'like', "%{$search}%");
            });
        }

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        if ($townshipId) {
            $query->where('township_id', $townshipId);
        }

        if ($segment === 'repeat') {
            $query->has('sales', '>=', 2);
        } elseif ($segment === 'one_time') {
            $query->has('sales', '=', 1);
        }

        match ($sort) {
            'invoices' => $query->orderByDesc('sales_count'),
            'weight' => $query->orderByDesc('total_weight_g'),
            'recent' => $query->orderByDesc('last_sale_at'),
            default => $query->orderByDesc('total_spent'),
        };

        $customers = $query->paginate(25)->withQueryString();

        $stats = [
            'total_customers' => Customer::count(),
            'repeat_customers' => Customer::has('sales', '>=', 2)->count(),
            'with_phone' => Customer::whereNotNull('phone_normalized')->count(),
            'with_region' => Customer::whereNotNull('region_id')->count(),
        ];

        return Inertia::render('JewelrySales/Customers', [
            'customers' => $customers,
            'stats' => $stats,
            'regions' => Region::orderBy('name')->get(['id', 'name', 'name_mm']),
            'townships' => Township::orderBy('name')->get(['id', 'region_id', 'name', 'name_mm']),
            'filters' => [
                'search' => $search,
                'region_id' => $regionId,
                'township_id' => $townshipId,
                'segment' => $segment,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Customer $customer): Response
    {
        $customer->load(['region:id,name,name_mm', 'township:id,name,name_mm']);

        $sales = $customer->sales()
            ->with(['dutyStaff:id,name', 'inputStaff:id,name', 'items.product'])
            ->orderByDesc('sale_at')
            ->get();

        $summary = [
            'invoices_count' => $sales->count(),
            'total_spent' => (float) $sales->sum('invoice_total'),
            'total_weight_g' => (float) $sales->sum('invoice_total_weight_g'),
            'first_sale_at' => $sales->min('sale_at'),
            'last_sale_at' => $sales->max('sale_at'),
        ];

        return Inertia::render('JewelrySales/CustomerShow', [
            'customer' => $customer,
            'sales' => $sales,
            'summary' => $summary,
        ]);
    }
}
